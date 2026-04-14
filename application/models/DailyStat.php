<?php


use Illuminate\Database\Eloquent\Model;

/**
 * class DailyStatModel
 *
 * @property int $id
 * @property int $day
 * @property int $ip ip 访问
 * @property int $uv 独立ip 用户
 * @property int $pv 官网访问数
 * @property int $activity 活跃
 * @property int $tui_user 渠道访问数
 * @property int $total_user 累计总用户
 * @property int $user 新增
 * @property int $build_user 渠道新增
 * @property int $and_user 安卓新增
 * @property int $order_number 订单数量
 * @property string $order_amount 订单额度
 * @property string $charge 充值
 * @property string $vip_charge vip充值
 * @property string $and_charge 安卓充值
 * @property string $build_charge 渠道充值
 * @property string $game_charge 游戏订单
 * @property string $live_total 直播消费
 * @property string $total_charge 累计成交
 * @property int $channel_users 渠道用户
 * @property int $and_down Android下载
 * @property int $ios_down pwa下载
 * @property int $total_down 总下载
 * @property int $pwa_user Pwa新增用户数
 * @property int $and_active 安卓活跃数
 * @property int $pwa_active pwa活跃数
 * @property int keep_1day 1日留存
 * @property int keep_1day_rate 1日留存率
 * @property int keep_3day 3日留存
 * @property int keep_3day_rate 3日留存率
 * @property int keep_7day 7日留存
 * @property int keep_7day_rate 7日留存率
 *
 * @author xiongba
 * @date 2022-06-17 16:29:28
 *
 * @mixin \Eloquent
 */
class DailyStatModel extends Model
{

    protected $table = "daily_stat";

    protected $primaryKey = 'id';

    protected $fillable = [
        'day',
        'ip',
        'uv',
        'pv',
        'activity',
        'tui_user',
        'total_user',
        'user',
        'build_user',
        'and_user',
        'order_number',
        'order_amount',
        'charge',
        'vip_charge',
        'and_charge',
        'build_charge',
        'game_charge',
        'live_total',
        'total_charge',
        'channel_users',
        'and_down',
        'pwa_down',
        'total_down',
        'pwa_user',
        'and_active',
        'pwa_active',
        'keep_1day',
        'keep_1day_rate',
        'keep_3day',
        'keep_3day_rate',
        'keep_7day',
        'keep_7day_rate',
    ];

    protected $guarded = 'id';

    public $timestamps = false;


    /**
     * 将二维数组还原成model对象
     *
     * @param array<array>|ArrayAccess $ary
     * @param bool $sync
     *
     * @return \Illuminate\Support\Collection|static[]
     */
    public static function makeCollect($ary, bool $sync = true)
    {
        $model = static::make();
        $models = [];
        foreach ($ary as $item) {
            if (empty($item)) {
                continue;
            }
            $object = clone $model;
            $object->exists = true;
            $object->setRawAttributes($item, $sync);
            $models[] = $object;
        }
        unset($object, $item, $ary);
        return $model->newCollection($models);
    }

    /**
     * @return Model|\Illuminate\Database\Query\Builder|object|null
     */
    static function hasStat()
    {
        $day = date('Ymd');
        return cached('h:' . $day)->expired(50000)->fetch(function () use ($day) {
            return self::useWritePdo()->where(['day' => $day])->first() ? 1 : 0;
        });
    }

    /**
     * @param array $data
     * @return DailyStatModel|Model|\Illuminate\Database\Query\Builder|object|null
     */
    static function initStat($data = [])
    {
        if ($hasStat = self::hasStat()) {
            return $hasStat;
        }
        $initData = [
            'day'                   => date('Ymd'),
            'ip'                    => 1,
            'uv'                    => 1,
            'pv'                    => 0,
            'activity'              => 0,
            'total_user'            => 0,
            'tui_user'              => 0,
            'user'                  => 0,
            'build_user'            => 0,
            'and_user'              => 0,
            'order_number'          => 0,
            'order_amount'          => 0,
            'charge'                => 0,
            'vip_charge'            => 0,
            'and_charge'            => 0,
            'build_charge'          => 0,
            'game_charge'           => 0,
            'live_total'            => 0,
            'total_charge'          => 0,
            'one_keep'              => 0,
            'three_keep'            => 0,
            'seven_keep'            => 0
        ];
        $data && $initData = array_merge($initData, $data);
        return self::create($initData);
    }

    static function addStat($filed = 'pv', $number = 1)
    {
        self::initStat();
        $day = date('Ymd');
        return self::where(['day' => $day])->increment($filed, $number);
    }


