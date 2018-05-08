<?php
/**
 * 书本搜索
 */

namespace Model\Search;

use Bare\Queue;

class BookSearch extends SearchBase
{
    // 数据库主键
    protected static $_primary_key = 'BookId';
    // 搜索位置
    protected static $_search_index = '29shu_book/list/';
    // 搜索名称
    protected static $_search_index_prefix = '29shu_book';
    // 搜索队列名称
    protected static $_search_queue = 'SearchBook';

    /**
     * 排行类型
     */
    const TOP_VIEW = 'viewcount';
    const TOP_LIKE = 'likecount';
    const TOP_FINISH = 'finish';
    const TOP_FAVORITE = 'favoritecount';
    /**
     * 搜索字段
     */
    public static $_search_fields = [
        'BookId' => [self::T_INT, self::INDEX_KEY],
        'BookName' => [self::T_STRING, 'bookname'],
        'Author' => [self::T_STRING, 'author'],
        'Type' => [self::T_INT, 'type'],
        'TypeName' => [self::T_STRING, 'typename'],
        'BookDesc' => [self::T_STRING, 'description'],
        'Words' => [self::T_INT, 'words'],
        'ViewCount' => [self::T_INT, 'viewcount'],
        'LikeCount' => [self::T_INT, 'likecount'],
        'FavoriteCount' => [self::T_INT, 'favoritecount'],
        'CreateTime' => [self::T_STRING, 'createtime'],
        'UpdateTime' => [self::T_STRING, 'updatetime'],
        'Status' => [self::T_INT, 'status'],
        'IsFinish' => [self::T_INT, 'finish'],
    ];

    /**
     * 搜索书本
     *
     * @param string       $keywords 搜索词
     * @param int          $offset   偏移量
     * @param int          $limit    每页数量
     * @param string|array $fields   返回字段
     * @return array ['total' => 总数, 'data' => [书本ID, ...]]
     */
    public static function searchBook($keywords, $offset = 0, $limit = 10, $fields = [])
    {
        $fields = self::fields($fields);
        $query = [
            "query" => [
                "bool" => [
                    "must" => [
                        [
                            "multi_match" => [
                                "query" => $keywords,
                                "type" => "best_fields",
                                "fields" => [
                                    "bookname^5",
                                    "author^2",
                                    "typename",
                                    "description"
                                ],
                                'minimum_should_match' => '50%'
                            ]
                        ]
                    ],
                    "filter" => [
                        [
                            "term" => [
                                "status" => 1
                            ]
                        ]
                    ]
                ]
            ],
            "sort" => [
                [
                    "_score" => [
                        "order" => "desc"
                    ]
                ],
                [
                    "updatetime" => [
                        "order" => "desc"
                    ]
                ]
            ],
            "_source" => array_values($fields),
            "from" => $offset,
            "size" => $limit
        ];

        $ret = self::query($query);

        return self::result($ret, $fields);
    }

    /**
     * 查询书本阅读、推荐排行
     *
     * @param string       $type   查询类型
     * @param int          $offset 偏移量
     * @param int          $limit  每页数量
     * @param string|array $fields 返回字段
     * @return array ['total' => 总数, 'data' => [书本ID, ...]]
     */
    public static function getBookTop($type = self::TOP_VIEW, $offset = 0, $limit = 10, $fields = [])
    {
        $fields = self::fields($fields);
        $query = [
            "query" => [
                "bool" => [
                    "filter" => [
                        [
                            "term" => [
                                "status" => [
                                    "value" => 1
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            "sort" => [
                [
                    "{$type}" => [
                        "order" => "desc"
                    ]
                ],
                [
                    "updatetime" => [
                        "order" => "desc"
                    ]
                ]
            ],
            "_source" => array_values($fields),
            "from" => $offset,
            "size" => $limit
        ];

        switch ($type) {
            case self::TOP_FINISH:
                $query['query']['bool']['must'][] = [
                    "range" => [
                        self::TOP_FINISH => [
                            "gte" => 1
                        ]
                    ]
                ];
                $query['sort'] = [
                    [
                        "updatetime" => [
                            "order" => "desc"
                        ]
                    ]
                ];
                break;
        }

        $ret = self::query($query);

        return self::result($ret, $fields);
    }

    /**
     * 通过标签查询书本
     *
     * @param string       $typename 标签ID
     * @param int          $offset   偏移量
     * @param int          $limit    每页数量
     * @param string|array $fields   返回字段
     * @return array ['total' => 总数, 'data' => [书本ID, ...]]
     */
    public static function getBookByTypeName($typename, $offset = 0, $limit = 10, $fields = [])
    {
        $query = [
            "query" => [
                "bool" => [
                    "must" => [
                        [
                            "match" => [
                                "typename" => str_replace('小说', '', $typename)
                            ]
                        ],
                        [
                            "term" => [
                                "status" => [
                                    "value" => 1
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            "sort" => [
                [
                    "updatetime" => [
                        "order" => "desc"
                    ]
                ]
            ],
            "_source" => array_values($fields),
            "from" => $offset,
            "size" => $limit
        ];

        $ret = self::query($query);

        return self::result($ret, $fields);
    }

    /**
     * 阅读量修改 （队列）
     *
     * @param int $book_id 书本ID
     * @param int $num
     * @return bool
     */
    public static function setViewCount($book_id, $num = 1)
    {
        $data = [];
        $ret = true;
        if ($num != 0) {
            $data['type'] = 'ViewCount';
            $data[self::INDEX_KEY] = $book_id;
            $data['num'] = $num;
            $ret = Queue::add("UpdateCount", $data);
        }

        return $ret;
    }

    /**
     * 推荐数修改 （队列）
     *
     * @param int $book_id 书本ID
     * @param int $num
     * @return bool
     */
    public static function setLikeCount($book_id, $num = 1)
    {
        $data = [];
        $ret = true;
        if ($num != 0) {
            $data['type'] = 'LikeCount';
            $data[self::INDEX_KEY] = $book_id;
            $data['num'] = $num;
            $ret = Queue::add("UpdateCount", $data);
        }

        return $ret;
    }

    /**
     * 新建搜索数据
     *
     * @param        $data
     * @param string $ver
     */
    public static function insertSearch($data, $ver = '')
    {
        parent::buildSearch($data, $ver);
    }
}
