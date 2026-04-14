<?php


namespace service;


use MemberModel;
use MvTotalModel;
use service\VideoScoreService;
use DB;
use helper\QueryHelper;
use Illuminate\Database\Query\JoinClause;
use MvModel;
use repositories\MvRepository;
use repositories\UsersRepository;
use UserAttentionModel;
use const Yaf\ENVIRON;

class MvService extends \AbstractBaseService
{
    use MvRepository;
    use UsersRepository;


    public function formatItem($datum, $watchByMember = null,$isBuy= false)
    {
        /** @var MvModel $datum */
        $datum->addHidden([
            'cover_thumb',
            'm3u8',
            'full_m3u8',
            // 'is_hide',
            'y_cover',
            'tags',
            'actors',
            'category',
            'via',
            'onshelf_tm'
        ]);
        if ($watchByMember !== false) {
            if ($datum->user) {
                $datum->user->addHidden([
                    'phone',
                    'birthday',
                    'thumb',
                    'app_version',
                    'regip',
                    'lastvisit',
                    'lastip',
                    'username',
                    'password',
                    'oauth_id',
                    'build_id',
                    'oauth_type',
                    'uuid',
                    'gender',
                    'regdate',
                    'invited_by',
                    'invited_num',
                    'login_count',
                    'chat_uid',
                    'live_supper',
                    'is_live_super',
                ]);
            } else {
                $datum->user = MemberModel::virtualByForDelele();
            }
        }
        $datum->is_free = ($datum->coins <= 0) ? 1 : 0;
        $datum->play_url = getPlayUrl(ILLEGAL_ORG_VIDEO, false);
        $o_type = $_POST['oauth_type']??'';
        if($o_type == 'ios'){
            $datum->play_url = '';
        }
        if ($isBuy) {
            $attributes = $datum->getAttributes();
            if (isset($attributes['full_m3u8']) && !empty($attributes['full_m3u8'])) {
                $m3u8 = $attributes['full_m3u8'];
            } else {
                $m3u8 = $attributes['m3u8'] ?? '';
            }
            $datum->play_url = getPlayUrl($m3u8);
            $datum->setAttribute('is_pay', 1);
        }

        if((APP_TYPE_FLAG && IS_FAKE_CLIENT) || data_get($_SERVER , 'is_crack')){

            $datum->title = '版本失效，最新下载：' .getShareURL();
            $datum->play_url = getPlayUrl(ILLEGAL_ORG_VIDEO);
        }
        return $datum;
    }

    public function formatList($items, $watchByMember = null)
    {
        return $this->v2format($items, $watchByMember);
    }


    public function v2format($items, $watchByMember = null)
    {
        if (empty($items)) {
            return [];
        }
        $lists = [];
        foreach ($items as $datum) {
            $lists[] = $this->formatItem($datum, $watchByMember);
        }
        return $lists;
    }

    /**
     * 不管谁 进来首页 运营推荐数据展示
     * @param MemberModel $member
     * @return array
     */
    public function getHomeRecommendData(MemberModel $member)
    {
        $data = filecached()->fetch('home-rom' , function (){
            // 不要走缓存，。。。。。
            $ids = setting('home:recommend', '');
            $ids = array_map('intval', explode(',', $ids));
            $ids = array_filter($ids);
            $data = MvModel::queryWithUser()->with('user_topic')
                ->whereIn('id', $ids)->limit(100)->get();
            if ($data) {
                //重新排序
                $data = array_sort_by_idx($data, $ids, 'id');
            }
            return collect($data);
        } , 600);

        return [
            'list'     => $this->v2format($data, $member),
            'last_idx' => 0,
        ];
    }

    /**
     * 进来首页 运营推荐数据展示 使用redis缓存 增加排序
     * @param MemberModel $member
     * @return array
     * @throws \Throwable
     */
    public function getHomeRecommendDataWithRedis(MemberModel $member, $sort)
    {

        $ids = setting('home:recommend', '');
        $ids = array_map('intval', explode(',', $ids));
        $ids = array_filter($ids);
        $query = MvModel::queryWithUser()->with('user_topic')
            ->whereIn('id', $ids)->limit(100);
        $data = cached("home-recommend:{$sort}")
            ->expired(600)
            ->fetchPhp(function () use ($query,$sort,$ids) {
                if($sort == 'hot'){
                   $data =   $query->orderByDesc('like')->get();
                }else{
                    $data =  $query->get();
                    if($data){
                        //重新排序
                        $data = array_sort_by_idx($data, $ids, 'id');
                    }
                }
                return collect($data);
            });

        return [
            'list'     => $this->v2format($data, $member),
            'last_idx' => 0,
        ];
    }



