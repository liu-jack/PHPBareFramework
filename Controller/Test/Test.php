<?php

namespace Controller\Test;

use Bare\Controller;
use Bare\DB;
use Bare\ViewModel;
use Classes\Image\PhotoImage;
use Common\ImgPath;
use Model\Common\Upload;
use Model\Mongo\UserData;
use Model\Admin\Admin\AdminLog;
use Model\Admin\Admin\SmsLog;
use Model\Book\{
    Book, Column
};
use Classes\Encrypt\Blowfish;
use Classes\Encrypt\Rsa;
use Model\Account\User as AUser;
use Model\Favorite\BookFavorite;
use Model\Admin\Admin\AdminUser;
use Model\Admin\Admin\AdminGroup;
use Model\Admin\Admin\AdminMenu;
use Bare\MongoModel;
use Model\Admin\Admin\AdminLogin;
use Model\Collect\CollectBook77 as Collect77;
use Model\Mongo\Test as MTest;

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
        //        var_dump(UserData::userReadBook(1, rand(1, 100)));
        //        var_dump(UserData::delete(1));
        //        var_dump(UserData::getUserData(1));
        //        var_dump(MTest::upsert(1, ['score' => rand(1, 100), 'date' => date('Y-m-d H:i:s')]));
        //        var_dump(MTest::delete(1));

        //        var_dump(MTest::getInfo(1));
        //        var_dump(MTest::updateUserCount(1));
        //        var_dump(MTest::getUserCount(1));

        //        logs('test');
        //        $info = Collect77::getBook('http://www.xiaoshuo77.com/view/0/207/');
        //        pre($info);die;
        //        var_dump(Book::updateBook(285,['IsFinish' => 2]));
        //        var_dump(arraySort($data, 'ParentId', SORT_DESC, 'AdminMenuId', SORT_DESC));
        //        var_dump(ViewModel::add([]));
        //        test2::test();
        //        test1::test1();
        //        var_dump(getFileExt('http://meitetest.oss-cn-hangzhou.aliyuncs.com/config/ecomm_ad/2017/09/13_33c56480.png'));
//        $image_status = PhotoImage::checkImageByUrl('http://meitetest.oss-cn-hangzhou.aliyuncs.com/config/ecomm_ad/2017/09/13_33c56480.png');
//        var_dump(Upload::saveImg(ImgPath::IMG_TEST, $image_status, [0, 450], 1, ['height' => [450 => 290]]));
    }

    /**
     *  php index.php test/test/test a1 a2 a3
     */
    public function test()
    {
        var_dump(md5(md5(microtime(true)) . (time() % 256)));
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
        //openssl genrsa -out rsa_private_key.pem 2048
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

class test1 extends test2
{
    public static function test1()
    {
        self::test();
    }
}

class test2
{
    protected static $class;

    public static function test()
    {
        echo static::class;
    }
}