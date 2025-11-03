#!/bin/bash
# Script de inicialización para optimizar PostgreSQL

set -e

# Este script se ejecuta automáticamente durante la inicialización de PostgreSQL

# Ejecutar como usuario postgres
psql -v ON_ERROR_STOP=1 --username "$POSTGRES_USER" --dbname "$POSTGRES_DB" <<-EOSQL
    -- Configurar parámetros de seguridad adicionales
    ALTER SYSTEM SET log_connections = 'on';
    ALTER SYSTEM SET log_disconnections = 'on';
    ALTER SYSTEM SET log_hostname = 'off';
    
    -- Configurar parámetros de rendimiento
    ALTER SYSTEM SET max_connections = '100';
    ALTER SYSTEM SET idle_in_transaction_session_timeout = '60000';
    ALTER SYSTEM SET statement_timeout = '60000';
    
    -- Aplicar cambios
    SELECT pg_reload_conf();
    
    -- Mostrar mensaje de confirmación
    \echo 'Configuración de PostgreSQL optimizada correctamente';
EOSQL