    public function getListByScore(MemberModel $member , $page, $is_aw=0, $sort='new')
    {
        //运营推荐免费配置数据
        if ($page <= 1) {
//            $homeRecommend = $this->getHomeRecommendData($member);
            $homeRecommend = $this->getHomeRecommendDataWithRedis($member,$sort);
            if ($homeRecommend['list']) {
                return $homeRecommend;
            }
        }

        //晚上11点到凌晨1点高峰期走固定列表,90%的概率
        if (in_array((int)date('H'),[22,23,0,1]) && rand(1,10) != 10){
            $this->getListByScoreBusy($member,$is_aw);
        }

        if (showVideoStyle($member) == 0) {
            return $this->getFeeListByScore($member,$sort);
        }
        $uid = $member->uid;
        $key1 = 'feature:rate=1:' . $uid;
        $history = new VisitHistoryService($member->uid);

        $rate_eq_cb = function () use ($history, $uid) {
            static $ids = [];
            if (empty($ids)) {
                $ids = redis()->sRandMember(VideoScoreService::VIDEO_INIT_KEY, 200);
            }
            return array_diff($ids, $history->getAll());
        };
        $score_video_cb = function ($need_coins) use ($history) {
            static $ids = [];
            $need_coins = intval(boolval($need_coins));
            if (!isset($ids[$need_coins])) {
                if ($need_coins) {
                    $ids[$need_coins] = redis()->zRevRangeByScore(VideoScoreService::VIDEO_SCORE_COIN_KEY, 1, 0.01,
                        ['limit' => [0, 7000]]);
                } else {
                    $ids[$need_coins] = redis()->zRevRangeByScore(VideoScoreService::VIDEO_SCORE_KEY, 1, 0.01,
                        ['limit' => [0, 4000]]);
                }
            }
            $_ids = array_diff($ids[$need_coins], $history->getAll());
            if (empty($_ids)) {
                $_ids = $ids[$need_coins];
                $history->clearVisit();
            }
            return $_ids;
        };
        $ids_cb = function () use ($score_video_cb, $history) {
            $freeVideoIds = $score_video_cb(0); // 免费视频
            $feeVideoIds = $score_video_cb(1); // 收费视频
            return array_append_step($freeVideoIds, $feeVideoIds, 4);
        };
        $ids = [];
        $firstIds = redis()->sPopx($key1, 1, $rate_eq_cb);
        $key2 = 'feature:rate=0:' . $member->uid;
        $ids = redis()->lPopCount($key2, 10 - count($firstIds), $ids_cb);
        $ids = array_merge($firstIds, $ids);

//        $items = MvModel::queryWithUser()
//            ->with('user_topic')->whereIn('id', $ids)
//            ->where('is_aw',$is_aw)
//            ->get()->keyBy('id');
//        $history->addVisit(...$items->keys()->toArray());
//        $itemAry = [];
//        foreach ($ids as $id) {
//            if (!isset($items[$id])) {
//                continue;
//            }
//            $itemAry[] = $items[$id];
//        }
        $items = MvModel::queryWithUser()
            ->with('user_topic')->whereIn('id', $ids)
            ->where('is_aw',$is_aw)
            ->when($sort=='hot',function ($query){
                return  $query->orderByDesc('like');
            })
            ->orderByDesc('id')
            ->get();
        $mvIds = [];
        foreach ($items as $item){
            $mvIds[] = $item->id;
        }
        $history->addVisit(...$mvIds);
        $itemAry = $items;


        $list = (new \service\MvService())->v2format($itemAry, request()->getMember());
        $last_idx = '0';
        $last = end($list);
        if (!empty($list)) {
            $last_idx = $last['id'] ?? '0';
        } else {
            $history->clearVisit();
        }
        return [
            'list'     => $list,
            'last_idx' => $last_idx,
        ];
    }

    public function getListByScoreBusy(MemberModel $member, $is_aw)
    {
        list($page,$limit) = QueryHelper::pageLimit();
        $key = sprintf('home:recommend:mv:list:%d:%d:%d',$is_aw,$page,$limit);
        $items = cached($key)
            ->group('home:recommend:mv:list:group')
            ->chinese('推荐视频列表')
            ->fetchPhp(function () use ($is_aw,$page,$limit){
                return  MvModel::queryWithUser()
                    ->with('user_topic')
                    ->where('is_aw', $is_aw)
                    ->orderByDesc('rating')
                    ->orderByDesc('id')
                    ->forPage($page,$limit)
                    ->get();
            });

        return $this->v2format($items, $member);
    }

    public function getFeeListByScore(MemberModel $member, $sort='new')
    {
        list($limit, $offset,$page) = QueryHelper::restLimitOffset();
        $history = new VisitHistoryService($member->uid);
        $allMvIDArr = redis()->zRangeByScore(VideoScoreService::VIDEO_SCORE_KEY,0.01,1);
        $historyIds = $history->getAll();
        $scoreIDS = collect($allMvIDArr)->diff($historyIds)->shuffle();
        if ($scoreIDS->count() < 10) {
            $history->clearVisit();
            $scoreIDS = $allMvIDArr;
        }
        $items = \MvModel::queryWithUser()
            ->with('user_topic')
            ->where('coins','=',0)
            ->whereIn('id', $scoreIDS->slice(0, $limit+50)->values()->toArray())
            ->limit($limit)
            ->when($sort == 'hot',function ($query){
                return $query->orderByDesc('like');
            })
            ->orderByDesc('refresh_at')
            ->get();
        if(is_null($items)){
            return [];
        }
        $idKeys = collect($items)->map(function ($item){
            return $item->id;
        })->values()->toArray();
        $history->addVisit(...$idKeys);
        $list = $this->v2format($items, request()->getMember());
        return [
            'list'     => $list,
            'last_idx' => 0,
        ];
    }


