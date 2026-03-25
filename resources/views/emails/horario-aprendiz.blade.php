{{-- resources/views/emails/horario-aprendiz.blade.php --}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Helvetica Neue', Arial, sans-serif;
            background-color: #f0f2f5;
            padding: 32px 16px;
            color: #1a1d23;
        }

        .container {
            max-width: 680px;
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
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
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
        .header-badge {
            background: #4caa16;
            color: #ffffff;
            border-radius: 6px;
            padding: 8px 16px;
            text-align: center;
            flex-shrink: 0;
        }
        .header-badge .label {
            font-size: 10px;
            letter-spacing: 1px;
            text-transform: uppercase;
            opacity: 0.85;
            margin-bottom: 2px;
        }
        .header-badge .date {
            font-size: 13px;
            font-weight: 700;
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

        /* ── Cuerpo ── */
        .body { padding: 24px 36px 36px; }

        .section-label {
            font-size: 10px;
            font-weight: 700;
            color: #9ca3af;
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-bottom: 14px;
        }

        /* ── Tabla ── */
        table { width: 100%; border-collapse: collapse; font-size: 12px; }

        thead th {
            background-color: #f8f9fa;
            color: #374151;
            padding: 10px 7px;
            text-align: center;
            font-weight: 700;
            font-size: 10px;
            letter-spacing: 1px;
            text-transform: uppercase;
            border: 1px solid #e2e5ea;
            border-bottom: 2px solid #4caa16;
        }
        thead th:first-child {
            text-align: left;
            padding-left: 14px;
            width: 118px;
        }

        tbody tr:nth-child(even) td { background-color: #fafafa; }

        td {
            border: 1px solid #e2e5ea;
            padding: 7px 5px;
            text-align: center;
            vertical-align: top;
        }

        .td-hora {
            background-color: #f8f9fa !important;
            font-weight: 700;
            color: #1a1d23;
            font-size: 10.5px;
            white-space: nowrap;
            text-align: left;
            padding: 10px 6px 10px 14px;
            border-left: 3px solid #4caa16;
            vertical-align: middle;
        }

        /* ── Clase/Badge — puede haber varias por celda ── */
        .clase {
            display: block;
            background: #ffffff;
            border: 1px solid #d1d5db;
            border-top: 2px solid #4caa16;
            border-radius: 4px;
            padding: 7px 9px;
            text-align: left;
            margin-bottom: 5px;
        }
        .clase:last-child { margin-bottom: 0; }

        .clase-instructor {
            font-size: 11px;
            font-weight: 700;
            color: #1a1d23;
            display: block;
        }
        .clase-programa {
            font-size: 10px;
            color: #6b7280;
            margin-top: 2px;
            display: block;
            line-height: 1.4;
        }
        .clase-ficha {
            font-size: 10px;
            color: #9ca3af;
            font-variant-numeric: tabular-nums;
            letter-spacing: 0.3px;
            margin-top: 3px;
            display: block;
        }
        .clase-ambiente {
            font-size: 10px;
            color: #4caa16;
            font-weight: 600;
            margin-top: 4px;
            display: block;
        }

        /* ── Empty ── */
        .empty-row td {
            padding: 28px;
            color: #9ca3af;
            font-style: italic;
            font-size: 13px;
        }

        /* ── Nota ── */
        .note {
            margin-top: 20px;
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
        <div>
            <div class="header-eyebrow">Servicio Nacional de Aprendizaje</div>
            <h1>Horario Semanal</h1>
            <div class="header-sub">Asignación de clases &middot; Semana en curso</div>
        </div>
        <div class="header-badge">
            <div class="label">Generado</div>
            <div class="date">{{ now()->format('d/m/Y') }}</div>
        </div>
    </div>

    {{-- ── Saludo ──
         $aprendiz es el objeto AprendizModel pasado desde el controller.
         $horario  es el array ['ok', 'asignaciones', 'grilla'] construido en el controller.
    --}}
    <div class="greeting">
        Estimado {{ $aprendiz->nombre ?? 'aprendiz' }},
        a continuación encontrará su horario de clases asignado para la semana en curso.
    </div>

    {{-- ── Grilla ── --}}
    <div class="body">
        <div class="section-label">Grilla semanal</div>

        @php
            /*
             * La grilla de aprendices tiene estructura:
             *   [ "HH:MM - HH:MM" => [ "Lunes" => [ clase1, clase2, ... ], "Martes" => [...] ] ]
             *
             * Cada $celda[$dia] es un ARRAY de clases (puede haber varias por franja-día).
             */
            $grilla  = $horario['grilla'] ?? [];
            $dias    = ['Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado'];

            // Filtrar franjas que tengan al menos una clase en algún día
            $grillaNeta = array_filter($grilla, function ($celdas) use ($dias) {
                if (!is_array($celdas)) return false;
                foreach ($dias as $d) {
                    if (!empty($celdas[$d]) && is_array($celdas[$d])) return true;
                }
                return false;
            });
        @endphp

        <table>
            <thead>
                <tr>
                    <th>Franja</th>
                    @foreach ($dias as $dia)
                        <th>{{ $dia }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse ($grillaNeta as $franja => $celdas)
                <tr>
                    <td class="td-hora">{{ $franja }}</td>

                    @foreach ($dias as $dia)
                        @php
                            $clase = $celdas[$dia] ?? null;
                        @endphp
                        <td>
                            @if ($clase)
                                @php
                                    $instructor = $clase['instructor'] ?? '—';
                                    $programa   = \Illuminate\Support\Str::limit($clase['programa'] ?? '—', 40);
                                    $ambiente   = $clase['ambiente'] ?? 'Virtual';
                                    $ficha      = $clase['ficha'] ?? null;
                                @endphp
                    
                                <div class="clase">
                                    <span class="clase-instructor">{{ $instructor }}</span>
                                    <span class="clase-programa">{{ $programa }}</span>
                    
                                    @if ($ficha)
                                        <span class="clase-ficha">Ficha {{ $ficha }}</span>
                                    @endif
                    
                                    <span class="clase-ambiente">{{ $ambiente }}</span>
                                </div>
                            @endif
                        </td>
                    @endforeach
                </tr>
                @empty
                <tr class="empty-row">
                    <td colspan="7">No hay clases asignadas para esta semana.</td>
                </tr>
                @endforelse
            </tbody>
        </table>

        <div class="note">
            Si detecta alguna inconsistencia en su horario, comuníquese con la coordinación académica
            dentro de las 24 horas siguientes a la recepción de este correo.
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