<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class ForcePasswordChange
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        $allowedRoutes = ['impostazioni.password', 'api.logout'];

        if (
            $user &&
            $user->role === 'medico' &&
            $user->anagraficaMedico?->tipo_registrazione === 'vivi' &&
            $user->password_changed_at === null &&
            !$request->routeIs($allowedRoutes)
        ) {
            return response()->json([
                'message' => 'Cambio password obbligatorio.',
                'error_code' => 'FORCE_PASSWORD_CHANGE'
            ], 403);
        }

        return $next($request);
    }
}
