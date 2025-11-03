# ====================================================================
# Dockerfile para Typesense (optimizado y consistente)
# ====================================================================
# - Basado en imagen oficial con versión fijada
# - Instala curl para healthchecks HTTP
# - Mantiene configuración en docker-compose para flexibilidad
# ====================================================================

FROM typesense/typesense:0.25.2

# Garantiza que tenemos shell POSIX
SHELL ["/bin/sh", "-c"]

# Instala curl de forma compatible con Alpine/Debian
RUN if command -v apk >/dev/null 2>&1; then \
      apk add --no-cache curl; \
    else \
      apt-get update && apt-get install -y --no-install-recommends curl && \
      rm -rf /var/lib/apt/lists/*; \
    fi

# Puerto del API
EXPOSE 8108