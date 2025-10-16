# Scripts del Workspace (package.json raíz)

Todos los comandos se ejecutan con `bun run <script>`. Este documento resume cada script del `package.json` en la raíz del proyecto, su comando exacto y una breve descripción de su propósito.

## General

| Script          | Comando                                                                     | Descripción                                                                         |
| --------------- | --------------------------------------------------------------------------- | ----------------------------------------------------------------------------------- |
| `i:all`         | `bun i && bun run i:be && bun run i:fe`                                     | Instala dependencias en el monorepo (raíz, backend y frontend).                     |
| `dev`           | `concurrently ... "bun run dev:be" "bun run queue:listen" "bun run dev:fe"` | Arranca el servidor backend, el listener de colas y el cliente de Vite en paralelo. |
| `quick-install` | `bun scripts/quick-install.ts`                                              | Asistente de instalación y puesta en marcha rápida del proyecto.                    |

## Backend (Local)

| Script                  | Comando                                                                                                | Descripción                                                                                                         |
| ----------------------- | ------------------------------------------------------------------------------------------------------ | ------------------------------------------------------------------------------------------------------------------- |
| `local`                 | `dotenv -e .env.local -- php backend/artisan`                                                          | Alias para ejecutar `php artisan` cargando `.env.local`. Úsalo como prefijo: `bun run local <comando>`.             |
| `artisan`               | `bun run local`                                                                                        | Alias directo de `local` para ejecutar comandos artisan.                                                            |
| `i:be`                  | `cd backend && composer install`                                                                       | Instala dependencias de PHP del backend.                                                                            |
| `dev:be`                | `bun run ziggy && bun run local serve --host=localhost --port=8080`                                    | Inicia el servidor HTTP de Laravel. Ejecuta Ziggy antes (si está configurado).                                      |
| `queue:listen`          | `bun run local queue:listen --queue=default ...`                                                       | Inicia el listener de colas para la cola `default` con opciones de rendimiento/seguridad.                           |
| `db:create`             | `bun run local db:create`                                                                              | Ejecuta el comando artisan para crear la base de datos (si existe el comando).                                      |
| `migrate`               | `bun run local migrate`                                                                                | Ejecuta migraciones de base de datos.                                                                               |
| `migrate:fresh`         | `bun run local migrate:fresh`                                                                          | Reconstruye el esquema desde cero.                                                                                  |
| `migrate:fresh:seed`    | `bun run local migrate:fresh --seed --force --no-interaction`                                          | Reconstruye y siembra datos de ejemplo. Pensado para entornos locales.                                              |
| `seed`                  | `bun run local db:seed`                                                                                | Siembra datos usando los seeders configurados.                                                                      |
| `seed:users`            | `bun run seed --class=Database\\Seeders\\SystemUsersSeeder`                                            | Ejecuta el seeder de usuarios del sistema.                                                                          |
| `seed:role-permission`  | `bun run local db:seed --class=RolePermissionSeeder`                                                   | Siembra roles y permisos iniciales.                                                                                 |
| `dump`                  | `cd backend && composer dump-autoload`                                                                 | Regenera el autoload de Composer.                                                                                   |
| `clear:all`             | `bun run local cache:clear && ... && composer dump-autoload -o`                                        | Limpia toda la caché de Laravel (config, rutas, vistas, eventos, optimize) y regenera autoload.                     |
| `tinker`                | `bun run local tinker`                                                                                 | Abre Tinker para evaluar código interactivo con el entorno local cargado.                                           |
| `logs:tail`             | `bun run local pail --ansi`                                                                            | Sigue logs en tiempo real con Laravel Pail. Requiere `pcntl` (no disponible en Windows).                            |
| `logs:tail:file`        | `powershell -NoProfile -Command Get-Content -Path backend\\storage\\logs\\laravel.log -Wait -Tail 200` | Alternativa compatible con Windows para seguir el archivo de log.                                                   |
| `env:check`             | `powershell -NoProfile -ExecutionPolicy Bypass -File ./scripts/ps/env-check.ps1`                       | Verifica llaves faltantes en `.env.local` según `.env.local.example` o `.env.example`. Sale con código 1 si faltan. |
| `debug:on`              | `powershell ... ./scripts/ps/debug-on.ps1 && bun run local config:clear`                               | Activa `APP_DEBUG=true` en `.env.local` y limpia caché de configuración.                                            |
| `debug:off`             | `powershell ... ./scripts/ps/debug-off.ps1 && bun run local config:clear`                              | Desactiva `APP_DEBUG=false` en `.env.local` y limpia caché de configuración.                                        |
| `ziggy`                 | `bun run local ziggy:generate ../frontend/src/ziggy.js`                                                | Genera rutas de Laravel para el frontend con Ziggy. (Planeado reemplazar por Wayfinder en el futuro.)               |
| `make-nwidart-module`   | `bun run local module:make`                                                                            | Crea un módulo usando Nwidart/Modules.                                                                              |
| `make-custom-module:be` | `bun run local make:project-module`                                                                    | Crea un módulo backend personalizado (comando artisan propio del proyecto).                                         |

