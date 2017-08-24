<?php

namespace Controller\Test;

use Bare\Controller;
use Bare\DB;
use Model\Account\UserData;
use Model\Admin\AdminLog;
use Model\Admin\SmsLog;
use Model\Book\{
    Book, Column
};
use Classes\Encrypt\Blowfish;
use Classes\Encrypt\Rsa;
use Model\Account\User as AUser;
use Model\Favorite\BookFavorite;
use Model\Admin\AdminUser;
use Model\Admin\AdminGroup;
use Model\Admin\AdminMenu;
use Bare\MongoModel;
use Model\Admin\AdminLogin;
use Model\Collect\CollectBook77 as Collect77;

/**
 * 测试用控制器
 */
class Test extends Controller
{
    public function index()
    {
        //pre(BookFavorite::remove(1,1));
        //$str = 'www.29fh.com';
        //$bencode = Blowfish::encode($str);
        //$bdecode = Blowfish::decode($bencode);
        //$rencode = Rsa::public_encode($str);
        //$rdecode = Rsa::private_decode($rencode);
        //pre($str,$bencode,$bdecode);
        //pre($str,$rencode,$rdecode);
        //pre(Book::updateBook(258, ['IsFinish' => 2]));
        //pre(table(258));
        //Collect83::getBookColumn(258, 'http://m.83zw.com/book/7/7447/');
//        $data = [
//            'UserId' => 1,
//            'LoginName' => 'camfee',
//            'UserNick' => 'camfee'
//        ];
        //pre(AUser::addUser($data));
        //$obj = new BookFavorite(BookFavorite::TYPE_BOOK);
        //var_dump($obj->removeBook(1,258));
        //$cover = cover(1328);
        //$cover = cover(264);
        //var_dump($cover);
        //if (getimagesize($cover) == false) {
        //unlink($cover);
        //}
//        $data = [
//            'UserName' => 'camfee',
//            'Password' => 'camfee',
//            'RealName' => 'camfee'
//        ];
//        pre(AdminUser::addUser($data));
//        pre(AdminUser::getUserByName('camfee'));
        //pre(AdminGroup::addGroup(['GroupName' => '测试组']));
        $data = [
            [
                'AdminMenuId' => 1,
                'ParentId' => 0,
                'Name' => '首页',
                'Url' => 'Admin_Info',
            ],
            [
                'AdminMenuId' => 7,
                'ParentId' => 1,
                'Name' => '首页',
                'Url' => 'Admin/Info',
            ],
            [
                'AdminMenuId' => 8,
                'ParentId' => 7,
                'Name' => '欢迎页',
                'Url' => 'Admin/Info/index',
            ],
            [
                'AdminMenuId' => 9,
                'ParentId' => 7,
                'Name' => '登录信息',
                'Url' => 'Admin/Info/info',
            ],
            [
                'AdminMenuId' => 2,
                'ParentId' => 0,
                'Name' => '用户',
                'Url' => 'Admin_User'
            ],
            [
                'AdminMenuId' => 10,
                'ParentId' => 2,
                'Name' => '用户管理',
                'Url' => 'Admin/User',
            ],
            [
                'AdminMenuId' => 11,
                'ParentId' => 10,
                'Name' => '用户列表',
                'Url' => 'Admin/User/index',
            ],
            [
                'AdminMenuId' => 3,
                'ParentId' => 0,
                'Name' => '数据',
                'Url' => 'Admin_Data'
            ],
            [
                'AdminMenuId' => 12,
                'ParentId' => 3,
                'Name' => '书本管理',
                'Url' => 'Admin/Book',
            ],
            [
                'AdminMenuId' => 13,
                'ParentId' => 12,
                'Name' => '书本列表',
                'Url' => 'Admin/Book/index',
            ],
            [
                'AdminMenuId' => 4,
                'ParentId' => 0,
                'Name' => '应用',
                'Url' => 'Admin_App'
            ],
            [
                'AdminMenuId' => 14,
                'ParentId' => 4,
                'Name' => '应用管理',
                'Url' => 'Admin/App',
            ],
            [
                'AdminMenuId' => 15,
                'ParentId' => 14,
                'Name' => '应用列表',
                'Url' => 'Admin/App/index',
            ],
            [
                'AdminMenuId' => 5,
                'ParentId' => 0,
                'Name' => '工具',
                'Url' => 'Admin_Tool'
            ],
            [
                'AdminMenuId' => 16,
                'ParentId' => 5,
                'Name' => '工具管理',
                'Url' => 'Admin/Tool',
            ],
            [
                'AdminMenuId' => 17,
                'ParentId' => 16,
                'Name' => '工具列表',
                'Url' => 'Admin/Tool/index',
            ],
            [
                'AdminMenuId' => 6,
                'ParentId' => 0,
                'Name' => '后台',
                'Url' => 'Admin_Admin'
            ],
            [
                'AdminMenuId' => 18,
                'ParentId' => 6,
                'Name' => '后台管理',
                'Url' => 'Admin/Admin',
            ],
            [
                'AdminMenuId' => 19,
                'ParentId' => 18,
                'Name' => '管理员列表',
                'Url' => 'Admin/Admin/index',
            ],
        ];
//        foreach ($data as $v) {
//            AdminMenu::addMenu($v);
//        }

//        pre(AdminLogin::getAuthMenu());

//        $data = [
//            'UserId' => 1,
//            'ItemId' => 1,
//            'Log' => 'test text'
//        ];
//        pre(AdminLog::addLog($data));
//        $data = [
//            'Mobile' => '185746111486',
//            'Content' => '验证码：123456',
//        ];
//        pre(SmsLog::addSmsLog($data));
//        pre(AdminMenu::getMenusByParentId());
//        logs('/test/test/', 'test');
//        pre(dirname('/asdf/3234/'),basename('/asdf/3234/'));
//        UserData::userReadBook(1, [1,2,3,4]);
//        var_dump(UserData::getUserData(1));
//        logs('test');
//        $info = Collect77::getBook('http://www.xiaoshuo77.com/view/0/207/');
//        pre($info);die;
//        var_dump(Book::updateBook(285,['IsFinish' => 2]));
    }

