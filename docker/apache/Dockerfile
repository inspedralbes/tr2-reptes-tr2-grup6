FROM php:8.2-apache

# Habilitar mòduls necessaris
RUN a2enmod rewrite headers

# Instal·lar extensió PDO MySQL
RUN docker-php-ext-install pdo_mysql

# Configuració d'Apache
COPY 000-default.conf /etc/apache2/sites-available/000-default.conf
COPY apache2.conf /etc/apache2/apache2.conf

WORKDIR /var/www/html

EXPOSE 80

CMD ["apache2-foreground"]