## Frontend (Local)

| Script     | Comando                        | Descripción                                |
| ---------- | ------------------------------ | ------------------------------------------ |
| `i:fe`     | `cd frontend && bun i`         | Instala dependencias del frontend con Bun. |
| `dev:fe`   | `cd frontend && bun dev`       | Inicia Vite en modo desarrollo.            |
| `build:fe` | `cd frontend && bun run build` | Construye el frontend para producción.     |

## Docker

| Script             | Comando                                                                                                                 | Descripción                                                                             |
| ------------------ | ----------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------- |
| `dk`               | `docker-compose --env-file .env.docker`                                                                                 | Alias base para docker-compose usando variables de `.env.docker`.                       |
| `dk:start`         | `concurrently ... "bun run dk:up:foreground" "bun run dev:fe:docker"`                                                   | Levanta contenedores en primer plano y arranca el cliente de Vite usando `.env.docker`. |
| `dev:fe:docker`    | `dotenv -e .env.docker -- bun --cwd ./frontend dev`                                                                     | Inicia Vite en el host con configuración de entorno Docker (URLs correctas).            |
| `dk:up:foreground` | `bun run dk up --remove-orphans`                                                                                        | Levanta contenedores en primer plano mostrando logs.                                    |
| `dk:up`            | `bun run dk up -d`                                                                                                      | Levanta contenedores en segundo plano (detached).                                       |
| `dk:build`         | `bun run dk build`                                                                                                      | Construye imágenes.                                                                     |
| `dk:down`          | `bun run dk down`                                                                                                       | Detiene y elimina contenedores, redes y volúmenes según compose.                        |
| `dk:logs`          | `bun run dk logs -f`                                                                                                    | Sigue logs de los contenedores.                                                         |
| `dk:artisan`       | `bun run dk exec backend php artisan`                                                                                   | Ejecuta `php artisan` dentro del contenedor `backend`.                                  |
| `dk:sh`            | `bun run dk exec backend sh`                                                                                            | Entra a una shell del contenedor `backend`.                                             |
| `dk:seed:users`    | `bun run dk run --rm -v ./.env.users:/var/www/backend/.env.users backend php artisan db:seed --class=SystemUsersSeeder` | Siembra usuarios cargando variables desde `.env.users`.                                 |

## PostgreSQL (Local)

| Script                  | Comando                                                                                                | Descripción                                                                                 |
| ----------------------- | ------------------------------------------------------------------------------------------------------ | ------------------------------------------------------------------------------------------- |
| `pg`                    | `dotenv -e .env.pg.local -- php backend/artisan`                                                       | Alias de artisan cargando `.env.pg.local` para usar PostgreSQL local.                       |
| `pg:dev`                | `concurrently ... "bun run pg:dev:be" "bun run pg:queue:listen" "bun run dev:fe"`                      | Flujo dev con backend sobre Postgres, colas y frontend en paralelo.                         |
| `pg:artisan`            | `bun run pg`                                                                                           | Alias directo del prefijo `pg` para ejecutar comandos artisan.                              |
| `pg:dev:be`             | `bun run pg:ziggy && bun run pg serve --host=localhost --port=8080`                                    | Inicia el servidor backend con entorno Postgres. Ejecuta Ziggy antes (si está configurado). |
| `pg:queue:listen`       | `bun run pg queue:listen --queue=default ...`                                                          | Inicia el listener de colas apuntando al entorno Postgres local.                            |
| `pg:migrate`            | `bun run pg migrate`                                                                                   | Ejecuta migraciones usando `.env.pg.local`.                                                 |
| `pg:migrate:fresh`      | `bun run pg migrate:fresh`                                                                             | Reconstruye esquema desde cero en Postgres.                                                 |
| `pg:migrate:fresh:seed` | `bun run pg migrate:fresh --seed --force --no-interaction`                                             | Reconstruye y siembra datos en Postgres.                                                    |
| `pg:seed`               | `bun run pg db:seed`                                                                                   | Siembra datos de ejemplo en Postgres.                                                       |
| `pg:logs:tail`          | `bun run pg pail --ansi`                                                                               | Tail con Pail para el entorno Postgres (no compatible con Windows por `pcntl`).             |
| `pg:logs:tail:file`     | `powershell -NoProfile -Command Get-Content -Path backend\\storage\\logs\\laravel.log -Wait -Tail 200` | Alternativa Windows para seguir el log en Postgres.                                         |
| `pg:ziggy`              | `bun run pg ziggy:generate ../frontend/src/ziggy.js`                                                   | Genera rutas para el frontend usando Ziggy con entorno Postgres.                            |
| `pg:tinker`             | `bun run pg tinker`                                                                                    | Abre Tinker con entorno Postgres cargado.                                                   |

