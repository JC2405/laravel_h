# Lógica de Horarios

## Flujo

Crear horario:
- Validar request
- Validar ficha en estado InActivo
- Validar conflictos
- Guardar en transacción

## Conflictos

Se validan en:

- Horas
- Días
- Fechas

## Reglas críticas

- NO permitir conflictos de instructor
- NO permitir conflictos de ambiente

## Nueva regla (MUY IMPORTANTE)

Existe tipo_formacion:

- TECNICA
- TRANSVERSAL

## Comportamiento

- TRANSVERSAL se superpone visualmente a TECNICA
- NO sobrescribe datos en BD
- Es solo lógica de visualización

## Validación adicional

Si tipo = TRANSVERSAL:
- Verificar estado del horario técnico
- Advertir si no está completo