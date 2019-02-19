<?php

namespace Model\Test;

class Preg
{
    private $rep = [
        '标签1' => 1,
        '标签2' => 2,
        '标签3' => 3,
        '标签4' => 4,
        '标签5' => 5,
        '标签6' => 6,
    ];

    public function test()
    {
        $rep = $this->rep;
        $content = '标签1，标签2，标签3，标签4，标签5，标签6，测试';
        $pattern = '/(标签1|标签2|标签3|标签4|标签5|标签6)/isU';
        $text = preg_replace_callback($pattern, function ($match) use ($rep) {
            return $rep[$match[1]];
        }, $content);
//        $text = preg_replace_callback($pattern, [$this, 'replace'], $content);
        return $text;
    }

    public function replace($match)
    {
        $rep = $this->rep;
        return $rep[$match[1]];
    }
}