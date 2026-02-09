FROM php:8.2-apache

# 1. Instalar dependencias y extensiones para MySQL (TiDB)
# Cambiamos libpq-dev (Postgres) por extensiones zip y mysql
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    && docker-php-ext-install pdo pdo_mysql zip

# 2. Habilitar mod_rewrite para que funcionen las rutas de Laravel
RUN a2enmod rewrite

# 3. Configurar Apache para que apunte a la carpeta /public
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/000-default.conf
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf

# 4. Copiar todo el código al contenedor
WORKDIR /var/www/html
COPY . /var/www/html

# 5. Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 6. Instalar dependencias de Laravel
RUN composer install --no-dev --optimize-autoloader

# 7. Dar permisos a las carpetas de escritura
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 80
