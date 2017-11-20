#!/bin/sh
/usr/bin/php7.0 /home/camfee/app/phpdoc/bin/phpdoc -d /home/camfee/www/www.bare.com/Controller/Api -t /home/camfee/www-doc/www.bare.com/ --title "Bare API" --force --template=responsive-twig  --visibility="public"
