<?php


/**
 * Class Html
 *
 *
 * @method static string|\element\Element checkbox($name, $values, $default = [], array $attr = [])
 * @method static string|\element\Element textarea($name, $value, array $attr = [])
 * @method static string|\element\Element radioMulti($name, $values, $default = [], array $attr = [])
 * @method static string|\element\Element radio($name, $value, array $attr = [])
 * @method static string|\element\Element input($name, $value, $type = 'text', $placeholder = null, array $attr = [], $autocomplete = 'off')
 * @method static string|\element\Element select($name, $array, array $attr = [])
 * @method static string|\element\Element css($url)
 *
 * @author xiongba
 * @date 2020-01-01 19:57:17
 */
class Html
{

    public static function __callStatic($name, $arguments)
    {
        return (new \element\HtmlElement)->{$name}(...$arguments);
    }


    public static function search($name, $array, $attr = [])
    {
        return self::__callStatic('select', [$name, merge_array(['' => '全部'], $array), $attr]);
    }

}