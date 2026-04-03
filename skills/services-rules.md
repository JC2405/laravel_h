# Services — Reglas

## Responsabilidad

Toda la lógica vive en Services

## Ejemplo correcto

Controller:
- recibe request
- llama Service

Service:
- valida lógica
- usa DB::transaction
- interactúa con modelos

## Prohibido

- lógica en controllers
- queries complejas fuera del service