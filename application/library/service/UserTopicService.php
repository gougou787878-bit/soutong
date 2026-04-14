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

/**
 * Class TopicService
 * @package service
 */
class UserTopicService
{
    const TOPIC_LIST_KEY = 'user-topic:list';
    const TOPIC_ROW_KEY = 'user-topic:row:';
    const TOPIC_MV_KEY = 'user-topic:mv:';

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
                return \TopicModel::queryBase()->orderBy('sort_num')->orderByDesc('id')->forPage($page, $size)->pluck('id');
            });
        $data = \TopicModel::queryBase()->whereIn('id', $ids)->orderBy('sort_num')->orderByDesc('id')->get();
        if ($data->isEmpty()) {
            return [];
        }
        return $data;
    }

    /**
     * @param $topic_id
     * @return mixed
     */
    static function getTopicInfo($topic_id , \MemberModel  $member)
    {
        /** @var \UserTopicModel $model */
        $model = \UserTopicModel::queryUser()->where('id', $topic_id)->first();
        if (is_null($model)) {
            return [];
        }
        $model->watchByUser($member)->addHidden('mv_id_str');
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
     * 根据合集编号 获取合集视频列表
     * @param $topic_id
     * @param $kwy
     * @param null|\MemberModel $member
     * @return array
     */
    public static function getMVList($topic_id , $kwy, $member = null)
    {
        $topic = \UserTopicModel::find($topic_id);
        if (!$topic) {
            return [];
        }

        list($page, $limit, $last_ix) = QueryHelper::pageLimit();
        $offset = ($page - 1) * $limit;

        $idAry = collect(explode(',', $topic->mv_id_str));
        $ids = $idAry->slice($offset, $limit)->toArray();
        $ret = \MvModel::queryBase()->whereIn('id', $ids)
            ->when($kwy , function ($query , $value){
                return $query->where('title' , 'like' , "%$value%");
            })
            ->orderBy('id', 'desc')
            //->limit($limit)
            ->with('user:uid,nickname,thumb,aff,expired_at,vip_level,uuid,sexType')
            ->get()
            ->keyBy('id');
        $items = [];
        foreach ($ids as $id) { //调整顺序
            if (isset($ret[$id])) {
                $items[] = $ret[$id];
            }
        }
        return (new MvService())->v2format($items, $member);
    }

    /**
     * 创建合集
     * @param \MemberModel $member
     * @param string $title
     * @param string $desc
     * @param string $image
     * @param string $idStr
     * @param bool $determine
     * @return object|\TopicModel
     * @throws \Throwable
     */
    public function create_topic(\MemberModel $member, string $title, string $desc, string $image, string $idStr, bool $determine = false)
    {
        $member = \MemberModel::find($member->uid);

        $idAry = collect(explode(',', $idStr))->unique()->map($this->getIntvalCb())->filter();
        if (empty($member->maker) || $member->auth_status != \MemberModel::AUTH_STATUS_YES) {
            //1 检查免费合集次数
            throw new \Exception('您还不是创作者');
        }

        if ($idAry->count() === 0) {
            throw new \Exception('视频不能为空');
        }


        return transaction(function () use ($member, $title, $desc, $image, $idStr, $idAry, $determine) {
            if ($member->maker->topic_count >= intval(setting('user-topic.count', 5))) {
                if (false == $determine) {
                    throw new \Exception('您的免费合集数已用完');
                }
                $total = intval(setting('user-topic.uint', 5));
                //价格小于等于0 不需要影响用户的日志和首款日志
                if ($total > 0) { // 扣费
                    $itOk = $member->incrMustGE_raw(['coins' => -$total, 'consumption' => $total]);
                    if (empty($itOk)) {
                        throw new \Exception('扣款失败,请确认您的金币是否足够', 1008);
                    }
                    //记录日志
                    $itOk = \UsersCoinrecordModel::addTopicExpend($member->uid, $title, $total);
                    if (empty($itOk)) {
                        throw new \Exception('操作失败，请重试');
                    }
                }
            }

            /** @var \MvModel[]|Collection $videoAry */
            $videoAry = \MvModel::queryBase()->whereIn('id', $idAry)->get(['topic_id', 'uid']);
            if (!$videoAry->count()) {
                throw new \Exception('视频不存在');
            }

            foreach ($videoAry as $video) {
                if ($video->uid != $member->uid) { //2 检查视频是否是用户的
                    throw new \Exception('只能操作自己的视频');
                }
                if ($video->topic_id) { //3 检查视频是否加入过合集
                    throw new \Exception('视频只能添加到一个合集中');
                }
            }
            \DB::enableQueryLog();
            $model = \UserTopicModel::createBy($member->uid, $title, $desc, $image, $idAry->join(','), $idAry->count());
            if (empty($model)) {
                trigger_log(print_r(\DB::getQueryLog(),1));
                throw new \Exception('操作失败，请重试1');
            }
            \DB::flushQueryLog();

            $itOk = \MvModel::queryBase()->whereIn('id', $idAry)->update(['topic_id' => $model->id]);
            if (empty($itOk)) { // 更新视频的topic_id
                trigger_log(print_r(\DB::getQueryLog(),1));
                throw new \Exception('操作失败，请重试2');
            }

            $member->maker->topic_count++;
            $itOk = $member->maker->save();
            if (empty($itOk)) {
                throw new \Exception('操作失败，请重试3');
            }
            \MemberModel::clearFor($member);
            return $model;
        });

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
        $topic = \UserTopicModel::find($topic_id);
        if (empty($topic)) {
            throw new \Exception('合集不存在');
        }
        return transaction(function () use ($topic, $member) {
            $where = [
                'uid'      => $member->uid,
                'topic_id' => $topic->id,
            ];
            $like = \UserTopicLikeModel::where($where)->first();

            if (empty($like)) {
                $isOk = \UserTopicLikeModel::create($where);
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
     * 切换合集置顶状态
     * @param \MemberModel $member
     * @param int $topic_id
     * @return \UserTopicModel
     * @throws \Throwable
     */
    public function toggle_top(\MemberModel $member, int $topic_id)
    {
        $topic = \UserTopicModel::find($topic_id);
        if (empty($topic) || $topic->uid != $member->uid) {
            throw new \Exception('合集不存在');
        }

        if ($topic->is_top == \UserTopicModel::IS_TOP_NO){
            $topCount = \UserTopicModel::where(['uid'=>$member->uid])
                ->where('is_top' , \UserTopicModel::IS_TOP_YES)
                ->count();

            $limitTop = intval(setting('user-topic.top-count' , 3));
            if ($topCount >= $limitTop){
                throw new \Exception("最多允许置顶{$limitTop}个");
            }
        }

        return transaction(function () use ($topic, $member) {
            $itOk =\UserTopicModel::toggleColumn(['id'=>$topic->id] , 'is_top' , array_keys(\UserTopicModel::IS_TOP));
            if (empty($itOk)) {
                throw new \Exception('操作失败');
            }
            $topic->is_top = \UserTopicModel::IS_TOP_YES;
            return $topic;
        });
    }

    /**
     * 修改合集的视频
     * @param \MemberModel $member
     * @param int $topic_id
     * @param string $idStr
     * @return \UserTopicModel
     * @throws \Throwable
     */
    public function update_topic(\MemberModel $member, int $topic_id, string $idStr)
    {
        $idAry = collect(explode(',', $idStr))->map($this->getIntvalCb())->unique()->filter();
        $topic = \UserTopicModel::find($topic_id);
        if (empty($topic) || $topic->uid != $member->uid) {
            throw new \Exception('合集不存在');
        }
        if ($idAry->count() === 0) {
            throw new \Exception('视频不能为空');
        }
        return transaction(function () use ($idAry, $member, $topic) {
            $where = [
                'uid'      => $member->uid,
                'topic_id' => $topic->id
            ];
            $itOk = \MvModel::where($where)->update(['topic_id' => 0]);
            if (empty($itOk)) { //将以前的topic id清空
                throw new \Exception('操作失败，请重试');
            }

            /** @var \MvModel[]|Collection $videoAry */
            $videoAry = \MvModel::queryBase()->whereIn('id', $idAry)->get(['topic_id', 'uid']);
            if (!$videoAry->count()) {
                throw new \Exception('视频不存在');
            }

            foreach ($videoAry as $video) {
                if ($video->uid != $member->uid) { //2 检查视频是否是用户的
                    throw new \Exception('只能操作自己的视频');
                }
                if ($video->topic_id && $video->topic_id != $topic->id) { //3 检查视频是否加入过合集
                    throw new \Exception('视频只能添加到一个合集中');
                }
            }

            $topic->mv_id_str = $idAry->join(',');
            $topic->video_count = $videoAry->count();
            if (!$topic->save()) {
                throw new \Exception('操作失败，请重试');
            }
            $itOk = \MvModel::queryBase()->whereIn('id', $idAry)->update(['topic_id' => $topic->id]);
            if (empty($itOk)) { // 更新视频的topic_id
                throw new \Exception('操作失败，请重试');
            }
            cached(self::TOPIC_ROW_KEY . $topic->id)->clearCached();
            return $topic;
        });

    }

    public function listOfLike($uid, \MemberModel $member)
    {
        list($page, $limit, $last_ix) = QueryHelper::pageLimit();

        $where = [
            'uid' => $uid
        ];
        $topics = \UserTopicLikeModel::where($where)
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

    public function listOfTopic($uid)
    {
        list($limit, $offset,$page) = QueryHelper::restLimitOffset();
        $where = [
            'uid' => $uid
        ];
        $topics = \UserTopicModel::queryBase()
            ->where($where)
            ->orderByDesc('is_top')
            ->orderByDesc('id')
            ->limit($limit)
            ->offset($offset)
            ->get();
        return $topics;
    }

    /**
     * 删除合集
     * @param \MemberModel $member
     * @param int $topic_id
     * @return bool
     * @throws \Throwable
     */
    public function delete_topic(\MemberModel $member, int $topic_id)
    {
        $topic = \UserTopicModel::find($topic_id);
        if (empty($topic) || $topic->uid != $member->uid) {
            throw new \Exception('合集不存在');
        }
        return transaction(function () use ($member, $topic) {
            $idAry = $topic->mv_id_ary;
            //更新视频的topid
            $itOk = \MvModel::whereIn('id', $idAry)->update(['topic_id' => 0]);
            if (empty($itOk)) {
                throw new \Exception('操作失败，请重试1');
            }
            //删除点赞
            $likeCount = \UserTopicLikeModel::where('topic_id' , $topic->id)->count();
            if ($likeCount){
                $itOk = \UserTopicLikeModel::where('topic_id' , $topic->id)->delete();
                if (empty($itOk)) {
                    throw new \Exception('操作失败，请重试2');
                }
            }
            if (!$topic->delete()) {
                throw new \Exception('操作失败，请重试3');
            }
            $member->maker->topic_count--;
            if (!$member->maker->save()){
                throw new \Exception('操作失败，请重试4');
            }
            cached(self::TOPIC_ROW_KEY . $topic->id)->clearCached();
            return true;
        });
    }

    protected function getIntvalCb()
    {
        return function ($v){
            return intval(trim($v));
        };
    }

    /**
     * 搜索没有加入过其他合集的视频
     * @param \MemberModel $member
     * @param $kwy
     * @return array
     */
    public function listmv(\MemberModel $member, $kwy)
    {
        list($page, $limit, $last_ix) = QueryHelper::pageLimit();
        $uid = $member->uid;
        $where = [
            'uid'      => $uid,
            'topic_id' => 0
        ];

        $list = \MvModel::queryBase()
            ->forPage($page, $limit)
            ->where($where)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->when($kwy, function ($query, $val) {
                return $query->where('title', 'like', "%$val%");
            })->get();
        return (new MvService())->v2format($list);
    }

    /**
     * 热门点赞
     * @param \MemberModel $member
     * @param $limit
     * @return Collection|\UserTopicModel[]
     */
    public function popular(\MemberModel $member, $limit)
    {
        $items = cached('topic:popular:' . $limit)
            ->expired(600)
            ->serializerJSON()
            ->setSaveEmpty(true)
            ->fetch(function () use ($limit) {
                return \UserTopicModel::orderByDesc('like_count')
                    ->orderByDesc('id')
                    ->limit($limit)
                    ->get()
                    ->map(function (\UserTopicModel $item) {
                        return $item->getAttributes();
                    })
                    ->toArray();
            });
        $items = \UserTopicModel::itRelated($items,
            [
                'user:uid,nickname,thumb,aff,expired_at,vip_level,uuid,sexType'
            ])
            ->map(function (\UserTopicModel $item) use ($member) {
                return $item->watchByUser($member);
            });
        return $items;
    }


}