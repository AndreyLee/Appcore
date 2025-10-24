FROM php:7.4-apache

# Instalar cliente MySQL (para mysqldump) e extensões PHP
RUN apt-get update && \
    apt-get install -y default-mysql-client && \
    docker-php-ext-install pdo pdo_mysql mysqli && \
    docker-php-ext-enable pdo_mysql

# Habilitar mod_rewrite do Apache para URLs amigáveis (se necessário no futuro)
RUN a2enmod rewrite

# Copiar os arquivos da aplicação para o diretório web do Apache
COPY . /var/www/html/

# Definir permissões (opcional, mas pode ser necessário)
RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html

# Expor a porta 80 (já exposta pelo php:7.4-apache, mas bom para documentação)
EXPOSE 80
