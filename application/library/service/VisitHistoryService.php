<?php


namespace service;


class VisitHistoryService
{

    private const KEY = '{visit:history:%s}:'; // 使用hash tag 保证 多个key 存在一个slot中
    private $uid;
    private static $all_history = null;

    public function __construct($uid)
    {
        $this->uid = (string)$uid;
    }

    private function genKey($suffix): string
    {
        return sprintf(self::KEY, $this->uid) . $suffix;
    }


    public function getAll(): array
    {
        if (self::$all_history === null) {
            $key1 = $this->genKey('visit');
            $key2 = $this->genKey('play');
            $key3 = $this->genKey('like');
            // 也可以直接使用 sUnion
            $ar1 = redis()->sMembers($key1);
            $ar2 = redis()->sMembers($key2);
            $ar3 = redis()->sMembers($key3);
            $ar = array_merge($ar1, $ar2, $ar3);
            self::$all_history = array_keys(array_flip($ar));
        }

        return self::$all_history;
    }

    public function addVisit(...$vid): int
    {
        $key = $this->genKey('visit');
        $result = redis()->sAddArray($key, $vid);
        redis()->expire($key, 86400 * 7);
        return $result;
    }

    public function delVisit(...$vid): int
    {
        $key = $this->genKey('visit');
        return redis()->sRem($key, ...$vid);
    }

    public function getVisit(): array
    {
        $key = $this->genKey('visit');
        return redis()->sMembers($key);
    }

    public function clearVisit(): int
    {
        $key = $this->genKey('visit');
        return redis()->del($key);
    }

    public function addPlay(...$vid): int
    {
        $key = $this->genKey('play');
        $result = redis()->sAddArray($key, $vid);
        redis()->expire($key, 86400 * 15);
        return $result;
    }

    public function delPlay(...$vid): int
    {
        $key = $this->genKey('play');
        return redis()->sRem($key, ...$vid);
    }


    public function getPlay(): array
    {
        $key = $this->genKey('play');
        return redis()->sMembers($key);
    }

    public function getPay(): array
    {
        $key = "mv_pay:list:" . $this->uid;
        return redis()->sMembers($key);
    }

    public function addLike(...$vid): int
    {
        $key = $this->genKey('like');
        $result = redis()->sAddArray($key, $vid);
        redis()->expire($key, 86400 * 15);
        return $result;
    }

    public function getLike(): array
    {
        $key = $this->genKey('like');
        return redis()->sMembers($key);
    }

    public function delLike($vid): int
    {
        $key = $this->genKey('like');
        return redis()->sRem($key, ...$vid);
    }


}