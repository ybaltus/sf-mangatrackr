FROM php:8.2-apache

RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Install php extensions
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

RUN chmod +x /usr/local/bin/install-php-extensions && \
    install-php-extensions pdo pdo_mysql opcache intl zip calendar dom mbstring gd

# Install Composer
RUN curl -sSk https://getcomposer.org/installer | php -- --disable-tls && \
    mv composer.phar /usr/local/bin/composer

# Install Nodejs
RUN apt-get update && \
    apt install -y nodejs npm

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