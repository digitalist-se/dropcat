# Dev setup

For testing dropcat locally, we have setup jenkins, a web instance and a
mysql instance. To start them, allow mounts, `docker login` to our
registry and then run:

```bash
docker-compose up [-d]
```
⚠️ _docker-compose on mac has a bug prior to version 1.25, which
prevents from logging in with basic auth. You won't be able to download
our php image even though you have logged in. Upgrade to at least 1.25
to fix._

For now, we are testing committed and pushed code to dropcat, so before
testing a change, you need to push the code, this will be changed so you
can test un-pushed code.

The same setup we have for testing locally will be set in our CI
environment, but we will use drone instead of Jenkins.

## Jenkins

This is a customised built, the Dockerfile is in .dev/services/jenkins

First time you start Jenkins, a password is printed out in the docker
logs, use that for setting up jenkins.

Jenkins is reachable on URL: http://0.0.0.0:8080/

### Setup job

#### Git project

Use the dropcat project: `https://github.com/digitalist-se/dropcat.git`
(for now use branch drush-10)

All needed is in the .dev/example folder. It will use the dropcat file
in `.dev/example/.dropcat`

#### Add shell

```bash
# Use local dropcat, mounted on /opt/dropcat
DROPCAT=/opt/dropcat/app/dropcat
DROPCAT_ENV=dev

install_drupal() {
  composer create-project --ignore-platform-reqs drupal/recommended-project .
  # Workaround for drupal 8.8.0
  chmod -R +w web/sites/default
  composer require drush/drush --dev --ignore-platform-reqs
  drush site:install demo_umami --account-pass=admin --db-url=mysql://root:root@db:3306/dropcat -y
  mkdir -p ${WORKSPACE}/.dropcat
  cp /opt/dropcat/.dev/example/.dropcat/dropcat.dev.yml ${WORKSPACE}/.dropcat
}

if [ ! -f composer.json ]; then
  install_drupal
fi

### Dropcat Commands
${DROPCAT} debug:check-connection
${DROPCAT} about
```

## Web

This is a customised build, the Dockerfile is in .dev/services/apache

This is an apache instance with php 7:2, this is used to deploy the code to.

## DB

This is a MariaDB instance, for testing.

