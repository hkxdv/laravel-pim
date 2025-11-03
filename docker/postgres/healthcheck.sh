#!/bin/bash
# Script de healthcheck para PostgreSQL

# Verificar si PostgreSQL está respondiendo
pg_isready -U "$POSTGRES_USER" -d "$POSTGRES_DB" -h localhost -p 5432

# Salir con el código de estado de pg_isready
exit $?