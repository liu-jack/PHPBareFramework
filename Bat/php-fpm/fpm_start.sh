#!/bin/sh
# php-fpm start
n=$(ps -aux|grep 'php-fpm: master' |wc -l)
if [ $n -gt 1 ]; then
	pid=$(ps -aux|grep 'php-fpm: master' |awk '{print $2}' |sed -n '1p')
	sudo kill -USR2 $pid
	echo "php-fpm restart ..."
else
	sudo /opt/app/php/7.2/sbin/php-fpm
	echo "php-fpm start ..."
fi
