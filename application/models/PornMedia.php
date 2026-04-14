<?php

/**
 * class PornMediaModel
 *
 * @property string $aff 上传用户AFF
 * @property string $cover 视频封面
 * @property string $created_at 创建时间
 * @property int $duration 视频持续时间
 * @property int $id
 * @property string $media_url 视频或图片地址
 * @property int $pid 黄游ID
 * @property int $relate_type 关联类型 1黄游 2评论
 * @property int $status 0 未转换 1 已转换 2 转换中
 * @property int $thumb_height 封面高
 * @property int $thumb_width 封面宽
 * @property int $type 类型 1图片 2视频
 * @property string $updated_at 更新时间
 *
 *
 * @date 2024-04-01 15:50:53
 *
 * @mixin \Eloquent
 */
class PornMediaModel extends EloquentModel
{
    protected $table = "porn_media";
    protected $primaryKey = 'id';
    protected $fillable = [
        'aff',
        'cover',
        'created_at',
        'duration',
        'media_url',
        'pid',
        'relate_type',
        'status',
        'thumb_height',
        'thumb_width',
        'type',
        'updated_at'
    ];
    protected $guarded = 'id';
    public $timestamps = true;

    const STATUS_NO = 0;
    const STATUS_OK = 1;
    const STATUS_ING = 2;
    const STATUS_TIPS = [
        self::STATUS_NO => '未转换',
        self::STATUS_OK => '已转换',
        self::STATUS_ING => '转换中'
    ];

    const TYPE_IMG = 1;
    const TYPE_VIDEO = 2;
    const TYPE_TIPS = [
        self::TYPE_IMG => '图片',
        self::TYPE_VIDEO => '视频',
    ];

    const TYPE_RELATE_POST = 1;
    const TYPE_RELATE_COMMENT = 2;
    const TYPE_RELATE_TIPS = [
        self::TYPE_RELATE_POST => '黄游',
        self::TYPE_RELATE_COMMENT => '评论',
    ];

    public function setCoverAttribute($value)
    {
        parent::resetSetPathAttribute('cover', $value);
    }

    public function getCoverAttribute()
    {
        $uri = $this->attributes['cover'] ?? '';
        if (strpos($uri, '://') !== false) {
            $uri = parse_url($uri,PHP_URL_PATH);
        }
        return $uri ? url_cover($uri) : '';
    }

    public function setMediaUrlAttribute($value)
    {
        parent::resetSetPathAttribute('media_url', $value);
    }

    public function getMediaUrlAttribute()
    {
        $uri = $this->attributes['media_url'] ?? '';
        $type = $this->attributes['type'] ?? '';
        $uri = parse_url($uri,PHP_URL_PATH);
        switch ($type) {
            case self::TYPE_IMG:
                return url_cover($uri);
            case self::TYPE_VIDEO:
                if (substr($uri, -4) == '.mp4' && MODULE_NAME == 'admin') {
                    return TB_CHECK_VIDEO . $uri;
                }
                return $uri;
        }

        return $uri;
    }
}
