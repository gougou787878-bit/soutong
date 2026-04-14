<?php


/**
 * class MembersLogModel
 *
 * @property int $id
 * @property string $uuid
 * @property string $oauth_type 设备类型
 * @property string $lastip
 * @property int $lastactivity
 * @property int $oltime 在线小时数
 * @property int $pageviews
 * @property string $app_version app版本号
 *
 * @author xiongba
 * @date 2020-02-28 18:01:16
 *
 * @mixin \Eloquent
 */
class MemberLogModel extends EloquentModel
{
    protected $table = "members_log";

    protected $primaryKey = 'id';

    protected $fillable = ['uuid', 'oauth_type', 'lastip', 'lastactivity', 'oltime', 'pageviews', 'app_version'];

    protected $guarded = 'id';

    public $timestamps = false;

    public static function createBy($uuid, $oauthType, $lastIp, $lastActivity, $appVersion)
    {
        try{
            $data = [
                'uuid'         => $uuid,
                'oauth_type'   => $oauthType,
                'lastip'       => $lastIp,
                'lastactivity' => $lastActivity,
                'app_version'  => $appVersion
            ];
            return self::create($data);
        }catch (Exception $e){
            //trigger_log("MemberLog:创建失败  {$uuid}");
            //trigger_log($e);
        }
        return self::useWritePdo()->where('uuid' , $uuid)->first();

    }
}