## Uso rápido

- Desarrollo común: `bun run dev` | `bun dev`
- Backend solo: `bun run dev:be`
- Frontend solo: `bun run dev:fe`
- Colas: `bun run queue:listen`
- Migraciones: `bun run migrate` / `bun run migrate:fresh` / `bun run migrate:fresh:seed`
- Verificar entorno: `bun run env:check`
- Logs en Windows: `bun run logs:tail:file`
- Flujo con Postgres: `bun run pg:dev`

## Notas de compatibilidad

- En Windows, `logs:tail` y `pg:logs:tail` requieren la extensión `pcntl` (no disponible); usa `logs:tail:file` y `pg:logs:tail:file`.

## Backend (Composer)

Comandos definidos en `backend/composer.json`. Se ejecutan con Composer dentro del directorio `backend`.

| Script             | Cómo ejecutarlo                 | Comando interno                                                                       | Descripción                                                                                |
| ------------------ | ------------------------------- | ------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------ |
| `dev`              | `composer run dev`              | `concurrently "php artisan serve" "php artisan queue:listen --tries=1" "bun run dev"` | Arranca servidor Laravel, listener de colas y Vite (frontend) en paralelo desde `backend`. |
| `pint`             | `composer run pint`             | `vendor/bin/pint`                                                                     | Formatea código PHP según reglas de Pint.                                                  |
| `pint:test`        | `composer run pint:test`        | `vendor/bin/pint --test`                                                              | Verifica formateo PHP sin escribir cambios.                                                |
| `package-discover` | `composer run package-discover` | `@php artisan package:discover --ansi`                                                | Fuerza la detección de paquetes de Laravel.                                                |

Notas:

- Los hooks `post-autoload-dump` y `post-root-package-install` se ejecutan automáticamente por Composer; no es necesario invocarlos manualmente.
- Para usar estos scripts, navega a `backend`: `cd backend` y luego ejecuta `composer run <script>`.

## Frontend ( `frontend/package.json`)

Comandos definidos en `frontend/package.json`. Se ejecutan con Bun dentro del directorio `frontend`.

| Script         | Cómo ejecutarlo        | Comando              | Descripción                                         |
| -------------- | ---------------------- | -------------------- | --------------------------------------------------- |
| `dev`          | `bun run dev`          | `vite`               | Inicia el servidor de desarrollo de Vite.           |
| `build`        | `bun run build`        | `vite build`         | Construye el frontend para producción.              |
| `preview`      | `bun run preview`      | `vite preview`       | Sirve el build para revisión local.                 |
| `format:check` | `bun run format:check` | `prettier --check .` | Verifica el formateo sin escribir cambios.          |
| `format:write` | `bun run format:write` | `prettier --write .` | Aplica formateo con Prettier.                       |
| `lint`         | `bun run lint`         | `eslint .`           | Ejecuta ESLint sobre el proyecto.                   |
| `lint:fix`     | `bun run lint:fix`     | `eslint . --fix`     | Intenta corregir problemas de lint automáticamente. |
| `types`        | `bun run types`        | `tsc --noEmit`       | Chequea tipos TypeScript sin emitir archivos.       |
