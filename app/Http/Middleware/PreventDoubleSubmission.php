<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware para prevenir doble envío de formularios
 * 
 * Este middleware utiliza tokens de idempotencia para asegurar que
 * una misma acción solo se ejecute una vez, incluso si el usuario
 * hace clic múltiples veces en el botón de envío.
 */
class PreventDoubleSubmission
{
    /**
     * Tiempo de expiración del token en segundos (5 minutos)
     */
    protected $tokenExpiration = 300;

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Solo aplicar a métodos POST, PUT, PATCH, DELETE
        if (!in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            return $next($request);
        }

        // Obtener el token de idempotencia del request
        $idempotencyKey = $request->header('X-Idempotency-Key') 
            ?? $request->input('_idempotency_key')
            ?? $request->header('Idempotency-Key');

        // Si no hay token, generar uno y continuar (primera vez)
        if (!$idempotencyKey) {
            return $next($request);
        }

        // Crear una clave única basada en el token y la ruta
        $cacheKey = 'idempotency:' . md5($idempotencyKey . ':' . $request->path() . ':' . $request->ip());

        // Verificar si este token ya fue usado
        if (Cache::has($cacheKey)) {
            Log::warning('Intento de doble envío detectado', [
                'ip' => $request->ip(),
                'path' => $request->path(),
                'method' => $request->method(),
                'user_id' => auth()->id(),
            ]);

            // Retornar respuesta indicando que la acción ya fue procesada
            if ($request->expectsJson() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Esta acción ya fue procesada. Por favor, no recargue la página.',
                    'error' => 'duplicate_submission'
                ], 409); // 409 Conflict
            }

            return redirect()->back()
                ->with('error', 'Esta acción ya fue procesada. Por favor, no recargue la página.');
        }

        // Marcar el token como usado antes de procesar la request
        Cache::put($cacheKey, true, $this->tokenExpiration);

        // Procesar la request
        $response = $next($request);

        // Si la respuesta fue exitosa (2xx), mantener el token en cache
        // Si hubo un error, eliminar el token para permitir reintentos
        if ($response->getStatusCode() >= 400) {
            Cache::forget($cacheKey);
        }

        return $response;
    }
}
