<?php


namespace App\console\Library;


class Color
{


    /**
     * 40 set black background
     * 41 set red background
     * 42 set green background
     * 43 set brown background
     * 44 set blue background
     * 45 set purple background
     * 46 Set Cyan Background
     * 47 set white background
     */

    const BLACK = 0;
    const RED = 1;
    const GREEN = 2;
    const BROWN = 3;
    const BLUE = 4;
    const PURPLE = 5;
    const CYAN = 6;
    const WHITE = 7;


    protected $color;
    protected $bgColor;

    public function __construct($color, $bgColor = 9)
    {
        $this->color = 30 + $color;
        $this->bgColor = 40 + $bgColor;
    }

    /**
     * @return int
     * @author xiongba
     */
    public function getColor(): int
    {
        return $this->color;
    }

    /**
     * @return int
     * @author xiongba
     */
    public function getBgColor(): int
    {
        return $this->bgColor;
    }


}

//
//for ($i = 30; $i < 39; $i++) {
//    echo sprintf("%06s", decbin($i)), "   ";
//}
//
//echo "\r\n";
//
//for ($i = 40; $i < 49; $i++) {
//    echo decbin($i), "   ";
//}
//echo "\r\n";