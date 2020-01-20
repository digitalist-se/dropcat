#!/bin/bash
install_composer() {
  EXPECTED_SIGNATURE="$(wget -q -O - https://composer.github.io/installer.sig)"
  php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
  ACTUAL_SIGNATURE="$(php -r "echo hash_file('sha384', 'composer-setup.php');")"

  if [ "$EXPECTED_SIGNATURE" != "$ACTUAL_SIGNATURE" ]
  then
      >&2 echo "ERROR: Invalid installer signature. Expected $EXPECTED_SIGNATURE, got $ACTUAL_SIGNATURE"
      rm composer-setup.php
      exit 1
  fi

  php composer-setup.php --install-dir=/usr/bin --filename=composer --version=1.9.1
  RESULT=$?
  rm composer-setup.php
  return $RESULT
}

install_drupal() {
  composer create-project drupal/recommended-project dropcat-test-project
  pushd dropcat-test-project || return 1
    # Workaround for drupal 8.8.0
    chmod -R +w web/sites/default
    composer require drush/drush --dev
    ./vendor/bin/drush site:install demo_umami --account-pass=admin --db-url=mysql://root:root@db:3306/dropcat -y
  popd || return 1
}

if [ ! -f .initialized ]; then
    echo "Initializing web container"
    # run initializing commands

    install_composer
    if [ -$? -ne 0 ]; then
      echo "Could not install composer. Return code: $?"
      exit 1
    fi

    pushd /var/www/html || exit 1
      install_drupal
      if [ -$? -ne 0 ]; then
        echo "Could not install drupal. Return code: $?"
        exit 1
      fi
    popd || exit 1
    touch .initialized
fi

exec "$@"