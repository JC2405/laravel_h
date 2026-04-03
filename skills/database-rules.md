# Base de Datos — Reglas

## Convenciones

- Tablas en español
- Primary Keys personalizadas (NO usar id)
- NO usar timestamps automáticos

## Ejemplo

FuncionarioModel:
- tabla: funcionario
- PK: idFuncionario

## Regla crítica

Siempre que se cree un modelo:
- definir $table
- definir $primaryKey
- public $timestamps = false

## Relaciones

- belongsTo
- hasMany
- belongsToMany con pivotes en camelCase