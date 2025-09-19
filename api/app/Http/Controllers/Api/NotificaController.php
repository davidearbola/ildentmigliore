<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificaController extends Controller
{
    /**
     * Restituisce le notifiche non lette per l'utente autenticato.
     */
    public function index()
    {
        $user = Auth::user();

        $notificheNonLette = $user->notifiche()->whereNull('letta_at')->get();

        return response()->json($notificheNonLette);
    }

    /**
     * Segna come lette SOLO le notifiche di tipo PROPOSTA_ACCETTATA
     */
    public function markAsReadNotificheMedico()
    {
        $user = Auth::user();

        // --- MODIFICA QUI: Aggiungi il filtro per il tipo di notifica ---
        $user->notifiche()
            ->where('tipo', 'PROPOSTA_ACCETTATA') // Sii specifico sul tipo di notifica
            ->whereNull('letta_at')
            ->update(['letta_at' => Carbon::now()]);

        return response()->json(['success' => true]);
    }
}