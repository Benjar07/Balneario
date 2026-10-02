FROM php:8.3-apache

RUN docker-php-ext-install mysqli pdo pdo_mysql

COPY . /var/www/html/

RUN sed -i 's/Listen 80/Listen ${PORT}/' /etc/apache2/ports.conf \
 && sed -i 's/:80/:${PORT}/' /etc/apache2/sites-available/000-default.conf

CMD ["bash", "-c", "echo '--- mods-enabled:'; ls -l /etc/apache2/mods-enabled | grep -i mpm; echo '--- LoadModule mpm:'; grep -Rn 'LoadModule .*mpm' /etc/apache2/mods-enabled /etc/apache2/conf-enabled /etc/apache2/sites-enabled /etc/apache2/apache2.conf; rm -f /etc/apache2/mods-enabled/mpm_event.* /etc/apache2/mods-enabled/mpm_worker.*; a2enmod mpm_prefork; echo '--- después del fix:'; ls -l /etc/apache2/mods-enabled | grep -i mpm; exec apache2-foreground"]