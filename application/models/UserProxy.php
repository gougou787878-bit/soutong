<?php

/**
 * class UserProxyModel
 *
 * @property int $aff 当前用户 aff 唯一标识(uid)
 * @property int $created_at 创建时间
 * @property int $id
 * @property int $proxy_level 当前用户代理层级，默认为顶级代理
 * @property string $proxy_node 当前代理层级，默认为顶级代理
 * @property int $root_aff 顶级代理唯一标识
 *
 * @mixin \Eloquent
 */
class UserProxyModel extends EloquentModel
{
    protected $table = 'user_proxy';

    const REDIS_PROXY_COUNT_DATA = 'proxy_count_data_';

    protected $fillable = [
        'root_aff', 'aff', 'proxy_level', 'proxy_node', 'created_at'
    ];
}