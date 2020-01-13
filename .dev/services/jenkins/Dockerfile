# https://hub.docker.com/_/nginx/
FROM registry.wklive.net:5000/digi/jenkins-php-dropcat-node:php72
COPY plugins.txt /plugins.txt
RUN cat /plugins.txt | /usr/local/bin/install-plugins.sh
#USER root
#RUN mkdir /root/.ssh
#COPY ../../keys/id_rsa /root/.ssh/id_rsa
