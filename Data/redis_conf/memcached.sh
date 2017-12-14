#!/bin/sh
memcached -p 20001 -u memcache -m 1024 -c 2048 -A -l 127.0.0.1 &
memcached -p 20002 -u memcache -m 1024 -c 2048 -A -l 127.0.0.1 &
memcached -p 20003 -u memcache -m 1024 -c 2048 -A -l 127.0.0.1 &

