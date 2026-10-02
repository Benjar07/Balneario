FROM php:8.3-apache

# PHP / MySQL
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Rewrite
RUN a2enmod rewrite

# Limpiar TODOS los MPM
RUN a2dismod mpm_event mpm_worker mpm_prefork mpm_threadpool mpm_itk || true

# Habilitar solamente prefork
RUN a2enmod mpm_prefork

# Comprobar configuración de MPM durante el BUILD
RUN echo "===== MPM ACTUALES =====" \
    && apache2ctl -M 2>&1 | grep mpm \
    && echo "========================"

# Copiar proyecto
COPY . /var/www/html/

RUN chown -R www-data:www-data /var/www/html

# Puerto Railway
RUN sed -i 's/Listen 80/Listen 8080/' /etc/apache2/ports.conf \
    && sed -i 's/<VirtualHost \*:80>/<VirtualHost *:8080>/' /etc/apache2/sites-available/000-default.conf

EXPOSE 8080

CMD ["apache2-foreground"]