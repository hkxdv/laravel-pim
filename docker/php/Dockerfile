# ====================================================================
# Dockerfile para PHP-FPM con soporte para PostgreSQL
# ====================================================================
# - Se basa en la imagen oficial PHP 8.4 FPM para máxima compatibilidad
# - Instala extensiones requeridas para Laravel, PostgreSQL y desarrollo
# - Configura permisos para usuario no-root (seguridad)
# - Optimiza para desempeño en producción
# ====================================================================

# Usamos una imagen base con versión específica para garantizar compilaciones reproducibles
FROM php:8.4-fpm

# Instalamos dependencias del sistema, incluyendo las necesarias para health-check, 
# y limpiamos todo en una sola capa para reducir el tamaño de la imagen
RUN apt-get update && apt-get install -y \
    ca-certificates \
    curl \
    git \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libpng-dev \
    libzip-dev \
    libfcgi-bin \
    libpq-dev \
    --no-install-recommends && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/*

# Instalamos extensiones de PHP
# - pdo_pgsql: Soporte para PostgreSQL (principal motor de BD)
# - mbstring: Soporte para caracteres multibyte (UTF-8)
# - exif: Procesamiento de metadatos de imágenes
# - pcntl: Control de procesos (usado por queue workers)
# - bcmath: Matemáticas de precisión arbitraria
# - gd: Manipulación de imágenes
# - zip: Manejo de archivos comprimidos
RUN docker-php-ext-install mbstring exif pcntl bcmath gd zip pdo_pgsql

# Obtenemos la última versión de Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Establecemos el directorio de trabajo
WORKDIR /var/www

# Creamos la estructura de directorios para la aplicación y configuramos permisos iniciales
# Esto asegura que el usuario no-root pueda escribir en el directorio desde el inicio
RUN mkdir -p backend/storage/framework/{sessions,views,cache} \
             backend/bootstrap/cache \
             database && \
    chown -R www-data:www-data /var/www

# Cambiamos al usuario no-root para todas las operaciones subsecuentes
USER www-data

# Copiamos los archivos de composer e instalamos dependencias aprovechando la caché de Docker
# Ejecutar como usuario no-root mejora la seguridad durante el proceso de construcción
COPY --chown=www-data:www-data backend/composer.json backend/composer.lock* ./backend/
RUN composer install --working-dir=./backend --optimize-autoloader --no-dev --no-interaction --prefer-dist --no-scripts

# Copiamos el resto de los archivos de la aplicación
COPY --chown=www-data:www-data backend/ ./backend/
COPY --chown=www-data:www-data database/ ./database/

# Ejecutamos los scripts de composer ahora que el código de la aplicación está presente
RUN composer run-script --working-dir=./backend post-autoload-dump --no-dev --no-interaction

# Volvemos temporalmente a usuario root solo para configurar permisos finales
USER root

# Configuramos permisos correctos para almacenamiento y caché de Laravel
RUN chown -R www-data:www-data /var/www/backend/storage /var/www/backend/bootstrap/cache && \
    chmod -R 775 /var/www/backend/storage /var/www/backend/bootstrap/cache

# Añadimos healthcheck para asegurar que el proceso FPM responde
# Habilitamos endpoint de ping de php-fpm para el healthcheck
RUN sed -ri 's~^;?ping.path\s*=.*$~ping.path = /ping~' /usr/local/etc/php-fpm.d/www.conf

# Añadimos healthcheck para asegurar que el proceso FPM responde
HEALTHCHECK --interval=15s --timeout=5s --start-period=10s --retries=5 CMD ["sh","-c","cgi-fcgi -connect 127.0.0.1:9000 /ping || exit 1"]

# Volvemos al usuario no-root para ejecutar el proceso de la aplicación
USER www-data

# Exponemos puerto y ejecutamos php-fpm
EXPOSE 9000
CMD ["php-fpm"]
