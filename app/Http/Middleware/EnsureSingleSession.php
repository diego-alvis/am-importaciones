<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureSingleSession
{
    /**
     * Garantiza que las cuentas con sesión única (Admin y Supervisores) solo mantengan una sesión activa.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();

            if (
                $user->enforcesSingleSession()
                && $user->current_session_id !== null
                && $user->current_session_id !== $request->session()->getId()
            ) {
                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                $message = 'Tu sesión fue cerrada porque esta cuenta inició sesión en otro dispositivo.';

                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'error' => $message,
                    ], 401);
                }

                return redirect()->route('login')->with('warning', $message);
            }
        }

        return $next($request);
    }
}
