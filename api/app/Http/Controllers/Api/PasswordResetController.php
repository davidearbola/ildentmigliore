<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;

class PasswordResetController extends Controller
{
    public function sendResetLinkEmail(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => "Non riusciamo a trovare un utente con questo indirizzo email."
            ], 404);
        }

        // Aggiungiamo questo controllo per bloccare gli utenti social.
        if ($user->auth_provider) {
            return response()->json([
                'message' => 'Questo account è associato a un accesso social (' . ucfirst($user->auth_provider) . '). Per accedere, usa il login social. Non è necessaria una password.'
            ], 422); 
        }

        $token = Password::broker()->createToken($user);
        $user->notify(new ResetPasswordNotification($token));

        return response()->json([
            'success' => true,
            'message' => 'Ti abbiamo inviato via email il link per il reset della password!'
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $status = Password::broker()->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->save();
            }
        );

        if ($status == Password::PASSWORD_RESET) {
            return response()->json(['success' => true, 'message' => 'La password è stata reimpostata con successo!']);
        }

        return response()->json(['success' => false, 'message' => 'Token non valido o scaduto.'], 400);
    }
}