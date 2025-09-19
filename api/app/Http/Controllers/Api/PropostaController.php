<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContropropostaMedico;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Notifications\PropostaAccettataMedicoNotification;

class PropostaController extends Controller
{
    /**
     * Restituisce tutte le proposte per il paziente loggato, divise per stato.
     */
    public function index()
    {
        $user = Auth::user();

        $preventiviIds = $user->anagraficaPaziente->preventivi()->pluck('id');

        $proposte = ContropropostaMedico::whereIn('preventivo_paziente_id', $preventiviIds)
            ->with(['medico.anagraficaMedico', 'preventivoPaziente'])
            ->orderBy('created_at', 'desc')
            ->get();

        $proposteSeparate = $proposte->groupBy(function ($proposta) {
            return $proposta->stato === 'inviata' ? 'nuove' : 'archiviate';
        });

        return response()->json([
            'nuove' => $proposteSeparate->get('nuove', []),
            'archiviate' => $proposteSeparate->get('archiviate', []),
        ]);
    }

    /**
     * Segna le proposte e le notifiche associate come lette/visualizzate.
     */
    public function markProposteAsVisualizzate(Request $request)
    {
        // Questa funzione ora si occupa SOLO di aggiornare lo stato delle proposte
        ContropropostaMedico::whereIn('id', $request->proposteIds)
            ->where('stato', 'inviata')
            ->update(['stato' => 'visualizzata']);

        // Ora chiamiamo la logica generica per pulire le notifiche
        Auth::user()->notifiche()
            ->where('tipo', 'NUOVA_PROPOSTA')
            ->whereNull('letta_at')
            ->update(['letta_at' => Carbon::now()]);

        return response()->json(['success' => true, 'message' => 'Proposte segnate come visualizzate.']);
    }


    /**
     * Accetta una singola proposta.
     */
    public function accetta(ContropropostaMedico $proposta)
    {
        if ($proposta->preventivoPaziente->anagraficaPaziente->user_id !== Auth::id()) {
            abort(403, 'Azione non autorizzata.');
        }

        $proposta->update(['stato' => 'accettata']);

        $medico = $proposta->medico;
        $medico->notifiche()->create([
            'tipo' => 'PROPOSTA_ACCETTATA',
            'messaggio' => 'Il paziente ' . $proposta->preventivoPaziente->anagraficaPaziente->user->name . ' ha accettato la tua proposta!',
            'url_azione' => '/dashboard/preventivi-accettati'
        ]);
        $medico->notify(new PropostaAccettataMedicoNotification($proposta));

        return response()->json(['success' => true, 'message' => 'Proposta accettata con successo.']);
    }

    /**
     * Rifiuta una singola proposta.
     */
    public function rifiuta(ContropropostaMedico $proposta)
    {
        if ($proposta->preventivoPaziente->anagraficaPaziente->user_id !== Auth::id()) {
            abort(403, 'Azione non autorizzata.');
        }

        $proposta->update(['stato' => 'rifiutata']);

        return response()->json(['success' => true, 'message' => 'Proposta rifiutata.']);
    }

    public function getProposteAccettatePerMedico()
    {
        $medico = Auth::user();

        $proposte = $medico->controproposte()
            ->where('stato', 'accettata')
            ->with([
                'preventivoPaziente.anagraficaPaziente' => function ($query) {
                    $query->with('user:id,name,email');
                }
            ])
            ->orderBy('updated_at', 'desc')
            ->get();

        return response()->json($proposte);
    }
}
