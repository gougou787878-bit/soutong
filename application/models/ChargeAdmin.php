<?php


use Illuminate\Database\Eloquent\Model;

/**
 * class ChargeAdminModel
 *
 * @property int $id 
 * @property int $touid 充值对象ID
 * @property int $coin 钻石数
 * @property int $addtime 添加时间
 * @property string $admin 管理员
 * @property string $ip IP
 * @property int $type 
 * @property string $des 
 *
 * @author xiongba
 * @date 2021-05-04 11:45:05
 *
 * @mixin \Eloquent
 */
class ChargeAdminModel extends Model
{

    protected $table = "charge_admin";

    protected $primaryKey = 'id';

    protected $fillable = ['touid', 'coin', 'addtime', 'admin', 'ip', 'type', 'des'];

    protected $guarded = 'id';

    public $timestamps = false;
    public function member()
    {
        return $this->hasOne(MemberModel::class,'uid','touid');
    }

    const TYPE_COIN_ADD = 1;
    const TYPE_COIN_SUB = 2;
    const TYPE_MSG_ADD = 3;
    const TYPE_MSG_SUB = 7;
    const TYPE_TICKET_ADD = 4;
    const TYPE_GAME_ADD = 5;
    const TYPE_GAME_SUB = 6;
    const TYPE_PROXY_ADD = 8;
    const TYPE_PROXY_SUB = 9;
    const TYPE_POST_ADD = 10;
    const TYPE_POST_SUB = 11;
    const TYPE = [
        self::TYPE_COIN_ADD   => '加币',
        self::TYPE_COIN_SUB   => '减币',
        self::TYPE_MSG_ADD    => '加消息',
        self::TYPE_MSG_SUB    => '减消息',
        self::TYPE_TICKET_ADD => '加影券',
        self::TYPE_GAME_ADD   => '游戏加额',
        self::TYPE_GAME_SUB   => '游戏减额',
        self::TYPE_PROXY_ADD  => '代理提现加额',
        self::TYPE_PROXY_SUB  => '代理提现减额',
        self::TYPE_POST_ADD   => '社区提现加额',
        self::TYPE_POST_SUB   => '社区提现减额'
    ];



}
