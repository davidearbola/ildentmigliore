<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiTokenIsValid
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Recupera la chiave API dall'header della richiesta
        $requestApiKey = $request->header('X-API-KEY');

        // 2. Recupera la chiave API corretta dal file .env
        $validApiKey = config('app.vivisalute_api_key');
        // Nota: Creeremo questa configurazione nel passaggio successivo

        // 3. Controlla se la chiave non è presente o non corrisponde
        if (! $requestApiKey || $requestApiKey !== $validApiKey) {
            // Se non corrisponde, blocca la richiesta con un errore 401
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // 4. Se la chiave è valida, lascia proseguire la richiesta
        return $next($request);
    }
}
