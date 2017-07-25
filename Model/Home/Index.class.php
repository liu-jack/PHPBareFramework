<?php

namespace Model\Home;

use \Bare\DB;

class Index
{
    public function test()
    {
        $pdo = DB::pdo(DB::DB_BARE_R);
        $res = $pdo->query('show tables')->fetchAll();

        return $res;
    }
}