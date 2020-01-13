# Dev setup

For testing dropcat localy, we have setup jenkins, an web instance and an mysql instance. To start them, run:

```bash
docker-compose up
```

For now, we are tesing committed and pushed code to dropcat, so before tesing a change, you need to push the code, this will be changed so you can test un-pushed code.

The same setup we have for testing localy will be set in our CI environment, but we will use drone instead of Jenkins.

## Jenkins

This is customed built, the Dockerfile is in .dev/services/jenkins

First time you start Jenkins, a password is printed out in the docker logs, use that for setup jenkins.

Jenkins is reachable on URL: http://0.0.0.0:8080/

### Setup job

#### Git project

Use the dropcat project: `https://github.com/digitalist-se/dropcat.git` (for now use branch drush-10)

All needed is in the .dev/example folder. It will use the dropcat file in `.dev/example/.dropcat`

#### Add shell

```bash
DROPCAT=../../app/dropcat
DROPCAT_ENV=dev
composer install
cd .dev/example
${DROPCAT} debug:check-connection
${DROPCAT} help generate:drush-alias
```

## Web

This is customed built, the Dockerfile is in .dev/services/apache

This is a apache instance with php 7:2, this is used to deploy the code to.

## DB

This is a MariaDB instance, for testing.

