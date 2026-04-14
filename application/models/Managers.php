<?php

/**
 * class BkManagersModel
 *
 * @property int $uid
 * @property string $oauth_type 设备'ios','android','android_rn','ios_rn'
 * @property string $oauth_id
 * @property string $uuid
 * @property string $username
 * @property string $password
 * @property int $role_id
 * @property string $role_type
 * @property int $gender
 * @property string $regip
 * @property int $regdate
 * @property string $lastip
 * @property int $lastvisit
 * @property int $expired_at 会员到期时间
 * @property int $lastpost
 * @property int $oltime 在线小时数
 * @property int $login_task 是否领取7日任务
 * @property int $pageviews 论坛用的,电影可以不用
 * @property int $score 用户积分
 * @property string $aff 邀请码md5( md5(uuid) )
 * @property int $invited_by 被谁 aff 邀请
 * @property int $invited_num 已邀请安装个数
 * @property int $newpm
 * @property int $new_comment_reply
 * @property int $new_topic_reply
 * @property int $login_count
 * @property string $app_version app版本号
 * @property int $validate
 * @property string $secret 状态
 * @property string $flag 状态
 * @author xiongba
 * @date 2020-02-27 14:59:11
 *
 * @mixin \Eloquent
 */
class ManagersModel extends EloquentModel
{
    protected $table = 'bk_managers';
    public static $tableName = 'bk_managers';


    protected $primaryKey = 'uid';

    protected $fillable = [
        'oauth_type',
        'oauth_id',
        'uuid',
        'username',
        'password',
        'role_id',
        'role_type',
        'gender',
        'regip',
        'regdate',
        'lastip',
        'lastvisit',
        'expired_at',
        'lastpost',
        'oltime',
        'login_task',
        'pageviews',
        'score',
        'aff',
        'invited_by',
        'invited_num',
        'newpm',
        'new_comment_reply',
        'new_topic_reply',
        'login_count',
        'app_version',
        'validate',
        'secret',
        'flag'
    ];
    const EXT_OPERATOR = [106];//外部运营账号 只查看广告数据相关
    public $timestamps = false;

    const ROLE_TYPE_ADMIN = 'admin';
    const ROLE_TYPE_NORMAL = 'normal';

    const STATUS_SUCCESS = 1;
    const STATUS_FAIL = 0;
    const STATUS = [
        self::STATUS_FAIL    => '禁用',
        self::STATUS_SUCCESS => '启用',
    ];
}