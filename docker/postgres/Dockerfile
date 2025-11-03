# ====================================================================
# Dockerfile para PostgreSQL con configuración segura
# ====================================================================
# - Se basa en la imagen oficial PostgreSQL 17-alpine pero con actualizaciones de seguridad
# - Aplica actualizaciones de seguridad para mitigar vulnerabilidades
# - Configura permisos y opciones de seguridad recomendadas
# ====================================================================

# Usamos la imagen base oficial de PostgreSQL 17-alpine
FROM postgres:17-alpine

# Actualizamos todos los paquetes para aplicar parches de seguridad
RUN apk update && \
    apk upgrade --no-cache && \
    # Instalamos herramientas de seguridad básicas
    apk add --no-cache \
        tzdata \
        ca-certificates && \
    # Limpiamos la caché de APK para reducir el tamaño de la imagen
    rm -rf /var/cache/apk/*

# Configuramos opciones de seguridad para PostgreSQL
RUN echo "host all all 0.0.0.0/0 scram-sha-256" >> /usr/local/share/postgresql/pg_hba.conf.sample && \
    echo "password_encryption = scram-sha-256" >> /usr/local/share/postgresql/postgresql.conf.sample

# Copiamos scripts de inicialización (se ejecutan sólo en la primera creación del cluster)
COPY docker/postgres/init-scripts /docker-entrypoint-initdb.d

# Configuramos permisos restrictivos para los directorios de datos
RUN mkdir -p /var/lib/postgresql/data && \
    chmod 700 /var/lib/postgresql/data

# Exponemos el puerto estándar de PostgreSQL
EXPOSE 5432

# El comando CMD se hereda de la imagen base postgres:17-alpine