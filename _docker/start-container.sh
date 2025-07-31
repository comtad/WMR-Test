#!/bin/sh

php-fpm -D

/usr/bin/supervisord -n -c /etc/supervisor/supervisord.conf

tail -f /dev/null
