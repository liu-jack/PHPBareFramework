<?php  defined('ROOT_PATH') or exit('Access deny');
/**
 * webpreg.php 网站采集正则配置
 * @author camfee<camfee@foxmail.com>
 * @date 2017/6/19 20:32
 */

return [
    101 => [ //siteid 站点ID 新浪
        1 => [ // channelid 频道ID
            'name' => '养育有道', // 频道名称 没啥用
            'url' => 'http://hi.baby.sina.com.cn/baby/yangyuyoudao/list.php?page={page}&dpc=1', // 列表页url 必须
            'tags' => ['育儿', '学龄期', '学龄期家庭教育'], // 文章标签2 必须
            'list_preg' => [ // 列表匹配正则  必须
                [
                    'reg' => '@<span class="title"><a href="([^"]+)"[^>]*>(.+)</a></span>@isU',
                    'field' => [1 => 'FromUrl', 2 => 'Title'] // match key => field
                ],
                [
                    'reg' => '@<a href="[^"]*"[^>]*><img src="([^"]+)" width="200" height="150" border="0" /></a>@isU',
                    'field' => [1 => 'Cover']
                ],
                [
                    'reg' => '@<span class="content">(.+)</span>@isU',
                    'field' => [1 => 'Description']
                ],
            ],
            'detail_preg' => [  // 内容匹配正则 必须
                'Author' => [ // 前面的匹配后 后面的不再执行
                    '@<span class="source"><a[^>]*>(.+)</a></span>@isU',
                    '@<span class="source">(.+)</span>@isU',
                    '@<span id="media_name"[^>]*>\s*(.+)\s*<a id="media_weibo"[^>]*>.*</a>\s*</span>@isU',
                ],
                'ArticleTime' => '@<span class="titer">(.+)</span>@isU',
                'Content' => '@<div class="content" id="artibody" data-sudaclick="blk_content">(.+)<div id="left_hzh_ad">@isU',
                'Title' => '@<h1 id="main_title">(.+)</h1>@isU',
                'Tags' => '@<p class="art_keywords">\s*<span class="art_keywords_tt">标签:</span>\s*(<a[^>]*>.+</a>\s*)</p>@isU',
                '__Tags' => '@<a[^>]*>(.+)</a>@isU',
            ],
            'type' => 1, // 文章类型 1:文章 2：图集 3：视频 默认1
            'page' => 1, // 首页页面 默认1
            'offset' => 1, // 翻页递增数 默认1
        ],
        2 => [
            'name' => '辣妈style',
            'url' => 'http://hi.baby.sina.com.cn/baby/lamazhenger/list.php?page={page}&dpc=1',
            'tags' => ['育儿', '学龄期', '学龄期家庭教育'],
            'list_preg' => 1, // 复用同站点下其他频道的正则
            'detail_preg' => 1, // 复用同站点下其他频道的正则
        ],
        3 => [
            'name' => '情感-心理',
            'url' => 'http://eladies.sina.com.cn/feel/xinli/',
            'tags' => ['情感', '婚姻'],
            'list_preg' => [ // 列表匹配正则
                [
                    'reg' => '@\{"URL":"([^"]+)"@isU',
                    'field' => [1 => 'FromUrl']
                ],
            ],
            'detail_preg' => 1, // 复用同站点下其他频道的正则
        ],
        4 => [
            'name' => '传统养生',
            'url' => 'http://feed.mix.sina.com.cn/api/roll/get?pageid=67&lid=566&num=50&ctime={time}&callback=feedCardJsonpCallback&_={mtime}&encode=utf-8',
            'tags' => ['健康', '保健'],
            'list_preg' => [ // 列表匹配正则
                [
                    'reg' => '@"url":"([^"]+)"@isU',
                    'field' => [1 => 'FromUrl']
                ],
                [
                    'reg' => '@"images":\[(.*)\]@isU',
                    'field' => [1 => 'Cover']
                ],
                'Cover' => [
                    '__reg' => '@"u":"([^"]+)"@isU',
                    'field' => 1
                ],
            ],
            'detail_preg' => 1,
        ],
        5 => [
            'name' => '健康-两性',
            'url' => 'http://feed.mix.sina.com.cn/api/roll/get?pageid=39&lid=663&num=50&ctime={time}&encode=utf-8&callback=feedCardJsonpCallback&_={mtime}',
            'tags' => ['两性', '性知识'],
            'list_preg' => 4,
            'detail_preg' => 1,
        ],
        6 => [
            'name' => '婴儿期-护理保健',
            'url' => 'http://roll.baby.sina.com.cn/babynewslist/yeq/default/hlbj/index_{page}.shtml',
            'tags' => ['育儿', '婴儿期', '婴儿护理'],
            'list_preg' => [
                [
                    'reg' => '@<li><a href="([^"]+)" target="_blank">(.+)</a></li>@isU',
                    'field' => [1 => 'FromUrl', 2 => 'Title']
                ],
            ],
            'detail_preg' => 1,
        ],
        7 => [
            'name' => '婴儿期-营养饮食',
            'url' => 'http://roll.baby.sina.com.cn/babynewslist/yeq/default/yyys/index_{page}.shtml',
            'tags' => ['育儿', '婴儿期', '婴儿辅食'],
            'list_preg' => 6,
            'detail_preg' => 1,
        ],
        8 => [
            'name' => '幼儿期-护理保健',
            'url' => 'http://roll.baby.sina.com.cn/babynewslist/yeq1/default/hlbj/index_{page}.shtml',
            'tags' => ['育儿', '幼儿期', '幼儿护理'],
            'list_preg' => 6,
            'detail_preg' => 1,
        ],
        9 => [
            'name' => '幼儿期-营养饮食',
            'url' => 'http://roll.baby.sina.com.cn/babynewslist/yeq1/default/yyys/index_{page}.shtml',
            'tags' => ['育儿', '幼儿期', '幼儿饮食'],
            'list_preg' => 6,
            'detail_preg' => 1,
        ],
        10 => [
            'name' => '怀孕期-孕期保健',
            'url' => 'http://roll.baby.sina.com.cn/babynewslist/hyq/default/yqbj/index_{page}.shtml',
            'tags' => ['怀孕', '孕期健康'],
            'list_preg' => 6,
            'detail_preg' => 1,
        ],
        11 => [
            'name' => '怀孕期-胎儿发育',
            'url' => 'http://roll.baby.sina.com.cn/babynewslist/hyq/default/tefy/index_{page}.shtml',
            'tags' => ['怀孕', '胎儿发育'],
            'list_preg' => 6,
            'detail_preg' => 1,
        ],
        12 => [
            'name' => '怀孕期-营养饮食',
            'url' => 'http://roll.baby.sina.com.cn/babynewslist/hyq/default/yyys/index_{page}.shtml',
            'tags' => ['怀孕', '孕期饮食'],
            'list_preg' => 6,
            'detail_preg' => 1,
        ],
        13 => [
            'name' => '备孕-不孕不育',
            'url' => 'http://roll.baby.sina.com.cn/babynewslist/zbhy/default/byby/index_{page}.shtml',
            'tags' => ['备孕', '不孕不育'],
            'list_preg' => 6,
            'detail_preg' => 1,
        ],
        14 => [
            'name' => '备孕-孕期饮食',
            'url' => 'http://roll.baby.sina.com.cn/babynewslist/zbhy/default/yyys/index_{page}.shtml',
            'tags' => ['备孕', '营养保健'],
            'list_preg' => 6,
            'detail_preg' => 1,
        ],
        15 => [
            'name' => '备孕-避孕流产',
            'url' => 'http://roll.baby.sina.com.cn/babynewslist/zbhy/default/bylc/index_{page}.shtml',
            'tags' => ['备孕', '避孕流产'],
            'list_preg' => 6,
            'detail_preg' => 1,
        ],
        16 => [
            'name' => '备孕-遗传优生',
            'url' => 'http://roll.baby.sina.com.cn/babynewslist/zbhy/default/ycys/index_{page}.shtml',
            'tags' => ['备孕', '遗传优生'],
            'list_preg' => 6,
            'detail_preg' => 1,
        ],
        17 => [
            'name' => '备孕-生男生女',
            'url' => 'http://roll.baby.sina.com.cn/babynewslist/zbhy/default/snsn/index_{page}.shtml',
            'tags' => ['备孕', '生男生女'],
            'list_preg' => 6,
            'detail_preg' => 1,
        ],
        18 => [
            'name' => '学龄前-营养饮食',
            'url' => 'http://roll.baby.sina.com.cn/babynewslist/xlq/default/yyys/index_{page}.shtml',
            'tags' => ['育儿', '学龄期', '学龄期饮食'],
            'list_preg' => 6,
            'detail_preg' => 1,
        ],
        19 => [
            'name' => '学龄前-护理保健',
            'url' => 'http://roll.baby.sina.com.cn/babynewslist/xlq/default/hlbj/index_{page}.shtml',
            'tags' => ['育儿', '学龄期', '学龄期护理'],
            'list_preg' => 6,
            'detail_preg' => 1,
        ],
        20 => [
            'name' => '学龄前-常见疾病',
            'url' => 'http://roll.baby.sina.com.cn/babynewslist/xlq/default/cjjb/index_{page}.shtml',
            'tags' => ['育儿', '学龄期', '学龄期护理'],
            'list_preg' => 6,
            'detail_preg' => 1,
        ],
        21 => [
            'name' => '新浪时尚-明星',
            'url' => 'http://roll.fashion.sina.com.cn/style/celebrity/index_{page}.shtml',
            'offset' => 5,
            'tags' => ['时尚', '明星'],
            'list_preg' => [
                [
                    'reg' => '@<h2><a href="([^"]+)" target="_blank">(.+)</a></h2>@isU',
                    'field' => [1 => 'FromUrl', 2 => 'Title']
                ],
                [
                    'reg' => '@<div class="blk_tw tw01">(.+)<div class="wb_info">@isU',
                    'field' => [1 => 'Cover']
                ],
                'Cover' => [
                    '__reg' => '@<div class="twpic">\s*<table>\s*<tr>\s*<td><a[^>]*><img src="([^"]+)"[^>]*></a></td>\s*</tr>\s*</table>\s*</div>@isU',
                    'field' => 1
                ],
            ],
            'detail_preg' => 1,
        ],
        22 => [
            'name' => '婚嫁-盛装新娘',
            'url' => 'http://roll.fashion.sina.com.cn/style/celebrity/index_{page}.shtml',
            'offset' => 5,
            'tags' => ['新娘', '婚纱'],
            'list_preg' => 21,
            'detail_preg' => 1,
        ],
        23 => [
            'name' => '婚嫁-珠宝钻戒',
            'url' => 'http://roll.fashion.sina.com.cn/wedding/rings/index_{page}.shtml',
            'offset' => 5,
            'tags' => ['新娘', '珠宝'],
            'list_preg' => 21,
            'detail_preg' => 1,
        ],
        24 => [
            'name' => '婚嫁-婚礼殿堂',
            'url' => 'http://roll.fashion.sina.com.cn/wedding/ceremony/index_{page}.shtml',
            'offset' => 5,
            'tags' => ['新娘', '婚礼'],
            'list_preg' => 21,
            'detail_preg' => 1,
        ],
        25 => [
            'name' => '新浪时尚-高清美图',
            'url' => 'http://platform.sina.com.cn/slide/album?app_key=1985696825&ch_id=24&size=img&num=50&jsoncallback=jQuery1720984109{time}_{mtime}&page={page}&_={mtime}',
            'type' => 2,
            'tags' => ['图片'],
            'list_preg' => [
                [
                    'reg' => '@"name":"([^"]+)"@isU',
                    'field' => [1 => 'Title']
                ],
                [
                    'reg' => '@"url":"([^"]+)"@isU',
                    'field' => [1 => 'FromUrl']
                ],
                [
                    'reg' => '@"img_url":"([^"]+)"@isU',
                    'field' => [1 => 'Cover']
                ],
            ],
            'detail_preg' => [
                'ArticleTime' => '@"createtime":"([^"]+)"@isU',
            ],
            'detail_type' => [
                'reg' => [
                    1 => '@<div id="efpBigPic">@isU',
                ],
                'url_reg' => ['FromUrl' => '@slide_(\d+)_(\d+)_(\d+)\.@isU'],
                'url' => 'http://slide.news.sina.com.cn/interface/slide_interface.php?ch={1}&sid={2}&id={3}&range=&key='
            ],
            'atlas_preg' => [
                'img' => '@"image_url":"([^"]*)"@isU',
                'text' => '@"intro":"([^"]*)"@isU',
            ],
        ],
        26 => [
            'name' => '美容-护肤',
            'url' => 'http://www.ixiumei.com/beauty/skincare/{page}.shtml',
            'tags' => ['美容', '护肤'],
            'list_preg' => [
                [
                    'reg' => '@<h2><a href="([^"]+)" target="_blank">(.+)</a></h2>@isU',
                    'field' => [1 => 'FromUrl', 2 => 'Title'],
                ],
                [
                    'reg' => '@<a href="[^"]*" target="_blank" class="mg-r20 fl-lf"><img.*(src|data-original)="([^"]*g)"[^>]*></a>@isU',
                    'field' => [2 => 'Cover'],
                ],
                [
                    'reg' => '@<p class="time">(.+)</p>@isU',
                    'field' => [1 => 'ArticleTime'],
                ],
            ],
            'detail_preg' => [
                'Author' => '@<p class="news-titbar">来源：<a[^>]*>(.+)</a>@isU',
                'Content' => '@<div class="details-bd details-bd-two" id="content">(.+)</div>\s*<div class="wx-text mg-t5 zm" id="wx-text"></div>@isU',
                'Tags' => '@<span class="details-tags">标签：\s*(<a[^>]*>.+</a>\s*)</span>@isU',
                '__Tags' => '@<a[^>]*>(.+)</a>@isU',
            ],
        ],
        27 => [
            'name' => '美容-彩妆',
            'url' => 'http://roll.fashion.sina.com.cn/beauty/makeup/index_{page}.shtml',
            'offset' => 5,
            'tags' => ['美容', '彩妆'],
            'list_preg' => 21,
            'detail_preg' => 1,
        ],
        28 => [
            'name' => '美体-身体护理',
            'url' => 'http://roll.fashion.sina.com.cn/body/care/index_{page}.shtml',
            'offset' => 5,
            'tags' => ['美容', '美体'],
            'list_preg' => 21,
            'detail_preg' => 1,
        ],
        29 => [
            'name' => '美体-身体艺术',
            'url' => 'http://roll.fashion.sina.com.cn/body/tattoo/index_{page}.shtml',
            'offset' => 5,
            'tags' => ['美容', '美体'],
            'list_preg' => 21,
            'detail_preg' => 1,
        ],
        30 => [
            'name' => '美容-美发',
            'url' => 'http://roll.fashion.sina.com.cn/beauty/hair/index_{page}.shtml',
            'offset' => 5,
            'tags' => ['美容', '秀发'],
            'list_preg' => 21,
            'detail_preg' => 1,
        ],
    ],
    109 => [ // 360娱乐
        1 => [
            'name' => '时尚-明星',
            'url' => 'http://yule.360.cn/feeds/getListByTag?tag=%E6%97%B6%E5%B0%9A&pn={page}',
            'tags' => ['时尚', '明星'],
            'list_preg' => [
                [
                    'reg' => '@"verify_time":"([^"]*)"@isU',
                    'field' => [1 => 'ArticleTime']
                ],
                [
                    'reg' => '@"text":"([^"]*)"@isU',
                    'field' => [1 => 'Title']
                ],
                [
                    'reg' => '@"pic":"([^"]*)"@isU',
                    'field' => [1 => 'Cover']
                ],
                [
                    'reg' => '@"id":([^,]*),@isU',
                    'field' => [1 => 'FromUrl'],
                    'FromUrl_prefix' => 'http://yule.360.cn/content/',
                ],
            ],
            'detail_preg' => [
                'Content' => '@<div class="content" data-bk="content_article">(.+)</div>\s*<!-- 关键字展示 -->@isU',
                'Tags' => '@<ul class="keywords-bar clearfix" data-bk="keywords">(.*) </ul>@isU',
                '__Tags' => '@<a[^>]*>(.+)</a>@isU',
            ],
            'detail_type' => [
                'type_reg' => [
                    2 => '@<!-- 图集 -->\s*<div class="detail-slide mt25" id="detail-slide" bk="pictures_slider">@isU'
                ],
            ],
            'atlas_preg' => [
                'img' => '@<li>\s*<div class="pic"><img src="([^"]+)"[^>]*></div>@isU',
                'text' => '@<p>(.+)</p>\s*<span class="describe-handle"></span>@isU',
            ],
        ],
        2 => [
            'name' => '明星',
            'url' => 'http://yule.360.cn/feeds/getListByTag?tag=%E6%98%8E%E6%98%9F&pn={page}',
            'tags' => ['娱乐', '热点'],
            'list_preg' => 1,
            'detail_preg' => 1,
            'detail_type' => 1,
            'atlas_preg' => 1,
        ],
        3 => [
            'name' => '影视',
            'url' => 'http://yule.360.cn/feeds/getListByTag?tag=%E5%BD%B1%E8%A7%86&pn={page}',
            'tags' => ['娱乐', '影视'],
            'list_preg' => 1,
            'detail_preg' => 1,
            'detail_type' => 1,
            'atlas_preg' => 1,
        ],
        4 => [
            'name' => '污力吐槽',
            'url' => 'http://yule.360.cn/feeds/getListByTonality?tonality=%E6%B1%A1%E5%8A%9B%E5%90%90%E6%A7%BD&pn={page}',
            'tags' => ['娱乐', '八卦'],
            'list_preg' => 1,
            'detail_preg' => 1,
            'detail_type' => 1,
            'atlas_preg' => 1,
        ],
        5 => [
            'name' => '搞笑-吐槽',
            'url' => 'http://yule.360.cn/feeds/getListByTonality?tonality=%E8%BD%BB%E6%9D%BE%E4%B8%80%E5%88%BB&pn={page}',
            'tags' => ['搞笑', '吐槽'],
            'list_preg' => 1,
            'detail_preg' => 1,
            'detail_type' => 1,
            'atlas_preg' => 1,
        ],
    ],
    113 => [ //爱丽时尚网
        1 => [
            'name' => '时装-明星风尚',
            'url' => 'http://fashion.aili.com/gossip/index_{page}.html#one',
            'page' => 0,
            'tags' => ['时尚', '明星'],
            'list_preg' => [
                [
                    'reg' => '@<h4><a href="([^"]+)"[^>]*>(.+)</a></h4>@isU',
                    'field' => [1 => 'FromUrl', 2 => 'Title']
                ],
                [
                    'reg' => '@<span class="mTyL_RB_Icon1">(.+)</span>@isU',
                    'field' => [1 => 'ArticleTime']
                ],
                [
                    'reg' => '@<div class="mTyL_L"><a[^>]*><img src="([^"]+)"[^>]*></a></div>@isU',
                    'field' => [1 => 'Cover']
                ],
            ],
            'detail_preg' => [
                'Content' => '@<div class="zarticle_inner" id="icontent">\s*<div class="zarticle_width">(.+)<script[^>]*></script>\s*</div>\s*</div>@isU',
                'Tags' => [
                    '@<div class="zpic_label_box clearflt">(.+)</div>@isU',
                    '@<div class="tBwrc">\s*<span>关键词</span>(.+)</div>@isU'
                ],
                '__Tags' => '@<a[^>]*>(.+)</a>@isU',
            ],
            'detail_type' => [
                'reg' => [
                    2 => '@<div class="zarticle_inner" id="icontent">@isU' //详细页类型 0:整页 1:ajax整页 2:翻多页
                ],
                'type_reg' => [
                    2 => '@<div class="show_img" id="show_img">@isU'
                ],
                'page_all_reg' => '@</script><span>共(\d+)页</span></div>@isU',
                'page_next_rep' => '@(_\d*)?(\.html)@isU',
            ],
            'atlas_preg' => [
                'img' => '@<li data-big="([^"]+)"@isU',
                'text' => '@<div style="display:none;" class="s_info">(.+)</div>\s*</div>\s*</li>@isU',
            ],
        ],
        2 => [
            'name' => '美容-美体塑性',
            'url' => 'http://beauty.aili.com/perfume/index_{page}.html#one',
            'page' => 0,
            'tags' => ['美容', '美体'],
            'list_preg' => 1,
            'detail_preg' => 1,
            'detail_type' => 1,
            'atlas_preg' => 1,
        ],
        3 => [
            'name' => '美容-美发造型',
            'url' => 'http://beauty.aili.com/hair/index_{page}.html#one',
            'page' => 0,
            'tags' => ['美容', '秀发'],
            'list_preg' => 1,
            'detail_preg' => 1,
            'detail_type' => 1,
            'atlas_preg' => 1,
        ],
    ],
    114 => [ // 时尚网
        1 => [
            'name' => '风尚-趋势',
            'url' => 'http://www.trends.com.cn/channel/fashion/data?category=trends&page={page}',
            'tags' => ['时尚', '流行'],
            'list_preg' => [
                [
                    'reg' => '@"type":10,"title":"([^"]*)","thumbnail":"([^"]*)","url":"([^"]*)",@isU',
                    'field' => [1 => 'Title', 2 => 'Cover', 3 => 'FromUrl'],
                    'FromUrl_prefix' => 'http://www.trends.com.cn',
                ],
            ],
            'detail_preg' => [
                'ArticleTime' => '@<li class="date">(.+)</li>@isU',
                'Author' => '@<li class="author">(.+)</li>@isU',
                'Description' => '@<div class="summary">(.+)</div>@isU',
                'Content' => '@<div class="article_content">(.+)</div>@isU',
                'Tags' => '@<ul class="article_label">\s*<li class="caption">标签:</li>(.+)</ul>@isU',
                '__Tags' => '@<a[^>]*>(.+)</a>@isU',
            ],
        ],
    ],
    115 => [ // 搜狐网
        1 => [
            'name' => '时尚-造型',
            'url' => 'http://v2.sohu.com/public-api/feed?scene=TAG&sceneId=63694&page={page}&size=20&callback=jQuery11240545{mtime}_{mtime}&_={mtime}',
            'tags' => ['时尚', '流行'],
            'list_preg' => [
                [
                    'reg' => '@"id":([^,]+),"authorId":([^,]+),@isU',
                    'field' => [1 => '__id', 2 => '__aid'],
                ],
                [
                    'reg' => '@"title":"([^"]+)"@isU',
                    'field' => [1 => 'Title']
                ],
                [
                    'reg' => '@"authorName":"([^"]+)"@isU',
                    'field' => [1 => 'Author']
                ],
                [
                    'reg' => '@"picUrl":"([^"]+)"@isU',
                    'field' => [1 => 'Cover'],
                    'Cover_prefix' => 'http:'
                ],
                [
                    'reg' => '@"tags":\[\{(.*)\}\],"publicTime"@isU',
                    'field' => [1 => 'Tags']
                ],
                'replace' => [
                    'FromUrl' => [
                        'rep' => 'http://www.sohu.com/a/{__id}_{__aid}',
                        'keys' => ['__id', '__aid'],
                    ],
                ]
            ],
            'detail_preg' => [
                'ArticleTime' => '@<span class="time" id="news-time" data-val="([^"]+)"></span>@isU',
                'Content' => '@<article class="article">(.+)</article>@isU',
                '__Tags' => '@"name":"([^"]+)"@isU',
            ],
        ],
        2 => [
            'name' => '时尚-街拍',
            'url' => 'http://v2.sohu.com/public-api/feed?scene=TAG&sceneId=63555&page={page}&size=20&callback=jQuery11240545{mtime}_{mtime}&_={mtime}',
            'tags' => ['时尚', '街拍'],
            'list_preg' => 1,
            'detail_preg' => 1,
        ],
        3 => [
            'name' => '搜狐健康',
            'url' => 'http://v2.sohu.com/public-api/feed?scene=CHANNEL&sceneId=24&page={page}&size=20&callback=jQuery11240545{mtime}_{mtime}&_={mtime}',
            'tags' => ['健康', '疾病'],
            'list_preg' => 1,
            'detail_preg' => 1,
        ],
        4 => [
            'name' => '奢品-珠宝',
            'url' => 'http://v2.sohu.com/public-api/feed?scene=TAG&sceneId=63689&page={page}&size=20&callback=jQuery11240545{mtime}_{mtime}&_={mtime}',
            'tags' => ['新娘', '珠宝'],
            'list_preg' => 1,
            'detail_preg' => 1,
        ],
        5 => [
            'name' => '美食-烘焙',
            'url' => 'http://v2.sohu.com/public-api/feed?scene=TAG&sceneId=26069&page={page}&size=20&callback=jQuery11240545{mtime}_{mtime}&_={mtime}',
            'tags' => ['美食', '烘焙'],
            'list_preg' => 1,
            'detail_preg' => 1,
        ],
        6 => [
            'name' => '美食-吃货研究所',
            'url' => 'http://v2.sohu.com/public-api/feed?scene=CATEGORY&sceneId=1444&page={page}&size=20&callback=jQuery11240545{mtime}_{mtime}&_={mtime}',
            'tags' => ['美食', '吃货'],
            'list_preg' => 1,
            'detail_preg' => 1,
        ],
        7 => [
            'name' => '美食-葡萄酒',
            'url' => 'http://v2.sohu.com/public-api/feed?scene=TAG&sceneId=20810&page={page}&size=20&callback=jQuery11240545{mtime}_{mtime}&_={mtime}',
            'tags' => ['美食', '饮品'],
            'list_preg' => 1,
            'detail_preg' => 1,
        ],
        8 => [
            'name' => '美食-咖啡',
            'url' => 'http://v2.sohu.com/public-api/feed?scene=TAG&sceneId=20846&page={page}&size=20&callback=jQuery11240545{mtime}_{mtime}&_={mtime}',
            'tags' => ['美食', '饮品'],
            'list_preg' => 1,
            'detail_preg' => 1,
        ],
        9 => [
            'name' => '旅游',
            'url' => 'http://v2.sohu.com/public-api/feed?scene=CHANNEL&sceneId=29&page={page}&size=20&callback=jQuery11240545{mtime}_{mtime}&_={mtime}',
            'tags' => ['生活', '旅游'],
            'list_preg' => 1,
            'detail_preg' => 1,
        ],
        10 => [
            'name' => '婴儿护理',
            'url' => 'http://v2.sohu.com/public-api/feed?scene=TAG&sceneId=69835&page={page}&size=20&callback=jQuery11240545{mtime}_{mtime}&_={mtime}',
            'tags' => ['分娩', '新生儿护理'],
            'list_preg' => 1,
            'detail_preg' => 1,
        ],
        11 => [
            'name' => '母婴-热点-辅食',
            'url' => 'http://v2.sohu.com/public-api/feed?scene=TAG&sceneId=70253&page={page}&size=20&callback=jQuery11240545{mtime}_{mtime}&_={mtime}',
            'tags' => ['育儿', '婴儿期', '婴儿辅食'],
            'list_preg' => 1,
            'detail_preg' => 1,
        ],
        12 => [
            'name' => '母婴-辣妈-性生活',
            'url' => 'http://v2.sohu.com/public-api/feed?scene=TAG&sceneId=69639&page={page}&size=20&callback=jQuery11240545{mtime}_{mtime}&_={mtime}',
            'tags' => ['两性', '性知识'],
            'list_preg' => 1,
            'detail_preg' => 1,
        ],
        13 => [
            'name' => '母婴-早教-胎教',
            'url' => 'http://v2.sohu.com/public-api/feed?scene=TAG&sceneId=69661&page={page}&size=20&callback=jQuery11240545{mtime}_{mtime}&_={mtime}',
            'tags' => ['怀孕', '胎教'],
            'list_preg' => 1,
            'detail_preg' => 1,
        ],
        14 => [
            'name' => '备孕-生男生女',
            'url' => 'http://v2.sohu.com/public-api/feed?scene=TAG&sceneId=69831&page={page}&size=20&callback=jQuery11240545{mtime}_{mtime}&_={mtime}',
            'tags' => ['备孕', '生男生女'],
            'list_preg' => 1,
            'detail_preg' => 1,
        ],
        15 => [
            'name' => '运势-周运势',
            'url' => 'http://v2.sohu.com/public-api/feed?scene=TAG&sceneId=65331&page={page}&size=20&callback=jQuery11240545{mtime}_{mtime}&_={mtime}',
            'tags' => ['星座', '星座运势'],
            'list_preg' => 1,
            'detail_preg' => 1,
        ],
        16 => [
            'name' => '星座趣闻-揭秘',
            'url' => 'http://v2.sohu.com/public-api/feed?scene=TAG&sceneId=65320&page={page}&size=20&callback=jQuery11240545{mtime}_{mtime}&_={mtime}',
            'tags' => ['星座', '星座盘点'],
            'list_preg' => 1,
            'detail_preg' => 1,
        ],
        17 => [
            'name' => '星座-心理测试',
            'url' => 'http://v2.sohu.com/public-api/feed?scene=TAG&sceneId=68517&page={page}&size=20&callback=jQuery11240545{mtime}_{mtime}&_={mtime}',
            'tags' => ['星座', '心理测试'],
            'list_preg' => 1,
            'detail_preg' => 1,
        ],
        18 => [
            'name' => '星座-风水',
            'url' => 'http://v2.sohu.com/public-api/feed?scene=CATEGORY&sceneId=1418&page={page}&size=20&callback=jQuery11240545{mtime}_{mtime}&_={mtime}',
            'tags' => ['星座', '五行风水'],
            'list_preg' => 1,
            'detail_preg' => 1,
        ],
        19 => [
            'name' => '美容-护肤',
            'url' => 'http://v2.sohu.com/public-api/feed?scene=TAG&sceneId=63678&page={page}&size=20&callback=jQuery11240545{mtime}_{mtime}&_={mtime}',
            'tags' => ['美容', '护肤'],
            'list_preg' => 1,
            'detail_preg' => 1,
        ],
        20 => [
            'name' => '美容-彩妆',
            'url' => 'http://v2.sohu.com/public-api/feed?scene=TAG&sceneId=63679&page={page}&size=20&callback=jQuery11240545{mtime}_{mtime}&_={mtime}',
            'tags' => ['美容', '彩妆'],
            'list_preg' => 1,
            'detail_preg' => 1,
        ],
        21 => [
            'name' => '美容-美发',
            'url' => 'http://v2.sohu.com/public-api/feed?scene=TAG&sceneId=63681&page={page}&size=20&callback=jQuery11240545{mtime}_{mtime}&_={mtime}',
            'tags' => ['美容', '秀发'],
            'list_preg' => 1,
            'detail_preg' => 1,
        ],
        22 => [
            'name' => '母婴百宝箱-婴儿护理',
            'url' => 'http://baobao.sohu.com/babyhl/index.shtml',
            'tags' => ['分娩', '新生儿护理'],
            'list_preg' => [
                [
                    'reg' => '@<span class="content-title"><a href="([^"]+)" target="_blank">(.+)</a></span>@isU',
                    'field' => [1 => 'FromUrl', 2 => 'Title']
                ],
                [
                    'reg' => '@<div class="content-pic">\s*<a[^>]*><img src="([^"]+)"[^>]*></a>\s*</div>@isU',
                    'field' => [1 => 'Cover']
                ]
            ],
            'detail_preg' => 1,
        ],
        23 => [
            'name' => '母婴百宝箱-婴儿疾病',
            'url' => 'http://baobao.sohu.com/babysill/index.shtml',
            'tags' => ['分娩', '婴幼疾病'],
            'list_preg' => 22,
            'detail_preg' => 1,
        ],
        24 => [
            'name' => '母婴百宝箱-分娩产后',
            'url' => 'http://baobao.sohu.com/yuezi0/index.shtml',
            'tags' => ['分娩', '月子事宜'],
            'list_preg' => 22,
            'detail_preg' => 1,
        ],
        25 => [
            'name' => '母婴百宝箱-产后恢复',
            'url' => 'http://baobao.sohu.com/huifu/index.shtml',
            'tags' => ['分娩', '产后恢复'],
            'list_preg' => 22,
            'detail_preg' => 1,
        ],
        26 => [
            'name' => '母婴百宝箱-婴儿喂养',
            'url' => 'http://baobao.sohu.com/babyeat/index.shtml',
            'tags' => ['分娩', '哺乳喂养'],
            'list_preg' => 22,
            'detail_preg' => 1,
        ],
    ],
    117 => [ // 新华时尚
        1 => [
            'name' => '美搭-穿搭',
            'url' => 'http://qc.wa.news.cn/nodeart/list?nid=11110198&pgnum={page}&cnt=10&tp=1&orderby=1?callback=jQuery11240745099{time}_{mtime}&_={mtime}',
            'tags' => ['时尚', '穿搭'],
            'list_preg' => [
                [
                    'reg' => '@"Title":"([^"]+)"@isU',
                    'field' => [1 => 'Title']
                ],
                [
                    'reg' => '@"SourceName":"([^"]+)"@isU',
                    'field' => [1 => 'Author']
                ],
                [
                    'reg' => '@"PubTime":"([^"]+)"@isU',
                    'field' => [1 => 'ArticleTime']
                ],
                [
                    'reg' => '@"LinkUrl":"([^"]+)"@isU',
                    'field' => [1 => 'FromUrl']
                ],
                [
                    'reg' => '@"Abstract":"([^"]+)"@isU',
                    'field' => [1 => 'Description']
                ],
                [
                    'reg' => '@"keyword":"([^"]+)"@isU',
                    'field' => [1 => 'Tags']
                ],
                [
                    'reg' => '@"allPics":\[(.+)\],@isU',
                    'field' => [1 => 'Cover']
                ],
                'Cover' => [
                    '__reg' => '@"([^"]+)"@isU',
                    'field' => 1
                ]
            ],
            'detail_preg' => [
                '__Tags' => '@[;"]([^;"])[;"]@isU',
                'Content' => '@<div class="swiper-container2" id="swiperContainer2">(.+)<div class="zan-wap">
@isU',
                '___img' => '@<img[^>]*id="\{[^"]*\}"[^>]*src="([^"]+)"[^>]*>@isU',
            ],
            'detail_type' => [
                'reg' => [
                    2 => '@<a href="([^"]+)"[^>]*>下一页</a>@isU',
                ],
                'type_reg' => [
                    1 => '@<div class="swiper-container2" id="swiperContainer2">@isU',
                    2 => '@<img[^>]*id="\{[^"]*\}"[^>]*src="([^"]+)"[^>]*>@isU'
                ],
                'page_next_reg' => '@<a href="([^"]+)"[^>]*>下一页</a>@isU',
            ],
            'atlas_preg' => [
                '__img_text' => '@<p[^>]*>(<a[^>]*>)?<img[^>]*id="\{[^"]*\}"[^>]*src="[^"]+"[^>]*>(</a>)?</p>\s*<p[^>]*>([^"]*)</p>@is',
                'img' => '@<img[^>]*id="\{[^"]*\}"[^>]*src="([^"]+)"[^>]*>@isU',
                'text' => '@<p[^>]*>([^"]*)</p>@isU',
            ]
        ],
        2 => [
            'name' => '美搭-街拍',
            'url' => 'http://qc.wa.news.cn/nodeart/list?nid=11110199&pgnum={page}&cnt=10&tp=1&orderby=1?callback=jQuery11240013482{time}_{mtime}&_={mtime}',
            'tags' => ['时尚', '街拍'],
            'list_preg' => 1,
            'detail_preg' => 1,
            'detail_type' => 1,
            'atlas_preg' => 1
        ],
        3 => [
            'name' => '珠宝',
            'url' => 'http://qc.wa.news.cn/nodeart/list?nid=11110204&pgnum={page}&cnt=10&tp=1&orderby=1?callback=jQuery11240740776{time}_{mtime}&_={mtime}',
            'tags' => ['新娘', '珠宝'],
            'list_preg' => 1,
            'detail_preg' => 1,
            'detail_type' => 1,
            'atlas_preg' => 1
        ],
        4 => [
            'name' => '美体-塑形',
            'url' => 'http://qc.wa.news.cn/nodeart/list?nid=11110205&pgnum={page}&cnt=10&tp=1&orderby=1?callback=jQuery11240165911{time}_{mtime}&_={mtime}',
            'tags' => ['美容', '美体'],
            'list_preg' => 1,
            'detail_preg' => 1,
            'detail_type' => 1,
            'atlas_preg' => 1
        ],
    ],
    119 => [ // 环球网
        1 => [
            'name' => '女人-情感心理',
            'url' => 'http://women.huanqiu.com/emotion/index.html',
            'tags' => ['情感', '恋爱'],
            'list_preg' => [
                [
                    'reg' => '@<li class="item">.*<h3><a[^>]*href="([^"]+)"[^>]*>(.+)</a></h3>\s*<h5>.*</h5>\s*<h6><span></span>(.*)</h6>\s*</li>@isU',
                    'field' => [1 => 'FromUrl', 2 => 'Title', 3 => 'ArticleTime']
                ],
                [
                    'reg' => '@<li class="item">.*<h3><a[^>]*href="[^"]+"[^>]*>.+</a></h3>\s*<h5>.*</h5>\s*<h6><span></span>.*</h6>\s*</li>@isU',
                    'field' => [0 => 'Cover']
                ],
                'Cover' => [
                    '__reg' => '@<a[^>]*><img src="([^"]+)"[^>]*></a>@isU',
                    'field' => 1
                ]
            ],
            'detail_preg' => [
                'Content' => '@<div class="text" id="text">(.+)<div class="spTopic">@isU',
                '___img' => '@<img[^>]*src="(file:[^"]*)"[^>]*>@isU'
            ],
        ],
        2 => [
            'name' => '健康-保健',
            'url' => 'http://health.huanqiu.com/health_promotion/index.html',
            'tags' => ['健康', '保健'],
            'list_preg' => 1,
            'detail_preg' => 1,
        ],
        3 => [
            'name' => '健康-疾病',
            'url' => 'http://health.huanqiu.com/xunyiweny/index.html',
            'tags' => ['健康', '疾病'],
            'list_preg' => 1,
            'detail_preg' => 1,
        ]
    ],
    120 => [ // PUA论坛
        1 => [
            'name' => '情感-恋爱',
            'url' => 'http://www.puahome.com/',
            'tags' => ['情感', '恋爱'],
            'list_preg' => [
                [
                    'reg' => '@<li>\s*<a href="([^"]+)"[^>]*><img src="([^"]+)"[^>]*></a>\s*<span class="_title"><a href="[^"]*"[^>]*>(.+)</a></span>\s*<div class="_desc"><em><a[^>]*>.*</a></em> 发表于 (.*)</div>\s*<div class="_summary">.*<a[^>]*>.*</a></div>\s*<span><a[^>]*>.*</a></span>\s*</li>@isU',
                    'field' => [1 => 'FromUrl', 2 => 'Cover', 3 => 'Title', 4 => 'ArticleTime'],
                    'FromUrl_prefix' => 'http://www.puahome.com/bbs/',
                    'Cover_prefix' => 'http://www.puahome.com/bbs/',
                ]
            ],
            'detail_preg' => [
                'Content' => '@<div class="t_fsz">(.+)</td></tr></table>@isU',
                '___img' => '@<img[^>]*zoomfile="([^"]+)"[^>]*>@isU'
            ],
        ]
    ],
    123 => [ // 春天女人
    ],
    124 => [ // 主妇网
        1 => [
            'name' => '情感-婆媳',
            'url' => 'http://qg.izhufu.net/poxi/44-{page}.html',
            'tags' => ['情感', '婆媳'],
            'list_preg' => [
                [
                    'reg' => "@<li>\s*<div class='article_img float_l'>\s*<a href='([^']+)'[^>]*>\s*<img src='([^']+)'[^>]*>\s*</a>\s*</div><div class='article_txt float_r'>\s*<a[^>]*>\s*<h3>(.+)</h3>\s*</a>\s*<p>.*</p>\s*<div class=\"article_label\">.*</div>\s*</div>\s*<div class=\"article_time\">\s*<span class=\"icon_time\">time</span>\s*<span>(.*)</span>\s*</div>\s*</div>\s*</li>@isU",
                    'field' => [1 => 'FromUrl', 2 => 'Cover', 3 => 'Title', 4 => 'ArticleTime'],
                ]
            ],
            'detail_preg' => [
                'Content' => [
                    '@<div class="text clear" id="zf-cont">(.+)</div>\s*</div>@isU',
                    '@<div class="container wrap">(.+)</div>\s*<div class="mark">@isU'
                ],
            ],
        ],
        2 => [
            'name' => '美食-食谱',
            'url' => 'http://meishi.izhufu.net/shipu/419-{page}.html',
            'tags' => ['美食', '烹饪'],
            'list_preg' => 1,
            'detail_preg' => 1,
        ],
        3 => [
            'name' => '美食-美食知识',
            'url' => 'http://meishi.izhufu.net/meishi/24-{page}.html',
            'tags' => ['美食', '吃货'],
            'list_preg' => 1,
            'detail_preg' => 1,
        ],
        4 => [
            'name' => '亲子-健康成长',
            'url' => 'http://qinzi.izhufu.net/jiankangchengzhang/29-{page}.html',
            'tags' => ['分娩', '新生儿护理'],
            'list_preg' => 1,
            'detail_preg' => 1,
        ],
        5 => [
            'name' => '亲子-产后保养',
            'url' => 'http://qinzi.izhufu.net/chanhoubaoyang/33-{page}.html',
            'tags' => ['分娩', '产后恢复'],
            'list_preg' => 1,
            'detail_preg' => 1,
        ],
        6 => [
            'name' => '亲子-宝宝喂养',
            'url' => 'http://qinzi.izhufu.net/baobaoweiyang/27-{page}.html',
            'tags' => ['分娩', '哺乳喂养'],
            'list_preg' => 1,
            'detail_preg' => 1,
        ],
    ],
    125 => [ // 北京时间
        1 => [
            'name' => '健康',
            'url' => 'http://pc.api.btime.com/btimeweb/getInfoFlow?callback=jQuery11130510232{time}_{mtime}&is_paging=0&refresh=1&refresh_type=2&channel=Health&sub_channel=&pid=3&from=haoindex&citycode=local_430100_430000&category=&req_count={page}&_={mtime}',
            'tags' => ['健康', '保健'],
            'list_preg' => [
                [
                    'reg' => '@"url":"([^"]+)"@isU',
                    'field' => [1 => 'FromUrl'],
                ],
                [
                    'reg' => '@"title":"([^"]+)"@isU',
                    'field' => [1 => 'Title'],
                ],
                [
                    'reg' => '@"source":"([^"]+)"@isU',
                    'field' => [1 => 'Author'],
                ],
                [
                    'reg' => '@"summary":"([^"]+)"@isU',
                    'field' => [1 => 'Description'],
                ],
                [
                    'reg' => '@"pdate_ymdhis":"([^"]+)"@isU',
                    'field' => [1 => 'ArticleTime'],
                ],
                [
                    'reg' => '@"covers":"([^"]+)"@isU',
                    'field' => [1 => 'Cover'],
                ],
                'Cover' => [
                    '__reg' => '@"([^"]+)"@isU',
                    'field' => 1
                ]
            ],
            'detail_preg' => [
                'Content' => '@<div class="content-text" id="content-text" bk="content-text">(.+)<div data-seed="111">\s*</div>@isU'
            ],
        ],
        2 => [
            'name' => '搞笑',
            'url' => 'http://pc.api.btime.com/btimeweb/getInfoFlow?callback=jQuery11130307607{time}_{mtime}&is_paging=0&refresh=1&refresh_type=2&channel=fun&sub_channel=&pid=3&from=&citycode=local_430100_430000&category=&req_count={page}&_={mtime}',
            'tags' => ['搞笑', '吐槽'],
            'list_preg' => 1,
            'detail_preg' => 1,
        ],
    ],
    127 => [ // 美食天下
        1 => [
            'name' => '美食天下',
            'url' => 'http://www.meishichina.com/index.php?ac=cms&op=getMoreDiffStateArticle&channelid=9&classid=0&orderby=hot&page={page}',
            'tags' => ['健康', '养生'],
            'list_preg' => [
                [
                    'reg' => '@\{"ArticleID":"[^"]*","ChannelID":"[^"]*","ClassID":"[^"]*","Title":"([^"]+)","ArticleLink":"([^"]+)","Summary":"([^"]*)","ArticlePic":"([^"]*)","Datetime":"([^"]*)","CreateTime":[^,]*,"subcontent":"[^"]*"\}@isU',
                    'field' => [1 => 'Title', 2 => 'FromUrl', 3 => 'Description', 4 => 'Cover', 5 => 'ArticleTime']
                ]
            ],
            'detail_preg' => [
                'Content' => '@<div class="content[^"]*">(.+)</div>\s*<div class="ui-page mt20">@isU'
            ],
            'detail_type' => [
                'reg' => [
                    2 => '@<a href="([^"]+)"[^>]*>下一页</a>@isU'
                ],
                'page_next_reg' => '@<a href="([^"]+)"[^>]*>下一页</a>@isU',
            ]
        ]
    ],
    128 => [ // 薄荷健康网
        1 => [
            'name' => '饮食-养生',
            'url' => 'http://www.bh5.com/yangsheng/ys/index_{page}.shtml',
            'tags' => ['健康', '养生'],
            'list_preg' => [
                [
                    'reg' => '@<dt><a href="([^"]+)" target="_blank">(.+)</a></dt>@isU',
                    'field' => [1 => 'FromUrl', 2 => 'Title']
                ],
                [
                    'reg' => '@<div class="tup"><a[^>]*>\s*<img src="([^"]*)"[^>]*>\s*</a></div>@isU',
                    'field' => [1 => 'Cover']
                ],
                [
                    'reg' => '@<div class="nr_sj">(.+)</div>@isU',
                    'field' => [1 => 'ArticleTime']
                ],
            ],
            'detail_preg' => [
                'Content' => '@<div id="bh5_c1">(.+)<div id="bh5_c2">@isU',
                'Author' => '@<em class="form">来源：(.+)</em>@isU'
            ],
            'detail_type' => [
                'reg' => [
                    2 => '@<a href="([^"]+)"[^>]*>下一页</a>@isU'
                ],
                'page_next_reg' => '@<a href="([^"]+)"[^>]*>下一页</a>@isU',
            ]
        ],
        2 => [
            'name' => '疾病',
            'url' => 'http://www.bh5.com/jibing/index_{page}.shtml',
            'tags' => ['健康', '疾病'],
            'list_preg' => 1,
            'detail_preg' => 1,
            'detail_type' => 1
        ],
    ],
    129 => [ // 小红提养生网
        1 => [
            'name' => '饮食养生-食疗药膳',
            'url' => 'http://www.xiaohongti.com/yinshi/slys/',
            'tags' => ['健康', '养生'],
            'list_preg' => [
                [
                    'reg' => '@<li>\s*<a href="([^"]+)"><img src="([^"]+)" width="165" height="120"></a>\s*<span class="span_title">\s*<i>.*</i><a[^>]*>(.+)</a></span>\s*<p class="hav_img">(.*)<a href="[^"]*" class="xiangyi">\[详细\]</a></p>\s*<span class="news_span1">(.*)</span>\s*</li>@isU',
                    'field' => [1 => 'FromUrl', 2 => 'Cover', 3 => 'Title', 4 => 'Description', 5 => 'ArticleTime']
                ]
            ],
            'detail_preg' => [
                'Content' => '@<div id="news_main" class="abody news_mainbox">(.+)</div>@isU',
                'Author' => '@来源：<a[^>]*>(.+)</a>@isU'
            ],
            'detail_type' => [
                'reg' => [
                    2 => '@<a"[^>]*href="([^"]+)"[^>]*>下一页</a>@isU'
                ],
                'page_next_reg' => '@<a"[^>]*href="([^"]+)"[^>]*>下一页</a>@isU',
            ]
        ]
    ],
    130 => [ // 老宗医养生在线
        1 => [
            'name' => '女性养生',
            'url' => 'http://www.laozongyi.com/shenghuo/nxys_{page}.html',
            'tags' => ['健康', '养生'],
            'list_preg' => [
                [
                    'reg' => '@<li class="cl" data-id="[^"]*">\s*<div class="pic apic"><a href="([^"]*)" target="_blank"><img src="([^"]*)"[^>]*></a></div>\s*<div class="title">\s*<div class="tit"><a href="[^"]+" target="_blank" class="liTitle">(.+)</a></div>\s*<span class="read"></span>\s*</div>\s*<div class="con">(.*)</div>\s*<div class="tag">\s*<span>.*</span><span class="time">(.*)</span>\s*</div>\s*</li>@isU',
                    'field' => [1 => 'FromUrl', 2 => 'Cover', 3 => 'Title', 4 => 'Description', 5 => 'ArticleTime'],
                    'FromUrl_prefix' => 'http://www.laozongyi.com'
                ]
            ],
            'detail_preg' => [
                'Content' => '@<!--文章内容开始-->(.+)<!--文章内容结束-->@isU',
                'Tags' => '@<div class="article-tag cl">\s*<span>Tag：</span>(.+)</div>@isU',
                '__Tags' => '@<a[^>]*>(.+)</a>@isU'
            ]
        ]
    ],
    131 => [ // 女性疾病
        1 => [
            'name' => '健康-疾病',
            'url' => 'http://www.qqyy.com/jibing/fuke/',
            'tags' => ['健康', '疾病'],
            'list_preg' => [
                [

                ]
            ],
            'detail_preg' => [

            ]
        ]
    ],
    132 => [ // 新娘网
        1 => [
            'name' => '婚礼',
            'url' => 'http://www.brides.com.cn/wedding/aisle-say/page_{page}.html',
            'tags' => ['新娘', '婚礼'],
            'list_preg' => [
                [
                    'reg' => '@<a target="_blank" href="([^"]+)" style="display:block;" class="zb2_hb">\s*<img src="([^"]+)" width="196" height="196" class="fl zb2_img" />\s*<div class="fl">\s*<p class="zb2_tags">.*</p>\s*<h3>(.+)</h3>\s*<p class="user_date"> <span class="user">.*</span> <span class="date">(.*)</span> </p>\s*<p class="p_text">(.*)<br />\s*<span>\[详细信息\]</span></p>\s*</div>\s*</a>@isU',
                    'field' => [1 => 'FromUrl', 2 => 'Cover', 3 => 'Title', 4 => 'ArticleTime', 5 => 'Description'],
                    'Cover_prefix' => 'http://www.brides.com.cn'
                ]
            ],
            'detail_preg' => [
                'Content' => '@<div class="zhengwen_div" >(.+)</div>\s*</div>\s*</div>@isU',
                'Tags' => '@<p class="keywords"><span>标签：</span>(.*)</p>@isU',
                '__Tags' => '@<a[^>]*>(.+)</a>@isU',
                '___img' => '@<img src="([^"]+)"[^>]*>@isU'
            ]
        ]
    ],
    133 => [ // 腾讯
        1 => [
            'name' => '明星-娱乐底片',
            'url' => 'http://ent.qq.com/c/yldpjh_1.htm?0.0934{mtime}',
            'tags' => ['图片'],
            'type' => 2,
            'list_preg' => [
                [
                    'reg' => '@<li><div class="box">\s*<div class="hd"><h2>.*<a target="_blank" href="([^"]+)">(.+)</a>\s*</h2></div>\s*<div class="bd"><div class="twC">\s*<a[^>]*><img class="pic" src="([^"]*)"[^>]*></a><p class="text">(.*)<a[^>]*>详细</a>\s*</p>\s*</div></div>\s*</div></li>@isU',
                    'field' => [1 => 'FromUrl', 2 => 'Title', 3 => 'Cover', 4 => 'Description'],
                    'FromUrl_prefix' => 'http://ent.qq.com'
                ]
            ],
            'atlas_preg' => [
                '__img_text' => '@<div[^>]*data-special-type="imgs"[^>]*>.+</div></div>@isU',
                'img' => '@<img [-]?src="([^"]+)"[^>]*>@isU',
                'text' => '@<p>(.+)</p>@isU'
            ]
        ],
        2 => [
            'name' => '明星-新闻',
            'url' => 'http://ent.qq.com/star/',
            'tags' => ['娱乐', '热点'],
            'list_preg' => [
                [
                    'reg' => '@<div class="Q-tpList"[^>]*><div class="Q-tpWrap">\s*<a target="_blank" class="pic" href="([^"]+)"><img class="picto" name="page_cnt_2" src="([^"]*)"></a><div class="text">\s*<em class="f14 l24"><a[^>]*>(.+)</a></em><div class="st">\s*<div class="info">\s*<span class="from">(.*)</span><span class="keywords">(.*)</span>\s*</div>\s*<div class="btns">@isU',
                    'field' => [1 => 'FromUrl', 2 => 'Cover', 3 => 'Title', 4 => 'Author', 5 => 'Tags'],
                ]
            ],
            'detail_preg' => [
                'Content' => '@<div id="Cnt-Main-Article-QQ" class="Cnt-Main-Article-QQ" bossZone="content">(.+)<div class="qq_articleFt">@isU',
                'ArticleTime' => '@<span class="a_time">(.*)</span>@isU',
                '__Tags' => '@([^;]+)@is'
            ]
        ],
        3 => [
            'name' => '明星-新闻-图片',
            'url' => 'http://ent.qq.com/star/',
            'tags' => ['娱乐', '热点'],
            'type' => 2,
            'list_preg' => [
                [
                    'reg' => '@<div class="Q-pList">\s*<div class="content">\s*<em class="photos"><span>组图</span><a target="_blank" class="linkto" href="([^"]+)">(.+)</a></em>(.*)</div>\s*<div class="st">\s*<div class="info">\s*<span class="from">(.*)</span><span class="keywords">(.*)</span>\s*</div>\s*<div class="btns">@isU',
                    'field' => [1 => 'FromUrl', 2 => 'Title', 3 => 'Cover', 4 => 'Author', 5 => 'Tags'],
                ],
                'Cover' => [
                    '__reg' => '@<li class="pic"><a[^>]*><img[^>]*src="([^"]+)"[^>]*></a></li>@isU',
                    'field' => 1
                ]
            ],
            'detail_preg' => [
                'ArticleTime' => '@<span class="a_time">(.*)</span>@isU',
                '__Tags' => '@([^;]+)@is'
            ],
            'detail_type' => [
                'reg' => [
                    1 => '@<div class="gallery" id="Gallery">@isU',
                ],
                'url_reg' => ['FromUrl' => '@/a/(\d+)/(\d+)\.@isU'],
                'url' => 'http://ent.qq.com/a/{1}/{2}.hdPic.js?time=0.941{mtime}'
            ],
            'atlas_preg' => [
                '__img_text' => "@\{'Name':'img', 'Content':(.+)\}\]\}\]\}@isU",
                'img' => "@'Name':'bigimgurl', 'Content':'[^']*', 'Attributes':[^,]*, 'Children':\[\{'Name':'[^']*', 'Content':'([^']+)'@isU",
                'text' => "@'Name':'cnt_article', 'Content':'[^']*', 'Attributes':[^,]*, 'Children':\[\{'Name':'[^']*', 'Content':'([^']*)'@isU"
            ]
        ],
        4 => [
            'name' => '电影',
            'url' => 'http://ent.qq.com/movie/',
            'tags' => ['娱乐', '影视'],
            'list_preg' => 2,
            'detail_preg' => 2
        ],
        5 => [
            'name' => '电影-图片',
            'url' => 'http://ent.qq.com/movie/',
            'tags' => ['娱乐', '影视'],
            'type' => 2,
            'list_preg' => 3,
            'detail_preg' => 3,
            'detail_type' => 3,
            'atlas_preg' => 3
        ],
        6 => [
            'name' => '电视剧',
            'url' => 'http://ent.qq.com/tv/',
            'tags' => ['娱乐', '影视'],
            'list_preg' => 2,
            'detail_preg' => 2
        ],
        7 => [
            'name' => '电视剧-图片',
            'url' => 'http://ent.qq.com/tv/',
            'tags' => ['娱乐', '影视'],
            'type' => 2,
            'list_preg' => 3,
            'detail_preg' => 3,
            'detail_type' => 3,
            'atlas_preg' => 3
        ],
        8 => [ //
            'name' => '旅游-达人游记',
            'url' => 'http://tags.open.qq.com/interface/tag/articles.php?callback=jQuery1820511{mtime}_{mtime}&p={page}&l=20&tag=%E6%B8%B8%E8%AE%B0&oe=gbk&ie=utf-8&site=ly&_={mtime}',
            'tags' => ['生活', '旅游'],
            'list_preg' => [

            ],
            'detail_preg' => [

            ]
        ],
    ],
    135 => [ //UC头条
        1 => [
            'name' => '娱乐',
            'url' => 'https://iflow.uczzd.cn/iflow/api/v1/channel/179223212?method=his&ftime={mtime}&count=20&summary=0&bid=999&m_ch=000&recoid={rand|10000|99999}{time}{rand|10000|99999}&uc_param_str=dnnivebichfrmintnwcpgieiwidsudpf&zzd_from=UCtoutiaoPC-iflow&app=UCtoutiaoPC-iflow&client_os=UCtoutiaoPC-iflow&fr=pc&_=&callback=superagentCallback{mtime}{rand|100|999}',
            'tags' => ['娱乐', '热点'],
            'list_preg' => [
                [
                    'reg' => '@\{"id":"([^"]+)","recoid":"[^"]*","title":"([^"]+)","subhead":"[^"]*","url":"[^"]*","hyperlinks":[^,]*,"strategy":[^,]*,"politics":[^,]*,"summary":"[^"]*","content":"[^"]*","thumbnails":\[(.*)\],"images":.*,"album":.*,"tags":\[(.*)\],@isU',
                    'field' => [1 => '__id', 2 => 'Title', 3 => 'Cover', 4 => 'Tags'],
                ],
                'Cover' => [
                    '__reg' => '@"url":"([^"]+)"@isU',
                    'field' => 1
                ],
                'replace' => [
                    'FromUrl' => [
                        'rep' => 'https://news.uc.cn/a_{__id}',
                        'keys' => ['__id'],
                    ],
                ]
            ],
            'detail_preg' => [
                '__Tags' => '@"([^"]+)"@isU',
                'Author' => '@<div class="sm-article-desc"><span>(.*)</span><span>.*</span></div>@isU',
                'ArticleTime' => '@<div class="sm-article-desc"><span>.*</span><span>(.*)</span></div>@isU',
                'Content' => '@<div class="sm-article-content">(.+)</div></div></div>@isU',
            ],
            'detail_type' => [
                'type_reg' => [
                    3 => '@<\!--\{video:0\}-->@isU'
                ],
                '_url' => '@"videos":\[\{"element":"[^"]*","url":"([^"]+)","index":"[^"]*","sign":"[^"]+"\}\]@isU',
                '_sign' => '@"videos":\[\{"element":"[^"]*","url":"[^"]+","index":"[^"]*","sign":"([^"]+)"\}\]@isU',
                'video_url' => [
                    'rep' => 'https://iflow.uczzd.cn/iflow/api/v1/article/video/parse?uc_param_str=dnnivebichfrmintnwcpgieiwidsudpf&app=UCtoutiaoPC-iflow&sn=6622123839553410000&pageUrl={_url}&url_sign={_sign}&fr=pc&callback=superagentCallback{mtime}{rand|10|99}',
                    'keys' => ['_url', '_sign'],
                ],
                'video_fun' => 'getUcVideo', // uc头条视频下载
            ]
        ],
        2 => [
            'name' => '健康',
            'url' => 'https://iflow.uczzd.cn/iflow/api/v1/channel/472933935?method=his&ftime={mtime}&count=20&summary=0&bid=999&m_ch=000&recoid={rand|10000|99999}{time}{rand|10000|99999}&uc_param_str=dnnivebichfrmintnwcpgieiwidsudpf&zzd_from=UCtoutiaoPC-iflow&app=UCtoutiaoPC-iflow&client_os=UCtoutiaoPC-iflow&fr=pc&_=&callback=superagentCallback{mtime}{rand|100|999}',
            'tags' => ['健康', '保健'],
            'list_preg' => 1,
            'detail_preg' => 1,
            'detail_type' => 1
        ],
        3 => [
            'name' => '时尚',
            'url' => 'https://iflow.uczzd.cn/iflow/api/v1/channel/1213442674?method=his&ftime={mtime}&count=20&summary=0&bid=999&m_ch=000&recoid={rand|10000|99999}{time}{rand|10000|99999}&uc_param_str=dnnivebichfrmintnwcpgieiwidsudpf&zzd_from=UCtoutiaoPC-iflow&app=UCtoutiaoPC-iflow&client_os=UCtoutiaoPC-iflow&fr=pc&_=&callback=superagentCallback{mtime}{rand|100|999}',
            'tags' => ['时尚', '流行'],
            'list_preg' => 1,
            'detail_preg' => 1,
            'detail_type' => 1
        ],
        4 => [
            'name' => '美食',
            'url' => 'https://iflow.uczzd.cn/iflow/api/v1/channel/10000?method=his&ftime={mtime}&count=20&summary=0&bid=999&m_ch=000&recoid={rand|10000|99999}{time}{rand|10000|99999}&uc_param_str=dnnivebichfrmintnwcpgieiwidsudpf&zzd_from=UCtoutiaoPC-iflow&app=UCtoutiaoPC-iflow&client_os=UCtoutiaoPC-iflow&fr=pc&_=&callback=superagentCallback{mtime}{rand|100|999}',
            'tags' => ['美食', '烹饪'],
            'list_preg' => 1,
            'detail_preg' => 1,
            'detail_type' => 1
        ],
        5 => [
            'name' => '旅游',
            'url' => 'https://iflow.uczzd.cn/iflow/api/v1/channel/1972619079?method=his&ftime={mtime}&count=20&summary=0&bid=999&m_ch=000&recoid={rand|10000|99999}{time}{rand|10000|99999}&uc_param_str=dnnivebichfrmintnwcpgieiwidsudpf&zzd_from=UCtoutiaoPC-iflow&app=UCtoutiaoPC-iflow&client_os=UCtoutiaoPC-iflow&fr=pc&_=&callback=superagentCallback{mtime}{rand|100|999}',
            'tags' => ['生活', '旅游'],
            'list_preg' => 1,
            'detail_preg' => 1,
            'detail_type' => 1
        ],
        6 => [
            'name' => '育儿',
            'url' => 'https://iflow.uczzd.cn/iflow/api/v1/channel/408250330?method=his&ftime={mtime}&count=20&summary=0&bid=999&m_ch=000&recoid={rand|10000|99999}{time}{rand|10000|99999}&uc_param_str=dnnivebichfrmintnwcpgieiwidsudpf&zzd_from=UCtoutiaoPC-iflow&app=UCtoutiaoPC-iflow&client_os=UCtoutiaoPC-iflow&fr=pc&_=&callback=superagentCallback{mtime}797',
            'tags' => ['育儿', '幼儿期', '幼儿护理'],
            'list_preg' => 1,
            'detail_preg' => 1,
            'detail_type' => 1
        ],
        7 => [
            'name' => '搞笑',
            'url' => 'https://iflow.uczzd.cn/iflow/api/v1/channel/10013?method=his&ftime={mtime}&count=20&summary=0&bid=999&m_ch=000&recoid={rand|10000|99999}{time}{rand|10000|99999}&uc_param_str=dnnivebichfrmintnwcpgieiwidsudpf&zzd_from=UCtoutiaoPC-iflow&app=UCtoutiaoPC-iflow&client_os=UCtoutiaoPC-iflow&fr=pc&_=&callback=superagentCallback{mtime}{rand|100|999}',
            'tags' => ['搞笑', 'UC头条'],
            'list_preg' => 1,
            'detail_preg' => 1,
            'detail_type' => 1
        ],
        8 => [
            'name' => '视频',
            'url' => 'https://iflow.uczzd.cn/iflow/api/v1/channel/10016?method=his&ftime={mtime}&count=20&summary=0&bid=999&m_ch=000&recoid={rand|10000|99999}{time}{rand|10000|99999}&uc_param_str=dnnivebichfrmintnwcpgieiwidsudpf&zzd_from=UCtoutiaoPC-iflow&app=UCtoutiaoPC-iflow&client_os=UCtoutiaoPC-iflow&fr=pc&_=&callback=superagentCallback{mtime}{rand|100|999}',
            'tags' => ['视频'],
            'list_preg' => 1,
            'detail_preg' => 1,
            'detail_type' => 1
        ],
    ]
];