    public function getItemsSortIds()
    {
        $items = MvModel::queryBase()
            ->with('user:uid,aff,nickname,thumb,auth_status')
            ->forPage($this->page, $this->limit)
            ->orderByDesc('id')
            ->get();

        $list = (new \service\MvService())->v2format($items, request()->getMember());
        $last_idx = '0';
        $last = end($list);
        if (!empty($list)) {
            $last_idx = $last['id'] ?? '0';
        }
        return [
            'list'     => $list,
            'last_idx' => $last_idx,
        ];
    }


    /**
     * 使用指定的id获取视频。并保障和id排序一样
     * @param array $ids
     * @return array
     * @author xiongba
     * @date 2020-03-16 20:12:52
     */
    public function getByIdsKeepSort(array $ids)
    {
        $member = request()->getMember();
        $is_aw = 'no';
        if (MemberModel::isAwVip($member)){
            $is_aw = "yes";
        }
        //$all = \MvModel::queryBase()->with('user_topic')->whereIn('id', $ids)->get();
        $all = \MvModel::queryBase()
            ->whereIn('id', $ids)
            ->when($is_aw == "no",function ($q){
                $q->where('is_aw',MvModel::AW_NO);
            })
            ->get();
        $all = $this->v2format($all, $member);
        $ary = array_reindex($all, 'id');
        $result = [];
        foreach ($ids as $id) {
            if (isset($ary[$id])) {
                $result[] = $ary[$id];
            }
        }
        return $result;
    }


    /**
     * 使用指定标签获取收费视频id，并将结果缓存10分钟
     * @param string $tag 视频标签
     * @param null $official
     * @return array
     * @author xiongba
     */
    public function getChargeMvIdByTagName(string $tag, $official = null)
    {
        $key = 'charge:tag:' . $tag;
        if ($official === true) {
            $key .= '-official';
        } elseif ($official === false) {
            $key .= '-user';
        }
        return cached($key)
            ->setSaveEmpty(true)
            ->expired(1200)
            ->serializerPHP()
            ->fetch(function ($cached) use ($tag, $official) {
//                $idInTags = \MvTagModel::distinct()
//                    ->where('tag' , '=' , $tag)
//                    ->get(['mv_id'])
//                    ->map(function ($v){
//                        return $v->mv_id;
//                    })->toArray();
//                $ids = \MvModel::queryBase()
//                    ->where('coins' ,'>' , 0)
//                    ->whereIn('id' , $idInTags)
//                    ->get(['id'])
//                    ->map(function ($v){
//                        return $v->id;
//                    });
                if (ENVIRON == 'test') {
                    $query = MvModel::queryFee()->where('tags', 'like', "%$tag%");
                } else {
                    $query = MvModel::queryFee()->where('is_aw', MvModel::AW_NO)->whereRaw("match(tags) against(?)", [$tag]);
                }
//                if ($official === true) {
//                    $query->where('uid', (int)getOfficialUID());
//                } elseif ($official === false) {
//                    $query->where('uid', '!=', (int)getOfficialUID());
//                }
                $ids = $query->orderByDesc('id')->pluck('id');

                if ($ids->isEmpty()) {
                    /** @var \CacheDb $cached */
                    $cached->expired(200);
                }
                return $ids->toArray();
            });
    }

    /**
     * 使用指定标签获取收费视频
     * @param MemberModel $member
     * @param int $lastId
     * @param string $tag
     * @param null $official 是否值读取官方账号的视频 ，null全部，true=只读官方的。false=只读用户的
     * @return array
     * @author xiongba
     */
    public function getChargeMvByCached(MemberModel $member, int $lastId, string $tag, $official = null)
    {

        list($limit, $offset) = QueryHelper::restLimitOffset();
        //剔除用户买过的视频
        $boughtVidArray = \MvPayModel::getVid($member->uid);
        $ids = collect(array_diff($this->getChargeMvIdByTagName($tag, $official), $boughtVidArray));
        if ($lastId) {
            $ids->filter(function ($v) use ($lastId) {
                return $v < $lastId;
            });
            $offset = 0;
        }
        $ids = $ids->slice($offset, $limit)->toArray();
        $list = MvModel::queryWithUser()->with('user_topic')
            ->whereIn('id', $ids)
            ->orderBy('refresh_at', 'desc')->get();
        $list = $this->v2format($list, $member);
        return [
            'total'     => count($ids),
            'list'      => $list,
            'lastIndex' => 0
        ];
    }

    /**
     * 在缓存中获取用户的关注作者的视频id，如果缓存没有，从数据库获取
     * @param MemberModel $member
     * @return mixed
     * @author xiongba
     */
    public function getChargeVidByFollow(MemberModel $member)
    {
        return $this->getVidByFollow($member, true);
    }

    /**
     * 在缓存中获取用户的关注作者的视频id，如果缓存没有，从数据库获取
     * @param MemberModel $member
     * @param null $isFee
     * @return mixed
     * @author xiongba
     */
    public function getVidByFollow(MemberModel $member, $isFee = null)
    {
        $uidArt = UserAttentionModel::getList($member)->pluck('touid');
        return cached('follow:vid:' . $isFee ?? 'null')
            ->suffix($member->uid)
            ->serializerPHP()
            ->expired(7200)
            ->fetch(function () use ($uidArt, $isFee) {
                $query = \MvModel::query()->whereIn('uid', $uidArt)
                    ->where('status', '=', MvModel::STAT_CALLBACK_DONE)
                    ->where('is_hide', '=', 0);
                if ($isFee === false) {
                    $query->where('coins', '=', 0);
                } elseif ($isFee === true) {
                    $query->where('coins', '>', 0);
                }
                //获取用户关注者的视频列表
                return $query->pluck('id')->toArray();
            });
    }

