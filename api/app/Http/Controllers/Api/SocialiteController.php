<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;

class SocialiteController extends Controller
{
    /**
     * Reindirizza l'utente alla pagina di autenticazione del provider.
     */
    public function authProviderRedirect($provider)
    {
        // Valida che il provider sia supportato per evitare vulnerabilità
        if (!in_array($provider, ['google', 'facebook'])) { // Aggiungi qui altri provider futuri
            return response()->json(['error' => 'Provider non supportato'], 422);
        }

        // Il metodo statless() è importante perché non manterremo una sessione
        return Socialite::driver($provider)->stateless()->redirect();
    }

    /**
     * Gestisce il callback dal provider dopo l'autenticazione.
     */
    public function socialAuthentication($provider)
    {
        try {
            // Otteniamo i dati dell'utente dal provider
            $socialUser = Socialite::driver($provider)->stateless()->user();

            // Cerchiamo un utente esistente o ne creiamo uno nuovo
            $user = User::updateOrCreate([
                'auth_provider_id' => $socialUser->getId(),
                'auth_provider' => $provider,
            ], [
                'name' => $socialUser->getName(),
                'email' => $socialUser->getEmail(),
                'email_verified_at' => now(), // L'email è già verificata dal provider
                'password' => Hash::make(Str::random(24)), // Generiamo una password casuale sicura
                'role' => 'paziente' // Ruolo di default per i nuovi utenti social
            ]);

            // === Logica per la SPA (Approccio Stateless) ===

            // Creiamo un token API per l'utente
            $token = $user->createToken('auth_token')->plainTextToken;

            // Reindirizziamo l'utente al frontend, passando il token come parametro URL
            $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');

            // Usiamo un frammento (#) per nascondere il token dalla cronologia del server
            return redirect($frontendUrl . '/social-callback#token=' . $token);
        } catch (\Exception $e) {
            // In caso di errore, reindirizziamo a una pagina di errore del frontend
            $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');
            return redirect($frontendUrl . '/login?error=social_auth_failed');
        }
    }
}
