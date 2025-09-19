<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\PreventivoPaziente;
use App\Models\User;
use App\Notifications\NuovaPropostaNotification;
use App\Notifications\PropostaGenerataMedicoNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use OpenAI;

class GeneraControproposte implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $preventivo;

    public function __construct(PreventivoPaziente $preventivo)
    {
        $this->preventivo = $preventivo;
    }

    public function handle(): void
    {
        try {
            $pazienteAnagrafica = $this->preventivo->anagraficaPaziente;
            $pazienteLat = $pazienteAnagrafica->lat;
            $pazienteLng = $pazienteAnagrafica->lng;
            $raggioKm = 10; // Raggio di ricerca in KM

            // 1. QUERY GEOSPAZIALE PER TROVARE I 3 MEDICI IDONEI PIÙ VICINI
            $mediciVicini = User::where('role', 'medico')
            ->join('anagrafica_medici', 'users.id', '=', 'anagrafica_medici.user_id')
            ->whereNotNull('anagrafica_medici.step_listino_completed_at')
            ->whereNotNull('anagrafica_medici.step_profilo_completed_at')
            ->whereNotNull('anagrafica_medici.step_staff_completed_at')
            ->select('users.*')
            ->selectRaw(
                '( 6371 * acos( cos( radians(?) ) * cos( radians( lat ) ) * cos( radians( lng ) - radians(?) ) + sin( radians(?) ) * sin( radians( lat ) ) ) ) AS distance', 
                [$pazienteLat, $pazienteLng, $pazienteLat]
            )
            ->having('distance', '<', $raggioKm)
            ->orderBy('distance', 'asc')
            ->limit(3)
            ->get();

            if ($mediciVicini->isEmpty()) {
                Log::info("Nessun medico idoneo trovato per il preventivo #{$this->preventivo->id}");
                return;
            }

            // 2. CICLO SUI MEDICI E GENERAZIONE PROPOSTE
            foreach ($mediciVicini as $medico) {
                // Prepara il listino del medico
                $listinoMedico = $this->getListinoCombinato($medico);

                // Prepara il prompt per OpenAI
                $prompt = $this->creaPromptOpenAI($this->preventivo->json_preventivo, $listinoMedico);
                
                // Chiama OpenAI
                $client = OpenAI::client(env('OPENAI_API_KEY'));
                $response = $client->chat()->create([
                    'model' => 'gpt-4-turbo',
                    'messages' => $prompt,
                    'response_format' => ['type' => 'json_object'],
                ]);
                $jsonProposta = json_decode($response->choices[0]->message->content, true);

                // Salva la controproposta
                $proposta = $medico->controproposte()->create([
                    'preventivo_paziente_id' => $this->preventivo->id,
                    'json_proposta' => $jsonProposta,
                ]);

                // Crea la notifica in-app e invia l'email
                $paziente = $pazienteAnagrafica->user;
                $paziente->notifiche()->create([
                    'tipo' => 'NUOVA_PROPOSTA',
                    'messaggio' => 'Hai ricevuto una nuova proposta dallo studio "' . $medico->anagraficaMedico->ragione_sociale . '".',
                    'url_azione' => '/dashboard/proposte' // URL relativo per il frontend
                ]);
                $paziente->notify(new NuovaPropostaNotification($proposta));

                // Invia email al medico
                $medico->notify(new PropostaGenerataMedicoNotification($proposta));
            }

        } catch (\Exception $e) {
            Log::error("Errore nel job GeneraControproposte per il preventivo #{$this->preventivo->id}: " . $e->getMessage());
            $this->fail($e);
        }
    }

    // in api/app/Jobs/GeneraControproposte.php, dentro la classe

private function getListinoCombinato($medico)
{
    $listinoMaster = DB::table('listino_master')->where('is_active', true)->get();
    $medicoMasterItems = DB::table('listino_medico_master_items')
        ->where('medico_user_id', $medico->id)
        ->where('is_active', true)
        ->whereNotNull('prezzo')
        ->get()
        ->keyBy('listino_master_id');

    $listinoCombinato = $listinoMaster->map(function ($item) use ($medicoMasterItems) {
        if ($medicoMasterItems->has($item->id)) {
            return ['prestazione' => $item->nome, 'prezzo' => $medicoMasterItems->get($item->id)->prezzo];
        }
        return null;
    })->filter();

    $vociCustom = DB::table('listino_medico_custom_items')
        ->where('medico_user_id', $medico->id)
        ->get()
        ->map(fn($item) => ['prestazione' => $item->nome, 'prezzo' => $item->prezzo]);

    return $listinoCombinato->merge($vociCustom)->all();
}

private function creaPromptOpenAI($preventivoPaziente, $listinoMedico)
{
    return [
        [
            'role' => 'system',
                'content' => 'Sei un assistente per uno studio dentistico. Il tuo compito è creare una controproposta per un paziente basandoti sul suo preventivo e sul listino prezzi dello studio medico dentistico. Analizza semanticamente ogni "prestazione" nel preventivo del paziente e trova la corrispondenza migliore nel "listino_medico". Il JSON di output deve avere una chiave "voci_proposta" (un array di oggetti) e una chiave "totale_proposta" (un numero). Per ogni voce del preventivo del paziente, crea un oggetto nell\'array "voci_proposta" con le chiavi: "prestazione_originale" (dal preventivo del paziente), "prestazione_corrispondente" (la voce trovata nel listino medico, o un messaggio che indica nessuna corrispondenza), "quantità" riportare la stessa quantità presente nel preventivo del paziente per ogni voce altrimenti indica 1 e "prezzo" (il prezzo dal listino medico) moltiplicato per la quantità. Se non trovi una corrispondenza esatta o semanticamente molto simile, imposta il prezzo a 0. Calcola la somma di tutti i prezzi e inseriscila in "totale_proposta".'
        ],
        [
            'role' => 'user',
            'content' => "Crea una controproposta basandoti su questi dati:\n\nPreventivo del Paziente (JSON):\n" . json_encode($preventivoPaziente) . "\n\nListino Prezzi del Medico (JSON):\n" . json_encode($listinoMedico)
        ]
    ];
}
}