#!/bin/sh
#sudo service php7.0-fpm restart
sudo /opt/app/php/7.1/sbin/php-fpm
sudo service mysql restart
#sudo service redis-server restart
sudo redis-server /etc/redis/redis.conf
sudo redis-server /etc/redis/6380.conf
sudo redis-server /etc/redis/6381.conf
sudo redis-server /etc/redis/6382.conf
sudo redis-server /etc/redis/6383.conf
sudo redis-server /etc/redis/6384.conf
sudo redis-server /etc/redis/6385.conf
sudo redis-server /etc/redis/6386.conf
#sudo service mongodb restart
sudo mongod --config /etc/mongodb.conf &
sudo service elasticsearch restart
#sudo teamviewer --daemon start
cd /home/camfee/app/elasticsearch-head
grunt server &
php /home/camfee/www/www.bare.com/index.php Queue/Index/index UpdateCount &
php /home/camfee/www/www.bare.com/index.php Queue/Index/index SearchBook &
