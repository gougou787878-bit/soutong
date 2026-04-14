<?php

use Carbon\Carbon;

/**
 * class MemberStripModel
 *
 * @property int $id
 * @property int $aff 用户标识
 * @property string $thumb 脱衣图片
 * @property int $thumb_w 图片宽度
 * @property int $thumb_h 图片高度
 * @property string $strip_thumb 脱衣后图片
 * @property int $strip_thumb_w 脱衣后图片宽度
 * @property int $strip_thumb_h 脱衣后图片高度
 * @property int $status 状态
 * @property string $reason 原因
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 * @property int $is_delete 是否删除
 * @property int $pay_type 1免费次数 2金币
 * @property int $coins 金币数
 * @property string $task_id 远程任务ID
 * @property int $re_ct
 *
 *
 * @date 2024-01-02 16:01:32
 *
 * @mixin \Eloquent
 */
class MemberStripModel extends EloquentModel
{

    protected $table = "member_strip";

    protected $primaryKey = 'id';

    protected $fillable = [
        'aff',
        'thumb',
        'thumb_w',
        'thumb_h',
        'strip_thumb',
        'strip_thumb_w',
        'strip_thumb_h',
        'status',
        'reason',
        'created_at',
        'updated_at',
        'is_delete',
        'pay_type',
        'coins',
        'task_id',
        're_ct'
    ];

    protected $guarded = 'id';

    public $timestamps = true;

    const STATUS_WAIT = 0;
    const STATUS_DOING = 1;
    const STATUS_SUCCESS = 2;
    const STATUS_FAIL = 3;
    const STATUS_TIPS = [
        self::STATUS_WAIT    => '排队中',
        self::STATUS_DOING   => '处理中',
        self::STATUS_SUCCESS => '已成功',
        self::STATUS_FAIL    => '已失败',
    ];

    const DELETE_NO = 0;
    const DELETE_OK = 1;
    const DELETE_TIPS = [
        self::DELETE_NO => '已删除',
        self::DELETE_OK => '未删除',
    ];

    const PAY_TYPE_FREE = 1;
    const PAY_TYPE_COINS = 2;
    const PAY_TYPE_TIPS = [
        self::PAY_TYPE_FREE => '免费',
        self::PAY_TYPE_COINS => '金币',
    ];

    //支付
    const AI_PAY_TYPE_FREE = 1;
    const AI_PAY_TYPE_COINS = 2;

    const SE_MY_STRIP = ['id', 'thumb', 'thumb_w', 'thumb_h', 'strip_thumb', 'strip_thumb_w', 'strip_thumb_h', 'status', 'reason', 'created_at', 'updated_at'];

    public function setThumbAttribute($value)
    {
        parent::resetSetPathAttribute('thumb', $value);
    }

    public function getThumbAttribute()
    {
        return $this->attributes['thumb'] ? url_cover($this->attributes['thumb']) : '';
    }

    public function setThumbStripAttribute($value)
    {
        parent::resetSetPathAttribute('strip_thumb', $value);
    }

    public function getStripThumbAttribute()
    {
        return $this->attributes['strip_thumb'] ? url_cover($this->attributes['strip_thumb']) : '';
    }

    public function getCreatedAtAttribute(){
        return date('Y-m-d H:i', strtotime($this->attributes['created_at']));
    }

    public static function create_record($aff, $thumb, $thumb_w, $thumb_h, $pay_type, $coins)
    {
        $data = [
            'aff'           => $aff,
            'thumb'         => $thumb,
            'thumb_w'       => $thumb_w,
            'thumb_h'       => $thumb_h,
            'strip_thumb'   => '',
            'strip_thumb_w' => 0,
            'strip_thumb_h' => 0,
            'status'        => self::STATUS_WAIT,
            'reason'        => '',
            'created_at'    => Carbon::now(),
            'updated_at'    => Carbon::now(),
            'pay_type'      => $pay_type,
            'coins'         => $pay_type == self::PAY_TYPE_COINS ? $coins : 0,
        ];
        return self::create($data);
    }

    public static function list_my_strip($aff, $status, $page, $limit)
    {
        return self::select(self::SE_MY_STRIP)
            ->where('aff', $aff)
            ->where('status', $status)
            ->where('is_delete', self::DELETE_NO)
            ->orderByDesc('id')
            ->forPage($page, $limit)
            ->get();
    }
}
