<?php

namespace service;

use AdsModel;
use helper\QueryHelper;
use helper\Util;
use Illuminate\Database\Query\JoinClause;
use MvModel;
use MvTotalModel;
use SearchIndexModel;

/**
 * 搜索search层
 * Class SearchService
 * @package service
 * @author xiongba
 * @date 2020-03-16 20:16:56
 */
class SearchService
{
    /**
     * @var MvService
     */
    private $service;

    /**
     * SearchService constructor.
     * @author xiongba
     */
    public function __construct()
    {
        $this->service = new MvService;
    }


    public function getIndexAdList(\MemberModel $memberModel, $token)
    {
        $adsData = \service\AdService::getADsByPosition(AdsModel::POSITION_SEARCH_INDEX);
        $uuid = $memberModel->uuid;
        $uid = $memberModel->uid;
        if ($adsData) {
            foreach ($adsData as &$_ad) {
                if (in_array($_ad['type'], [2, 4])) {//不是外部链接
                    $_ad['url'] = getDataByExplode('#', $_ad['url']);//81592#81589
                } else {
                    $_ad['url'] = $_ad['url'] . "&uuid={$uuid}&token={$token}&uid={$uid}";
                }
            }
        }
        return $adsData;
    }

    /**
     * 获取热门搜索的关键词
     * @return string[]
     */
    public function getHotKeyword($is_ios=false)
    {
        $key = "keyword:top";
        $flag = false;
        return cached($key)
            ->serializerPHP()
            ->expired(3600)
            ->fetch(function ()use($is_ios,$key,$flag) {
                $hotWordSData=[];
                $limit = setting('search.hot.limit', 6);
                $all = SearchIndexModel::getHotSearch($limit);
                if($all) {
                    foreach ($all as $item) {
                        $hotWordSData[] = $item->word;
                    }
                }
                $hotWords = trim(setting('search.hot.words', ''));
                if($hotWords = explode(',', $hotWords)){
                    $hotWordSData = array_merge($hotWords,$hotWordSData);
                }
                \CacheKeysModel::createOrEdit($key,'热门搜索');
                return $hotWordSData;
            });
    }

    /**
     * 获取今天热播视频
     * @param int $limit
     * @return array
     */
    public function getHotPlay($limit = 6)
    {
        $useDb = (bool)setting('search.hotPlay.useDb', 1);
        if ($useDb) {
            return $this->getHotPlayUseDb($limit);
        } else {
            return $this->getHotPlayUseConfig($limit);
        }
    }
    /**
     * 获取今天创作者热播视频
     * @param int $limit
     * @return array
     */
    public function getCreatorHotPlay($limit = 6)
    {
        return $this->getCreatorHotPlayUseDb($limit);
    }

    /**
     * 获取今天热播视频
     * @param int $limit
     * @return array
     */
    public function getHotLike($limit = 6)
    {
        $all = cached('search:hot:like:limit-' . $limit)
            ->expired(600)
            ->serializerPHP()
            ->fetch(function () use ($limit) {
                return MvTotalModel::getLikeScoreMv($limit);
            });
        return $this->service->v2format($all);
    }

    /**
     * 带分页的视频热播榜
     * @param $type
     * @param $page
     * @param $limit
     * @return array
     */
    public function getRankListByType($type,$page,$limit){
        $all =  cached(sprintf('mv:rank:list:%s%d%d',$type,$page,$limit))
            ->expired(3600)
            ->serializerPHP()
            ->fetch(function () use ($type,$page,$limit) {
                if($type == 'like'){
                    return MvTotalModel::getScoreMv('like_num', $page, $limit);
                }elseif($type == 'sale'){
                    return MvTotalModel::getScoreMv('sale_num', $page, $limit);
                }elseif ($type == 'view'){
                    return MvTotalModel::getScoreMv('view_num', $page, $limit);
                }elseif ($type == 'creator'){
                    return MvTotalModel::getScoreMv('c_view_num', $page, $limit);
                }
            });
        return $this->service->v2format($all);
    }



