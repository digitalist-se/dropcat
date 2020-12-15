# https://hub.docker.com/_/nginx/
FROM registry.wklive.net:5000/dg/digi-jenkins:3.0.11
#COPY plugins.txt /plugins.txt
#RUN cat /plugins.txt | /usr/local/bin/install-plugins.sh
#USER root
#RUN mkdir /root/.ssh
#COPY ../../keys/id_rsa /root/.ssh/id_rsa

USER root
#RUN apt-get -y update && \
#    apt-get -y install \
#    rsync

# Update drush
#RUN composer global require drush/drush:^10

# Dropcat backup folder needs to be writable.
RUN mkdir -p /backup && chown jenkins:jenkins /backup
# Install drush launcher.
#RUN wget -O drush.phar https://github.com/drush-ops/drush-launcher/releases/download/0.6.0/drush.phar && \
#    chmod +x drush.phar && \
#    chown jenkins:jenkins drush.phar && \
#    mv drush.phar /usr/local/bin/drush

# Necessary for npm/yarn commands to work
#RUN echo -e \
#    'export NVM_DIR="$HOME/.nvm"\n\
#    [ -s "$NVM_DIR/nvm.sh" ] && \. "$NVM_DIR/nvm.sh" # This loads nvm\n'\
#    >> /var/jenkins_home/.bashrc && \
#    chown jenkins:jenkins /var/jenkins_home/.bashrc
USER jenkins
