#!/bin/sh
sudo service php7.0-fpm restart
sudo service mysql restart
sudo service redis-server restart
sudo service mongodb restart
sudo service elasticsearch restart
cd /home/camfee/app/elasticsearch-head
grunt server &
php /home/camfee/www/www.bare.com/index.php Queue/Index/index UpdateCount &
php /home/camfee/www/www.bare.com/index.php Queue/Index/index SearchBook &
