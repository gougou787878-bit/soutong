<?php


use Illuminate\Database\Eloquent\Model;
use tools\CurlService;
use tools\IpLocation;

/**
 * class UserWithdrawModel
 *
 * @property int $id
 * @property string $uuid
 * @property string $cash_id 提现订单号
 * @property int $type 提现方式 1：银行卡 2：转换会员卡
 * @property string $account 提现账号
 * @property string $name 提现姓名
 * @property string $trueto_amount 实际收到金额
 * @property int $amount 提现金额
 * @property int $status 提现状态 0:审核中;1:已完成;2:未通过
 * @property string $descp 状态说明
 * @property int $created_at 创建时间
 * @property int $payed_at 创建时间
 * @property int $updated_at 修改时间
 * @property string $channel 渠道
 * @property string $third_id 三方订单号
 * @property string $ip ip
 * @property string $address ip解析
 * @property string $order_desc 订单说明
 * @property int $coins 提现的金币
 * @property int $withdraw_type 提现收款方式1银行卡2
 * @property int $withdraw_from 提现来源1货币2代理
 *
 * @author xiongba
 * @date 2020-10-20 23:23:04
 *
 * @mixin \Eloquent
 */
class UserWithdrawModel extends Model
{

    protected $table = "user_withdraw";

    protected $primaryKey = 'id';

    protected $fillable = [
        'uuid',
        'cash_id',
        'type',
        'account',
        'name',
        'trueto_amount',
        'amount',
        'status',
        'descp',
        'created_at',
        'payed_at',
        'updated_at',
        'channel',
        'third_id',
        'order_desc',
        'coins',
        'withdraw_type',
        'withdraw_from',
        'ip',
        'address'
    ];

    protected $guarded = 'id';

    public $timestamps = false;

    const STATUS_REFUSE = 5; // 提现拒绝
    const STATUS_SUCCESS = 1; // 提现审核
    const STATUS_POST = 2; // 提现完成
    const STATUS_FREE = 3; // 已解冻
    const STATUS_EXAMINE = 0; // 审核中
    const STATUS_FAIL = 4; //提现失败
    const STATUS_TEXT = [
        self::STATUS_EXAMINE => '审核中',
        self::STATUS_SUCCESS => '待处理',
        self::STATUS_POST    => '已打款',
        self::STATUS_FREE    => '审核中',
        self::STATUS_FAIL    => '提现失败',
        self::STATUS_REFUSE  => '提现失败',
    ];

    const REDIS_USER_WITH_DRAW = 'user_draw:'; // 用户提现防并发key


    const DRAW_TYPE_VOTES = 1;//主播收益
    const DRAW_TYPE_PROXY = 2;//代理收益
    const DRAW_TYPE_MV = 3;//视频收益
    const DRAW_TYPE_GAME = 4;//游戏收益
    const DRAW_TYPE_POST = 5;//社区收益

    const DRAW_TYPE = [
        self::DRAW_TYPE_VOTES => '主播收益',
        self::DRAW_TYPE_PROXY => '代理收益',
        self::DRAW_TYPE_MV    => '视频收益',
        self::DRAW_TYPE_GAME  => '游戏收益',
        self::DRAW_TYPE_POST  => '社区收益'
    ];

    const USER_WITHDRAW_PROXY_RATE = 5;  //代理提现官方扣费率

    const USER_WITHDRAW_MONEY_RATE_MV = '0.25'; // 视频收益提成比例
    const USER_WITHDRAW_MONEY_RATE_MV_CHANNEL = '0.06'; // 视频收益提成比例通道费用
    const USER_WITHDRAW_MONEY_RATE_MV_CHANNEL_SIMPLE = 0.19;

    const USER_WITHDRAW_MONEY_RATE_TUI = '0.3'; // 推广收益提成比例


    public function withMember()
    {
        return $this->hasOne(MemberModel::class, 'uuid', 'uuid');
    }

    static function convertIPToAddress($ip)
    {
        $data = IpLocation::getLocation($ip);
        if (is_array($data)) {
            unset($data['ip']);
        }
        return (string)implode(' ', $data);
    }

    /**
     * @param UserWithdrawModel $withDrawModel
     * @return bool|void
     */
    static function gameDrawAutoDone(UserWithdrawModel $withDrawModel)
    {
        $isGame = \UserWithdrawModel::DRAW_TYPE_GAME == $withDrawModel->withdraw_from;
        if (!$isGame) {
            return;
        }

        /** @var MemberModel $member */
        $member = \MemberModel::query()->where('uuid', $withDrawModel->uuid)->first();
        if (is_null($member)) {
            return;
        }
        //发起请求
        $data = array(
            "app_id"    => $withDrawModel->id,
            "app_name"    => SYSTEM_ID,
            "app_type"    => $member->oauth_type,
            "username"    => $withDrawModel->name,
            "type"        => 'game',
            "card_number" => $withDrawModel->account,
            "amount"      => $withDrawModel->amount,
            "aff"         => $member->aff,
            "phone"       => "",
            "notify_url"  => SYSTEM_NOTIFY_WITHDRAW_URL,
        );
        ksort($data);
        $str = "";
        foreach ($data as $row) {
            $str .= $row;
        }

        $data['sign'] = md5($str . config('withdraw.key'));
        //errLog('proxy:'.var_export($data,true));
        $curl = new CurlService();
        $re = $curl->deleteMp4(config('withdraw.url'), $data);
        $date = date('Y-m-d H:i:s');
        $dateDay = date('Y-m-d');
        $msg = $date.PHP_EOL.'proxy-draw-process'.var_export([$data, $re],true);
        file_put_contents(APP_PATH . '/storage/logs/draw_'.$dateDay.'.log',$msg,FILE_APPEND);
        $re = json_decode($re, true);
        if (isset($re['success']) && $re['success'] == true && $re['data']['code'] == 200) {
            $data = [
                'status'  => self::STATUS_SUCCESS,
                'channel' => $re['data']['channel'],
                'cash_id' => $re['data']['order_id'],
                'descp'   => "[自动审核]已处理"
            ];
            self::where('id', $withDrawModel->id)->update($data);
        }else{
            self::where('id', $withDrawModel->id)->update(['descp'=>$re['errors'][0]['message']]);
        }
        return true;

    }
}
