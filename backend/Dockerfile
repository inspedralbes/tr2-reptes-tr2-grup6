FROM php:8.2-apache

# Instal·lar extensions necessàries (PDO MySQL, Zip, etc)
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-install pdo pdo_mysql zip

# Habilitar mod_rewrite d'Apache
RUN a2enmod rewrite

# Copiar codi font
COPY . /var/www/html/

# Permisos per a la carpeta uploads i cache
RUN chown -R www-data:www-data /var/www/html/public/uploads \
    && chmod -R 775 /var/www/html/public/uploads

# Exposar port 80
EXPOSE 80
