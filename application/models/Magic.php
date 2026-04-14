<?php

/**
 * class MagicModel
 *
 * @property string $cover 封面地址
 * @property string $created_at 创建时间
 * @property int $id
 * @property int $pay_coins 支付额
 * @property int $pay_ct 支付数
 * @property int $sort 排序
 * @property int $status 状态
 * @property string $title 标题
 * @property string $updated_at 更新时间
 * @property string $val 远程请求的参数
 * @property string $video 示例视频
 * @property int $type 类型
 * @property int $coins 金币数
 *
 *
 * @date 2025-08-09 18:16:35
 *
 * @mixin \Eloquent
 */
class MagicModel extends EloquentModel
{

    protected $table = "magic";
    protected $primaryKey = 'id';
    protected $fillable = [
        'cover',
        'created_at',
        'pay_coins',
        'pay_ct',
        'sort',
        'status',
        'title',
        'updated_at',
        'val',
        'video',
        'type',
        'coins',
    ];

    protected $guarded = 'id';
    public $timestamps = true;

    const STATUS_NO = 0;
    const STATUS_OK = 1;
    const STATUS_TIPS = [
        self::STATUS_NO => '禁用',
        self::STATUS_OK => '启用'
    ];

    const TYPE_COINS = 0;
    const TYPE_FIX = 1;
    const TYPE_TIPS = [
        self::TYPE_COINS => '金币',
        self::TYPE_FIX => '金币/次数',
    ];

    const SE_LAYOUT_1 = ["id", "title", "cover", "video", "type", "coins"];
    const SE_LAYOUT_2 = ["id", "val", "type", "coins"];
    const CK_MAGIC_MATERIAL = 'ck:magic:material:%s:%s';
    const GP_MAGIC_MATERIAL = 'gp:magic:material';
    const CN_MAGIC_MATERIAL = 'AI魔法-素材列表';

    const CK_MAGIC_DETAIL = 'ck:magic:detail:v1:%s';
    const GP_MAGIC_DETAIL = 'gp:magic:detail';
    const CN_MAGIC_DETAIL = 'AI魔法-素材详情';

    public function getCoverAttribute(): string
    {
        $url = $this->attributes['cover'] ?? '';
        return $url ? url_image($url) : '';
    }

    public function setCoverAttribute($value)
    {
        $this->resetSetPathAttribute('cover', $value);
    }

    public function getVideoAttribute(): string
    {
        $url = $this->attributes['video'] ?? '';
        if (MODULE_NAME == 'admin'){
            return $url ? getAdminPlayM3u8($url) : '';
        }

        return $url ? getPlayUrl($url, false) : '';
    }

    public function setVideoAttribute($value)
    {
        $this->resetSetPathAttribute('video', $value);
    }

    public static function defend_ct($id, $coins)
    {
        $increment_coins = DB::raw('pay_coins+' . $coins);
        self::where('id', $id)->increment('pay_ct', 1, ['pay_coins' => $increment_coins]);
    }

    public static function list_material($page, $limit)
    {
        $cache_key = sprintf(self::CK_MAGIC_MATERIAL, $page, $limit);
        return cached($cache_key)
            ->group(self::GP_MAGIC_MATERIAL)
            ->chinese(self::CN_MAGIC_MATERIAL)
            ->fetchPhp(function () use ($page, $limit) {
                return self::select(self::SE_LAYOUT_1)
                    ->where('status', self::STATUS_OK)
                    ->orderByDesc('sort')
                    ->forPage($page, $limit)
                    ->get();
            });
    }

    public static function detail($id)
    {
        $cache_key = sprintf(self::CK_MAGIC_DETAIL, $id);
        return cached($cache_key)
            ->group(self::GP_MAGIC_DETAIL)
            ->chinese(self::CN_MAGIC_DETAIL)
            ->fetchPhp(function () use ($id) {
                return self::select(self::SE_LAYOUT_2)
                    ->where('status', self::STATUS_OK)
                    ->where('id', $id)
                    ->first();
            });
    }
}
