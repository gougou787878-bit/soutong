<?php
/**
 *
 *gay 资源网站 lanPron 咨询  服务控制中心
 *
 * 所有业务逻辑处理  沿用pwa服务资源
 *
 * Class LanPronService
 */
namespace service;

use helper\QueryHelper;

class LanPronService extends \AbstractBaseService
{

    static $tabData  = [
        [
            'current' => true,
            'name'    => '首页',
            'type'    => '/',
            'api'     => '/api/lanpron/home',
            'params'  => '0',
        ],
        [
            'current' => false,
            'name'    => '最新视频',
            'type'    => '/list',
            'api'     => '/api/lanpron/new',
            'params'  => 'new',
        ],
        [
            'current' => false,
            'name'    => '近期热门',
            'type'    => '/list',
            'api'     => '/api/lanpron/hot',
            'params'  => 'hot',
        ],
        [
            'current' => false,
            'name'    => '他们在看',
            'type'    => '/list',
            'api'     => '/api/lanpron/viewed',
            'params'  => 'viewed',
        ],
        [
            'current' => false,
            'name'    => '全部分类',
            'type'    => '/category',
            'api'     => '/api/lanpron/category',
            'params'  => '0',
        ],
        [
            'current' => false,
            'name'    => '回家不迷路',
            'type'    => '/address',
            'api'     => '/api/lanpron/address',
            'params'  => '0',
        ],
    ];


    public static function formatDetail($datum, $watchByMember = null,$isBuy= false)
    {
        /** @var MvModel $datum */
        $datum->addHidden([
            'uid',
            'music_id',
            'coins',
            'vip_coins',
           // 'm3u8',
            //'full_m3u8',
            'v_ext',
            'is_hide',
            'gif_thumb',
            'gif_width',
            'gif_height',
            'gif_thumb_url',
            'directors',
            'is_hide',
            'y_cover',
            'y_cover_url',
            'actors',
            'category',
            'via',
            'refresh_at',
            'onshelf_tm',
            'thumb_start_time',
            'thumb_duration',
            'topic_id',
            'created_at',
            'cover_thumb',
        ]);


        $datum->play_url = getPlayUrl(ILLEGAL_ORG_VIDEO, false);
        if($full_m3u8 = $datum->full_m3u8){
            $datum->play_url =getPlayUrl($full_m3u8,true);
            $datum->full_m3u8 = ILLEGAL_ORG_VIDEO;
        }elseif($m3u8 = $datum->m3u8){
            $datum->play_url =getPlayUrl($m3u8,true);
            $datum->m3u8 = ILLEGAL_ORG_VIDEO;
        }
        $datum->id_code = self::getID2Code($datum->id);//id 转化器
        'product' == APP_ENVIRON && $datum->id = 0;
        return $datum;
    }


    public static  function formatList($items, $watchByMember = null)
    {
        if (empty($items) || is_null($items)) {
            return [];
        }

        return collect($items)->map(function (\MvModel $mv){
            $mv->addHidden([
                'uid',
                'music_id',
                'coins',
                'vip_coins',
                'm3u8',
                'full_m3u8',
                 'v_ext',
                 'is_hide',
                 'gif_thumb',
                 'gif_width',
                 'gif_height',
                 'gif_thumb_url',
                 'directors',
                 'is_hide',
                'y_cover',
                'y_cover_url',
                'actors',
                'category',
                'via',
                'refresh_at',
                'onshelf_tm',
                'thumb_start_time',
                'thumb_duration',
                'topic_id',
                'created_at',
                'cover_thumb',
            ]);
            $mv->id_code = self::getID2Code($mv->id);//id 转化器
            'product' == APP_ENVIRON && $mv->id = 0;
            return $mv;
        })->values()->toArray();

    }

    static function getID2Code($id)
    {
        $aff_code = generate_code($id);
        $verify_code = substr(sha1($id), -4);
        return "{$aff_code}-{$verify_code}";
    }

