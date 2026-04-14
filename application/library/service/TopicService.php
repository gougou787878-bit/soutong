<?php
/**
 *
 * @date 2020/2/27
 * @author
 * @copyright kuaishou by KS
 * @todo 专题合集视频相关
 *
 */

namespace service;

use helper\QueryHelper;
use Illuminate\Support\Collection;
use MemberModel;
use TopicModel;

/**
 * Class TopicService
 * @package service
 */
class TopicService
{
    const TOPIC_LIST_KEY = 'topic:list';
    const TOPIC_ROW_KEY = 'topic:row:';
    const TOPIC_MV_KEY = 'topic:mv:';

    /**
     * 获取可用专题列表
     * @param $page
     * @param $size
     * @return mixed
     */
    public static function getTopicList($page, $size)
    {
        $ids = cached(self::TOPIC_LIST_KEY)
            ->hash($page)
            ->expired(3600)
            ->serializerJSON()
            ->fetch(function () use ($page, $size) {
                return TopicModel::queryBase()->orderByDesc('id')->forPage($page, $size)->pluck('id');
            });
        $data = TopicModel::queryBase()->whereIn('id', $ids)->orderByDesc('id')->get();
        if ($data->isEmpty()) {
            return [];
        }
        return $data;
    }

    /**
     * 专题列表
     * @param \MemberModel $member
     * @param $limit
     * @return Collection|\UserTopicModel[]
     */
    public static function getTopics(\MemberModel $member,$sort,$page,$limit)
    {
        $key = 'topic1:list:%s:%d:%d';
        $rediskey =   sprintf($key,$sort,$page,$limit);
        $items = cached($rediskey)
            ->expired(3600)
            ->serializerJSON()
            ->setSaveEmpty(true)
            ->fetch(function () use ($sort,$page,$limit) {
                return \TopicModel::queryBase()
                    ->when($sort,function ($query)use($sort){
                        if($sort == 'like'){
                            return $query->orderByDesc('like_count');
                        }elseif ($sort == 'hot'){
                            return $query->orderByDesc('play_count');
                        }else{
                            return $query->orderByDesc('refresh_at');
                        }
                    })
                    ->orderByDesc('id')
                    ->forPage($page,$limit)
                    ->get()
                    ->map(function (\TopicModel $item) {
                        return $item->getAttributes();
                    })
                    ->toArray();
            });
        $items = \TopicModel::itRelated($items,
            [
                'user:uid,nickname,thumb,aff,expired_at,vip_level,uuid,sexType'
            ])
            ->map(function (\TopicModel $item) use ($member) {
                $item->setHidden(['image']);
                return $item->watchByUser($member);
            });
        return $items;
    }

    /**
     * @param $topic_id
     * @return mixed
     */
    static function getTopicInfo($topic_id,MemberModel $member,$flag=0)
    {
        TopicModel::setWatchUser($member);
        $model = TopicModel::queryBase()->where('id', $topic_id)->first();
        if($flag){
            bg_run(function () use ($topic_id){
                TopicModel::queryBase()->where('id', $topic_id)->increment('play_count');
            });

        }
        if (is_null($model)) {
            return null;
        }
        $model->addHidden(['mv_id_str','image','mv_id_ary']);
        return $model->toArray();
    }

    /**
     * 清除列表缓存
     * @param int $topic_id
     * @return int
     */
    static function clearTopicList($topic_id = 0)
    {
        redis()->del(self::TOPIC_LIST_KEY);
        $topic_id && redis()->del(self::TOPIC_ROW_KEY . $topic_id);
        return true;
    }

    /**
     * 清除合集视频缓存
     * @param int $topic_id
     * @return int
     */
    static function clearTopicMV($topic_id = 0)
    {
        $topic_id && redis()->del(self::TOPIC_MV_KEY . $topic_id);
        return true;
    }

    /**
     * 后台使用下来 选择
     * @return array|mixed|null
     */
    public static function getSearchTopicList()
    {
        static $data = null;
        if ($data === null) {
            $data = self::getTopicList(1, 100);
        }
        if (!$data) {
            return [];
        }
        $data = collect($data)->toArray();
        return array_column($data, 'title', 'id');
    }

    public static function getTopicMVIDS($topic_id)
    {
        return cached(self::TOPIC_MV_KEY . $topic_id)
            ->expired(3600)
            ->serializerJSON()
            ->fetch(function () use ($topic_id) {
                return \TopicRelationModel::query()
                    ->where('topic_id',$topic_id)->pluck('mv_id')->toArray();
        });


    }

