<?php
/**
 * 书本搜索
 */

namespace Model\Search;

use Bare\DB;
use Bare\Queue;

class BookSearch
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
    const TOP_VIEW = 'viewcount';
    const TOP_LIKE = 'likecount';
    const TOP_FINISH = 'finish';
    const TOP_FAVORITE = 'favoritecount';
    /**
     * 搜索字段
     */
    const SEARCH_FIELDS = [
        'BookId' => ['int', 'id'],
        'BookName' => ['string', 'bookname'],
        'Author' => ['string', 'author'],
        'Type' => ['int', 'type'],
        'TypeName' => ['string', 'typename'],
        'BookDesc' => ['string', 'description'],
        'Words' => ['int', 'words'],
        'ViewCount' => ['int', 'viewcount'],
        'LikeCount' => ['int', 'likecount'],
        'FavoriteCount' => ['int', 'favoritecount'],
        'CreateTime' => ['string', 'createtime'],
        'UpdateTime' => ['string', 'updatetime'],
        'Status' => ['int', 'status'],
        'IsFinish' => ['int', 'finish'],
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
                "filtered" => [
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
                "filtered" => [
                    'filter' => [
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
                    ]
                ]
            ],
            "sort" => [
                [
                    "{$type}" => [
                        "order" => "desc",
                        "mode" => "max"
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
            case 'finish':
                $query['query']['filtered']['filter']['bool']['must'][] = [
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
     * 新增一条书本时, 同步数据 （队列）
     *
     * @param array $row 所有字段必选, 见 self::SEARCH_FIELDS 定义
     * @throws \Exception
     * @return bool
     */
    public static function addBook(array $row): bool
    {
        if (count(array_diff_key(self::SEARCH_FIELDS, $row)) > 0) {
            throw new \Exception('Fields miss');
        }

        $data = [];
        foreach (self::SEARCH_FIELDS as $k => $v) {
            $t = $row[$k];
            switch ($v[0]) {
                case 'int':
                    $t = (int)$t;
                    break;
                case 'string':
                    $t = (string)$t;
                    break;
            }
            $data[$v[1]] = $t;
        }

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
     * @param array $row     任选至少一个数据, 见 self::SEARCH_FIELDS 定义
     * @return bool
     */
    public static function updateBook(int $book_id, array $row): bool
    {
        $data = [];
        foreach (self::SEARCH_FIELDS as $k => $v) {
            if (isset($row[$k])) {
                $t = $row[$k];
                switch ($v[0]) {
                    case 'int':
                        $t = (int)$t;
                        break;
                    case 'string':
                        $t = (string)$t;
                        break;
                }
                $data[$v[1]] = $t;
            }
        }

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

            $t_body = [];
            foreach (self::SEARCH_FIELDS as $k => $v) {
                if ($k == 'BookId') {
                    continue;
                }
                $t = $row[$k];
                switch ($v[0]) {
                    case 'int':
                        $t = (int)$t;
                        break;
                    case 'string':
                        $t = (string)$t;
                        break;
                }
                $t_body[$v[1]] = $t;
            }

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