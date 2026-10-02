FROM php:8.3-apache

# Extensiones PHP necesarias para MySQL
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Rewrite
RUN a2enmod rewrite

# Asegurar que SOLO mpm_prefork esté habilitado
RUN a2dismod mpm_event mpm_worker mpm_threadpool mpm_prefork || true \
    && a2enmod mpm_prefork

# Copiar aplicación
COPY . /var/www/html/

# Permisos
RUN chown -R www-data:www-data /var/www/html

# Apache
RUN sed -i 's/^Listen 80$/Listen 8080/' /etc/apache2/ports.conf \
    && sed -i 's/<VirtualHost \*:80>/<VirtualHost *:8080>/' /etc/apache2/sites-available/000-default.conf

EXPOSE 8080

CMD ["apache2-foreground"]