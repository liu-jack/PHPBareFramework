<?php

namespace Controller\Tool;

use  Bare\Controller;

/**
 * 数据库管理
 */
class Sql extends Controller
{
    /**
     * 书库表管理 php index.php Tool/Sql/createdb
     */
    public function createdb()
    {
        need_cli();
        $sql = config('sql/database')['createdb'];
        $dbconfig = config('db')['mysql']['create']['db'];

        $dsn = "mysql:host={$dbconfig['host']};port={$dbconfig['port']};";
        $pdo = new \PDO($dsn, $dbconfig['user'], $dbconfig['password']);

        $pdo->exec($sql);

        echo 'finished' . PHP_EOL;
    }

    /**
     * 书库表管理 php index.php Tool/Sql/book
     */
    public function book()
    {
        need_cli();
        $sqls = config('sql/book');
        $create_book_sql = $sqls['create_book'];
        $create_collect_sql = $sqls['create_book_collect'];

        $this->_m->run29shu($create_book_sql);
        $this->_m->run29shu($create_collect_sql);
        echo 'finished' . PHP_EOL;
    }

    /**
     * 书库章节表管理 php index.php Tool/Sql/column
     */
    public function column()
    {
        need_cli();
        $sqls = config('sql/book');
        $create_sql = $sqls['create_book_column'];

        for ($i = 0; $i < 256; $i++) {
            $suff = sprintf('%02x', $i);
            $sql = str_replace('BookColumn', 'BookColumn_' . $suff, $create_sql);
            $this->_m->run29shu($sql);
            echo $i . PHP_EOL;
        }
    }

    /**
     * 书库内容表管理 php index.php Tool/Sql/content
     */
    public function content()
    {
        need_cli();
        $sqls = config('sql/book');
        $create_sql = $sqls['create_book_content'];

        for ($i = 0; $i < 256; $i++) {
            $suff = sprintf('%02x', $i);
            $sql = str_replace('BookContent', 'BookContent_' . $suff, $create_sql);
            $this->_m->run29shuContent($sql);
            echo $i . PHP_EOL;
        }
    }

    /**
     * 通行证表管理 php index.php Tool/Sql/passport
     */
    public function passport()
    {
        need_cli();
        $sqls = config('sql/user');
        $create_sql_connect = $sqls['create_passport_connect'];
        $this->_m->runPassport($create_sql_connect);
        $create_sql = $sqls['create_passport'];
        $this->_m->runPassport($create_sql);
        for ($i = 0; $i < 256; $i++) {
            $suff = sprintf('%02x', $i);
            $sql = str_replace('`User`', '`User_' . $suff . '`', $create_sql);
            $this->_m->runPassport($sql);
            echo $i . PHP_EOL;
        }
    }

    /**
     * 用户表管理 php index.php Tool/Sql/account
     */
    public function account()
    {
        need_cli();
        $sqls = config('sql/user');
        $create_sql_connect = $sqls['create_connect'];
        $this->_m->runAccount($create_sql_connect);
        $create_sql = $sqls['create_account'];
        $this->_m->runAccount($create_sql);
        for ($i = 0; $i < 256; $i++) {
            $suff = sprintf('%02x', $i);
            $sql = str_replace('`User`', '`User_' . $suff . '`', $create_sql);
            $this->_m->runAccount($sql);
            echo $i . PHP_EOL;
        }
    }

    /**
     * 收藏表管理 php index.php Tool/Sql/favorite
     */
    public function favorite()
    {
        need_cli();
        $sqls = config('sql/common');
        $create_sql = $sqls['create_favorite'];
        $this->_m->runFavorite($create_sql);
        for ($i = 0; $i < 256; $i++) {
            $suff = sprintf('%02x', $i);
            $sql = str_replace('`Favorite`', '`Favorite_' . $suff . '`', $create_sql);
            $this->_m->runFavorite($sql);
            echo $i . PHP_EOL;
        }
    }

    /**
     * 应用表管理 php index.php Tool/Sql/application
     */
    public function application()
    {
        need_cli();
        $sqls = config('sql/application');
        foreach ($sqls as $k => $v) {
            $this->_m->runApplication($v);
        }
    }

    /**
     * 评论表管理 php index.php Tool/Sql/comment
     */
    public function comment()
    {
        need_cli();
        $sqls = config('sql/common');
        $create_sql = $sqls['create_comment'];
        $this->_m->runComment($create_sql);
        for ($i = 0; $i < 256; $i++) {
            $suff = sprintf('%02x', $i);
            $sql = str_replace('`Comment`', '`Comment_' . $suff . '`', $create_sql);
            $this->_m->runComment($sql);
            echo $i . PHP_EOL;
        }
    }

    /**
     * 标签表管理 php index.php Tool/Sql/tag
     */
    public function tag()
    {
        need_cli();
        $sqls = config('sql/common');
        $create_sql = $sqls['create_tag'];
        $create_sql2 = $sqls['create_tagname'];

        $this->_m->runTag($create_sql);
        $this->_m->runTag($create_sql2);
        for ($i = 0; $i < 256; $i++) {
            $suff = sprintf('%02x', $i);
            $sql = str_replace('`Tag`', '`Tag_' . $suff . '`', $create_sql);
            $this->_m->runTag($sql);
            echo $i . PHP_EOL;
        }
    }

    /**
     * 后台表管理 php index.php Tool/Sql/admin
     */
    public function admin()
    {
        need_cli();
        $sqls = config('sql/admin');
        foreach ($sqls as $k => $v) {
            $this->_m->runAdmin($v);
        }
    }

    /**
     * 采集表管理 php index.php Tool/Sql/collect
     */
    public function collect()
    {
        need_cli();
        $sqls = config('sql/collect');
        foreach ($sqls as $k => $v) {
            $this->_m->runCollect($v);
        }
    }

    /**
     * 手机表管理 php index.php Tool/Sql/mobile
     */
    public function mobile()
    {
        need_cli();
        $sqls = config('sql/mobile');
        foreach ($sqls as $k => $v) {
            $this->_m->runMobile($v);
        }
    }

    /**
     * 图片表管理 php index.php Tool/Sql/picture
     */
    public function picture()
    {
        need_cli();
        $sqls = config('sql/picture');
        foreach ($sqls as $k => $v) {
            $this->_m->runPicture($v);
        }
    }

    /**
     * 支付平台管理 php index.php Tool/Sql/payment
     */
    public function payment()
    {
        need_cli();
        $sqls = config('sql/payment');
        foreach ($sqls as $k => $v) {
            $this->_m->runPayment($v);
        }
    }

    /**
     * 标签表管理 php index.php Tool/Sql/update
     */
    public function update()
    {
        need_cli();
        $sqls = 'ALTER TABLE `User` CHANGE COLUMN `Userid` `UserId`  bigint(20) UNSIGNED NOT NULL COMMENT \'用户ID\' AFTER `Id`';
        $search = '`User`';
        $repalce = '`User_';
        $func = 'runAccount';

        for ($i = 0; $i < 256; $i++) {
            $suff = sprintf('%02x', $i);
            $sql = str_replace($search, $repalce . $suff . '`', $sqls);
            $this->_m->$func($sql);
            echo $i . PHP_EOL;
        }
    }
}
