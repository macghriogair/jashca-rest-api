#!/usr/bin/env bash

# enable login as www-data and set home to /var/www/app
chsh -s /bin/bash www-data && usermod -d /var/www/app www-data

su www-data -c "/var/www/app/bin/console cache:clear --no-warmup \
  && /code/bin/console cache:warm"

## Start supervisor supervising the workers (non-daemonized)
/usr/bin/supervisord -n -c /etc/supervisor/supervisord.conf
