# https://hub.docker.com/_/nginx/
FROM php:7.3-apache
ARG sshpass=root
RUN apt-get -y update && \
    apt-get -y install \
    openssh-server \
    supervisor \
    vim

#Set up SSH access
RUN mkdir /var/run/sshd
#RUN sed -i.bak s/PermitRootLogin\ prohibit-password/PermitRootLogin\ yes/g  /etc/ssh/sshd_config
RUN echo "root:$sshpass" | chpasswd

COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY sshd_config /etc/ssh/sshd_config
EXPOSE 22 80

CMD ["/usr/bin/supervisord"]