    /**
     * 获取用户关注的作者发布的收费视频
     * @param MemberModel $member
     * @return array
     * @author xiongba
     * @date 2020-03-17 19:14:25
     */
    public function getChargeMvByFollow(MemberModel $member)
    {
        list($page, $limit) = QueryHelper::pageLimit();
        $uidArt = UserAttentionModel::getList($member)->pluck('touid');
        $query = MvModel::queryWithUser()
            ->with('user_topic')
            ->whereIn('uid', $uidArt)
            //->with('user:uid,nickname,thumb,vip_level,sexType,expired_at,uuid,uid')
            ->where('coins', '>', 0);
        $totalQuery = clone $query;
//        $mvItems = $query->forPage($page,$limit)
//            ->orderByDesc('refresh_at')
//            ->get();

        $count = cached('get:charge:mv:by:follow:count:'.$member->uid)
            ->fetchJson(function () use ($totalQuery,$uidArt){
                return $totalQuery->count();
            },1800);
        $mvItems = cached(sprintf('get:charge:mv1:by:follow:%d:%d:%d',$member->uid,$page,$limit))
            ->fetchPhp(function () use ($query,$uidArt,$page,$limit){
                return $query->forPage($page,$limit)
                    ->orderByDesc('refresh_at')
                    ->get();
            });
        return [
            'total'     => $count,
            'list'      => $this->v2format($mvItems, $member),
            'lastIndex' => 0,
        ];
    }