    static function getCode2ID($code)
    {
        list($aff_code, $verfiy_code) = explode('-', $code);
        $id = get_num($aff_code);
        $verify_code_id = substr(sha1($id), -4);
        if ($verify_code_id == $verfiy_code) {
            return $id;
        }
        return 1000;//返回一个固定视频编号
    }


    static function getHomePageData(){
        $tabData = self::$tabData;
        unset($tabData[5],$tabData[4],$tabData[0]);//去掉 首页 分类
        reset($tabData);
        return collect($tabData)->map(function ($item){
                self::getCateDataByItem($item,6,true);
                return $item;
        })->values()->toArray();
    }

    static function getCateDataByItem(&$item,$limit=6,$needAds =false){
        $item['list'] = [];
        $item['ads'] = [];
        if($item['params'] == 'new'){
            $data = self::getNewMvData(1,$limit);
            $item['list'] = self::formatList($data);
            $item['ads'] =AdService::getADsByPosition(\AdsModel::POSITION_LANPRON_MID);
        }elseif($item['params'] == 'hot'){
            $data = self::getWeekMvData(1,$limit);
            $item['list'] = self::formatList($data);
            $item['ads'] =AdService::getADsByPosition(\AdsModel::POSITION_LANPRON_MID);
        }elseif($item['params'] == 'viewed'){
            $item['ads'] =AdService::getADsByPosition(\AdsModel::POSITION_LANPRON_END);
            $data = self::getDailyMvData(1,$limit);
            $item['list'] = self::formatList($data);
        }
    }


    /**
     * 最新
     * @param int $page
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Query\Builder[]|\Illuminate\Support\Collection
     */
    static function getNewMvData($page = 1, $limit = 18)
    {
        $key = "lan:mv:new:{$page}:{$limit}";
        return cached($key)->fetchPhp(function () use($page,$limit){
            $where = [
                ['created_at', '<=', strtotime("-2 day")]
            ];
            return \MvModel::queryBase()->where($where)->orderByDesc('id')->forPage($page, $limit)->get();
        });
    }


    /**
     * 最新
     * @param int $page
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Query\Builder[]|\Illuminate\Support\Collection
     */
    static function getWeekMvData($page = 1, $limit = 18)
    {
        $key = "lan:week:mv:{$page}:{$limit}";
        return cached($key)->fetchPhp(function () use($page,$limit){
            $where = [
                ['created_at', '<=', strtotime("-7 day")]
            ];
            return \WeekRelationModel::where($where)
                ->with('mv')
                ->orderByDesc('id')
                ->forPage($page,$limit)
                ->get()
                ->map(function (\WeekRelationModel $item){
                    if(!is_null($item)){
                        if($mv = $item->mv){
                            return $mv;
                        }
                    }
                    return null;
                })->filter()->values();
        });
    }


    /**
     * 最新
     * @param int $page
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Query\Builder[]|\Illuminate\Support\Collection
     */
    static function getDailyMvData($page = 1, $limit = 18)
    {
        $key = "lan:daily:mv:{$page}:{$limit}";
        return cached($key)->fetchPhp(function () use($page,$limit){
            $date = date('Y-m-d', strtotime("-$page days"));

            /** @var \DailyVideoModel $dailyModel */
            $dailyModel = \DailyVideoModel::queryBase()
                ->where('day', '<=',$date)
                ->orderByDesc('id')
                ->first();
            if (is_null($dailyModel)) {
                return null;
            }
            $vidArr = explode(',', $dailyModel->vids);
            $vidArr && $vidArr = array_unique($vidArr);
            $data = [];
            if ($vidArr) {
                $data = \MvModel::queryBase()->whereIn('id',$vidArr)
                    ->limit($limit)
                    ->orderByDesc('id')
                    ->get();
            }
            return $data;
        });
    }

