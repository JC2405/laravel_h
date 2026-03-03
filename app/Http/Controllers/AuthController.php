<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Services\Auth\AuthService;

class AuthController extends Controller
{
    public function __construct(protected AuthService $service) {}

    /** POST /api/login */
    public function login(LoginRequest $request)
    {
        $resultado = $this->service->loginFuncionario($request->validated());

        if (!$resultado['ok']) {
            return response()->json(['message' => $resultado['mensaje']], 401);
        }

        return response()->json($resultado, 200);
    }

    /** POST /api/auth/logout */
    public function logout()
    {
        $this->service->logout();
        return response()->json(['message' => 'Sesión cerrada correctamente.']);
    }

    /** POST /api/auth/refresh */
    public function refresh()
    {
        $resultado = $this->service->refresh();

        if (!$resultado['ok']) {
            return response()->json(['message' => $resultado['mensaje']], 401);
        }

        return response()->json($resultado);
    }

    /** GET /api/auth/me */
    public function me()
    {
        $funcionario = auth('funcionario')->user();

        if (!$funcionario) {
            return response()->json(['message' => 'No autenticado.'], 401);
        }

        $funcionario->load('roles'); 

        return response()->json([
            'id'     => $funcionario->idFuncionario,
            'nombre' => $funcionario->nombre,
            'correo' => $funcionario->correo,
            'rol'    => $funcionario->roles->first()?->nombreRol,
        ]);
    }

    /** GET /api/sidebar */
    public function sidebar()
    {
        $funcionario = auth('funcionario')->user();

        if (!$funcionario) {
            return response()->json(['message' => 'No autenticado.'], 401);
        }

        $funcionario->load('roles'); // ✅ load en lugar de loadMissing

        $rol   = strtolower($funcionario->roles->first()?->nombreRol ?? '');
        $items = $this->service->getSidebarPorRol($rol);

        return response()->json(['rol' => $rol, 'sidebar' => $items]);
    }
}