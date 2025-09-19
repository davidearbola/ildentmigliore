<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AnagraficaMedico;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Notifications\WelcomeViviSaluteUser;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;


class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'role' => 'paziente',
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $user->sendEmailVerificationNotification();

        return response()->json([
            'success' => true,
            'message' => 'Registrazione effettuata con successo. Per favore controlla la tua email per verificare il tuo account!'
        ], 201);
    }

    public function registerMedico(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'ragione_sociale' => ['required', 'string', 'max:255'],
            'p_iva' => ['required', 'string', 'max:20', 'unique:' . AnagraficaMedico::class],
            'cellulare' => ['required', 'string', 'max:20'],
            'indirizzo' => ['required', 'string', 'max:255'],
            'citta' => ['required', 'string', 'max:255'],
            'cap' => ['required', 'string', 'max:10'],
            'provincia' => ['required', 'string', 'max:255'],
        ]);

        $user = User::create([
            'role' => 'medico',
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // --- BLOCCO AGGIUNTO ---
        // Geocodifica l'indirizzo del medico
        // TODO: Riattivare la chiamata all'API di Google quando sarà disponibile!
        /*
        $fullAddress = "{$request->indirizzo}, {$request->cap} {$request->citta}, {$request->provincia}";
        $apiKey = env('Maps_API_KEY');
        $lat = null;
        $lng = null;
    
        $response = Http::get("https://maps.googleapis.com/maps/api/geocode/json", [
            'address' => $fullAddress,
            'key' => $apiKey,
        ]);
    
        if ($response->successful() && $response->json('status') === 'OK') {
            $location = $response->json('results.0.geometry.location');
            $lat = $location['lat'];
            $lng = $location['lng'];
        }
        */

        // WORKAROUND TEMPORANEO: Usiamo coordinate fisse (es. centro di Roma)
        $lat = 45.4642;
        $lng = 9.1900;
        // --- FINE BLOCCO AGGIUNTO ---

        $user->anagraficaMedico()->create([
            'ragione_sociale' => $request->ragione_sociale,
            'p_iva' => $request->p_iva,
            'cellulare' => $request->cellulare,
            'indirizzo' => $request->indirizzo,
            'citta' => $request->citta,
            'cap' => $request->cap,
            'provincia' => $request->provincia,
            'lat' => $lat,
            'lng' => $lng,
        ]);

        $user->sendEmailVerificationNotification();

        return response()->json([
            'success' => true,
            'message' => 'Registrazione effettuata con successo. Per favore controlla la tua email per verificare il tuo account!'
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if ($user && ! $user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Per favore, verifica il tuo indirizzo email prima di accedere.'], 403);
        }

        if (!Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            return response()->json([
                'success' => false,
                'message' => trans('auth.failed')
            ], 401);
        }

        $request->session()->regenerate();

        return response()->json([
            'success' => true,
            'user' => Auth::user()
        ]);
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Logout effettuato con successo.']);
    }

    public function user(Request $request)
    {
        $user = $request->user();
        if ($user->role === 'paziente') {
            $user->load('anagraficaPaziente');
        }

        if ($user->role === 'medico') {
            $user->load('anagraficaMedico');
        }

        return response()->json($user);
    }

    public function registerFromViviSalute(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:' . User::class],
            'ragione_sociale' => ['required', 'string', 'max:255'],
            'p_iva' => ['required', 'string', 'max:20', 'unique:' . AnagraficaMedico::class],
            'cellulare' => ['required', 'string', 'max:20'],
            'indirizzo' => ['required', 'string', 'max:255'],
            'citta' => ['required', 'string', 'max:255'],
            'cap' => ['required', 'string', 'max:10'],
            'provincia' => ['required', 'string', 'max:255'],
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $temporaryPassword = 'Vivi' . random_int(1000, 9999) . '!';

        $user = User::create([
            'role' => 'medico',
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($temporaryPassword),
            'email_verified_at' => now(),
        ]);

        $user->anagraficaMedico()->create([
            'tipo_registrazione' => 'vivi',
            'ragione_sociale' => $validated['ragione_sociale'],
            'p_iva' => $validated['p_iva'],
            'cellulare' => $validated['cellulare'],
            'indirizzo' => $validated['indirizzo'],
            'citta' => $validated['citta'],
            'cap' => $validated['cap'],
            'provincia' => $validated['provincia'],
            'lat' => $validated['lat'],
            'lng' => $validated['lng'],
        ]);

        $user->notify(new WelcomeViviSaluteUser($temporaryPassword));

        return response()->json([
            'message' => 'Medico registrato con successo tramite API.',
            'user_id' => $user->id,
        ], 201);
    }
}