    /**
     * 标签视频
     * @param int $page
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Query\Builder[]|\Illuminate\Support\Collection
     */
    static function getTagMvData($tag,$page = 1, $limit = 18)
    {
        $type = 'newest';
        $key = sprintf("tag:%s,sort-%s,p-%d:%d", $tag, $type, $page, $limit);
        $ids = cached($key)
            ->expired(7200)
            ->serializerJSON()
            ->fetch(function () use ($tag, $page, $limit) {
                $results = \MvModel::queryBase()
                    ->select(['id'])
                    ->where('is_aw',\MvModel::AW_NO)
                    ->whereRaw("match(tags) against(? in boolean mode)", [$tag])
                    ->orderByDesc('id')
                    ->forPage($page, $limit)
                    ->get();
                return collect($results)->pluck('id')->toArray();
            });

        if ($ids) {
            return \MvModel::whereIn('id', $ids)->get();
        }
        return [];
    }

    static function searchData($text ,$page, $limit,$member = null){
        $response = (new EsLib())->matchInKey(EsLib::ELK_INDEX,$text,['title','tags','nickname'],$page,$limit,['_source'=>false]);
        if(isset($response['hits']) && $response['hits']['total']['value']){
            $mv_id_array = array_column($response['hits']['hits'],'_id');
            //print_r($mv_id_array);die;
            $total = 200;
            $data =  \MvModel::queryBase()->whereIn('id',$mv_id_array)->where('is_aw',\MvModel::AW_NO)->orderByDesc('id')->get();
            if($data){
                $data  = self::formatList($data);
            }
            return [
                'total'  => $total,
                'list'   => $data,
            ];
        }
        return [
            'total'  => 0,
            'list'   => [],
        ];
    }
    
    static function getRowDetail($id,$member = null){
        $data = \MvModel::where('id', $id)->first();
        if (empty($data)) {
            throw new \Exception('视频不存在');
        }
        return self::formatDetail($data,$member);
    }


    static function getRecommendOld($id){
        /** @var \MvModel $mv */
        $mv = \MvModel::mvInfo($id);
        if(!is_null($mv)){
            $key = sprintf("lan:recommend:%d",$mv->uid);
            $data = cached($key)->fetchPhp(function () use ($mv){
                return \MvModel::queryBase()
                    ->where([
                        ['uid','!=',$mv->uid]
                    ])
                    ->whereRaw("match(tags) against(? in boolean mode)", [implode(' ',$mv->tags_list)])
                    ->where('is_aw',\MvModel::AW_NO)
                    ->orderByDesc('id')
                    ->limit(12)
                    ->get();
            });
        }else{
           $data = \MvModel::queryBase()
                ->where([
                    ['refresh_at','>=',strtotime("-3 month")]
                ])->orderByDesc('like')
                ->limit(12)
                ->get();
        }
        return self::formatList($data);

    }

    public static function getRecommend($id){
        /** @var \MvModel $mv */
        $mv = \MvModel::mvInfo($id);
        if(!is_null($mv) && count($mv->tags_list) > 0){
            $tag = $mv->tags_list[0];
            $key = sprintf("lan:recommend:%s",$tag);
            $data = cached($key)->fetchPhp(function () use ($tag){
                return \MvModel::queryBase()
                    ->whereRaw("match(tags) against(? in boolean mode)", [$tag])
                    ->where('is_aw',\MvModel::AW_NO)
                    ->orderByDesc('like')
                    ->orderByDesc('id')
                    ->limit(12)
                    ->get();
            });
        }else{
            $data = \MvModel::queryBase()
                ->where([
                    ['refresh_at','>=',strtotime("-3 month")]
                ])->orderByDesc('like')
                ->limit(12)
                ->get();
        }
        return self::formatList($data);

    }

    /**
     * lanpron 专用 友情链接
     * @return mixed
     */
    static function getLanPornData()
    {
        $idData = [
            //44,//男蜜圈
            38,//搜同
            28,//gtv
            //32,//蓝颜
            15,//菠萝
            53,//猎奇
            48,//blued
            55,//天涯
            40,//7du
            21,//成人快手
            12,//蚂蚁加速
        ];
        'product' != APP_ENVIRON && $idData = [9];
        return cached(\AdsAppModel::REDIS_LANPORN_KEY)->fetchJson(function () use ($idData) {
            return \AdsAppModel::whereIn('id', $idData)->orderByDesc('sort')->get(['id', 'title', 'link_url'])->toArray();
        }, 1000);
    }
}