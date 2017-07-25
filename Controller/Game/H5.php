<?php

namespace Controller\Game;

use Bare\Controller;

/**
 * H5游戏
 */
class H5 extends Controller
{
    /**
     * 首页
     */
    public function index()
    {
        $list = self::getGameList();
        $seo = [
            'title' => 'H5小游戏',
            'key' => implode('，', $list) . '，好玩的H5小游戏，有趣的H5小游戏',
            'desc' => implode('H5小游戏，', $list) . 'H5小游戏。',
        ];
        $this->value('seo', $seo);
        $this->value('list', $list);
        $this->view();
    }

    /**
     * 游戏页
     * @param $name
     * @param $arguments
     * @throws \Exception
     */
    public function __call($name, $arguments)
    {
        $list = self::getGameList();
        if (isset($list[$name])) {
            $seo = [
                'title' => $list[$name] . 'H5小游戏',
                'key' => $list[$name] . 'H5小游戏，好玩的H5小游戏，有趣的H5小游戏',
                'desc' => $list[$name] . 'H5小游戏，好玩的H5小游戏，有趣的H5小游戏。',
            ];
            $this->value('seo', $seo);
            $this->view('Game/H5/' . $name);
        } else {
            throw new \Exception("the game don't exists");
        }
    }

    /**
     * 获取游戏列表
     * @return array
     */
    private static function getGameList()
    {
        $gamelist = config('game/h5');
        return !empty($gamelist) ? $gamelist : [];
    }
}
