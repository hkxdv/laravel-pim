# ====================================================================
# Dockerfile para Frontend con Bun (LEGACY)
# ====================================================================
# - Basado en la imagen oficial de Bun para mejor rendimiento
# - Configura entorno de desarrollo para React/TypeScript
# - Optimiza el manejo de dependencias con bun.lock
# - Expone el puerto de Vite (5173) para desarrollo
# - NOTA: Esta configuración se mantiene como referencia pero
#   actualmente no está en uso activo (véase docker-compose.override.yml)
# ====================================================================

# Utilizamos la imagen oficial de Bun con versión específica para consistencia
FROM oven/bun:1

# Establecemos el directorio de trabajo en la raíz de la aplicación
# para reflejar la estructura definida en docker-compose
WORKDIR /app

# Copiamos archivos de configuración de paquetes
# Estos son los únicos archivos necesarios para instalar dependencias
COPY frontend/package.json frontend/bun.lock* frontend/tsconfig.json ./frontend/

# Cambiamos al directorio frontend para instalar dependencias
WORKDIR /app/frontend

# Instalamos dependencias con lockfile para garantizar consistencia
# --frozen-lockfile evita actualizaciones no deseadas durante la instalación
RUN bun install --frozen-lockfile

# Copiamos el resto del código fuente de la aplicación frontend
# En desarrollo sería sobreescrito por el volumen montado
# pero es útil para construcciones de producción
COPY frontend/ .

# Exponemos el puerto que usa Vite para desarrollo
EXPOSE 5173

# Comando para iniciar el servidor de desarrollo
# El script "dev" en package.json debe incluir la bandera --host
# para permitir acceso desde fuera del contenedor
CMD ["bun", "run", "dev"]
