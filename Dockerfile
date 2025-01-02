# Usa una imagen base de PHP 8.1 con soporte para extensiones necesarias
FROM php:8.1-fpm

# Instala las dependencias necesarias
RUN apt-get update && apt-get install -y \
    unixodbc \
    unixodbc-dev \
    libgssapi-krb5-2 \
    libpq-dev \
    gnupg2 \
    curl \
    && curl https://packages.microsoft.com/keys/microsoft.asc | apt-key add - \
    && curl https://packages.microsoft.com/config/debian/9/prod.list > /etc/apt/sources.list.d/mssql-release.list \
    && apt-get update \
    && ACCEPT_EULA=Y apt-get install -y msodbcsql17 \
    && pecl install sqlsrv pdo_sqlsrv \
    && docker-php-ext-enable sqlsrv pdo_sqlsrv

RUN apt-get update \
    && apt-get install -y nginx \
    supervisor

RUN rm /etc/nginx/sites-available/default

COPY default /etc/nginx/sites-available/default

# Instala Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Establece el directorio de trabajo
WORKDIR /var/www

# Copia los archivos del proyecto
COPY . .

# Instala las dependencias de PHP
RUN composer install --no-dev --optimize-autoloader

# Copia el archivo de configuración de supervisord
COPY ./supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Expone el puerto 9000 para PHP-FPM
EXPOSE 9000

# Configura el punto de entrada para supervisord
CMD ["supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
