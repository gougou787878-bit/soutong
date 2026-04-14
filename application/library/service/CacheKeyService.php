<?php

namespace service;

class CacheKeyService
{
    const CACHE_ALL_GROUP = 'cache:all:group';

    public static function adder($name, $group)
    {
        $rs = json_encode(['name' => $name, 'group' => $group]);
        redis()->sAdd(self::CACHE_ALL_GROUP, $rs);
    }

    public static function all_group()
    {
        $all_group = redis()->sMembers(self::CACHE_ALL_GROUP);
        return collect($all_group)->map(function ($item) {
            if (!$item) {
                return NULL;
            }
            $rs = json_decode($item);
            return $rs ? $rs : NULL;
        })->filter()->values();
    }

    public static function clear_group($group)
    {
        cached('')->clearGroup($group);
    }
}