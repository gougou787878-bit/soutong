<?php

use Carbon\Carbon;

/**
 * class MemberFaceModel
 *
 * @property int $id
 * @property int $aff Aff
 * @property int $material_id 素材ID
 * @property string $ground 用户底板
 * @property int $ground_w
 * @property int $ground_h
 * @property string $thumb 用户上传头像
 * @property int $thumb_w
 * @property int $thumb_h
 * @property string $face_thumb 处理之后图片
 * @property int $face_thumb_w
 * @property int $face_thumb_h
 * @property int $is_delete 是否删除
 * @property int $status 状态
 * @property string $reason 处理异常描述
 * @property string $created_at
 * @property string $updated_at
 * @property int $type
 * @property int $coins
 *
 *
 * @date 2024-01-02 20:10:07
 *
 * @mixin \Eloquent
 */
class MemberFaceModel extends EloquentModel
{
    protected $table = "member_face";
    protected $primaryKey = 'id';
    protected $fillable = [
        'aff',
        'material_id',
        'ground',
        'ground_w',
        'ground_h',
        'thumb',
        'thumb_w',
        'thumb_h',
        'face_thumb',
        'face_thumb_w',
        'face_thumb_h',
        'is_delete',
        'status',
        'reason',
        'created_at',
        'updated_at',
        'type',
        'coins',
    ];
    protected $guarded = 'id';
    public $timestamps = false;

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
        self::DELETE_NO => '未删除',
        self::DELETE_OK => '已删除',
    ];

    const TYPE_COINS = 0;
    const TYPE_TIME = 1;
    const TYPE_TIPS = [
        self::TYPE_COINS => '金币',
        self::TYPE_TIME => '免费次数',
    ];

    const SE_MY_FACE = ['id', 'ground', 'ground_w', 'ground_h', 'thumb', 'thumb_w', 'thumb_h', 'face_thumb', 'face_thumb_w', 'face_thumb_h', 'status', 'reason', 'created_at'];

    public function setGroundAttribute($value)
    {
        parent::resetSetPathAttribute('ground', $value);
    }

    public function getGroundAttribute(): string
    {
        return $this->attributes['ground'] ? url_cover($this->attributes['ground']) : '';
    }

    public function setThumbAttribute($value)
    {
        parent::resetSetPathAttribute('thumb', $value);
    }

    public function getThumbAttribute(): string
    {
        return $this->attributes['thumb'] ? url_cover($this->attributes['thumb']) : '';
    }

    public function setFaceThumbAttribute($value)
    {
        parent::resetSetPathAttribute('face_thumb', $value);
    }

    public function getFaceThumbAttribute(): string
    {
        return $this->attributes['face_thumb'] ? url_cover($this->attributes['face_thumb']) : '';
    }

    public function getCreatedAtAttribute(){
        return date('Y-m-d H:i', strtotime($this->attributes['created_at']));
    }

    public static function create_record($aff, $material_id, $type, $coins, $ground, $ground_w, $ground_h, $thumb, $thumb_w, $thumb_h)
    {
        $data = [
            'aff'          => $aff,
            'material_id'  => $material_id,
            'ground'       => $ground,
            'ground_w'     => $ground_w,
            'ground_h'     => $ground_h,
            'thumb'        => $thumb,
            'thumb_w'      => $thumb_w,
            'thumb_h'      => $thumb_h,
            'face_thumb'   => '',
            'face_thumb_w' => 0,
            'face_thumb_h' => 0,
            'is_delete'    => self::DELETE_NO,
            'status'       => self::STATUS_WAIT,
            'reason'       => '',
            'created_at'   => Carbon::now(),
            'updated_at'   => Carbon::now(),
            'type'         => $type,
            'coins'        => $coins,
        ];
        return self::create($data);
    }

    public static function create_customize_record($aff, $type, $coins, $ground, $ground_w, $ground_h, $thumb, $thumb_w, $thumb_h)
    {
        $data = [
            'aff'          => $aff,
            'material_id'  => 0,
            'ground'       => $ground,
            'ground_w'     => $ground_w,
            'ground_h'     => $ground_h,
            'thumb'        => $thumb,
            'thumb_w'      => $thumb_w,
            'thumb_h'      => $thumb_h,
            'face_thumb'   => '',
            'face_thumb_w' => 0,
            'face_thumb_h' => 0,
            'is_delete'    => self::DELETE_NO,
            'status'       => self::STATUS_WAIT,
            'reason'       => '',
            'created_at'   => date('Y-m-d H:i:s'),
            'updated_at'   => date('Y-m-d H:i:s'),
            'type'         => $type,
            'coins'        => $coins,
        ];
        return self::create($data);
    }

    public static function list_my_face($aff, $status, $page, $limit)
    {
        return self::select(self::SE_MY_FACE)
            ->where('aff', $aff)
            ->where('status', $status)
            ->where('is_delete', self::DELETE_NO)
            ->orderByDesc('id')
            ->forPage($page, $limit)
            ->get();
    }
}
