<?php


class CacheKeysModel extends EloquentModel
{
    protected $table = 'cache_keys';

    protected $fillable = [
        'name',
        'key'
    ];


    /**
     * 保存缓存key
     * @param string $key
     * @param string $memo
     */
    public static function createOrEdit($key, $memo)
    {
        $data = [
            'name' => $memo,
            'key'  => $key,
        ];
        CacheKeysModel::updateOrCreate($data);
    }

}