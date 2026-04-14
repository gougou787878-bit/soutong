<?php

/**
 * @property int $id
 * @property string $media_url 媒体地址
 * @property string $cover 视频封面
 * @property int $thumb_width 封面宽
 * @property int $thumb_height 封面高
 * @property int $pid 帖子ID
 * @property int $type 类型 0 图片 1 视频
 * @property int $relate_type 关联类型 0 帖子 1 评论
 * @property int $status 0 未转换 1 转换 2 转换中
 * @property int $duration 视频持续时间
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 * @mixin \Eloquent
 */
class SeedPostMediaModel extends EloquentModel
{
    protected $table = 'seed_post_media';
    protected $primaryKey = 'id';
    protected $fillable = [
        'media_url',
        'cover',
        'thumb_width',
        'thumb_height',
        'pid',
        'type',
        'relate_type',
        'created_at',
        'status',
        'duration',
    ];

    public $timestamps = true;

    const STATUS_NO = 0;
    const STATUS_OK = 1;
    const STATUS_ING = 2;
    const STATUS_TIPS = [
        self::STATUS_NO  => '未转换',
        self::STATUS_OK  => '已转换',
        self::STATUS_ING => '转换中'
    ];

    const TYPE_IMG = 1;
    const TYPE_VIDEO = 2;
    const TYPE_TIPS = [
        self::TYPE_IMG   => '图片',
        self::TYPE_VIDEO => '视频',
    ];

    const TYPE_RELATE_POST = 1;
    const TYPE_RELATE_COMMENT = 2;
    const TYPE_RELATE_TIPS = [
        self::TYPE_RELATE_POST    => '帖子',
        self::TYPE_RELATE_COMMENT => '评论',
    ];

    protected $appends = ['ori_media_url'];

    public function setCoverAttribute($value)
    {
        parent::resetSetPathAttribute('cover', $value);
    }

    public function getCoverAttribute()
    {
        $uri = $this->attributes['cover'] ?? '';
        return $uri ? url_cover($uri) : '';
    }

    public function setMediaUrlAttribute($value)
    {
        parent::resetSetPathAttribute('media_url', $value);
    }

    public function getOriMediaUrlAttribute()
    {
        $media_url = $this->getOriginal('media_url') ?? '';
        if (strpos($media_url, '://') !== false) {
            $media_url = parse_url($media_url,PHP_URL_PATH);
        }
        return $media_url;
    }

    public static function createVideoRecord($id, $c, $w, $h, $d, $mp4, $m3u8, $status)
    {
        return self::create([
            'media_url'    => $status == self::STATUS_NO ? $mp4 : $m3u8,
            'cover'        => $c,
            'thumb_width'  => $w,
            'thumb_height' => $h,
            'pid'          => $id,
            'type'         => self::TYPE_VIDEO,
            'relate_type'  => self::TYPE_RELATE_POST,
            'created_at'   => date('Y-m-d H:i:s'),
            'status'       => $status,
            'duration'     => $d,
        ]);
    }

    public static function createImgRecord($id, $cover, $w, $h)
    {
        return self::create([
            'media_url'    => $cover,
            'cover'        => $cover,
            'thumb_width'  => $w,
            'thumb_height' => $h,
            'pid'          => $id,
            'type'         => self::TYPE_IMG,
            'relate_type'  => self::TYPE_RELATE_POST,
            'created_at'   => date('Y-m-d H:i:s'),
            'status'       => self::STATUS_OK,
            'duration'     => 0,
        ]);
    }
}
