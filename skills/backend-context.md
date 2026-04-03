# Backend SENA Horarios — Contexto General

## Arquitectura

El sistema sigue esta estructura estricta:

Request → Controller → Service → Model → BD

- Controllers: delgados, solo validan y delegan
- Services: contienen TODA la lógica de negocio
- Models: usan Eloquent con claves personalizadas

## Reglas obligatorias

- NO poner lógica en Controllers
- NO usar lógica compleja en Models
- SIEMPRE usar Services para lógica de negocio
- SIEMPRE usar FormRequest para validaciones

## Respuestas API

Éxito:
{ "ok": true, "message": "...", "data": {} }

Error:
{ "ok": false, "message": "...", "codigo": "ERROR_CODE" }


# Estilo de código

- Usar nombres claros
- Métodos pequeños
- Evitar duplicación
- Priorizar legibilidad sobre complejidad