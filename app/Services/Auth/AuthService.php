<?php

namespace App\Services\Auth;

use App\Models\FuncionarioModel;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class AuthService
{
    public function loginFuncionario(array $credentials): array
    {
        $funcionario = FuncionarioModel::with('roles')
            ->where('correo', $credentials['correo'])
            ->first();

        // ✅ Verificar que el hash sea válido antes de comparar
        if (!$funcionario) {
            return ['ok' => false, 'mensaje' => 'Credenciales incorrectas.'];
        }

        try {
            $passwordValido = Hash::check($credentials['password'], $funcionario->password);
        } catch (\RuntimeException $e) {
            // El password en BD no está hasheado — error de datos, no de código
            return ['ok' => false, 'mensaje' => 'El password no tiene el formato correcto. Contacta al administrador.'];
        }

        if (!$passwordValido) {
            return ['ok' => false, 'mensaje' => 'Credenciales incorrectas.'];
        }

        if (strtolower($funcionario->estado) !== 'activo') {
            return ['ok' => false, 'mensaje' => 'La cuenta está inactiva.'];
        }

        try {
            // ✅ JWTAuth::fromUser() genera el token directamente desde el modelo
            $token = JWTAuth::fromUser($funcionario);
        } catch (JWTException $e) {
            return ['ok' => false, 'mensaje' => 'No se pudo generar el token.'];
        }

        $rol       = $funcionario->roles->first();
        $nombreRol = $rol ? strtolower($rol->nombreRol) : null;

        return [
            'ok'      => true,
            'token'   => $token,
            'tipo'    => 'Bearer',
            'expira'  => config('jwt.ttl') * 60, // ✅ lee directo del config/jwt.php
            'usuario' => [
                'id'     => $funcionario->idFuncionario,
                'nombre' => $funcionario->nombre,
                'correo' => $funcionario->correo,
                'rol'    => $nombreRol,
            ],
            'sidebar' => $this->getSidebarPorRol($nombreRol),
        ];
    }

    public function refresh(): array
    {
        try {
            // ✅ JWTAuth::refresh() renueva el token del request actual
            $nuevoToken = JWTAuth::refresh(JWTAuth::getToken());
            return ['ok' => true, 'token' => $nuevoToken, 'tipo' => 'Bearer'];
        } catch (JWTException $e) {
            return ['ok' => false, 'mensaje' => 'Token inválido o expirado.'];
        }
    }

    public function logout(): void
    {
        // ✅ JWTAuth::invalidate() añade el token a la blacklist
        JWTAuth::invalidate(JWTAuth::getToken());
    }

    public function getSidebarPorRol(?string $rol): array
    {
        $menus = [
            'coordinador' => [
                ['label' => 'Dashboard',     'icon' => 'dashboard',  'ruta' => '/dashboard'],
                ['label' => 'Funcionarios',  'icon' => 'users',      'ruta' => '/funcionarios'],
                ['label' => 'Aprendices',    'icon' => 'student',    'ruta' => '/aprendices'],
                ['label' => 'Fichas',        'icon' => 'folder',     'ruta' => '/fichas'],
                ['label' => 'Programas',     'icon' => 'book',       'ruta' => '/programas'],
                ['label' => 'Ambientes',     'icon' => 'building',   'ruta' => '/ambientes'],
                ['label' => 'Horarios',      'icon' => 'calendar',   'ruta' => '/horarios'],
                ['label' => 'Sedes',         'icon' => 'location',   'ruta' => '/sedes'],
                ['label' => 'Configuración', 'icon' => 'settings',   'ruta' => '/configuracion'],
            ],
            'instructor' => [
                ['label' => 'Dashboard',   'icon' => 'dashboard', 'ruta' => '/dashboard'],
                ['label' => 'Mis Horarios','icon' => 'calendar',  'ruta' => '/mis-horarios'],
                ['label' => 'Mis Fichas',  'icon' => 'folder',    'ruta' => '/mis-fichas'],
                ['label' => 'Aprendices',  'icon' => 'student',   'ruta' => '/aprendices'],
            ],
        ];

        return $menus[$rol] ?? [];
    }
}