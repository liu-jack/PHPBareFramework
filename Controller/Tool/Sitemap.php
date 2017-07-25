<?php
/**
 * Sitemap.php
 * @author camfee<camfee@foxmail.com>
 * @date 2017/5/24 22:56
 */

namespace Controller\Tool;

use Bare\Controller;
use Bare\DB;

class Sitemap extends Controller
{
    const FROM_ID = 77;

    /**
     * 网站地图生成
     * php index.php tool/sitemap/index
     */
    public function index()
    {
        need_cli();
        $size = 5000;
        $pdo = DB::pdo(DB::DB_29SHU_R);
        $total = $pdo->clear()->select("count(*)")->from('book')->where(['Status' => 1])->getValue();
        if ($total > $size) {
            $allpage = ceil($total / $size);
        } else {
            $allpage = 1;
        }
        $date = date('Y-m-d');
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xml .= '<sitemapindex>' . PHP_EOL;
        $xml .= '<sitemap><loc>http://29shu.iok.la/Data/sitemap/sitemap_menu.xml</loc><lastmod>' . $date . '</lastmod></sitemap>' . PHP_EOL;
        for ($i = 1; $i <= $allpage; $i++) {
            $xml .= '<sitemap><loc>http://29shu.iok.la/Data/sitemap/sitemap_' . $i . '.xml</loc><lastmod>' . $date . '</lastmod></sitemap>' . PHP_EOL;
        }
        $xml .= '</sitemapindex>' . PHP_EOL;
        file_put_contents(ROOT_PATH . 'sitemap.xml', $xml);

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xml .= '<urlset>' . PHP_EOL;

        $types = config('book/types');
        $xml .= '<url><loc>http://29shu.iok.la</loc><lastmod>' . $date . '</lastmod><changefreq>daily</changefreq><priority>1.0</priority></url>' . PHP_EOL;
        foreach ($types as $k => $v) { // 小说分类
            $xml .= '<url><loc>http://29shu.iok.la/book/type_' . $k . '.html</loc><lastmod>' . $date . '</lastmod><changefreq>daily</changefreq><priority>1.0</priority></url>' . PHP_EOL;
        }

        $games = config('game/h5');
        $xml .= '<url><loc>http://29shu.iok.la/game/h5/index.html</loc><lastmod>' . $date . '</lastmod><changefreq>daily</changefreq><priority>1.0</priority></url>' . PHP_EOL;
        foreach ($games as $k => $v) { // 游戏列表
            $xml .= '<url><loc>http://29shu.iok.la/game/h5/' . $k . '.html</loc><lastmod>' . $date . '</lastmod><changefreq>daily</changefreq><priority>1.0</priority></url>' . PHP_EOL;
        }

        $xml .= '</urlset>' . PHP_EOL;
        file_put_contents(DATA_PATH . 'sitemap/sitemap_menu.xml', $xml);

        for ($i = 1; $i <= $allpage; $i++) {
            echo "All Process: {$i}/{$allpage} \n";
            $xml = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
            $xml .= '<urlset>' . PHP_EOL;
            $offset = ($i - 1) * $size;
            $list = $pdo->clear()->select("`BookId`,`UpdateTime`")->from('book')->where(['Status' => 1])->limit($offset, $size)->getAll();
            if (!empty($list)) {
                $m = count($list);
                foreach ($list as $k => $v) {
                    echo "Page Process: " . ($k + 1) . "/{$m} \r";
                    $xml .= '<url><loc>http://29shu.iok.la/book/' . self::FROM_ID . '_' . $v['BookId'] . '.html</loc><lastmod>' . date('Y-m-d', $v['UpdateTime']) . '</lastmod><changefreq>daily</changefreq><priority>0.9</priority></url>' . PHP_EOL;
                }
            }
            $xml .= '</urlset>' . PHP_EOL;
            file_put_contents(DATA_PATH . 'sitemap/sitemap_' . $i . '.xml', $xml);
        }

        echo "\nFinished!\n";
    }
}