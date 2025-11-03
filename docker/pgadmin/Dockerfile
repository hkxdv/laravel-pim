# ====================================================================
# Dockerfile para pgAdmin (entorno de desarrollo)
# ====================================================================
# - Basado en la imagen oficial dpage/pgadmin4
# - Instala utilidades mínimas (curl, tzdata) para healthchecks y debug
# - Mantiene la configuración en docker-compose para flexibilidad
# ====================================================================

FROM dpage/pgadmin4:latest

# Instalamos utilidades básicas de forma compatible con Alpine/Debian
USER root
RUN set -e; \
    if command -v apk >/dev/null 2>&1; then \
      apk add --no-cache curl tzdata; \
    else \
      apt-get update && apt-get install -y --no-install-recommends curl tzdata && \
      rm -rf /var/lib/apt/lists/*; \
    fi

# Volvemos al usuario por defecto de la imagen
USER pgadmin

# Puerto HTTP por defecto de pgAdmin
EXPOSE 80