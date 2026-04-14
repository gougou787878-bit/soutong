<?php


namespace helper;


/**
 * Class OperateHelper
 * @package helper
 * @author xiongba
 */
class OperateHelper
{

    /**
     * 查询指定的值是否在范围类
     * @param $value
     * @param $start
     * @param $end
     * @return bool
     * @author xiongba
     */
    public static function between($value, $start, $end)
    {
        if ($value > $start && $value < $end) {
            return true;
        }
        return false;
    }

    public static function betweenIn($value, $start, $end)
    {
        if ($value >= $start && $value <= $end) {
            return true;
        }
        return false;
    }

    public static function betweenInToday($value)
    {
        return self::betweenInDay($value, date('Y-m-d'));
    }

    public static function betweenInDay($value, $date)
    {
        return self::betweenIn($value, strtotime($date), strtotime("$date 23:59:59"));
    }

    public static function betweenInDate($value, $start, $end)
    {
        return self::betweenIn($value, strtotime($start), strtotime($end." 23:59:59"));
    }

    public static function betweenInVer($currentVersion, $startVersion, $endVersion)
    {
        if (version_compare($currentVersion, $startVersion, '>=') &&
            version_compare($currentVersion, $endVersion, '<=')) {
            return true;
        }
        return false;
    }

    /**
     * @param string $currentVersion 指定版本
     * @param string $startVersion 范围开始的版本
     * @param string $endVersion 范围结束的版本
     * @return bool
     * @author xiongba
     */
    public static function inVer($currentVersion, $startVersion, $endVersion)
    {
        return self::betweenInVer($currentVersion, $startVersion, $endVersion);
    }


}