<?php
/**
 * CollectBookBase.php
 *
 * @author camfee <camfee@foxmail.com>
 * @date   17-8-12 下午2:29
 *
 */

namespace Model\Collect;


class CollectBookBase
{
    const FROM_ID_77 = 77;
    const BASE_URL_77 = 'http://www.xiaoshuo77.com';
    const FROM_ID_83 = 83;
    const BASE_URL_83 = 'http://m.83zw.com';

    public static $log_book_path = 'collect/book/book_';
    public static $log_book_err_path = 'collect/book/book_err_';
    public static $log_column_path = 'collect/book/column_';
    public static $log_column_err_path = 'collect/book/column_err_';
    public static $log_content_err_path = 'collect/book/content_err_';
}