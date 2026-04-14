<?php

use tools\RedisService;

/**
 *
 * @property int $id
 * @property int $created_at 创建时间
 * @property string $description 描述
 * @property int $home 首页显示
 * @property string $horizontal_img 横向图片
 * @property string $img_url 标签封面图
 * @property string $name 标签
 * @property int $sort_num 排序
 * @property int $updated_at
 * @property int $status
 * @property int $user_up
 * @property int $works_num 作品数
 *
 * @author xiongba
 * @date 2020-11-14 12:37:07
 *
 * @mixin \Eloquent
 */
class TagsModel extends EloquentModel
{

    const YES = 1;
    const NO = 0;

    const STATUS = [
        self::NO  => '否',
        self::YES => '是',
    ];

    const USER_UP = [
        self::NO  => '否',
        self::YES => '是',
    ];

    protected $table = 'tags';

    const REDIS_TAGS_LIST = 'redis_tags_lists_';

    //发现精彩-标签列表
    const CK_TAGS_FIND_LIST = "tags:fx:list:%s:%s";
    const GP_TAGS_FIND_LIST = "tags:fx:list";
    const CN_TAGS_FIND_LIST = "发现精彩-标签列表";

    const HOME_YES = 1;
    const HOME_NO = 0;
    const HOME = [
        self::HOME_NO  => '否',
        self::HOME_YES => '是',
    ];


    protected $fillable = [
        'name',
        'sort_num',
        'created_at',
        'updated_at',
        'home',
        'img_url',
        'horizontal_img',
        'description',
        'status',
        'user_up',
        'works_num',
    ];


    protected $appends = ['img_url_full', 'horizontal_img_url'];


    public function getImgUrlFullAttribute()
    {
        return url_cover($this->attributes['img_url'] ?? null);
    }

    public function getHorizontalImgUrlAttribute()
    {
        return url_cover($this->attributes['horizontal_img'] ?? null);
    }


    public static function queryBase()
    {
        return self::query();
    }


    static function addTag($data)
    {
        $has = self::where('name', trim($data['name']))->first();
        if ($has) {
            return false;
        }
        $data['created_at'] = time();
        $flag = self::insert($data);
        $flag && self::clearTagCache();
        return $flag;
    }

    static function updateTag($id, $data)
    {
        $flag = self::where('id', $id)->update($data);
        $flag && self::clearTagCache();
        return $flag;
    }

    static function delTag($id)
    {
        $flag = self::where('id', $id)->delete();
        $flag && self::clearTagCache();
        return $flag;
    }

    static function clearTagCache()
    {
        $key_1 = self::REDIS_TAGS_LIST . '0_24';
        $key_2 = self::REDIS_TAGS_LIST . '24_24';
        RedisService::del($key_1);//page_1
        RedisService::del($key_2);//page_2
        redis()->del('pre:upload:tag');
    }

    public static function tagList(){
        return cached('pre:upload:tag')->fetchJson(function (){
            return self::where('user_up', self::YES)
                ->orderBy('sort_num')
                ->pluck('name')
                ->toArray();
        }, 7200);
    }

    public static function updateWorksNum(){
        TagsModel::query()->chunkById(20,function (\Illuminate\Support\Collection $items){
            collect($items)->each(function (TagsModel $item){
                $ct = MvModel::queryBase()
                    ->whereRaw("match(tags) against(? in boolean mode)", [$item->name])
                    ->where('is_aw',MvModel::AW_NO)
                    ->count('id');
                $item->works_num = $ct;
                $item->save();
            });
        });
    }

    public static function getList($page, $limit){
        return cached(sprintf(self::CK_TAGS_FIND_LIST, $page, $limit))
            ->group(self::GP_TAGS_FIND_LIST)
            ->chinese(self::CN_TAGS_FIND_LIST)
            ->fetchPhp(function () use ($page, $limit){
                return self::queryBase()
                    ->selectRaw('id,name,img_url,works_num')
                    ->where('status', self::YES)
                    ->orderByDesc('sort_num')
                    ->orderByDesc('id')
                    ->forPage($page, $limit)
                    ->get();
            });
    }
}