    /**
     *  php index.php test/test/test a1 a2 a3
     */
    public function test()
    {
//        need_cli();
//        while (true) {
//            logs('test/test', $_GET['argv']);
//            sleep(10);
//        }
//        $ret = Book::getBookByIds(31);
//        $view_count = !empty($ret['ViewCount']) ? $ret['ViewCount'] : 0;
//        pre(Book::updateBook(31, ['ViewCount' => $view_count + 1]));
//        $ret = Book::getBookByIds(31);
//        $view_count = !empty($ret['LikeCount']) ? $ret['LikeCount'] : 0;
//        pre(Book::updateBook(31, ['LikeCount' => $view_count + 1]));
    }

    public function encrypt()
    {
        //AES: blowfish
        $data = 'www.29fh.com';
        $key = 'oScGU3fj8m/tDCyvsbEhwI91M1FcwvQqWuFpPoDHlFk='; //echo base64_encode(openssl_random_pseudo_bytes(32));
        $iv_size = openssl_cipher_iv_length('BF-CBC');
        $iv = (openssl_random_pseudo_bytes($iv_size));
        echo '内容: ' . $data . "\n";
        //$enc = openssl_encrypt($str, 'bf-ecb', $key, true);
        //$dec = openssl_decrypt($enc, 'bf-ecb', $key, true);
        $encrypted = openssl_encrypt($data, 'BF-CBC', $key, OPENSSL_RAW_DATA, $iv);
        echo '加密: ' . base64_encode($encrypted) . "\n";

        //$encrypted = base64_decode($encrypted);
        $decrypted = openssl_decrypt($encrypted, 'BF-CBC', $key, OPENSSL_RAW_DATA, $iv);
        echo '解密: ' . $decrypted . "\n";

        //RSA:
        //用openssl生成rsa密钥对(私钥/公钥):
        //openssl genrsa -out rsa_private_key.pem 1024
        //openssl rsa -pubout -in rsa_private_key.pem -out rsa_public_key.pem

        /* $data = 'phpbest';
        echo '原始内容: '.$data."\n";

        openssl_public_encrypt($data, $encrypted, file_get_contents(dirname(__FILE__).'/rsa_public_key.pem'));
        echo '公钥加密: '.base64_encode($encrypted)."\n";

        $encrypted = base64_decode('nMD7Yrx37U5AZRpXukingESUNYiSUHWThekrmRA0oD0=');
        openssl_private_decrypt($encrypted, $decrypted, file_get_contents(dirname(__FILE__).'/rsa_private_key.pem'));
        echo '私钥解密: '.$decrypted."\n"; */

    }
}