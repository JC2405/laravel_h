<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\JWTException;

class JwtFuncionarioMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        try {
            $user = auth('funcionario')->authenticate();

            if (!$user) {
                return response()->json(['message' => 'No autenticado.'], 401);
            }

        } catch (TokenExpiredException $e) {
            return response()->json(['message' => 'Token expirado.'], 401);
        } catch (TokenInvalidException $e) {
            return response()->json(['message' => 'Token inválido.'], 401);
        } catch (JWTException $e) {
            return response()->json(['message' => 'Token no proporcionado.'], 401);
        }

        if (!empty($roles)) {
            $user->load('roles'); // ✅ load en lugar de loadMissing
            $rolUsuario = strtolower($user->roles->first()?->nombreRol ?? '');

            if (!in_array($rolUsuario, array_map('strtolower', $roles))) {
                return response()->json(['message' => 'No tienes permiso para este recurso.'], 403);
            }
        }

        return $next($request);
    }
}