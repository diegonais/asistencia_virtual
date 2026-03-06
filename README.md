# Asistencia Virtual (Docker)

Guia rapida para levantar el proyecto con Docker Compose.

## 1. Configurar variables de entorno

Copiar el archivo de ejemplo:

```powershell
Copy-Item .env.example .env
```

Opcional: editar `.env` para cambiar puertos o credenciales.

## 2. Levantar el proyecto

Construir imagenes y levantar contenedores:

```powershell
docker compose up -d --build
```

Ver estado de servicios:

```powershell
docker compose ps
```

La app queda disponible en:

- `http://localhost:3002` (o el valor de `APP_PORT` en `.env`)

## 3. Comandos utiles

Detener servicios:

```powershell
docker compose down
```

Detener y eliminar volumen de datos (reset completo de BD):

```powershell
docker compose down -v
```

## 4. Acceder a PostgreSQL por consola

Entrar a consola `psql` dentro del contenedor:

```powershell
docker compose exec db psql -U postgres -d asistencia_virtual
```

Si cambiaste usuario o base en `.env`, usa esos valores:

```powershell
docker compose exec db psql -U <DB_USER> -d <DB_NAME>
```

Ejecutar una consulta directa sin entrar a `psql` interactivo:

```powershell
docker compose exec db psql -U postgres -d asistencia_virtual -c "SELECT now();"
```

