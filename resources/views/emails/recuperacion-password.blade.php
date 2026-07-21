{{-- resources/views/emails/reset-password.blade.php --}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperación de contraseña</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Helvetica Neue', Arial, sans-serif;
            background-color: #f0f2f5;
            padding: 32px 16px;
            color: #1a1d23;
        }

        .container {
            max-width: 560px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 20px rgba(0,0,0,0.10);
        }

        /* ── Header ── */
        .header {
            background: #ffffff;
            padding: 28px 36px 24px;
            border-top: 5px solid #4caa16;
            border-bottom: 1px solid #e8eaed;
        }
        .header-eyebrow {
            font-size: 10px;
            color: #4caa16;
            letter-spacing: 2.5px;
            text-transform: uppercase;
            font-weight: 700;
            margin-bottom: 8px;
        }
        .header h1 {
            font-size: 23px;
            font-weight: 700;
            color: #1a1d23;
            line-height: 1.2;
        }
        .header-sub {
            font-size: 13px;
            color: #6b7280;
            margin-top: 4px;
        }

        /* ── Saludo ── */
        .greeting {
            padding: 14px 36px;
            font-size: 13px;
            color: #4b5563;
            background: #fafafa;
            border-bottom: 1px solid #e8eaed;
            line-height: 1.6;
        }
        .greeting strong { color: #1a1d23; }

        /* ── Cuerpo ── */
        .body { padding: 28px 36px 36px; }

        .section-label {
            font-size: 10px;
            font-weight: 700;
            color: #9ca3af;
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-bottom: 14px;
        }

        .body p {
            font-size: 13px;
            color: #4b5563;
            line-height: 1.7;
            margin-bottom: 18px;
        }

        /* ── Botón ── */
        .btn-wrap {
            text-align: center;
            margin: 26px 0 22px;
        }
        .btn {
            display: inline-block;
            background: #4caa16;
            color: #ffffff !important;
            text-decoration: none;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            padding: 13px 32px;
            border-radius: 6px;
        }

        /* ── Enlace alterno ── */
        .link-fallback {
            font-size: 11px;
            color: #9ca3af;
            word-break: break-all;
            background: #fafafa;
            border: 1px solid #e2e5ea;
            border-radius: 4px;
            padding: 10px 12px;
            margin-bottom: 20px;
        }
        .link-fallback a {
            color: #4caa16;
            text-decoration: none;
        }

        /* ── Nota ── */
        .note {
            margin-top: 8px;
            padding: 12px 16px;
            background: #fafafa;
            border: 1px solid #e2e5ea;
            border-left: 3px solid #4caa16;
            font-size: 12px;
            color: #6b7280;
            line-height: 1.6;
            border-radius: 0 4px 4px 0;
        }

        /* ── Footer ── */
        .footer {
            text-align: center;
            padding: 18px 36px;
            font-size: 11px;
            color: #9ca3af;
            border-top: 1px solid #e8eaed;
            background: #f8f9fa;
            line-height: 2;
        }
        .footer strong { color: #4caa16; font-weight: 700; }
    </style>
</head>
<body>
<div class="container">

    {{-- ── Header ── --}}
    <div class="header">
        <div class="header-eyebrow">Servicio Nacional de Aprendizaje</div>
        <h1>Recuperación de contraseña</h1>
        <div class="header-sub">Solicitud de restablecimiento de acceso</div>
    </div>

    {{-- ── Saludo ── --}}
    <div class="greeting">
        Hola <strong>{{ $funcionario->nombre }} {{ $funcionario->apellido }}</strong>,
        recibimos una solicitud para restablecer la contraseña de tu cuenta.
    </div>

    {{-- ── Cuerpo ── --}}
    <div class="body">
        <div class="section-label">Restablecer acceso</div>

        <p>
            Haz clic en el siguiente botón para crear una nueva contraseña.
            Por tu seguridad, este enlace tiene un tiempo de vigencia limitado.
        </p>

        <div class="btn-wrap">
            <a href="{{ $link }}" class="btn">Restablecer contraseña</a>
        </div>

        <p style="margin-bottom: 8px; font-size: 12px; color: #9ca3af;">
            Si el botón no funciona, copia y pega este enlace en tu navegador:
        </p>
        <div class="link-fallback">
            <a href="{{ $link }}">{{ $link }}</a>
        </div>

        <div class="note">
            Si no solicitaste este cambio, puedes ignorar este correo. Tu contraseña actual
            seguirá siendo válida y no se realizará ningún cambio en tu cuenta.
        </div>
    </div>

    {{-- ── Footer ── --}}
    <div class="footer">
        Generado automáticamente &middot; <strong>SENA</strong> {{ date('Y') }} &middot; Sistema de Gestión de Horarios<br>
        Por favor no responda este mensaje directamente.
    </div>

</div>
</body>
</html>