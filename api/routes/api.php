<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PasswordResetController;
use App\Http\Controllers\Api\EmailVerificationController;
use App\Http\Controllers\Api\GeoController;
use App\Http\Controllers\Api\ImpostazioniUtenteController;
use App\Http\Controllers\Api\ListinoController;
use App\Http\Controllers\Api\PreventivoController;
use App\Http\Controllers\Api\ProfiloMedicoController;
use App\Http\Controllers\Api\NotificaController;
use App\Http\Controllers\Api\PropostaController;
use App\Http\Controllers\Api\SocialiteController;
use App\Jobs\TestLogJob;

/*
|--------------------------------------------------------------------------
| Rotte Pubbliche
|--------------------------------------------------------------------------
*/

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register-medico', [AuthController::class, 'registerMedico']);

// --- ROTTE GEOGRAFICHE ---
Route::get('/province', [GeoController::class, 'getProvince']);
Route::get('/comuni/{param}', [GeoController::class, 'getComuni']);

/*
|--------------------------------------------------------------------------
| Rotte Protette da Autenticazione (`auth:sanctum`)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {

    // --- ROTTE SEMPRE ACCESSIBILI (PRE-CAMBIO PASSWORD) ---
    // Queste sono le uniche rotte necessarie per gestire l'account base.

    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::prefix('impostazioni')->name('impostazioni.')->group(function () {
        Route::post('/anagrafica', [ImpostazioniUtenteController::class, 'updateAnagrafica'])->name('anagrafica');
        Route::put('/email', [ImpostazioniUtenteController::class, 'updateEmail'])->name('email');
        Route::put('/password', [ImpostazioniUtenteController::class, 'updatePassword'])->name('password');
    });

    // --- NUOVA ROTTA PER PROFILO PUBBLICO ---
    Route::get('/profilo-pubblico-medico/{medicoId}', [ProfiloMedicoController::class, 'showPublicProfile'])
        ->where('medicoId', '[0-9]+');

    // --- ROTTE PROTETTE DAL CAMBIO PASSWORD OBBLIGATORIO ---
    // Tutte le funzionalità della dashboard del medico vanno qui dentro.
    Route::middleware('force.password.change')->group(function () {

        // Profilo Medico
        Route::prefix('profilo-medico')->group(function () {
            Route::get('/', [ProfiloMedicoController::class, 'index']);
            Route::post('/descrizione', [ProfiloMedicoController::class, 'updateDescrizione']);
            Route::post('/foto-studio', [ProfiloMedicoController::class, 'uploadFotoStudio']);
            Route::delete('/foto-studio/{foto}', [ProfiloMedicoController::class, 'destroyFotoStudio']);
            Route::post('/staff', [ProfiloMedicoController::class, 'storeStaff']);
            Route::put('/staff/{staff}', [ProfiloMedicoController::class, 'updateStaff']);
            Route::delete('/staff/{staff}', [ProfiloMedicoController::class, 'destroyStaff']);
        });

        // Listino
        Route::prefix('listino')->group(function () {
            Route::get('/', [ListinoController::class, 'index']);
            Route::post('/master', [ListinoController::class, 'updateMasterItem']);
            Route::post('/custom', [ListinoController::class, 'storeCustomItem']);
            Route::put('/custom/{item}', [ListinoController::class, 'updateCustomItem']);
            Route::delete('/custom/{item}', [ListinoController::class, 'destroyCustomItem']);
        });

        // Notifiche e Proposte Medico
        Route::get('/notifiche', [NotificaController::class, 'index']);
        Route::post('/notifiche-mark-as-read', [NotificaController::class, 'markAsReadNotificheMedico']);
        Route::get('/proposte-accettate', [PropostaController::class, 'getProposteAccettatePerMedico']);
    });

    // --- ROTTE PAZIENTE (Non affette dal middleware medico) ---
    Route::post('/preventivi', [PreventivoController::class, 'store']);
    Route::get('/preventivi/{preventivoPaziente}/stato', [PreventivoController::class, 'stato']);
    Route::post('/preventivi/{preventivoPaziente}/conferma', [PreventivoController::class, 'conferma']);
    Route::get('/preventivi/{preventivoPaziente}/proposte-stato', [PreventivoController::class, 'proposteStato']);
    Route::prefix('proposte')->group(function () {
        Route::get('/', [PropostaController::class, 'index']);
        Route::post('/mark-as-read-paziente', [PropostaController::class, 'markProposteAsVisualizzate']);
        Route::post('/{proposta}/accetta', [PropostaController::class, 'accetta']);
        Route::post('/{proposta}/rifiuta', [PropostaController::class, 'rifiuta']);
    });
});

/*
|--------------------------------------------------------------------------
| Altre Rotte Pubbliche (Password Reset, Verifica Email, etc.)
|--------------------------------------------------------------------------
*/
Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLinkEmail']);
Route::post('/reset-password', [PasswordResetController::class, 'resetPassword']);

Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
    ->middleware(['signed'])
    ->name('verification.verify');

Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])
    ->middleware(['auth:sanctum', 'throttle:6,1']);

Route::post('/resend-verification-email', [EmailVerificationController::class, 'publicResend'])
    ->middleware('throttle:6,1');

/*
|--------------------------------------------------------------------------
| Rotta API Privata
|--------------------------------------------------------------------------
*/
Route::post('/private/register-medico', [AuthController::class, 'registerFromViviSalute'])->middleware('api.key');

/*
|--------------------------------------------------------------------------
| Rotte per Socialite
|--------------------------------------------------------------------------
*/

Route::get('/auth/{provider}/redirect', [SocialiteController::class, 'authProviderRedirect']);
Route::get('/auth/{provider}/callback', [SocialiteController::class, 'socialAuthentication']);

Route::get('/test-job', function () {
    // Mette in coda il nostro job di test.
    TestLogJob::dispatch();

    return response()->json([
        'success' => true,
        'message' => 'Job di test aggiunto alla coda con successo. Controlla i log tra un minuto.',
    ]);
});