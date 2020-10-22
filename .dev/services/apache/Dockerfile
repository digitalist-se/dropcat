# https://hub.docker.com/_/php
FROM php:7.3-apache
ARG sshpass=root
RUN apt-get -y update && \
    apt-get -y install \
    openssh-server \
    supervisor \
    vim \
    wget \
    git \
    zip \
    unzip \
    libpng-dev \
    rsync \
    mariadb-client

RUN pecl install xdebug && \
    echo "xdebug.remote_enable=1\n" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
         "xdebug.coverage_enable=1\n" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
         "xdebug.idekey=\"PHPSTORM\"\n" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
         "xdebug.remote_port=9001\n" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
    docker-php-ext-install gd pdo_mysql mysqli opcache && \
    docker-php-ext-enable xdebug

# Use the default production configuration
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
RUN sed -i s/128M/512M/ "$PHP_INI_DIR/php.ini"

RUN sed -i s+DocumentRoot\ /var/www/html+DocumentRoot\ /var/www/html/test_latest_stage/web+ /etc/apache2/sites-enabled/000-default.conf

#Set up SSH access
RUN mkdir /var/run/sshd
#RUN sed -i.bak s/PermitRootLogin\ prohibit-password/PermitRootLogin\ yes/g  /etc/ssh/sshd_config
RUN echo "root:$sshpass" | chpasswd

COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY sshd_config /etc/ssh/sshd_config
RUN mkdir /root/.ssh
COPY authorized_keys /root/.ssh/authorized_keys

COPY docker_entrypoint.sh /usr/bin/

# Install drush launcher.
RUN wget -O drush.phar https://github.com/drush-ops/drush-launcher/releases/download/0.6.0/drush.phar && \
    chmod +x drush.phar && \
    chown www-data:www-data drush.phar && \
    mv drush.phar /usr/local/bin/drush

EXPOSE 22 80

ENTRYPOINT ["/usr/bin/docker_entrypoint.sh"]
CMD ["/usr/bin/supervisord"]
