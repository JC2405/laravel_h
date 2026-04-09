{{-- resources/views/emails/horario.blade.php --}}
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
            vertical-align: middle;
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
        }

        /* ── Badge ── */
        .badge {
            display: block;
            background: #ffffff;
            border: 1px solid #d1d5db;
            border-top: 2px solid #4caa16;
            border-radius: 4px;
            padding: 7px 9px;
            text-align: left;
        }
        .badge-ficha {
            font-size: 11px;
            font-weight: 700;
            color: #1a1d23;
            display: block;
        }
        .badge-programa {
            font-size: 10px;
            color: #6b7280;
            margin-top: 2px;
            display: block;
            line-height: 1.4;
        }
        .badge-ambiente {
            font-size: 10px;
            color: #4caa16;
            font-weight: 600;
            margin-top: 5px;
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
            @php
                $fechasInicio = [];
                $fechasFin = [];
                foreach ($horario['clases'] ?? [] as $clase) {
                    $fi = $clase->bloque->fechaInicio ?? $clase['bloque']['fechaInicio'] ?? $clase->fechaInicio ?? $clase['fechaInicio'] ?? null;
                    $ff = $clase->bloque->fechaFin ?? $clase['bloque']['fechaFin'] ?? $clase->fechaFin ?? $clase['fechaFin'] ?? null;
                    if ($fi) $fechasInicio[] = $fi;
                    if ($ff) $fechasFin[] = $ff;
                }
                $minFecha = count($fechasInicio) > 0 ? min($fechasInicio) : null;
                $maxFecha = count($fechasFin) > 0 ? max($fechasFin) : null;
            @endphp
            <div class="header-sub">
                Asignación de clases &middot; <br>
                @if($minFecha && $maxFecha)
                    Vigente del {{ \Carbon\Carbon::parse($minFecha)->format('d/m/Y') }} al {{ \Carbon\Carbon::parse($maxFecha)->format('d/m/Y') }}
                @else
                    Semana en curso
                @endif
            </div>
        </div>
        <div class="header-badge">
            <div class="label">Generado</div>
            <div class="date">{{ now()->format('d/m/Y') }}</div>
        </div>
    </div>

    {{-- ── Saludo ──
         CORRECCIÓN: la variable que llega es $horario (array con 'clases' y 'grilla'),
         NO existe $aprendiz en este correo. Se obtiene el nombre del primer instructor
         desde la primera clase disponible.
    --}}
    <div class="greeting">
        @php
            $nombreInstructor = null;
            $primeraClase = collect($horario['clases'] ?? [])->first();
            if ($primeraClase) {
                $nombreInstructor = $primeraClase->funcionario->nombre
                    ?? $primeraClase['funcionario']['nombre']
                    ?? null;
            }
        @endphp
        Estimado{{ $nombreInstructor ? ' ' . $nombreInstructor : ' instructor' }},
        a continuación encontrará su horario de clases asignado para la semana en curso.
    </div>

 
    <div class="body">
        <div class="section-label">Grilla semanal</div>

        @php
            $grilla = $horario['grilla'] ?? [];
            $dias   = ['Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado'];

            // DESPUÉS — más simple y seguro
              $grillaNeta = array_filter($grilla, fn($celdas) => !empty($celdas));
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
                        <td>
                            {{--
                                CORRECCIÓN: la grilla del instructor tiene celdas como:
                                ['ficha' => 'Ficha 3171062', 'programa' => '...', 'ambiente' => '...', ...]
                                NO es un array anidado como la de aprendices.
                            --}}
                            @if (!empty($celdas[$dia]))
                                @php $celda = $celdas[$dia]; @endphp
                                <div class="badge">
                                    <span class="badge-ficha">
                                        {{ $celda['ficha'] ?? '—' }}
                                    </span>
                                    <span class="badge-programa">
                                        {{ \Illuminate\Support\Str::limit($celda['programa'] ?? '', 38) }}
                                    </span>
                                    <span class="badge-ambiente">
                                        {{ $celda['ambiente'] ?? 'Virtual' }}
                                    </span>
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