FROM php:8.3-apache

RUN docker-php-ext-install mysqli pdo pdo_mysql

# Dejar un único MPM (prefork) ya desde el build
RUN rm -f /etc/apache2/mods-enabled/mpm_*.load /etc/apache2/mods-enabled/mpm_*.conf \
 && a2enmod mpm_prefork \
 && apache2ctl -t -D DUMP_MODULES | grep -i mpm

COPY . /var/www/html/

RUN sed -i 's/Listen 80/Listen ${PORT}/' /etc/apache2/ports.conf \
 && sed -i 's/:80/:${PORT}/' /etc/apache2/sites-available/000-default.conf