FROM php:8.2-apache

RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Install dependencies
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
    locales apt-utils git libicu-dev g++ libpng-dev libxml2-dev libzip-dev libonig-dev libxslt-dev unzip

RUN echo "en_US.UTF-8 UTF-8" > /etc/locale.gen && \
    echo "fr_FR.UTF-8 UTF-8" >> /etc/locale.gen && \
    locale-gen

# Install php extensions
RUN docker-php-ext-configure intl
RUN docker-php-ext-install pdo pdo_mysql opcache intl zip calendar dom mbstring gd xsl
RUN pecl install apcu && docker-php-ext-enable apcu

# Install Composer
RUN curl -sSk https://getcomposer.org/installer | php -- --disable-tls && \
    mv composer.phar /usr/local/bin/composer

# Install Nodejs
RUN apt install -y nodejs npm

# Install Symfony CLI
RUN curl -1sLf 'https://dl.cloudsmith.io/public/symfony/stable/setup.deb.sh' | bash && \
     apt install symfony-cli

# Copy the conf for Apache
COPY ./docker/vhosts/mangatracker.conf /etc/apache2/sites-available/000-default.conf

# Copie the project files
COPY . /var/www/

# Change the workdir on the container
WORKDIR /var/www/

ENTRYPOINT ["bash", "./docker/docker.sh"]