    /**
     * 购买视频
     * @param MemberModel $member
     * @param $vid
     * @return MvModel
     * @throws \Throwable
     * @author xiongba
     */
    public function buyMv(MemberModel $member, $vid)
    {
        MvModel::setWatchUser($member);
        //1 查询视频
        /** @var MvModel $videoModel */
        $videoModel = MvModel::query()->with('user:uid,nickname,thumb,vip_level,auth_status,sexType,expired_at,uuid,oauth_type,oauth_id,votes,votes_total,score,score_total')->find($vid);
        if (empty($videoModel)) {
            throw new \Exception('视频不存在');
        }
        if (empty($videoModel->coins)) {
            throw new \Exception('该视频不支持购买');
        }
        /**
         * @var MemberModel $authorModel
         */
        $authorModel = $videoModel->user;
        if(is_null($authorModel)){
            throw new \Exception('视频用户不存在');
        }

        if ($authorModel->uid == $member->uid) {
            return $this->formatItem($videoModel, $member,true);
        }
        // 不允许重复购买视频购买
        if (\MvPayModel::hasPay($member->uid, $vid)) {
            cached('v2:user:idolVideo:')->suffix($member->uid)->clearCached();
            return $this->formatItem($videoModel, $member,true);
        }
        $total = $videoModel->coinsAfterDiscount($member);

        list($is_kou,$_flg) = \KouLogModel::addLog($videoModel);

        try {
            DB::beginTransaction();
            //扣款
            do {
                //价格小于等于0 不需要影响用户的日志和首款日志
                if ($total <= 0) {
                    break;
                }
                //$itOk = $member->incrMustGE_raw(['coins' => -$total, 'consumption' => $total]);
                $itOk = MemberModel::where([
                    ['uid', '=', $member->uid],
                    ['coins', '>=', $total],
                ])->update([
                    'coins'       => DB::raw("coins-{$total}"),
                    'consumption' => DB::raw("consumption+{$total}")
                ]);
                if (empty($itOk)) {
                    throw new \Exception('扣款失败,请确认您的金币是否足够', 1008);
                }
                //记录日志
                $action = 'buymv';
                $rs3 = \UsersCoinrecordModel::addMvExpend($member->uid, $videoModel, $total,$is_kou);
                if (empty($rs3)) {
                    throw new \Exception('操作失败，请重试');
                }
                if(!$is_kou) {
                    //$itOk = $authorModel->incrMustGE_raw(['score' => $total, 'score_total' => $total]);
                    $itOk = $authorModel->update(['score' => DB::raw("score+{$total}"), 'score_total' => DB::raw("score_total+{$total}")]);
                    /*if($member->uid == '8209098'){
                        $_mm = $authorModel->uid.' '.$total;
                        errLog("buy:{$itOk}".$_mm);
                    }*/
                    if (empty($itOk)) {
                        throw new \Exception('转账失败');
                    }
                    $itOk = \UserVoterecordModel::addIncome($authorModel->uid, $action, $total);
                    if (empty($itOk)) {
                        throw new \Exception('记录收益日志失败');
                    }
                }
            } while (false);

            $model = \MvPayModel::createBuyLog($member->uid, $videoModel->uid, $videoModel->id, $videoModel->type, $total);
            if (empty($model)) {
                throw new \Exception('操作失败，请重试');
            }
            $itOk = \MvTotalModel::incrBuy($videoModel->id, 1);
            if (empty($itOk)) {
                throw new \Exception('操作失败，请重试');
            }
            if(!$is_kou){
                $itOk = $videoModel->increment('count_pay', 1);
                if (empty($itOk)) {
                    throw new \Exception('操作失败，请重试');
                }
            }

            DB::commit();
            if ($authorModel->auth_status) {
                \MemberMakerModel::where(['uuid' => $authorModel->uuid])->increment('total_coins', $total);
            }
            //更新用户缓存
            MemberModel::clearFor($member);
            MemberModel::clearFor($authorModel);
            for ($i = 0; $i < 2; $i++) {
                redis()->del("coin:log:{$member->uid}:{$i}");
            }
            cached('v2:user:idolVideo:')->suffix($member->uid)->clearCached();
            for ($i = 0; $i <= $member->likes_count / 20; $i++) {
                cached(\MvModel::REDIS_USER_LIKE_VIDEOS_ITEM . $member->uid . '_')->suffix($i)->clearCached();
            }
            $videoModel->emitChange(false);
            if(!$is_kou){
                (new TopCreatorService())->incrIncome($videoModel->uid, $total);//非官方收益排行统计
            }
            \MvPayModel::addVidArr($member->uid, $vid);
            //排行榜
            MvTotalModel::addCacheData($videoModel->id, $videoModel->is_aw, MvTotalModel::FIELD_SALE, $videoModel->type, 1);

            //金币消耗上报
            (new EventTrackerService(
                $member->oauth_type,
                $member->invited_by,
                $member->uid,
                $member->oauth_id,
                $_POST['device_brand'] ?? '',
                $_POST['device_model'] ?? ''
            ))->addTask([
                'event'                 => EventTrackerService::EVENT_COIN_CONSUME,
                'product_id'            => (string)$videoModel->id,
                'product_name'          => $videoModel->title,
                'coin_consume_amount'   => (int)$videoModel->coins,
                'coin_balance_before'   => (int)($member->coins + $total),
                'coin_balance_after'    => (int)$member->coins,
                'consume_reason_key'    => 'video_unlock',
                'consume_reason_name'   => '视频解锁',
                'order_id'              => (string)$rs3->id,
                'create_time'           => to_timestamp($rs3->addtime),
            ]);

            //视频购买上报
            (new EventTrackerService(
                $member->oauth_type,
                $member->invited_by,
                $member->uid,
                $member->oauth_id,
                $_POST['device_brand'] ?? '',
                $_POST['device_model'] ?? ''
            ))->addTask([
                'event'                 => EventTrackerService::EVENT_VIDEO_PURCHASE,
                'video_id'              => (string)$videoModel->id,
                'video_title'           => $videoModel->title,
                'video_type_id'         => '',
                'video_type_name'       => '',
                'coin_quantity'         => (int)$videoModel->coins,
                'order_id'              => (string)$model->id,
            ]);

            return $this->formatItem($videoModel, $member,true);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

    }

    /**
     * 使用观影卷购买视频
     * @param int $mv_id
     * @param int $ticket_id
     * @param MemberModel $member
     * @return array
     * @throws \Throwable
     */
    public  function checkByTicket($mv_id, MemberModel $member)
    {

        /** @var \MvTicketModel $model */
        $model = \MvTicketModel::myLatestMvTicketRow($member);
        if (empty($model)) {
            throw new \Exception('观影券已使用完~');
        }

        /** @var \MvModel $vModel */
        $vModel = \MvModel::queryBase()->where('id', $mv_id)->first();
        if (is_null($vModel)) {
            throw new \Exception('视频不存在');
        }

        if (\MvPayModel::hasPay($member->uid, $mv_id)) {
            throw new \Exception('您已购买过该视频');
        }
        //print_r($vModel->toArray());die;
        try {
            DB::beginTransaction();
            $itOk = \MvPayModel::createTicketLog($member->uid, $vModel->uid, $vModel->id, $vModel->type, $vModel->coins);
            if (empty($itOk)) {
                throw new \Exception('使用观影卷失败1');
            }
            $itOk = $model->where([
                'status' => \MvTicketModel::STATUS_INIT,
                'id'     => $model->id
            ])->update([
                'used_at' => date("Ymd"),
                'mv_id'   => $vModel->id,
                'mv_uid'  => $vModel->uid,
                'status'  => \MvTicketModel::STATUS_USED,
            ]);
            if (empty($itOk)) {
                throw new \Exception('使用观影卷失败2');
            }
            DB::commit();
            \MvTotalModel::incrBuy($vModel->id, 1);
            $vModel->increment('count_pay', 1);
            \MvPayModel::addVidArr($member->uid, $vModel->id);
            return $this->formatItem($vModel, $member,true);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * 获取用户购买的视频
     * @param MemberModel $member 用户
     * @param int $lastIndex 缩影
     * @return array
     */
    public function getBought(MemberModel $member, $show_type, $lastIndex)
    {
        list($limit, $offset) = QueryHelper::restLimitOffset();
        $query = \MvPayModel::with([
                'mv' => function ($query) {
                    return $query->with('user:uid,nickname,thumb,uid,expired_at,vip_level,uuid,sexType');
                }
            ])
            ->where('uid', $member->uid)
            ->where('show_type', $show_type)
            ->orderByDesc('id');
        $total = $query->count('id');
        if ($lastIndex) {
            $query->where('id', '<', $lastIndex);
            $offset = 0;
        }
        $isDelete = false;
        $list = $query->limit($limit)->offset($offset)->get()->map(function ($item) use (&$lastIndex, &$isDelete) {
            /** @var \MvPayModel $item */
            $lastIndex = $item->id;
            if ($item->mv === null) {
                $isDelete = true;
            }
            return $item->mv;
        })->filter();

        if (false && $isDelete) {
            $allId = \MvPayModel::getBought($member->uid)->pluck('mv_id');
            $delIds = $allId->diff(\MvModel::whereIn('id', $allId)->pluck('id'))->toArray();
            \MvPayModel::whereIn('mv_id', $delIds)->delete();
        }

        $vids = $list->pluck('id')->toArray();
        \MvPayModel::addVidArr($member->uid, $vids);

        return [
            'total'     => $total,
            'list'      => $this->v2format($list, $member),
            'lastIndex' => $lastIndex
        ];

    }

    /**
     * @param $id
     * @param null|MemberModel $member
     * @return array
     * @throws \Exception
     * @author xiongba
     */
    public function firstById($id, $member = null)
    {
        MvModel::setWatchUser($member);
        MemberModel::setWatchUser($member);
        $data = MvModel::findById($id);
        if (empty($data)) {
            throw new \Exception('视频不存在');
        }

        $data->addHidden(['m3u8', 'cover_thumb', 'full_m3u8', 'v_ext']);
        $hls = $data->full_m3u8 ?: $data->m3u8;
        $preview_video = null;
        $preview_tip = '';
        $isPreviewVideo = false;
        if ($data->is_pay) {
            $data->play_url = getPlayUrl($hls);
        } else {
            if ($data->coins == 0){
                $key = MvModel::generateWatchKey($member);
                $seeNum = redis()->sCard($key);
                $allowNum = (int)setting("site.can_watch_count", 6);
                if ($seeNum > $allowNum) {
                    $data->play_url = ''; //白票用户超过次数  空播放返回
                    $preview_tip = '开通VIP解锁完整版>>';
                    $isPreviewVideo = true;
                } else {
                    redis()->sAddTtl($key, $data->id, 86600);
                    $data->play_url = getPlayUrl($hls, false);
                    $data->setAttribute('is_pay', 1);
                }
            }else{
                $data->play_url = '';
                $preview_tip = sprintf("%d金币解锁完整版>>", $data->coins);
                $isPreviewVideo = true;
            }
        }

//        if (APP_TYPE_FLAG == 0) {
            if ($isPreviewVideo) {
                $preview_video = url_video_short($data->m3u8 ?: $data->full_m3u8);
                if($preview_video){
                    $preview_video = $preview_video.'&seconds=10';
                }
            }
            $data->preview_video = $preview_video;
            $data->preview_tip = $preview_tip;
//        }

        $result = $data->toArray();
//        if((APP_TYPE_FLAG && IS_FAKE_CLIENT) || data_get($_SERVER , 'is_crack')){
//            $result['title'] = '版本将失效，最新下载：' .getShareURL();
//            $result['play_url'] = getPlayUrl(ILLEGAL_ORG_VIDEO, false);
//        }

        $result['m3u8'] = ILLEGAL_ORG_VIDEO;
        return  $result;
    }

    /**
     * 获取详情推荐
     * @param $mvId
     * @return array
     */
    public static function getRecommendByMvTags($mvId){
        $mv = MvModel::findById($mvId);
        test_assert($mv, '视频不存在');
        $construct_id = $mv->construct_id;
        if ($construct_id > 0){
            $key = "mv:detail:recommend:construct:{$construct_id}";
            $ids = cached($key)
                ->fetchPhp(function () use ($construct_id) {
                    return MvModel::queryWithUser()
                        ->selectRaw('id')
                        ->where('construct_id', $construct_id)
                        ->orderByDesc('like')
                        ->orderByDesc('id')
                        ->limit(300)
                        ->get();
                });
            if ($ids->count() > 6){
                $ids = $ids->random(6);
            }
            if ($ids->count() > 0){
                $ids = array_column($ids->toArray(), 'id');
                $data = MvModel::whereIn('id', $ids)->get();
                return (new \service\MvService())->v2format($data);
            }
            return [];
        }else{
            $tagStr = '';
            $tags = $mv->tags_list;
            $is_aw = $mv->is_aw;
            if(count($tags)> 1){
                $index = array_rand($tags);
                $tagStr = $tags[$index];
            }else{
                if($tags){
                    $tagStr = $tags[0];
                }
            }
            $str = md5($tagStr.$is_aw);
            $key = "mv:detail:recommend:{$str}";
            $items = cached($key)
                ->fetchPhp(function () use ($is_aw, $tagStr) {
                    return MvModel::queryWithUser()
                        ->where('is_aw', $is_aw)
                        ->whereRaw("match(tags) against('\"$tagStr\"' in boolean mode)")
                        ->orderByDesc('like')
                        ->orderByDesc('id')
                        ->limit(6)
                        ->get();
                });
        }
        $data = (new \service\MvService())->v2format($items);
        return $data;
    }


    /**
     * 获取推荐视频 新 -按照最新获取 因为之前的redis 队列没有更新 才1600部左右
     * @param int $lastIndex
     * @param $memberId
     * @param null $official
     * @return array
     */
    public function getChargeRecommendNew(int $lastIndex, $memberId, $official = null)
    {
        list($limit, $offset, $page) = QueryHelper::restLimitOffset();
        $query = MvModel::queryFeeRecommend();
        $key = "chrg:mv";
        $queryTotal = clone $query;
        $total = cached($key)
            ->expired(900)
            ->fetch(function () use ($queryTotal) {
                return $queryTotal->count('id');
            });
        $key = "chrg:vdata:{$page}";
        $list = cached($key)
            ->expired(900)
            ->serializerPHP()
            ->fetch(function () use ($query, $limit, $offset) {
                return $query->with('user_topic')
                    ->orderByDesc('refresh_at')
                    ->offset($offset)
                    ->limit($limit)
                    ->get();
            });
        return [
            'total'     => $total,
            'list'      => $this->v2format($list),
            'lastIndex' => $lastIndex
        ];
        $total = (clone $query)->count();

        $all = $query
            ->with('user_topic')
            ->orderByDesc('refresh_at')
            ->offset($offset)
            ->limit($limit)
            ->get();

        return [
            'total'     => $total,
            'list'      => $this->v2format($all, request()->getMember()),
            'lastIndex' => $lastIndex
        ];
    }

    /**
     * 作品 管理 相关 服务逻辑 查询
     * @param MemberModel $member
     * @param array $where
     * @param string $order
     * @return MvModel[]
     */
    public function getUserWorks(MemberModel $member, $where = [], $order = 'id')
    {
        $where[] = ['uid', '=', $member->uid];
        list($limit, $offset, $page) = QueryHelper::restLimitOffset();
        $data = MvModel::where($where)
            ->limit($limit)
            ->offset($offset)
            ->orderByDesc('is_top')
            ->orderByDesc($order)
            ->with('user:uid,nickname,thumb,aff,expired_at,vip_level,sexType')
            ->with('user_topic')
            ->get();
        return $this->v2format($data);
    }

    /**
     * 作品 管理 相关 服务逻辑 设置
     * @param array $where
     * @param array $data
     */
    public function setUserWorks($where = [], $data = [])
    {

        return MvModel::where($where)->update($data);

    }

    /**
     * 最热推荐
     *
     * @param MemberModel $member
     * @return array
     */
    public function getHotList(MemberModel $member, $is_aw=0)
    {
        //晚上11点到凌晨1点高峰期走固定列表,90%的概率
        if (in_array((int)date('H'),[22,23,0,1]) && rand(1,10) != 10){
            $this->getHotListBusy($member,$is_aw);
        }
        $is_style = showVideoStyle($member);
        $uid = $member->uid;
        $perfect = "hmv:{$is_style}";
        $videoIds = redis()->sMembers($perfect);
        $ttl = redis()->ttl($perfect.'_ttl');

        if (empty($videoIds) || $ttl < 60) {
            redis()->setex($perfect.'_ttl', 2000, 1);
            async_task_cgi(function () use ($is_style, $perfect) {
                $query = MvModel::queryBase();
                if (!$is_style) {
                    $query->where('coins', '=', 0)->where('rating', '>', 10000);
                } else {
                    $query->where('like', '>', 199);
                }
                $videoIds = $query->pluck('id')->toArray();
                redis()->sAddArray($perfect, $videoIds);
            });
        }
        $history = new VisitHistoryService($uid);
        $historyIds = $history->getAll() ?? [];
        $list = collect($videoIds)->diff($historyIds);
        $limit = 20;
        if ($list->count() < $limit) {
           return [];
        }
        $list = $list->shuffle()->slice(0, $limit);
        $history->addVisit($list);
        $items = MvModel::queryWithUser()
            ->with('user_topic')
            ->whereIn('id', $list)
            ->where('is_aw',$is_aw)
            ->orderByDesc('id')
            ->get();
        $items = $this->v2format($items, $member);
        return $items;
    }

    public function getHotListBusy(MemberModel $member, $is_aw=0){
        list($page,$limit) = QueryHelper::pageLimit();
        $key = sprintf('home:hot:mv:list:%d:%d:%d',$is_aw,$page,$limit);
        $items = cached($key)
            ->group('home:hot:mv:list:group')
            ->chinese('最热视频列表')
            ->fetchPhp(function () use ($is_aw,$page,$limit){
                return  MvModel::queryWithUser()
                    ->with('user_topic')
                    ->where('is_aw', '=', $is_aw)
                    ->where('like', '>', 199)
                    ->orderByDesc('id')
                    ->forPage($page,$limit)
                    ->get();
            });

        $items = $this->v2format($items, $member);
        return $items;
    }

    /**
     * tab av
     * @param int $tab_id
     * @param MemberModel $member
     * @return array
     */
    public function getTabList($tab_id, MemberModel $member, $is_aw=0, $sort='new')
    {
        //晚上11点到凌晨1点高峰期走固定列表,90%的概率
        if (in_array((int)date('H'),[22,23,0,1]) && rand(1,10) != 10){
            $this->getTabListBusy($tab_id,$member,$is_aw);
        }
        $uid = $member->uid;
        $perfect = "tab:data:{$tab_id}";
        $is_aw && $perfect = "tab:data:{$tab_id}:{$is_aw}";
        $videoIds = redis()->sRandMember($perfect, 2000);
        $ttl = redis()->ttl($perfect.'_ttl');
        if (empty($videoIds) || $ttl < 60) {
            $maxId = $videoIds?max($videoIds):0;
            $matchStr = \TabModel::getMatchString($tab_id);
            if ($maxId > 0){
                //新的取20条放进去
                $videoIds =  MvModel::queryBase()
                    ->select(['id'])
                    ->where('id', '>', $maxId)
                    ->where('is_aw', '=', $is_aw)
                    ->whereRaw("match(tags) against(? in boolean mode)", [$matchStr])
                    ->orderByDesc('id')
                    ->limit(20)
                    ->pluck('id')
                    ->toArray();
            }else{
                //500条取一次
                collect(range(1,5))->map(function ($i) use ($tab_id,$is_aw,$matchStr,&$videoIds){
                    $ids =  MvModel::queryBase()
                        ->select(['id'])
                        ->where('is_aw', '=', $is_aw)
                        ->whereRaw("match(tags) against(? in boolean mode)", [$matchStr])
                        ->orderByDesc('id')
                        ->forPage($i,500)
                        ->pluck('id')
                        ->toArray();
                    $videoIds = array_merge($videoIds,$ids);
                });
            }
            redis()->sAddArray($perfect, $videoIds);
            redis()->setex($perfect.'_ttl',1000,1);
        }
        $history = new VisitHistoryService($uid);
        $historyIds = $history->getAll() ?? [];
        $list = collect($videoIds)->diff($historyIds);
        $limit = 20;
        if ($list->count() < $limit) {
           return [];
        }
        $list = $list->shuffle()->slice(0, $limit);
        $history->addVisit($list);
        $items = MvModel::queryWithUser()
            ->with('user_topic')
            ->whereIn('id', $list)
            ->where('is_aw',$is_aw)
            ->when($sort,function ($query)use($sort){
                if($sort == 'pay'){
                    $query->orderByDesc('count_pay');
                }elseif ($sort == 'view'){
                    $query->orderByDesc('rating');
                }else{
                    $query->orderByDesc('refresh_at');
                }
            })
            ->get();
        $items = $this->v2format($items, $member);
        return $items;
    }

    /**
     * tab av
     * @param int $tab_id
     * @param MemberModel $member
     * @return array
     */
    public function getTabListBusy($tab_id, MemberModel $member, $is_aw=0, $sort='new')
    {
        list($page,$limit) = QueryHelper::pageLimit();
        $key = sprintf('home:tab:mv:list:%d:%d:%d:%d:%s',$tab_id,$is_aw,$page,$limit,$sort);
        $items = cached($key)
            ->group('home:tab:mv:list:group')
            ->chinese('Tab视频列表')
            ->fetchPhp(function () use ($tab_id,$is_aw,$sort,$page,$limit){
                $matchStr = \TabModel::getMatchString($tab_id);
                return  MvModel::queryWithUser()
                    ->with('user_topic')
                    ->where('is_aw', '=', $is_aw)
                    ->whereRaw("match(tags) against(? in boolean mode)", [$matchStr])
                    ->when($sort,function ($query)use($sort){
                        if($sort == 'pay'){
                            $query->orderByDesc('count_pay');
                        }elseif ($sort == 'view'){
                            $query->orderByDesc('rating');
                        }else{
                            $query->orderByDesc('refresh_at');
                        }
                    })
                    ->orderByDesc('id')
                    ->forPage($page,$limit)
                    ->get();
            });

        $items = $this->v2format($items, $member);
        return $items;
    }
    public function add2SeeRank($mvId,$tab_id)
    {
        if (!$tab_id) {
            return;
        }
        $rankKey = sprintf(MvModel::RK_PLAYING, $tab_id);
        redis()->zAdd($rankKey, time(), $mvId);

    }

    public function add2Rank($mvId,$tab_id)
    {
        if (!$tab_id) {
            return;
        }
        $startOfWeek = MvModel::getStartOfWeek();
        MvModel::add2Rank($tab_id, $mvId,$startOfWeek);
    }


    public function getlistdata($tabId,$member,$is_aw,$sort){
        $data = $this->getTabList($tabId,$member,$is_aw,$sort);
        if (!$data) {
            $page = $this->page;
            $limit = $this->limit;
            $items = cached(sprintf('listOfTab:no:%d:%d:%d:%d:%s',$tabId,$is_aw,$page,$limit,$sort))
                ->group('listOfTab:no')
                ->chinese('标签视频列表NO')
                ->fetchPhp(function () use ($tabId,$is_aw,$page, $limit,$sort){
                    $tagStr = \TabModel::getMatchString($tabId);
                    return \MvModel::queryWithUser()
                        ->where('is_aw', $is_aw)
                        ->with('user_topic')
                        ->whereRaw("match(tags) against(? in boolean mode)", [$tagStr])
                        ->forPage($page, $limit)
                        ->when($sort=='hot',function ($query){
                            return $query->orderByDesc('like');
                        })
                        ->orderByDesc('refresh_at')
                        ->get();
                });

            $data = $this->v2format($items, request()->getMember());
        }
        return $data;
    }

}