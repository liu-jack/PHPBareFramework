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
//        $sql = '';
//        for ($i = 0; $i < 256; $i++) {
//            $this->_m->runApplication($sql);
//            echo $i . PHP_EOL;
//        }
    }

    /**
     * 设备表管理 php index.php Tool/Sql/device
     */
    public function device()
    {
        need_cli();
        $sqls = config('sql/common');
        $create_sql = $sqls['create_device'];
        $this->_m->runDevice($create_sql);
        for ($i = 0; $i < 256; $i++) {
            $suff = sprintf('%02x', $i);
            $sql = str_replace('`Device`', '`Device_' . $suff . '`', $create_sql);
            $this->_m->runDevice($sql);
            echo $i . PHP_EOL;
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
        $create_sql1 = $sqls['create_group'];
        $create_sql2 = $sqls['create_user'];
        $create_sql3 = $sqls['create_menu'];
        $create_sql4 = $sqls['create_log'];
        $create_sql5 = $sqls['create_sms'];
        $this->_m->runAdmin($create_sql1);
        $this->_m->runAdmin($create_sql2);
        $this->_m->runAdmin($create_sql3);
        $this->_m->runAdmin($create_sql4);
        $this->_m->runAdmin($create_sql5);
    }

    /**
     * 采集表管理 php index.php Tool/Sql/collect
     */
    public function collect()
    {
        need_cli();
        $sqls = config('sql/collect');
        $create_sql1 = $sqls['create_collect_web'];
        $create_sql2 = $sqls['create_picinfo'];
        $create_sql3 = $sqls['create_article'];
        $create_sql4 = $sqls['create_content'];
        $this->_m->runCollect($create_sql1);
        $this->_m->runCollect($create_sql2);
        $this->_m->runCollect($create_sql3);
        $this->_m->runCollect($create_sql4);
    }

    /**
     * 手机表管理 php index.php Tool/Sql/mobile
     */
    public function mobile()
    {
        need_cli();
        $sqls = config('sql/mobile');
        $create_sql1 = $sqls['create_version'];
        $create_sql2 = $sqls['create_image'];
        $create_sql3 = $sqls['create_recommend'];
        $this->_m->runMobile($create_sql1);
        $this->_m->runMobile($create_sql2);
        $this->_m->runMobile($create_sql3);
    }

    /**
     * 图片表管理 php index.php Tool/Sql/picture
     */
    public function picture()
    {
        need_cli();
        $sqls = config('sql/collect');
        $create_sql1 = $sqls['create_atlas'];
        $create_sql2 = $sqls['create_picture'];
        $this->_m->runPicture($create_sql1);
        $this->_m->runPicture($create_sql2);
    }
}