    /**
     * 从数据库中获取热播的数据
     * @param int $limit
     * @return array
     */
    public function getHotPlayUseDb($limit = 6)
    {
        $all = cached('search:hot:play:limit-' . $limit)
            ->expired(600)
            ->serializerPHP()
            ->fetch(function () use ($limit) {
                return MvTotalModel::getViewScoreMv($limit);
            });
        return $this->service->v2format($all);
    }
    /**
     * 从数据库中获取热播的数据
     * @param int $limit
     * @return array
     */
    public function getCreatorHotPlayUseDb($limit = 6)
    {
        $all = cached('search:creator:hot:play:limit-' . $limit)
            ->expired(600)
            ->serializerPHP()
            ->fetch(function () use ($limit) {
                return MvTotalModel::getCreatorViewScoreMv($limit);
            });
        return $this->service->v2format($all);
    }

    /**
     * 获取配置中配置的热播数据
     * @return array
     */
    public function getHotPlayUseConfig()
    {
        $ids = explode(',', setting('search.hotPlay.ids', '265,266,267,268,269,270'));
        return $this->service->getByIdsKeepSort($ids);
    }


    /**
     * 获取今天热销的视频
     * @param int $limit
     * @return array
     */
    public function getHotSale($limit = 6)
    {
        $useDb = (bool)setting('search.hotSale.useDb', 1);
        if ($useDb || true) {
            return $this->getHotSaleUseDb($limit);
        } else {
            return $this->getHotSaleConfig($limit);
        }
    }


    /**
     * 从数据库中获取热销视频
     * @param int $limit
     * @return array
     */
    public function getHotSaleUseDb($limit = 6)
    {
        $all = cached('search:hot:sale:limit-' . $limit)
            ->expired(600)
            ->serializerPHP()
            ->fetch(function () use ($limit) {
                return MvTotalModel::getSaleScoreMv($limit);
            });
        return $this->service->v2format($all , request()->getMember());
    }

    /**
     * 获取配置中配置的热销视频
     * @return array
     */
    public function getHotSaleConfig()
    {
        $ids = explode(',', setting('search.hotSale.ids', '265,266,267,268,269,270'));
        return $this->service->getByIdsKeepSort($ids);
    }


    /**
     * 分析指定的文本的关键词，并更具关键词查询对应的视频id
     * @param string $text
     * @return array 返回一个二维数组 ，[ 匹配上的视频id [], 分析出来的关键词 [] , 是否走了缓存 boolean ]
     * @author xiongba
     * @date 2020-03-17 11:13:28
     */
    public function getVidWithKeyword(string $text)
    {
        $key = 'search:index:' . SearchIndexModel::generateWordHash($text);
        $keywords = [];
        $useCached = true;
        $vidArray = cached($key)
            ->serializerPHP()
            ->expired(1800)
            ->fetch(function () use ($text, &$keywords, &$useCached) {
                $useCached = false;
                $keywords = Util::keyword($text);
                return \MvWordsModel::getVidByWords($keywords);
            });
        return [$vidArray, $keywords, $useCached];
    }

