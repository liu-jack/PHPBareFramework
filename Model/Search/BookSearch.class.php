<?php
/**
 * 书本搜索
 */

namespace Model\Search;

use Bare\DB;
use Bare\Queue;

class BookSearch extends SearchBase
{
    /**
     * 搜索位置
     */
    const BOOK_SEARCH = '29shu_book/list/';

    /**
     * 搜索名称
     */
    const BOOK_SEARCH_INDEX = '29shu_book';
    /**
     * 排行类型
     */
    const TOP_VIEW = 'view_count';
    const TOP_LIKE = 'like_count';
    const TOP_FINISH = 'finish';
    const TOP_FAVORITE = 'favorite_count';
    /**
     * 搜索字段
     */
    public static $_search_fields = [
        'BookId' => [self::FIELD_TYPE_INT, 'id'],
        'BookName' => [self::FIELD_TYPE_STRING, 'book_name'],
        'Author' => [self::FIELD_TYPE_STRING, 'author'],
        'Type' => [self::FIELD_TYPE_INT, 'type'],
        'TypeName' => [self::FIELD_TYPE_STRING, 'typename'],
        'BookDesc' => [self::FIELD_TYPE_STRING, 'description'],
        'Words' => [self::FIELD_TYPE_INT, 'words'],
        'ViewCount' => [self::FIELD_TYPE_INT, 'view_count'],
        'LikeCount' => [self::FIELD_TYPE_INT, 'like_count'],
        'FavoriteCount' => [self::FIELD_TYPE_INT, 'favorite_count'],
        'CreateTime' => [self::FIELD_TYPE_STRTOTIME, 'create_time'],
        'UpdateTime' => [self::FIELD_TYPE_STRTOTIME, 'update_time'],
        'Status' => [self::FIELD_TYPE_INT, 'status'],
        'IsFinish' => [self::FIELD_TYPE_INT, 'finish'],
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
                                    "book_name^2",
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
                    "update_time" => [
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
                    "must" => [
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
                    "update_time" => [
                        "order" => "desc"
                    ]
                ]
            ],
            "_source" => "_id",
            "from" => $offset,
            "size" => $limit
        ];

        switch ($type) {
            case 'finish':
                $query['query']['bool']['must'][] = [
                    "range" => [
                        "finish" => [
                            "gte" => 1
                        ]
                    ]
                ];
                unset($query['sort'][0]);
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
                    "update_time" => [
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
     * 新增一条书本时, 同步数据 （队列）
     *
     * @param array $row 所有字段必选, 见 self::$_search_fields 定义
     * @throws \Exception
     * @return bool
     */
    public static function addBook(array $row): bool
    {
        $data = self::checkFields($row, true);

        $ret = Queue::add("SearchBook", [
            'type' => 'add',
            'data' => $data
        ]);

        return $ret;
    }

    /**
     * 书本更新时, 同步数据 （队列）
     *
     * @param int   $book_id 书本ID
     * @param array $row     任选至少一个数据, 见 self::$_search_fields 定义
     * @return bool
     */
    public static function updateBook(int $book_id, array $row): bool
    {
        $data = self::checkFields($row);

        $ret = true;
        if (count($data) > 0) {
            $data['id'] = $book_id;
            $ret = Queue::add("SearchBook", [
                'type' => 'update',
                'data' => $data
            ]);
        }

        return $ret;
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
            $data['id'] = $book_id;
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
            $data['id'] = $book_id;
            $data['num'] = $num;
            $ret = Queue::add("UpdateCount", $data);
        }

        return $ret;
    }

    /**
     * 导入搜索数据
     *
     * @param $data
     */
    public static function insertSearch($data)
    {
        $head = "{\"index\":{\"_index\":\"" . self::BOOK_SEARCH_INDEX . "\",\"_type\":\"list\",\"_id\":\"{id}\"}}";
        $query = "";
        foreach ($data as $row) {
            $t_head = str_replace('{id}', $row['BookId'], $head);
            $query .= $t_head . "\n";
            if (strtotime($row['UpdateTime']) <= 0) {
                $row['UpdateTime'] = $row['CreateTime'];
            }
            $t_body = self::checkFields($row);

            $query .= json_encode($t_body) . "\n";
        }

        $es = DB::search(DB::SEARCH_DEFAULT);
        $ret = $es->query("_bulk", $es::HTTP_POST, $query);
        if ($ret === false) {
            echo json_encode($es->getLastError()) . "\n";
        }
    }

    /**
     * 执行搜索
     *
     * @param $query
     * @return mixed
     */
    public static function query($query)
    {
        $es = DB::search(DB::SEARCH_DEFAULT);

        return $es->query(self::BOOK_SEARCH . '_search', $es::HTTP_POST, $query);
    }

    /**
     * 添加
     *
     * @param $data
     */
    public static function add($data)
    {
        $query = self::BOOK_SEARCH . $data['id'];
        $id = $data['id'];
        unset($data['id']);
        $es = DB::search(DB::SEARCH_DEFAULT);
        $ret = $es->query($query, $es::HTTP_PUT, $data);
        if ($ret === false) {
            logs([
                'query' => $query,
                'data' => $data,
                'id' => $id
            ], 'Search/SearchBook');
        }
    }

    /**
     * 更新
     *
     * @param $data
     */
    public static function update($data)
    {
        $query = self::BOOK_SEARCH . $data['id'] . '/_update';
        $id = $data['id'];
        unset($data['id']);
        $es = DB::search(DB::SEARCH_DEFAULT);
        $ret = $es->query($query, $es::HTTP_POST, ['doc' => $data]);
        if ($ret === false) {
            logs([
                'query' => $query,
                'data' => $data,
                'id' => $id
            ], 'Search/SearchBook');
        }
    }

    /**
     * 删除
     *
     * @return mixed
     */
    public static function delete()
    {
        $query = self::BOOK_SEARCH_INDEX;
        $es = DB::search(DB::SEARCH_DEFAULT);

        return $es->query($query, $es::HTTP_DELETE);
    }
}