<?php

namespace App\Services\Auth;

use App\Models\FuncionarioModel;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class AuthService
{
    /**
     * Login de funcionario. Retorna token + datos + sidebar.
     */
    public function loginFuncionario(array $credentials): array
    {
        $funcionario = FuncionarioModel::with('roles')
            ->where('correo', $credentials['correo'])
            ->first();

        if (!$funcionario || !Hash::check($credentials['password'], $funcionario->password)) {
            return ['ok' => false, 'mensaje' => 'Credenciales incorrectas.'];
        }

        if (strtolower($funcionario->estado) !== 'activo') {
            return ['ok' => false, 'mensaje' => 'La cuenta está inactiva.'];
        }

        try {
            // Generamos el token usando el guard 'funcionario'
            $token = auth('funcionario')->login($funcionario);
        } catch (JWTException $e) {
            return ['ok' => false, 'mensaje' => 'No se pudo generar el token.'];
        }

        $rol = $funcionario->roles->first();
        $nombreRol = $rol ? strtolower($rol->nombreRol) : null;

        return [
            'ok'      => true,
            'token'   => $token,
            'tipo'    => 'Bearer',
            'expira'  => auth('funcionario')->factory()->getTTL() * 60, // segundos
            'usuario' => [
                'id'     => $funcionario->idFuncionario,
                'nombre' => $funcionario->nombre,
                'correo' => $funcionario->correo,
                'rol'    => $nombreRol,
            ],
            'sidebar' => $this->getSidebarPorRol($nombreRol),
        ];
    }

    /**
     * Refresca el token actual.
     */
    public function refresh(): array
    {
        try {
            $nuevoToken = auth('funcionario')->refresh();
            return ['ok' => true, 'token' => $nuevoToken, 'tipo' => 'Bearer'];
        } catch (JWTException $e) {
            return ['ok' => false, 'mensaje' => 'Token inválido o expirado.'];
        }
    }

    /**
     * Logout: invalida el token en la blacklist.
     */
    public function logout(): void
    {
        auth('funcionario')->logout();
    }

    /**
     * Devuelve los items del sidebar según el rol.
     * Coordinador ve TODO. Instructor solo su módulo operativo.
     */
    public function getSidebarPorRol(?string $rol): array
    {
        $menus = [
            'coordinador' => [
                ['label' => 'Dashboard',      'icon' => 'dashboard',   'ruta' => '/dashboard'],
                ['label' => 'Funcionarios',   'icon' => 'users',       'ruta' => '/funcionarios'],
                ['label' => 'Aprendices',     'icon' => 'student',     'ruta' => '/aprendices'],
                ['label' => 'Fichas',         'icon' => 'folder',      'ruta' => '/fichas'],
                ['label' => 'Programas',      'icon' => 'book',        'ruta' => '/programas'],
                ['label' => 'Ambientes',      'icon' => 'building',    'ruta' => '/ambientes'],
                ['label' => 'Horarios',       'icon' => 'calendar',    'ruta' => '/horarios'],
                ['label' => 'Sedes',          'icon' => 'location',    'ruta' => '/sedes'],
                ['label' => 'Configuración',  'icon' => 'settings',    'ruta' => '/configuracion'],
            ],
            'instructor' => [
                ['label' => 'Dashboard',      'icon' => 'dashboard',   'ruta' => '/dashboard'],
                ['label' => 'Mis Horarios',   'icon' => 'calendar',    'ruta' => '/mis-horarios'],
                ['label' => 'Mis Fichas',     'icon' => 'folder',      'ruta' => '/mis-fichas'],
                ['label' => 'Aprendices',     'icon' => 'student',     'ruta' => '/aprendices'],
            ],
        ];

        return $menus[$rol] ?? [];
    }
}