    /**
     * 使用关键词搜索mv
     * @param string $text
     * @param \MemberModel $member
     * @return array
     * @throws \Throwable
     * @author xiongba
     * @date 2019-12-26 20:05:50
     */
    public function searchMv(string $text, \MemberModel $member)
    {
        list($limit, $offset,$page) = QueryHelper::restLimitOffset();
        if (false && setting('use:fc:search:', 0)) {
            //使用分词搜索
            list($vidArray, $keywords, $useCached) = $this->getVidWithKeyword($text);
            if (!$useCached) {
                \helper\Util::PanicFrequency($member->uid, 10, 60, "uid: {$member->uid} #{$text}# 1分钟内只能搜索10次");
            }
            array_shift($keywords);

            if ($offset == 0) {
                //第一页才进行关键字收录
                SearchIndexModel::addOrUpdate($text, count($vidArray), $keywords, $vidArray, $useCached ? true : false);
                \SearchTotalModel::addOrUpdate($text, $keywords);
            }
            $list = [];
            $total = 0;
            if (!empty($vidArray)) {
                $total = count($vidArray);
                $vidArray = array_slice($vidArray, $offset, $limit);
                if (!empty($vidArray)) {
                    $list = $this->service->getByIdsKeepSort($vidArray);
                }
            }
            //搜索引擎 有数据 就返回  没有就走原生搜索查下
            if ($list) {
                return [
                    'total'  => $total,
                    'list'   => $list,
                    'lastId' => 0,
                ];
            }
        }
        if(true){
            $_hasKey = SearchIndexModel::generateWordHash($text);
            $vidArray = SearchIndexModel::firstValue($text);
            /*$vidArray = cached("ss:{$_hasKey}")->setSaveEmpty(true)
                ->fetchJson(function ()use($text){
                return \SearchIndexModel::firstValue($text);
            },900);*/
            //var_dump($vidArray);die;
            $list = [];
            $total = 0;
            if ($vidArray) {
                $total = count($vidArray);
                $vidArray = array_slice($vidArray, $offset, $limit);
                $list = $this->service->getByIdsKeepSort($vidArray);
            }
            //搜索引擎 有数据 就返回  没有就走原生搜索查下
            if ($total) {
                return [
                    'total'  => $total,
                    'list'   => $list,
                    'lastId' => 0,
                ];
            }
        }

        if(true){
            /*$mv_id_array = (new ElkService())->searchIDS($text,$page,$limit);
            if($mv_id_array){
                return $this->searchByIDData($mv_id_array,$text);
            }*/
            $response = (new EsLib())->matchInKey(EsLib::ELK_INDEX,$text,['title','tags','nickname'],$page+1,$limit,['_source'=>false]);
            if(isset($response['hits']) && $response['hits']['total']['value']){
                return $this->searchByIDData(array_column($response['hits']['hits'],'_id'),$text);
            }
        }
        //硬搜索限制  对 vip 或 有币的  暂时不处理
        if($member->vip_level || $member->coins || $member->is_vip){
        }else{
            \helper\Util::PanicFrequency($member->uid, 10, 300, "uid: {$member->uid} #{$text}# 300-10,会员无限搜索~");
        }
        return $this->searchOriginData($text,$member);
    }

    public function searchMvNew($kwy, $show_type)
    {
        list($page, $limit) = QueryHelper::pageLimit();
        jobs([SearchIndexModel::class, 'firstValue'], [$kwy]);

        //es查询
        $idAry = EsService::search_mv($kwy, $show_type, $page, $limit);

        // 在ES中无法查询到 直接返回空
        if (empty($idAry)) {
            return [
                'total' => 0,
                'list' => [],
                'lastId' => 0,
            ];
        }

        $key = sprintf(MvModel::SEARCH_MV_LIST, $kwy, $show_type, $page, $limit);
        $list = cached($key)
            ->fetchPhp(function () use ($idAry) {
                $data =  MvModel::queryBase()
                    ->whereIn('id', $idAry)
                    ->with('user:uid,aff,thumb,nickname')
                    ->get();
                if($data){
                    $data  = (new MvService())->v2format($data);
                    $data = array_sort_by_idx($data, $idAry,'id');
                }
                return $data;
            }, 1800);
        if ($list){
            $list = (new MvService())->v2format($list);
        }
        return [
            'total' => count($list),
            'list' => $list,
            'lastId' => 0,
        ];
    }

    /**
     * es - search
     * @param array $mv_id_array
     * @param $text
     * @return array
     */
    function searchByIDData($mv_id_array = [],$text){
        $total = 1000;
       /* $data = cached('s:data:' . substr(md5($text),0,5))
            ->expired(1200)
            ->serializerPHP()
            ->fetch(function ()use($mv_id_array){
                $data =  \MvModel::query()->whereIn('id',$mv_id_array)
                    ->with('user:uid,aff,thumb,nickname')
                    ->get();
                return $data;
            });*/
        //list($limit,$offset,$page) = QueryHelper::restLimitOffset();
        //$search_mv_arr = collect($mv_id_array)->slice($offset,$limit)->toArray();

        /*if($_POST['oauth_id'] =='811021f5bdbf236f8c95454e24b9b9fb'){
            error_log("search:".var_export([$limit,$offset,$mv_id_array,$search_mv_arr],1));
        }*/

        $member = request()->getMember();
        $is_aw = 'no';
        if (\MemberModel::isAwVip($member)){
            $is_aw = "yes";
        }

        $data =  MvModel::queryBase()
            ->whereIn('id',$mv_id_array)
            ->when($is_aw == "no",function ($q){
                $q->where('is_aw', MvModel::AW_NO);
            })
            ->with('user:uid,aff,thumb,nickname')
            ->get();
        if($data){
            $data  = (new MvService())->v2format($data);
            $data = array_sort_by_idx($data,$mv_id_array,'id');
        }
        return [
            'total'  => $total,
            'list'   => $data,
            'lastId' => 0,
        ];
    }

