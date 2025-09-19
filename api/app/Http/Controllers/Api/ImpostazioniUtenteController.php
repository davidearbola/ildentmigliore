<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rule;

class ImpostazioniUtenteController extends Controller
{
    /**
     * Aggiorna l'anagrafica dell'utente (paziente o medico).
     */
    public function updateAnagrafica(Request $request)
    {
        $user = $request->user();

        if ($user->role === 'paziente') {
            $validatedData = $request->validate([
                'cellulare' => 'required|string|min:9',
                'indirizzo' => 'required|string|max:255',
                'citta'     => 'required|string|max:255',
                'cap'       => 'required|string|size:5',
                'provincia' => 'required|string|size:2',
            ]);
            $user->anagraficaPaziente()->update($validatedData);
        }

        if ($user->role === 'medico') {
            $validatedData = $request->validate([
                'ragione_sociale' => 'required|string|max:255',
                'p_iva' => 'required|string|size:11',
                'indirizzo' => 'required|string|max:255',
                'citta' => 'required|string|max:255',
                'cap' => 'required|string|size:5',
                'provincia' => 'required|string|size:2',
            ]);
            $user->anagraficaMedico()->update($validatedData);
        }

        return response()->json([
            'success' => true,
            'message' => 'Dati aggiornati con successo.'
        ]);
    }

    /**
     * Aggiorna l'email dell'utente.
     */
    public function updateEmail(Request $request)
    {
        $user = $request->user();
        $validated = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
        ]);

        $user->email = $validated['email'];
        $user->email_verified_at = null;
        $user->save();
        $user->sendEmailVerificationNotification();
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['success' => true, 'message' => 'Email aggiornata. Controlla la tua nuova casella di posta per verificare l\'indirizzo e accedi di nuovo.']);
    }

    /**
     * Aggiorna la password dell'utente.
     */
    public function updatePassword(Request $request)
    {
        $user = $request->user();
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);
        if (!Hash::check($validated['current_password'], $user->password)) {
            return response()->json(['success' => false, 'message' => 'La password attuale non è corretta.'], 422);
        }
        $user->password = Hash::make($validated['password']);
        $user->password_changed_at = now();
        $user->save();
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return response()->json(['success' => true, 'message' => 'Password aggiornata con successo. Effettua nuovamente il login.']);
    }
}
