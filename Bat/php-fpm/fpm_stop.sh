#!/bin/sh
# php-fpm stop
pid=$(ps -aux|grep 'php-fpm: master' |awk '{print $2}' |sed -n '1p')
sudo kill -INT $pid
echo "php-fpm stop ..."