    static function countSystem($day = null)
    {
        date_default_timezone_set('Asia/Shanghai');
        $date = date('Y-m-d', strtotime('-1 day'));//前一天
        $day && $date = $day;
        $start_day = "{$date} 00:00:00";
        $end_day = "{$date} 23:59:59";
        //推广邀请
        $tui_user = AffOpenLogModel::where('created_at', '>=', strtotime($start_day))
            ->where('created_at', '<', strtotime($end_day))->count('id');
        //推广独立ip 来源
        $dis_ap = AffOpenLogModel::where('created_at', '>=', strtotime($start_day))
            ->where('created_at', '<', strtotime($end_day))
            ->selectRaw('distinct(ip) as ip')->count();

        $today_user_data = MemberModel::where('regdate', '>=', strtotime($start_day))
            ->where('regdate', '<', strtotime($end_day))
            ->select([
                'uid',
                'oauth_type',
                'build_id'
            ])->get();
        //今日新增
        $today_user = collect($today_user_data)->count();
        //今日安卓新增
        $today_and_user = collect($today_user_data)->where('oauth_type', '=', 'android')->count();
        //今日渠道新增
        $today_build_user = collect($today_user_data)->where('build_id', '!=', '')->count();
        //累计用户
        $total_user = MemberModel::orderByDesc('uid')->first(['uid'])->uid;

        //今日活跃
        $today_active = MemberLogModel::where('lastactivity', '>=', strtotime($start_day))->count();

        //累计成交
        $total_charge = OrdersModel::query()->where('status', OrdersModel::STATUS_SUCCESS)->sum('pay_amount');

        $today_charge_data = OrdersModel::query()
            ->where('updated_at', '>=', strtotime($start_day))
            ->where('updated_at', '<=', strtotime($end_day))
            ->get([
                'id',
                'status',
                'order_type',
                'amount',
                'pay_amount',
                'oauth_type',
                'build_id'
            ]);

        $today_charge_number = collect($today_charge_data)->count();
        $today_charge_amount = collect($today_charge_data)->sum('amount');
        $today_charge = collect($today_charge_data)->where('status', '=',
            OrdersModel::STATUS_SUCCESS)->sum('pay_amount');
        $today_charge_vip = collect($today_charge_data)
            ->where('status', '=', OrdersModel::STATUS_SUCCESS)
            ->where('order_type', '=', OrdersModel::TYPE_VIP)
            ->sum('pay_amount');
        $today_charge_game = collect($today_charge_data)
            ->where('status', '=', OrdersModel::STATUS_SUCCESS)
            ->where('order_type', '=', OrdersModel::TYPE_GAME)
            ->sum('pay_amount');
        $today_charge_and = collect($today_charge_data)
            ->where('status', '=', OrdersModel::STATUS_SUCCESS)
            ->where('oauth_type', '=', 'android')
            ->sum('pay_amount');
        $today_charge_channel = collect($today_charge_data)
            ->where('status', '=', OrdersModel::STATUS_SUCCESS)
            ->where('build_id', '!=', '')
            ->sum('pay_amount');

        $date = date('Ymd', strtotime($date));
        //$today_live = SdkConsumeModel::where('date','=',$date)->sum('amount');
        $today_live = 0;
        $initData = [
            'day' => $date,
            'ip' => $dis_ap,
            'activity' => $today_active,
            'total_user' => $total_user,
            'tui_user' => $tui_user,
            'user' => $today_user,
            'build_user' => $today_build_user,
            'and_user' => $today_and_user,
            'order_number' => $today_charge_number,
            'order_amount' => $today_charge_amount / 100,
            'charge' => $today_charge / 100,
            'vip_charge' => $today_charge_vip / 100,
            'game_charge' => $today_charge_game / 100,
            'and_charge' => $today_charge_and / 100,
            'live_total' => $today_live / 100,
            'build_charge' => $today_charge_channel / 100,
            'total_charge' => $total_charge / 100
        ];
        self::updateOrCreate(['day' => $date], $initData);
        print_r($initData);

    }

