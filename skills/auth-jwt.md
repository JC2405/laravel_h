# Autenticación JWT

## Guard

funcionario

## Roles

1 = coordinador
2 = instructor

## Middleware

jwt.funcionario
jwt.funcionario:coordinador
jwt.funcionario:instructor

## Reglas

- El token incluye rol
- Validar permisos por middleware, no en controller
