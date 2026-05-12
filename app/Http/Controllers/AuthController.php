<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Services\Auth\AuthService;
use App\Services\Funcionario\FuncionarioService;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function __construct(protected AuthService $service, protected FuncionarioService $funcionario)
    {
    }

    /** POST /api/login */
    public function login(LoginRequest $request)
    {
        $resultado = $this->service->loginFuncionario($request->validated());

        if (!$resultado['ok']) {
            return response()->json(['message' => $resultado['mensaje']], 401);
        }

        return response()->json($resultado, 200);
    }

    /** POST /api/logout */
    public function logout()
    {
        try {
            $token = JWTAuth::parseToken();
            $this->service->logout();
        } catch (JWTException $e) {
            return response()->json(['message' => 'Sesion Expirada Te esperamos Pronto']);
        }

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
            'id'            => $funcionario->idFuncionario,
            'nombre'        => $funcionario->nombre,
            'apellido'      => $funcionario->apellido,
            'documento'     => $funcionario->documento,
            'correo'        => $funcionario->correo,
            'telefono'      => $funcionario->telefono,  
            'estado'        => $funcionario->estado,
            'idTipoContrato'=> $funcionario->idTipoContrato,
            'rol'           => $funcionario->roles->first()?->nombreRol,
        ]);
    }


}