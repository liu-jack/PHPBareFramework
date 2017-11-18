#!/bin/bash
#path=$(cd `dirname $0`; pwd)
cd `dirname $0`
cd ../../

git pull origin master

/bin/rsync -av --exclude ".git/" /home/www/www.bare.com 127.0.0.1::www_bare_com/  &>>/tmp/rsync-bare.log
