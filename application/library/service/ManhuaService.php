<?php
/**
 *
 * @date 2025/02/40
 * @author
 * @copyright kuaishou by KS
 * @todo 漫画相关逻辑都在这里  雕哥
 *
 */

namespace service;

use helper\QueryHelper;
use MemberModel;
use MhModel;
use Yaf\Exception;

/**
 * Class ManhuaService
 * @package service
 */
class ManhuaService
{
    /**
     * @param $id
     * @return MhModel
     * @throws \Exception
     */
    public static function getDetailData(MemberModel $member, $id)
    {
        MhModel::setWatchUser($member);
        /** @var MhModel $detail */
        $detail = MhModel::getRow($id);
        if (is_null($detail)) {
            throw new \Exception("查无漫画信息");
        }

        $detail->load('series');
        $detail->now_total= 0 ;//目前总章节
        $detail->from_episode = 1;//从第0 还是第1开始
        if ($detail->series) {
            $detail->now_total = collect($detail->series)->count();
            $detail->from_episode = $detail->series[0]->episode;
        }
        jobs([MhModel::class, 'addView'], [$id]);
        return $detail;
    }

    static function readManhua(MemberModel $member, $m_id, $s_id)
    {
        MhModel::setWatchUser($member);
        /** @var MhModel $detail */
        $detail = MhModel::getRow($m_id);
        if (is_null($detail)) {
            throw new \Exception("查无漫画信息");
        }
        if ( ($member->is_vip && $detail->coins==0 )||
            ($detail->is_pay)
            ||
            ($detail->newest_series>5 && $s_id<=2)
        ) {
            return \MhSrcModel::getSeriesSrc($m_id, $s_id);
        }
        throw new \Exception("无权查看漫画章节详细信息~");
    }


    static function guessByManHuaLike($manhua_id, $limit = 18)
    {
        $manhua_id = (int)$manhua_id;
        $where = [];
        /** @var ComicsModel $story */
        /*$story = ComicsModel::find($manhua_id);
        if(!is_null($story)){
            $story->category_id && $where['category_id'] = $story->category_id;
        }*/
        $data = MhModel::queryBase()->when($where, function ($query, $value) {
            return $query->where($value);
        })
            ->where('id', '!=', $manhua_id)
            ->orderByDesc('recommend')
            ->orderByDesc('refresh_at')
            ->limit($limit)
            ->get();
        return $data;
    }

    /**
     * @param $comics_id
     * @return bool
     * @throws \Throwable
     */
    static function getOrderData($comics_id)
    {
        $member = request()->getMember();
        if ($member->isBan()) {
            throw new \Exception("违规发布广告,已被禁言~");
        }
        //print_r($member);die;
        //\MemberModel::clearFor($member);die;
        /** @var MhModel $comics */
        $comics = MhModel::getRow($comics_id);
        if (is_null($comics)) {
            throw new \Exception("查无漫画信息");
        }
        if ($comics->coins <= 0) {
            throw new \Exception('当前定价暂未设置');
        }
        $total = $comics->coins;
        if ($member->coins <= 0) {
            throw new \Exception('余额不足，不能进行支付');
        }
        if ($total > $member->coins) {
            throw new \Exception('余额不足，不能进行支付');
        }
        $has_pay = \MhPayModel::hasBuy($member->uid, $comics->id);
        if ($has_pay) {
            throw new \Exception('当前已下单，请勿重复支付');
        }
        try {
            \DB::beginTransaction();
            $where[] = ['uid', '=', $member->uid];
            $where[] = ['coins', '>=', $total];
            $is_ok = MemberModel::where($where)->decrement('coins', $total);
            //金币用户减
            if (!$is_ok) {
                throw new \Exception('余额不足，不能进行支付');
            }
            $pay = \MhPayModel::create([
                'uid'        => $member->uid,
                'coins'      => $total,
                'mh_id'      => $comics->id,
                'type'       => 'buy',//购买
                'created_at' => date('Y-m-d H:i:s')
            ]);
            //记录日志
            $tips = "[购买漫画]{$comics->title}";
            $rs3 = \UsersCoinrecordModel::createForExpend('buyMh', $member->uid, $member->uid,
                $total,
                $comics->id,
                0,
                0,
                0,
                null,
                $tips);

            \DB::commit();
            //统计
            \SysTotalModel::incrBy('now:mhpay:num');
            \SysTotalModel::incrBy('now:mhpay',$total);
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
            'product_id'            => (string)$comics->id,
            'product_name'          => "购买漫画:" . $comics->title,
            'coin_consume_amount'   => (int)$total,
            'coin_balance_before'   => (int)($member->coins),
            'coin_balance_after'    => (int)$member->coins - $total,
            'consume_reason_key'    => 'comic_purchase',
            'consume_reason_name'   => '漫画购买',
            'order_id'              => (string)$rs3->id,
            'create_time'           => to_timestamp($rs3->addtime),
        ]);

