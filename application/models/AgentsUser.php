<?php


use Illuminate\Database\Eloquent\Model;

/**
 * class AgentsUserModel
 *
 * @property int $id 
 * @property string $username 用户名
 * @property int $root_id 顶级ID
 * @property int $parent_id 上级ID
 * @property string $password 密码
 * @property string $channel_name 渠道名
 * @property int $rate 提成比例
 * @property int $rate_gold 金币提成
 * @property string $phone 联系号码
 * @property int $agent_level 代理等级
 * @property string $channel 渠道号
 * @property string $balance 账户余额
 * @property string $total 累计金额
 * @property string $exchnage 累计提现金额
 * @property int $status 状态 0 禁用 1启用
 * @property string $aff 91 推广码
 * @property int $audit 审计账号
 * @property string $last_login_time 最后登录时间
 * @property string $last_login_ip 最后登录IP
 * @property string $extended_data 扩展数据
 * @property string $created_at 
 * @property string $updated_at 
 * @property int $web_stat
 *
 * @author xiongba
 * @date 2020-03-04 17:22:35
 *
 * @mixin \Eloquent
 */
class AgentsUserModel extends Model
{

    protected $table = "agents_user";

    protected $primaryKey = 'id';

    protected $fillable = ['username', 'root_id', 'parent_id', 'password', 'channel_name', 'rate', 'rate_gold', 'phone', 'agent_level', 'channel', 'balance', 'total', 'exchnage', 'status', 'aff', 'audit', 'last_login_time', 'last_login_ip', 'extended_data', 'created_at', 'updated_at','web_stat'];

    protected $guarded = 'id';

    public $timestamps = false;

    /**
     * 验证渠道合法性 每天一次  过期设置
     *
     * @param $channel
     * @return mixed
     */
    static function verifyChan($channel)
    {
        $channel = htmlspecialchars($channel);
        return cached('chan:' . $channel)->expired(86400)->serializerJSON()->fetch(function () use ($channel) {
            $data =  self::where('channel', '=', $channel)->first();
            return is_null($data)?[]:$data->toArray();
        });
    }

    /**
     * 渠道列表 每5分钟更新一次  过期设置
     *
     * @param $channel
     * @return mixed
     */
    static function getChanDataList()
    {
        return cached('chanlist')->expired(300)->serializerJSON()->fetch(function () {
            $rows = self::query()->get(['id', 'channel']);
            if (is_null($rows)) {
                return [];
            }
            $data = $rows->toArray();
            return array_column($data, 'channel');
        });
    }

    /**
     * 验证渠道aff的合法性
     *
     * @param code
     * @return mixed
     */
    static function verifyChanAff($code)
    {
        $code = htmlspecialchars($code);
        $aff = (int)get_num($code);
        return cached(sprintf('chan:aff:%s', $aff))
            ->fetchJson(function () use ($aff) {
                return self::where('aff', '=', $aff)->exists();
            });
    }

    public static function getChannelByUsername($username)
    {
        $username = htmlspecialchars($username);
        return cached(sprintf('agent:username:%s', $username))
            ->fetchJson(function () use ($username) {
                $data =  self::where('username', '=', $username)->first();
                if (empty($data)){
                    return [];
                }
                $data = $data->toArray();
                return [
                    'chan' => $data['channel'],
                    'invite_code' => generate_code($data['aff'])
                ];
            });
    }

    public static function getChannelByUsernameYac($username)
    {
        $username = htmlspecialchars($username);
        return yac()->fetch("report-chan" . $username,function () use ($username) {
            $data =  self::where('username', '=', $username)->first();
            if (empty($data)){
                return [];
            }
            $data = $data->toArray();
            return [
                'chan' => $data['channel'],
                'invite_code' => generate_code($data['aff'])
            ];
        });
    }

    public static function getChannelByAffYac($aff)
    {
        return yac()->fetch("report-chan-aff-" . $aff,function () use ($aff) {
            $data =  self::where('aff', '=', $aff)->first();
            if (empty($data)){
                return [];
            }
            $data = $data->toArray();
            return [
                'chan' => $data['channel'],
                'invite_code' => generate_code($data['aff'])
            ];
        });
    }

    public static function getUsernameByAff($aff)
    {
        return yac()->fetch("agent-by-aff:" . $aff, function () use ($aff) {
            /** @var self $data */
            $data =  self::where('aff', '=', $aff)->first();
            if (empty($data)){
                return '';
            }
            return $data->username;
        }, 300);
    }
    
}
