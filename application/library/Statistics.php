<?php


class Statistics
{

    static $time = [];
    static $memory = [];


    public static function startTime()
    {
        array_push(self::$time , self::microtime_float());
    }

    public static function endTime()
    {
        $time = array_pop(self::$time);
        if ($time !== false) {
            return sprintf("%.8f" , self::microtime_float() - $time);
        } else {
            return -1;
        }
    }

    protected static function microtime_float()
    {
        list($usec, $sec) = explode(" ", microtime());
        return ((float)$usec + (float)$sec);
    }


    public static function startMemory()
    {
        array_push(self::$memory , memory_get_usage());
    }

    public static function endMemory()
    {
        $memory = array_pop(self::$memory);
        if ($memory !== false) {
            return self::formatByte(memory_get_usage() - $memory);
        } else {
            return '-1';
        }
    }

    protected static function formatByte($num)
    {
        $p = 0;
        $format = 'bytes';
        if ($num > 0 && $num < 1024) {
            $p = 0;
            return number_format($num) . ' ' . $format;
        }
        if ($num >= 1024 && $num < pow(1024, 2)) {
            $p = 1;
            $format = 'KB';
        }
        if ($num >= pow(1024, 2) && $num < pow(1024, 3)) {
            $p = 2;
            $format = 'MB';
        }
        if ($num >= pow(1024, 3) && $num < pow(1024, 4)) {
            $p = 3;
            $format = 'GB';
        }
        if ($num >= pow(1024, 4) && $num < pow(1024, 5)) {
            $p = 3;
            $format = 'TB';
        }
        $num /= pow(1024, $p);
        return number_format($num, 3) . ' ' . $format;
    }

}