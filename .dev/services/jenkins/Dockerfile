# https://hub.docker.com/_/nginx/
FROM registry.wklive.net:5000/digi/jenkins-php-dropcat-node:php72
COPY plugins.txt /plugins.txt
RUN cat /plugins.txt | /usr/local/bin/install-plugins.sh
#USER root
#RUN mkdir /root/.ssh
#COPY ../../keys/id_rsa /root/.ssh/id_rsa

USER root
# Dropcat backup folder needs to be writable.
RUN chown jenkins:jenkins /backup
# Install drush launcher.
RUN wget -O drush.phar https://github.com/drush-ops/drush-launcher/releases/download/0.6.0/drush.phar && \
    chmod +x drush.phar && \
    chown jenkins:jenkins drush.phar && \
    mv drush.phar /usr/local/bin/drush
USER jenkins