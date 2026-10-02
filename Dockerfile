FROM php:8.2-apache

# Desactivar los MPM que sobran y dejar solo prefork
RUN a2dismod mpm_event mpm_worker || true \
 && a2enmod mpm_prefork

# Extensiones para MySQL (PDO)
RUN docker-php-ext-install pdo pdo_mysql

COPY . /var/www/html/

# Railway asigna el puerto por la variable PORT
RUN sed -i 's/Listen 80/Listen ${PORT}/' /etc/apache2/ports.conf \
 && sed -i 's/:80/:${PORT}/' /etc/apache2/sites-available/000-default.conf

EXPOSE 8080