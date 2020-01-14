# https://hub.docker.com/_/php
FROM php:7.2-apache
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
    libpng-dev

RUN docker-php-ext-install gd pdo_mysql

#Set up SSH access
RUN mkdir /var/run/sshd
#RUN sed -i.bak s/PermitRootLogin\ prohibit-password/PermitRootLogin\ yes/g  /etc/ssh/sshd_config
RUN echo "root:$sshpass" | chpasswd

COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY sshd_config /etc/ssh/sshd_config
RUN mkdir /root/.ssh
COPY authorized_keys /root/.ssh/authorized_keys

COPY docker_entrypoint.sh /usr/bin/

EXPOSE 22 80

ENTRYPOINT ["/usr/bin/docker_entrypoint.sh"]
CMD ["/usr/bin/supervisord"]
