<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\JWTException;

class JwtFuncionarioMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        try {
            $user = auth('funcionario')->userOrFail();
        } catch (TokenExpiredException $e) {
            return response()->json(['message' => 'Token expirado.'], 401);
        } catch (TokenInvalidException $e) {
            return response()->json(['message' => 'Token inválido.'], 401);
        } catch (JWTException $e) {
            return response()->json(['message' => 'Token no proporcionado.'], 401);
        }

        // Si la ruta exige un rol específico, lo verificamos aquí
        if (!empty($roles)) {
            $rolUsuario = strtolower($user->roles->first()?->nombreRol ?? '');
            if (!in_array($rolUsuario, array_map('strtolower', $roles))) {
                return response()->json(['message' => 'No tienes permiso para este recurso.'], 403);
            }
        }

        return $next($request);
    }
}
