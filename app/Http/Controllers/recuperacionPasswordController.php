<?php

namespace App\Http\Controllers;

use App\Services\Horario\MailService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

class RecuperacionPasswordController extends Controller
{
    public function __construct(
        protected MailService $mailService
    ) {}

    public function enviarCorreoRecuperacionPassword(Request $request)
    {
        $request->validate([
            'correo' => ['required', 'email'],
        ]);

        return response()->json(
            $this->mailService->enviarRecuperacionPassword($request->correo)
        );
    }

     public function cambiarPassword(Request $request)
    {
        $request->validate([
            'token' => ['required'],
            'password' => [
                'required',
                'confirmed',
                Password::min(3)
                    ->letters(),
            ],
        ]);

        return response()->json(
            $this->mailService->cambiarPassword(
                $request->token,
                $request->password
            )
        );
    }
}