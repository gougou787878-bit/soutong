<?php


/**
 * class AiNavModel
 *
 * @property int $id
 * @property string $title 标题
 * @property int $type 类型
 * @property string $cover 封面
 * @property int $sort 排序
 * @property string $created_at
 * @property string $updated_at
 * @property int $parent 父级ID
 * @property int $status 状态
 * @property string $desc 描述
 *
 *
 * @date 2025-08-12 22:22:02
 *
 * @mixin \Eloquent
 */
class AiNavModel extends EloquentModel
{
    protected $table = "ai_nav";
    protected $primaryKey = 'id';
    protected $fillable = [
        'title',
        'type',
        'cover',
        'sort',
        'created_at',
        'updated_at',
        'parent',
        'status',
        'desc',
    ];
    protected $guarded = 'id';
    public $timestamps = true;

    const TYPE_UNDRESS = 1;
    const TYPE_SWAP_FACE = 2;
    const TYPE_MAGIC = 3;
    const TYPE_DRAW = 4;
    const TYPE_NOVEL = 5;
    const TYPE_IMG_SWAP_FACE = 6;
    const TYPE_VIDEO_SWAP_FACE = 7;
    const TYPE_GIRL_FRIEND = 8;

    const TYPE_TIPS = [
        self::TYPE_UNDRESS          => '脱衣',
        self::TYPE_SWAP_FACE        => '换脸',
        self::TYPE_MAGIC            => '图生视频',
        self::TYPE_DRAW             => '绘画',
        self::TYPE_NOVEL            => '小说创作',
        self::TYPE_IMG_SWAP_FACE    => '图片换脸',
        self::TYPE_VIDEO_SWAP_FACE  => '视频换脸',
        self::TYPE_GIRL_FRIEND      => 'AI女友',
    ];

    const STATUS_NO = 0;
    const STATUS_OK = 1;
    const STATUS_TIPS = [
        self::STATUS_NO   => '下架',
        self::STATUS_OK   => '上架'
    ];

    const LAYOUT_1 = ['id', 'cover', 'title', 'type', 'desc'];

    public function getCoverAttribute(){
        return url_cover($this->attributes['cover']);
    }

    public static function list(){
        return cached('ck:ai:nav')
            ->group('gp:ai:nav')
            ->chinese('AI导航')
            ->fetchPhp(function (){
                return self::select(self::LAYOUT_1)
                    ->where('status', self::STATUS_OK)
                    ->where('parent', 0)
                    ->orderByDesc('sort')
                    ->get()
                    ->map(function (self $model){
                        $model->child = [];
                        if ($model->type == self::TYPE_SWAP_FACE){
                            $model->child = self::select(self::LAYOUT_1)
                                ->where('status', self::STATUS_OK)
                                ->where('parent', $model->id)
                                ->orderByDesc('sort')
                                ->get();
                        }
                        return $model;
                    });
            });
    }
}
