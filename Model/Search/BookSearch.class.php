<?php
/**
 * 书本搜索
 */

namespace Model\Search;

use Bare\Queue;

class BookSearch extends SearchBase
{
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
        'BookId' => [self::T_INT, self::PRIMARY_KEY],
        'BookName' => [self::T_STRING, 'bookname'],
        'Author' => [self::T_STRING, 'author'],
        'Type' => [self::T_INT, 'type'],
        'TypeName' => [self::T_STRING, 'typename'],
        'BookDesc' => [self::T_STRING, 'description'],
        'Words' => [self::T_INT, 'words'],
        'ViewCount' => [self::T_INT, 'viewcount'],
        'LikeCount' => [self::T_INT, 'likecount'],
        'FavoriteCount' => [self::T_INT, 'favoritecount'],
        'CreateTime' => [self::T_STRTOTIME, 'createtime'],
        'UpdateTime' => [self::T_STRTOTIME, 'updatetime'],
        'Status' => [self::T_INT, 'status'],
        'IsFinish' => [self::T_INT, 'finish'],
    ];

    /**
     * 搜索书本
     *
     * @param string $keywords 搜索词
     * @param int    $offset   偏移量
     * @param int    $limit    每页数量
     * @return array ['total' => 总数, 'data' => [书本ID, ...]]
     */
    public static function searchBook(string $keywords, int $offset = 0, int $limit = 10): array
    {
        $query = [
            "query" => [
                "bool" => [
                    "must" => [
                        [
                            "multi_match" => [
                                "query" => $keywords,
                                "type" => "best_fields",
                                "fields" => [
                                    "bookname^2",
                                    "author^1",
                                    "typename^0.2",
                                    "description^0.2"
                                ]
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
            "_source" => "_id",
            "from" => $offset,
            "size" => $limit
        ];

        $ret = self::query($query);

        $ids = [];
        $total = empty($ret['hits']['total']) ? 0 : $ret['hits']['total'];
        if ($ret !== false) {
            foreach ($ret['hits']['hits'] as $v) {
                $ids[$v['_id']] = $v['_id'];
            }
        }

        return ['total' => $total, 'data' => $ids];

    }

    /**
     * 查询书本阅读、推荐排行
     *
     * @param string $type   查询类型
     * @param int    $offset 偏移量
     * @param int    $limit  每页数量
     * @return array ['total' => 总数, 'data' => [书本ID, ...]]
     */
    public static function getBookTop(string $type = self::TOP_VIEW, int $offset = 0, int $limit = 10): array
    {
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
            "_source" => "_id",
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
        $ids = [];
        $total = empty($ret['hits']['total']) ? 0 : $ret['hits']['total'];
        if ($ret !== false) {
            foreach ($ret['hits']['hits'] as $v) {
                $ids[$v['_id']] = $v['_id'];
            }
        }

        return ['total' => $total, 'data' => $ids];
    }

    /**
     * 通过标签查询书本
     *
     * @param string $typename 标签ID
     * @param int    $offset   偏移量
     * @param int    $limit    每页数量
     * @return array ['total' => 总数, 'data' => [书本ID, ...]]
     */
    public static function getBookByTypeName(string $typename, int $offset = 0, int $limit = 10): array
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
            "_source" => "_id",
            "from" => $offset,
            "size" => $limit
        ];

        $ret = self::query($query);
        $ids = [];
        $total = empty($ret['hits']['total']) ? 0 : $ret['hits']['total'];
        if ($ret !== false) {
            foreach ($ret['hits']['hits'] as $v) {
                $ids[$v['_id']] = $v['_id'];
            }
        }

        return ['total' => $total, 'data' => $ids];
    }

    /**
     * 阅读量修改 （队列）
     *
     * @param int $book_id 书本ID
     * @param int $num
     * @return bool
     */
    public static function setViewCount(int $book_id, int $num = 1): bool
    {
        $data = [];
        $ret = true;
        if ($num != 0) {
            $data['type'] = 'ViewCount';
            $data[self::PRIMARY_KEY] = $book_id;
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
    public static function setLikeCount(int $book_id, int $num = 1): bool
    {
        $data = [];
        $ret = true;
        if ($num != 0) {
            $data['type'] = 'LikeCount';
            $data[self::PRIMARY_KEY] = $book_id;
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
        parent::buildSearch($data, 'BookId', $ver);
    }
}