    /**
     * 根据合集编号 获取合集视频列表
     * @param $topic_id
     * @param null $limit
     * @return array
     */
    public static function getMVList(MemberModel $member,$topic_id, $limit = null)
    {
        $mv_id = self::getTopicMVIDS($topic_id);

        if (!$mv_id) {
            return [];
        }
        $offset = 0;
        if ($limit === null) {
            list($limit, $offset) = QueryHelper::restLimitOffset();
        }
        sort($mv_id);
        $ids = collect($mv_id)->slice($offset, $limit)->toArray();
        $items = \MvModel::queryBase()->whereIn('id', $ids)
            ->orderBy('id', 'desc')
            ->limit($limit)
            ->with('user:uid,nickname,thumb,uid,expired_at,vip_level,uuid,sexType')
            ->get();
        return (new MvService())->v2format($items, $member);
    }

    /**
     * 切换点赞
     * @param \MemberModel $member
     * @param int $topic_id
     * @return \UserTopicModel
     * @throws \Throwable
     */
    public function toggle_like(\MemberModel $member, int $topic_id)
    {
        $topic = \TopicModel::find($topic_id);
        if (empty($topic)) {
            throw new \Exception('合集不存在');
        }
        return transaction(function () use ($topic, $member) {
            $where = [
                'uid'      => $member->uid,
                'topic_id' => $topic->id,
            ];
            $like = \TopicLikeModel::where($where)->first();

            if (empty($like)) {
                $isOk = \TopicLikeModel::create($where);
                $num = 1;
                $status = 'set';
            } else {
                $isOk = $like->delete();
                $num = -1;
                $status = 'unset';
            }
            if (empty($isOk)) {
                throw new \Exception('操作失败');
            }
            $topic->like_count += $num;
            $isOk = $topic->save();
            if (empty($isOk)) {
                throw new \Exception('操作失败');
            }
            return $status;
        });
    }
    /**
     * 购买合集
     * @param $comics_id
     * @return bool
     * @throws \Throwable
     */
    static function buyCollect($topic_id)
    {

        $member = request()->getMember();
        //print_r($member);die;
        //\MemberModel::clearFor($member);die;
        /** @var \TopicModel $topic */
        $topic =  TopicModel::queryBase()->where('id',$topic_id)->first();
        if (is_null($topic)) {
            throw new \Exception("查无合集信息");
        }
        if($topic->uid == $member->uid){
            throw new \Exception('自己的合集不用购买');
        }
        if ($topic->coins <= 0) {
            throw new \Exception('当前定价暂未设置');
        }
        $total = $topic->coins;
        if ($member->coins <= 0) {
            throw new \Exception('余额不足，不能进行支付');
        }
        if ($total > $member->coins) {
            throw new \Exception('余额不足，不能进行支付');
        }
        $has_pay = \TopicPayModel::hasBuy($member->uid, $topic_id);
        if ($has_pay) {
            throw new \Exception('当前已下单，请勿重复支付');
        }
        try {
            \DB::beginTransaction();
            $where[] = ['uid', '=', $member->uid];
            $where[] = ['coins', '>=', $total];
            $is_ok = \MemberModel::where($where)->decrement('coins', $total);
            //金币用户减
            if (!$is_ok) {
                throw new \Exception('余额不足，不能进行支付');
            }
            \TopicPayModel::create([
                'uid'        => $member->uid,
                'coins'      => $total,
                'topic_id'      => $topic_id,
                'type'       => 'buy',//购买
                'created_at' => date('Y-m-d H:i:s')
            ]);
            \TopicPayModel::addIdArr($member->uid,$topic_id);
            //记录日志
            $tips = "[购买合集]{$topic->title}";
            $rs3 = \UsersCoinrecordModel::createForExpend('buyTopic', $member->uid, $member->uid,
                $total,
                $topic->id,
                0,
                0,
                0,
                null,
                $tips);

            \DB::commit();
            //统计
//            \SysTotalModel::incrBy('now:mhpay:num');
//            \SysTotalModel::incrBy('now:mhpay',$total);
        } catch (\Throwable $exception) {
            \DB::rollBack();
            throw  $exception;
        }
        \MemberModel::clearFor($member);

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
            'product_id'            => (string)$topic->id,
            'product_name'          => "购买合集:" . $topic->title,
            'coin_consume_amount'   => (int)$total,
            'coin_balance_before'   => (int)($member->coins),
            'coin_balance_after'    => (int)$member->coins - $total,
            'consume_reason_key'    => 'collect_purchase',
            'consume_reason_name'   => '合集购买',
            'order_id'              => (string)$rs3->id,
            'create_time'           => to_timestamp($rs3->addtime),
        ]);

        return true;
    }

    public function listOfLike($uid, \MemberModel $member)
    {
        list($page, $limit, $last_ix) = QueryHelper::pageLimit();

        $where = [
            'uid' => $uid
        ];
        $topics = \TopicLikeModel::where($where)
            ->with('topic')
            ->forPage($page, $limit)
            ->orderByDesc('id')
            ->get()
            ->pluck('topic');

        $results = [];
        foreach ($topics as $topic){
            $results[] = $topic->watchByUser($member);
        }

        return $results;

    }

}