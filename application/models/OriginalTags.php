<?php

/**
 * class OriginalTagsModel
 *
 * @property int $id
 * @property string $category 分类
 * @property int $sort_num 排序
 * @property string $name 名称
 * @property int $status 状态
 *
 * @author xiongba
 * @date 2020-03-04 20:13:00
 *
 * @mixin \Eloquent
 */
class OriginalTagsModel extends EloquentModel
{

    protected $table = "original_tags";

    protected $primaryKey = 'id';

    protected $fillable = [
        'name',
        'category',
        'sort_num',
        'status'
    ];

    protected $guarded = 'id';

    public $timestamps = false;


    const  CATEGORY_TIPS = [
        'type' => '影片类型',
        'plot' => '剧情类型',
        'area' => '地区',
        'lgbt' => 'LGBTQ+',
    ];

    const STATUS_NO = 0;
    const STATUS_OK = 1;
    const STATUS_TIPS = [
        self::STATUS_NO => '否',
        self::STATUS_OK => '是',
    ];

    //片库配置
    const CK_ORIGINAL_CONF_LIST = "ck:original:conf:list:%s";
    const GP_ORIGINAL_CONF_LIST = "gp:original:conf:list";
    const CN_ORIGINAL_CONF_LIST = "片库配置列表";

    public static function getTagsByCate($cate)
    {
        $data = cached(sprintf(self::CK_ORIGINAL_CONF_LIST, $cate))
            ->group(self::GP_ORIGINAL_CONF_LIST)
            ->chinese(self::CN_ORIGINAL_CONF_LIST)
            ->fetchJson(function () use ($cate){
                return self::where('status', self::STATUS_OK)
                    ->where('category', $cate)
                    ->orderByDesc('sort_num')
                    ->get()
                    ->map(function ($item) {
                        $data['key'] = $item->name;
                        $data['name'] = $item->name;
                        return $data;
                    })
                    ->toArray();
            });

        return array_merge([
            [
                'key' => '',
                'name' => '全部'
            ]
        ], $data);
    }

}
