FROM php:8.1-apache

RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    libonig-dev \
    libxml2-dev \
    libssl-dev \
    && docker-php-ext-install pdo_mysql mysqli mbstring zip intl

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /var/www/html

# Habilita mod_rewrite do Apache
RUN a2enmod rewrite

# Copia apenas os arquivos necessários primeiro
COPY composer.json composer.lock ./

# Instala as dependências
RUN composer install --no-dev --ignore-platform-reqs --no-scripts

# Copia o resto dos arquivos
COPY . .

# Ajusta permissões
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 755 /var/www/html/writable

EXPOSE 80