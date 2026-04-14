<?php


namespace App\console;


use App\console\Library\Color;

abstract class AbstractConsole
{


    public $name;

    public $description;

    abstract public function process($argc, $argv);


    protected function log()
    {

        $args = func_get_args();
        $color = array_pop($args);

        if (!($color instanceof Color)) {
            $args[] = $color;
        }

        if (func_num_args()) {
            if ($color instanceof Color) {
                echo "\033[" . $color->getBgColor() . ";" . $color->getColor() . ";5m";
            }
            echo sprintf(" [%s] %s", date('Y-m-d H:i:s'), join(" ", $args));
            if ($color instanceof Color) {
                echo " \033[0m";
            }
            echo " \r\n";
        } else {
            echo "";
        }

    }


    protected function logWarning()
    {
        $args = func_get_args();
        $args[] = new Color(Color::BROWN);
        call_user_func_array([$this, 'log'], $args);
    }

    protected function logError()
    {
        $args = func_get_args();
        $args[] = new Color(Color::WHITE, Color::RED);
        call_user_func_array([$this, 'log'], $args);
    }

    protected function logSuccess()
    {
        $args = func_get_args();
        $args[] = new Color(Color::GREEN);
        call_user_func_array([$this, 'log'], $args);
    }

    /**
     * 进度条
     * @param $total
     * @param $cur
     * @param string $string
     * @author xiongba
     * @date 2019-12-24 10:43:59
     */
    protected function logProgress($total, $cur, $string = '')
    {
        $color = new Color(Color::BROWN, Color::BLACK);
        $grid = 100;
        if ($cur > $total) {
            $cur = $total;
        }

        $rate = $cur / $total;

        $curRate = ceil($rate * $grid);
        echo "\033[" . $color->getBgColor() . ";" . $color->getColor() . ";5m";
        echo sprintf(" [%s] ", date('Y-m-d H:i:s'));
        echo "[ ", str_repeat('#', $curRate), str_repeat(' ', $grid - $curRate), "] ";
        echo sprintf("%2d%%", $curRate);
        if (!empty($string)) {
            echo ' ', $string;
        }
        echo " \033[0m\r";
        if ($total <= $cur) {
            echo "\r\n";
        }
    }

}