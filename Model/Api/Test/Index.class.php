<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/3/30
 * Time: 11:18
 */

namespace Model\Api\Test;

use Bare\M\Model;

class Index extends Model
{
    public static function index()
    {
        return ['get' => $_GET, 'post' => $_POST];
    }
}