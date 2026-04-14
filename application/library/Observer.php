<?php


class Observer
{

    public static function boot($config)
    {
        foreach ($config as $class => $observer) {
            self::register($class, $observer);
        }
    }

    public static function register($modelClass, $observer)
    {
        if (method_exists($modelClass, 'observe')) {
            $modelClass::observe($observer);
        }
    }


}