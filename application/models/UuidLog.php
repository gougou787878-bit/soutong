<?php


/**
 * class UuidLogModel
 *
 * @property int $created_at
 * @property int $id
 * @property int $new_invited_num 写入这条数据时候这个 new_invited_num 的历史数
 * @property string $new_uuid 用户唯一id
 * @property string $oauth_id
 * @property string $oauth_type
 * @property int $old_invited_num 写入这条数据时候这个 old_invited_num 的历史数
 * @property string $old_uuid 用户唯一id
 * @property int $uid
 *
 * @property MemberModel $withMember
 *
 * @author xiongba
 * @date 2020-03-07 17:29:07
 *
 * @mixin \Eloquent
 */
class UuidLogModel extends EloquentModel
{
    protected $table = 'uuid_log';

    protected $fillable = [
        'created_at',
        'new_invited_num',
        'new_uuid',
        'oauth_id',
        'oauth_type',
        'old_invited_num',
        'old_uuid',
        'uid'
    ];


    const OAUTH_TYPE_IOS = 'ios';
    const OAUTH_TYPE_ANDROID = 'android';
    const OAUTH_TYPE_ = '';
    const OAUTH_TYPE =[
        self::OAUTH_TYPE_IOS =>'iOS',
        self::OAUTH_TYPE_ANDROID =>'安卓',
        self::OAUTH_TYPE_ =>'未知',
    ];

    public function withMember()
    {
        return $this->hasOne(MemberModel::class, 'uid', 'uid');
    }
}