cd `dirname $0`
cd ../../

# create database
php index.php Tool/Sql/createdb

# book
php index.php Tool/Sql/book
php index.php Tool/Sql/column
php index.php Tool/Sql/content

# 用户
php index.php Tool/Sql/passport
php index.php Tool/Sql/account

# 共用
php index.php Tool/Sql/favorite
php index.php Tool/Sql/comment
php index.php Tool/Sql/tag

# app
php index.php Tool/Sql/application
php index.php Tool/Sql/mobile

# 后台
php index.php Tool/Sql/admin

# 采集
php index.php Tool/Sql/collect
php index.php Tool/Sql/picture