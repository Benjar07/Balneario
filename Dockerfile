```dockerfile
FROM php:8.3-apache

# Extensiones PHP necesarias
RUN docker-php-ext-install mysqli

# Apache
RUN a2enmod rewrite

# Copiar aplicación
COPY . /var/www/html/

# Permisos
RUN chown -R www-data:www-data /var/www/html

# Script de inicio
COPY start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

CMD ["/usr/local/bin/start.sh"]
```