    /**
     * 直接搜索 通过mv_id
     * @param $mv_id
     * @param $member
     * @return array
     */
    public function searchByMVID($mv_id){
        $total = 1;
        $list = cached('search:mv_id:' . $mv_id)
            ->expired(1200)
            ->serializerPHP()
            ->fetch(function ()use($mv_id){
                return MvModel::queryBase()
                    ->where('id','=',(int)$mv_id)
                    ->with('user:uid,aff,thumb,nickname')
                    ->get();
            });
        return [
            'total'  => $total,
            'list'   => (new MvService())->v2format($list),
            'lastId' => 0,
        ];
    }

    /**
     * 原始数据 硬搜索
     * @param string $text
     * @param \MemberModel $member
     * @return array
     */
    public function searchOriginData(string $text, \MemberModel $member){
        $date = date("H");
        if($date >=23 || $date<=2){
            return [
                'total'  => 0,
                'list'   => [],
                'lastId' => 0,
            ];
        }

        //直接使用like 搜索
        list($page,$limit) = QueryHelper::pageLimit();
        $query = MvModel::queryBase()
            ->where('created_at','>=',strtotime('-2 months'))
            ->where('title', 'like', "%$text%");
        $total = cached('search:count:')
            ->suffix($text)
            ->expired(1200)
            ->fetch(function () use ($query) {
                return (clone $query)->count('id');
            });
        $list = cached('search:list:' . $text)
            ->suffix($page)
            ->expired(1200)
            ->serializerPHP()
            ->fetch(function () use ($query, $limit, $page) {
                return $query->with('user:uid,aff,thumb,nickname')->orderByDesc('like')->forPage($page , $limit)->get();
            });
        return [
            'total'  => intval($total ?? 0),
            'list'   => (new MvService())->v2format($list,$member),
            'lastId' => 0,
        ];
    }

    public function searchUser($kwy, \MemberModel $userMember)
    {
        list($limit, $offset) = QueryHelper::restLimitOffset();
        $isMaker = false;
        if (is_numeric($kwy)) {
            $where = [
                ['role_id', '=', \MemberModel::USER_ROLE_LEVEL_MEMBER],
                ['uid', '=', (int)$kwy],
            ];
            $query = \MemberModel::query()
                ->select(['uid', 'aff', 'uuid', 'thumb', 'nickname', 'fans_count', 'videos_count', 'person_signnatrue'])
                ->where($where);
        } else {
            $query = \MemberMakerModel::with(['member' => function ($query) {
                return $query->select(['uid', 'aff', 'uuid', 'thumb', 'nickname', 'fans_count', 'videos_count', 'person_signnatrue']);
            }])->where('nickname', 'like', "%{$kwy}%")->orderByDesc('id');
            $isMaker = true;
        }
        $query->limit($limit)->offset($offset);

        list($total, $items) = cached('search:nickname:' . $kwy)
            //->setSaveEmpty(true)
            ->hash("p-" . ($offset + 1))
            ->serializerPHP()
            ->expired(1800)
            ->fetch(function ($cached) use (&$total, $query, $userMember, $isMaker) {
                $countQuery = clone $query;
                $results = $query->get();
                if ($isMaker) {
                    $results = $results->pluck('member');
                }
                $total = $countQuery->count();
                if ($total == 0) {
                    //如果没有数据，只缓存一分钟
                    $cached->expired(60);
                }
                return [$total, $results];
            });
        $service = new FollowedService();
        $results = $items->toArray();
        foreach ($results as &$result) {
            $result['is_attention'] = $service->isAttentionNew($userMember->aff, $result['aff']);
        }
        return [
            'total'     => $total,
            'list'      => $results,
            'lastIndex' => 0,
        ];

    }

    public function hotSearchRank(){
        $format = function ($str) {
            $str = trim($str);
            if (empty($str)) {
                return [];
            }
            $str = str_replace("，", ',', $str);
            $str = str_replace(',', '&', $str);
            parse_str($str, $ary);
            $return = [];
            foreach ($ary as $word => $num) {
                $return[] = [
                    'work' => (string)$word,
                    'num' => (int)$num,
                ];
            }
            return $return;
        };
        $result = $format(setting('hot:word-mv', ''));
        return collect(array_values($result))->sortByDesc('num')->values()->slice(0, 10);
    }


}