<?php

/**
 * class MemberMagicModel
 *
 * @property int $aff 用户aff
 * @property string $cover 切片封面
 * @property int $cover_height 封面高度
 * @property int $cover_width 封面宽度
 * @property string $created_at 创建时间
 * @property int $duration 时长
 * @property int $id
 * @property int $is_delete 是否删除
 * @property string $magic_param AI魔法调用参数
 * @property int $re_ct 重试次数
 * @property string $reason 处理异常描述
 * @property string $remote_video 远程视频地址
 * @property int $status 状态
 * @property string $task_id 远程任务ID
 * @property string $thumb 用户上传头像
 * @property int $thumb_h 图片高度
 * @property int $thumb_w 图片宽度
 * @property string $updated_at 更新时间
 * @property string $video 处理完成视频
 * @property int $pay_type 1免费次数 2金币
 * @property int $coins 金币数
 *
 *
 * @date 2025-08-09 18:16:31
 *
 * @mixin \Eloquent
 */
class MemberMagicModel extends EloquentModel
{
    protected $table = "member_magic";
    protected $primaryKey = 'id';
    protected $fillable = [
        'aff',
        'cover',
        'thumb_w',
        'thumb_h',
        'created_at',
        'duration',
        'is_delete',
        'magic_param',
        're_ct',
        'reason',
        'remote_video',
        'status',
        'task_id',
        'thumb',
        'cover_width',
        'cover_height',
        'updated_at',
        'video',
        'pay_type',
        'coins',
    ];

    protected $guarded = 'id';
    public $timestamps = true;

    const STATUS_WAIT = 0;
    const STATUS_DOING = 1;
    const STATUS_SLICE = 4;
    const STATUS_SUCCESS = 2;
    const STATUS_FAIL = 3;
    const STATUS_TIPS = [
        self::STATUS_WAIT => '排队中',
        self::STATUS_DOING => '处理中',
        self::STATUS_SLICE => '切片中',
        self::STATUS_SUCCESS => '已成功',
        self::STATUS_FAIL => '已失败',
    ];

    const DELETE_NO = 0;
    const DELETE_OK = 1;
    const DELETE_TIPS = [
        self::DELETE_NO => '未删除',
        self::DELETE_OK => '已删除',
    ];

    const PAY_TYPE_FREE = 1;
    const PAY_TYPE_COINS = 2;
    const PAY_TYPE_TIPS = [
        self::PAY_TYPE_FREE => '免费',
        self::PAY_TYPE_COINS => '金币',
    ];

    const SE_LAYOUT_1 = [
        "id",
        "aff",
        "thumb",
        "thumb_w",
        "thumb_h",
        "video",
        'status',
        "reason",
        'created_at',
        'cover',
        'cover_width',
        'cover_height',
        'duration'
    ];

    protected $appends = ['down_url'];

    public function getThumbAttribute(): string
    {
        $url = $this->attributes['thumb'] ?? '';
        return $url ? url_image($url) : '';
    }

    public function setThumbAttribute($value)
    {
        $this->resetSetPathAttribute('thumb', $value);
    }

    public function getVideoAttribute(): string
    {
        $url = $this->attributes['video'] ?? '';
        if (APP_MODULE == 'admin'){
            return $url ? getAdminPlayM3u8($url) : '';
        }
        return $url ? getPlayUrl($url, false) : '';
    }

    public function setVideoAttribute($value)
    {
        $this->resetSetPathAttribute('video', $value);
    }

    public function getCoverAttribute(): string
    {
        $url = $this->attributes['cover'] ?? '';
        return $url ? url_image($url) : '';
    }

    public function setCoverAttribute($value)
    {
        $this->resetSetPathAttribute('cover', $value);
    }

    public function getDownUrlAttribute()
    {
        $url = $this->attributes['video'] ?? '';
        return $url ? getPlayUrlPwa($url) : '';
    }

    public static function list_my_generate_video($aff, $status, $page, $limit)
    {
        return self::select(self::SE_LAYOUT_1)
            ->where('aff', $aff)
            ->where('is_delete', self::DELETE_NO)
            ->when($status == self::STATUS_DOING, function ($q){
                return $q->whereIn('status', [self::STATUS_DOING, self::STATUS_SLICE]);
            })
            ->when($status != self::STATUS_DOING, function ($q) use ($status){
                return $q->where('status', $status);
            })
            ->orderByDesc('id')
            ->forPage($page, $limit)
            ->get();
    }

    public static function del_generate_video($aff, $ids)
    {
        self::where('aff', $aff)
            ->where('is_delete', self::DELETE_NO)
            ->whereIn('status', [
                self::STATUS_SUCCESS,
                self::STATUS_FAIL
            ])
            ->whereIn('id', $ids)
            ->get()
            ->map(function ($item) {
                $item->is_delete = self::DELETE_OK;
                $isOk = $item->save();
                test_assert($isOk, '系统异常,删除失败');
            });
    }

    public static function create_record($aff, $material, $thumb, $thumb_w, $thumb_h, $pay_type, $coins)
    {
        $data = [
            "aff" => $aff,
            "thumb" => $thumb,
            "thumb_w" => $thumb_w,
            "thumb_h" => $thumb_h,
            "magic_param" => $material->val,
            "is_delete" => self::DELETE_NO,
            "status" => self::STATUS_WAIT,
            "task_id" => "",
            "video" => "",
            "reason" => "",
            "pay_type" => $pay_type,
            "coins" => $coins,
        ];
        return self::create($data);
    }

    //视频切片
    public static function approvedMv($data)
    {
        $crypt = new \tools\CryptService();
        $sign = $crypt->make_sign($data);
        $data['sign'] = $sign;
        $data['notifyUrl'] = NOTIFY_BACK_URL . '/index.php?m=ai&a=on_magic_slice';
        $curl = new \tools\CurlService();
        $return = $curl->request(config('mp4.accept'), $data);
        errLog("post reslice req:" . var_export([$data, $return], true));
        return $return;
    }
}
