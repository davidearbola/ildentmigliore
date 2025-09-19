<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ListinoMaster;
use App\Models\ListinoMedicoCustomItem;
use App\Models\ListinoMedicoMasterItem;
use App\Models\ListinoTipologia; // Importa il nuovo modello
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ListinoController extends Controller
{
    /**
     * Ritorna il listino completo per il medico autenticato e le tipologie disponibili.
     */
    public function index()
    {
        $medico = Auth::user();

        // Carica tutte le tipologie disponibili per il frontend
        $tipologieDisponibili = ListinoTipologia::all();

        // 1. Prendi tutte le voci master attive, caricando la relazione con la tipologia
        $vociMaster = ListinoMaster::with('tipologia')->where('is_active', true)->get();

        // 2. Prendi le personalizzazioni del medico (prezzo e stato)
        $medicoMasterItems = $medico->listinoMasterItems()
            ->get()
            ->keyBy('listino_master_id');

        // 3. Unisci le voci master con le personalizzazioni del medico
        $listinoCombinato = $vociMaster->map(function ($voceMaster) use ($medicoMasterItems) {
            $personalizzazione = $medicoMasterItems->get($voceMaster->id);

            // La tipologia è sempre quella definita nel listino master, non è personalizzabile dal medico
            $tipologia = $voceMaster->tipologia;

            return [
                'id' => $voceMaster->id,
                'nome' => $voceMaster->nome,
                'descrizione' => $voceMaster->descrizione,
                'prezzo' => $personalizzazione ? $personalizzazione->prezzo : null,
                'is_active' => $personalizzazione ? $personalizzazione->is_active : true,
                'tipo' => 'master',
                'id_tipologia' => $tipologia ? $tipologia->id : null,       // Aggiunto id tipologia
                'nome_tipologia' => $tipologia ? $tipologia->nome : null, // Aggiunto nome tipologia
            ];
        });

        // 4. Prendi le voci custom del medico, caricando la relazione con la tipologia
        $vociCustom = $medico->listinoCustomItems()->with('tipologia')->get()->map(function ($item) {
            return [
                'id' => $item->id,
                'nome' => $item->nome,
                'descrizione' => $item->descrizione,
                'prezzo' => $item->prezzo,
                'is_active' => true,
                'tipo' => 'custom',
                'id_tipologia' => $item->tipologia ? $item->tipologia->id : null,       // Aggiunto id tipologia
                'nome_tipologia' => $item->tipologia ? $item->tipologia->nome : null, // Aggiunto nome tipologia
            ];
        });

        // 5. Unisci i due listini
        $listinoFinale = $listinoCombinato->merge($vociCustom);

        return response()->json([
            'listino' => $listinoFinale,
            'tipologie' => $tipologieDisponibili,
        ]);
    }

    /**
     * Aggiorna o crea il prezzo/stato di una voce master per il medico.
     * La tipologia non viene modificata qui.
     */
    public function updateMasterItem(Request $request)
    {
        $medico = Auth::user();
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:listino_master,id',
            'items.*.prezzo' => 'nullable|numeric|min:0',
            'items.*.is_active' => 'required|boolean',
            // La validazione per id_tipologia è stata rimossa
        ]);

        foreach ($validated['items'] as $itemData) {
            $medico->listinoMasterItems()->updateOrCreate(
                ['listino_master_id' => $itemData['id']],
                [
                    'prezzo' => $itemData['prezzo'],
                    'is_active' => $itemData['is_active']
                    // Il campo id_tipologia è stato rimosso dall'aggiornamento
                ]
            );
        }

        $this->checkListinoCompletion($medico);

        return response()->json(['success' => true, 'message' => 'Voci del listino aggiornate.']);
    }

    /**
     * Crea una nuova voce personalizzata per il medico.
     */
    public function storeCustomItem(Request $request)
    {
        $medico = Auth::user();
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.nome' => 'required|string|max:255',
            'items.*.descrizione' => 'nullable|string',
            'items.*.prezzo' => 'required|numeric|min:0',
            // La tipologia è obbligatoria per le voci custom
            'items.*.id_tipologia' => 'required|exists:listino_tipologie,id',
        ]);

        foreach ($validated['items'] as $itemData) {
            $medico->listinoCustomItems()->create($itemData);
        }

        return response()->json(['success' => true, 'message' => 'Voci personalizzate aggiunte con successo.'], 201);
    }

    /**
     * Modifica una voce personalizzata del medico.
     */
    public function updateCustomItem(Request $request, ListinoMedicoCustomItem $item)
    {
        if ($item->medico_user_id !== Auth::id()) {
            abort(403, 'Azione non autorizzata.');
        }

        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'descrizione' => 'nullable|string',
            'prezzo' => 'required|numeric|min:0',
            'id_tipologia' => 'required|exists:listino_tipologie,id',
        ]);

        $item->update($validated);

        return response()->json(['success' => true, 'message' => 'Voce aggiornata.']);
    }

    /**
     * Elimina una voce personalizzata del medico.
     */
    public function destroyCustomItem(ListinoMedicoCustomItem $item)
    {
        if ($item->medico_user_id !== Auth::id()) {
            abort(403, 'Azione non autorizzata.');
        }

        $item->delete();

        return response()->json(['success' => true, 'message' => 'Voce eliminata con successo.']);
    }

    private function checkListinoCompletion($medico)
    {
        $anagrafica = $medico->anagraficaMedico;
        $vociCompletate = $medico->listinoMasterItems()
            ->whereNotNull('prezzo')
            ->where('is_active', true)
            ->count();
        $isComplete = $vociCompletate >= 3;

        if ($isComplete && !$anagrafica->step_listino_completed_at) {
            $anagrafica->update(['step_listino_completed_at' => Carbon::now()]);
        } elseif (!$isComplete && $anagrafica->step_listino_completed_at) {
            $anagrafica->update(['step_listino_completed_at' => null]);
        }
    }
}