    static function countSystemNew($day = null)
    {
        date_default_timezone_set('Asia/Shanghai');
        $date = date('Y-m-d', strtotime('-1 day'));//前一天
        $day && $date = $day;

        //渠道用户
        $channel_users = SysTotalModel::getValueBy('member:create:invite', $date);
        //Android下载
        $and_down = SysTotalModel::getValueBy('and:download', $date);
        //pwa下载
        $pwa_down = SysTotalModel::getValueBy('pwa:download', $date);
        //总下载
        $total_down = $and_down + $pwa_down;
        //渠道访问
        $tui_user = SysTotalModel::getValueBy('now:aff:open', $date);
        //推广独立ip 来源
        $dis_ap = SysTotalModel::getValueBy('now:aff:open:ip:norepeat', $date);
        //pv 访问量
        $pv = SysTotalModel::getValueBy('welcome', $date);
        //今日新增
        $today_user = SysTotalModel::getValueBy('member:create', $date);
        //今日安卓新增
        $today_and_user = SysTotalModel::getValueBy('member:create:and', $date);
        //今日pwa新增
        $today_pwa_user = $today_user - $today_and_user;
        //今日渠道新增
        $today_build_user = SysTotalModel::getValueBy('member:create:invite', $date);
        //累计用户
        $total_user = MemberModel::orderByDesc('uid')->first(['uid'])->uid;
        //今日活跃
        $today_active = SysTotalModel::getValueBy('member:active', $date);
        //and 活跃
        $today_and_active = SysTotalModel::getValueBy('member:active:and', $date);
        //pwa 活跃
        $today_pwa_active = $today_active - $today_and_active;
        //累计成交
        $total_charge = OrdersModel::query()->where('status', OrdersModel::STATUS_SUCCESS)->sum('pay_amount');
        $today_charge_number = SysTotalModel::getValueBy('add-order', $date);
        $today_charge_amount = SysTotalModel::getValueBy('add-order-amount', $date);
        $today_charge = SysTotalModel::getValueBy('order-amount', $date);
        $today_charge_vip = SysTotalModel::getValueBy('pay-vip', $date);
        $today_charge_game = 0;
        $today_charge_and = SysTotalModel::getValueBy('order-amount-and', $date);
        $today_charge_channel = SysTotalModel::getValueBy('invite-order-amount', $date);

        //留存率计算
        $reg_1date = date("Y-m-d", strtotime($date . " -1 day"));
        $reg_3date = date("Y-m-d", strtotime($date . " -3 day"));
        $reg_7date = date("Y-m-d", strtotime($date . " -7 day"));
        $keep_1day = SysTotalModel::getValueBy('keep:1day', $date);
        $reg_1day = SysTotalModel::getValueBy('member:create', $reg_1date);
        $keep1_rate = $reg_1day > 0 ? round($keep_1day / $reg_1day, 4) * 100 : 0;
        $keep_3day = SysTotalModel::getValueBy('keep:3day', $date);
        $reg_3day = SysTotalModel::getValueBy('member:create', $reg_3date);
        $keep3_rate = $reg_3day > 0 ? round($keep_3day / $reg_3day, 4) * 100 : 0;
        $keep_7day = SysTotalModel::getValueBy('keep:7day', $date);
        $reg_7day = SysTotalModel::getValueBy('member:create', $reg_7date);
        $keep7_rate = $reg_7day > 0 ? round($keep_7day / $reg_7day, 4) * 100 : 0;

        $date = date('Ymd', strtotime($date));
        $today_live = 0;
        $initData = [
            'day'               => $date,
            'ip'                => $dis_ap,
            'uv'                => 1,
            'pv'                => $pv,
            'activity'          => $today_active,
            'total_user'        => $total_user,
            'tui_user'          => $tui_user,
            'user'              => $today_user,
            'build_user'        => $today_build_user,
            'and_user'          => $today_and_user,
            'order_number'      => $today_charge_number,
            'order_amount'      => $today_charge_amount,
            'charge'            => $today_charge,
            'vip_charge'        => $today_charge_vip,
            'game_charge'       => $today_charge_game,
            'and_charge'        => $today_charge_and,
            'live_total'        => $today_live,
            'build_charge'      => $today_charge_channel,
            'total_charge'      => $total_charge / 100,
            'channel_users'     => $channel_users,
            'and_down'          => $and_down,
            'pwa_down'          => $pwa_down,
            'total_down'        => $total_down,
            'pwa_user'          => $today_pwa_user,
            'and_active'        => $today_and_active,
            'pwa_active'        => $today_pwa_active,
            'keep_1day'         => $keep_1day,
            'keep_1day_rate'    => $keep1_rate,
            'keep_3day'         => $keep_3day,
            'keep_3day_rate'    => $keep3_rate,
            'keep_7day'         => $keep_7day,
            'keep_7day_rate'    => $keep7_rate,
        ];
        self::updateOrCreate(['day' => $date], $initData);
        print_r($initData);
    }


}
