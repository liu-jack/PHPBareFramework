<?php

namespace Model\Tool;

use Bare\DB;

class Sql
{
    protected static $pdo = null;

    public function run29shu($sql)
    {
        if (empty(self::$pdo)) {
            self::$pdo = DB::pdo(DB::DB_29SHU_W);
        }
        return self::$pdo->exec($sql);
    }

    public function run29shuContent($sql)
    {
        if (empty(self::$pdo)) {
            self::$pdo = DB::pdo(DB::DB_29SHU_CONTENT_W);
        }
        return self::$pdo->exec($sql);
    }

    public function runPassport($sql)
    {
        if (empty(self::$pdo)) {
            self::$pdo = DB::pdo(DB::DB_PASSPORT_W);
        }
        return self::$pdo->exec($sql);
    }

    public function runAccount($sql)
    {
        if (empty(self::$pdo)) {
            self::$pdo = DB::pdo(DB::DB_ACCOUNT_W);
        }
        return self::$pdo->exec($sql);
    }

    public function runFavorite($sql)
    {
        if (empty(self::$pdo)) {
            self::$pdo = DB::pdo(DB::DB_FAVORITE_W);
        }
        return self::$pdo->exec($sql);
    }

    public function runApplication($sql)
    {
        if (empty(self::$pdo)) {
            self::$pdo = DB::pdo(DB::DB_APPLICATION_W);
        }
        return self::$pdo->exec($sql);
    }

    public function runDevice($sql)
    {
        if (empty(self::$pdo)) {
            self::$pdo = DB::pdo(DB::DB_DEVICE_W);
        }
        return self::$pdo->exec($sql);
    }

    public function runComment($sql)
    {
        if (empty(self::$pdo)) {
            self::$pdo = DB::pdo(DB::DB_COMMENT_W);
        }
        return self::$pdo->exec($sql);
    }

    public function runTag($sql)
    {
        if (empty(self::$pdo)) {
            self::$pdo = DB::pdo(DB::DB_TAG_W);
        }
        return self::$pdo->exec($sql);
    }

    public function runAdmin($sql)
    {
        if (empty(self::$pdo)) {
            self::$pdo = DB::pdo(DB::DB_ADMIN_W);
        }
        return self::$pdo->exec($sql);
    }

    public function runCollect($sql)
    {
        if (empty(self::$pdo)) {
            self::$pdo = DB::pdo(DB::DB_COLLECT_W);
        }
        return self::$pdo->exec($sql);
    }

    public function runMobile($sql)
    {
        if (empty(self::$pdo)) {
            self::$pdo = DB::pdo(DB::DB_MOBILE_W);
        }
        return self::$pdo->exec($sql);
    }
    
    public function runPicture($sql)
    {
        if (empty(self::$pdo)) {
            self::$pdo = DB::pdo(DB::DB_PICTURE_W);
        }
        return self::$pdo->exec($sql);
    }
}