        return true;
    }

    static function getFavorites($comics_id)
    {
        if (empty($comics_id)) {
            throw new Exception('参数错误');
        }
        $member = request()->getMember();
        if ($member->isBan()) {
            throw new Exception('涉嫌违规，不允许收藏');
        }
        /** @var MhModel $has */
        $has = MhModel::getRow($comics_id);
        if (!$has) {
            throw new Exception('漫画记录不存在');
        }
        if (!frequencyLimit(10, 3, request()->getMember())) {
            throw new Exception('短时间内赞操作太頻繁了,稍后再试试');
        }
        $user_id = $member->uid;
        /** @var \MhFavoritesModel $hasLike */
        $hasLike = \MhFavoritesModel::hasLike($member->uid, $has->id);

        if ($hasLike) {
            \MhFavoritesModel::where(['id' => $hasLike->id])->delete();
            $msg = "收藏取消";
            return [
                'status' => 0,
                'msg'    => $msg
            ];
        } else {
            \MhFavoritesModel::insert([
                'uid'        => $user_id,
                'mh_id'      => $has->id,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            $has->increment('favorites');
            $msg = "收藏成功";
            return [
                'status' => 1,
                'msg'    => $msg
            ];
        }
    }

    /**
     * @param $postData
     * @param bool $is_filter
     * @return array
     */
    public static function searchManhua($postData, $is_filter = false)
    {
        list($limit, $offset, $page) = QueryHelper::restLimitOffset('page','limit',21);
        //\DB::enableQueryLog();
        $query = MhModel::queryBase();
        $order = 'refresh_at';
        if ($is_filter) {
            $order = $postData['order'] ?? '';
            if (!in_array($order, ['id', 'rating', 'favorites'])) {
                $order = 'refresh_at';
            }
            $type = $postData['type'] ?? '';
            if (!in_array($type, ['doing', 'finish', 'short','vip','pay'])) {
                $type = '';
            }
            $query->when($type == 'doing', function ($query, $value) {
                return $query->where('is_finish', '=', MhModel::FINISH_NO);
            })->when($type == 'finish', function ($query, $value) {
                return $query->where('is_finish', '=', MhModel::FINISH_YES);
            })->when($type =='short', function ($query, $value) use ($type) {
                //return $query->where('type','=',trim($type));
                return $query->where([
                    ['is_finish', '=', MhModel::FINISH_YES],
                    ['newest_series', '=', 1],
                ]);
            })->when(in_array($type,['vip','pay']) , function ($query, $value) use ($type) {
                return $query->where('coins', $type != "vip" ? '>' : '=', 0);
            });
            if ($tab_id = $postData['tab'] ?? '') {
                $tagString = \MhTabModel::getMatchString($tab_id);
                $tagString && $query->whereRaw("match(tags) against(?)", [$tagString]);
            }
        } else {
            $kwy = $postData['word'];
            if (is_numeric($kwy)) {
                $query->where('id', '=', (int)$kwy);
            } else {
                $query->where(function ($query) use ($kwy) {
                    $query->whereRaw("match(tags) against(?)", [$kwy])->orWhere('title', 'like', "%$kwy%");
                });
            }
        }
        $list = $query
            ->orderByDesc($order)
            ->limit($limit)
            ->offset($offset)
            ->get();
       /* if(!IS_PWA)
        {
            errLog("manhua".var_export([$postData,\DB::getQueryLog()],true));
        }*/
        return $list;
    }

    const TAB_LIST_KEY = 'mh:list';
    const TAB_SEARCH_KEY = 'mh:filter';

    static function clearCache($id)
    {
        redis()->del("t:manhua:{$id}");
        redis()->del(self::TAB_LIST_KEY);
        redis()->del(self::TAB_SEARCH_KEY);

    }

    /**
     * 获取tab栏目标签
     * @return \Illuminate\Support\Collection
     */
    public static function getTabList()
    {
        $list = cached(self::TAB_LIST_KEY)->clearCached()->fetchJson(function () {
            return \MhTabModel::queryBase(['is_tab' => 1])
                ->orderBy('sort_num')
                ->limit(6)
                ->get(['tab_id', 'tab_name', 'tags_str','show_style','show_number'])
                ->toArray();
        });
        return $list;
    }

    /**
     * 搜索过滤分类选择数据
     * @return mixed
     */
    public static function getSearchList()
    {
        $list = cached(self::TAB_SEARCH_KEY)
            ->fetchJson(function () {
                return \MhTabModel::queryBase(['is_category' => 1])
                    ->orderByDesc('sort_num')
                    ->get(['tab_id', 'tab_name'])
                    ->toArray();
            });
        return $list;
    }

    /**
     * 主页数据
     * @return array
     */
    static function getHomeData()
    {
        $cateTabData = self::getTabList();
        array_unshift($cateTabData, [
            'tab_id'      => 0,
            'tab_name'    => '最近更新',
            'tags_str'    => '',
            'tags_ary'    => [],
            'show_style'  => \MhTabModel::SHOW_STYLE_DF,
            'show_number' => \MhTabModel::SHOW_NUMBER_DF,
            'show_type' => \MhTabModel::SHOW_STYLE_TYPE[\MhTabModel::SHOW_STYLE_DF],
        ]);
        $data = collect($cateTabData)->map(function ($_cateTab) {
            $data = cached("mh:cat:{$_cateTab['tab_id']}")->clearCached()->fetchJson(function () use ($_cateTab) {
                return MhModel::queryBase()->where(function ($query) use ($_cateTab) {
                    if ($_cateTab['tab_id'] && $tagStr = $_cateTab['tags_str']) {
                        $tagStr = str_replace(',', ' ', $tagStr);
                        $query->whereRaw("match(tags) against(?)", [$tagStr]);
                    }
                    return $query;

                })->orderByDesc('refresh_at')
                    ->limit(max(\MhTabModel::SHOW_NUMBER_DF, $_cateTab['show_number']))
                    ->get()
                    ->toArray();
            }, rand(1000, 2000));//过期随机 避免同时穿透
            $_cateTab['items'] = $data;
            $_cateTab['show_style'] = empty($_cateTab['show_style']) ? \MhTabModel::SHOW_STYLE_DF : $_cateTab['show_style'];
            $_cateTab['show_number'] = max(\MhTabModel::SHOW_NUMBER_DF, $_cateTab['show_number']);
            $_cateTab['show_type'] = \MhTabModel::SHOW_STYLE_TYPE[$_cateTab['show_style']];
            return $_cateTab;
        })->toArray();
        return $data;
    }
}