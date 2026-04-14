<?php
/**
 *
 * @date 2025/02/40
 * @author
 * @copyright kuaishou by KS
 * @todo 图集相关逻辑都在这里  雕哥
 *
 */

namespace service;

use helper\QueryHelper;
use MemberModel;
use PictureModel;
use Yaf\Exception;

/**
 * Class PictureService
 * @package service
 */
class PictureService
{
    /**
     * @param $id
     * @return PictureModel
     * @throws \Exception
     */
    public static function getDetailData(MemberModel $member, $id)
    {
        PictureModel::setWatchUser($member);
        /** @var PictureModel $detail */
        $detail = PictureModel::getRow($id);
        if (is_null($detail)) {
            throw new \Exception("查无图集信息");
        }
        //print_r($member->getAttributes());die;
        $detail->watchByUser($member);
        //$detail->append(['total']);
        //$detail->total = $detail->getTotalAttribute();
        $detail->has_right = $detail->is_pay;
        if($detail->has_right){
            $detail->load('series')->series;
        }else{
            $detail->load(['series'=>function($query){
                return $query->limit(FREE_SEE_LIMIT);
            }])->get();
        }
        jobs([PictureModel::class, 'addView'], [$id]);
        return $detail;
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
        /** @var PictureModel $comics */
        $comics = PictureModel::getRow($comics_id);
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
        $has_pay = \PicturePayModel::hasBuy($member->uid, $comics->id);
        if ($has_pay) {
            throw new \Exception('已下单完成付款了，勿重复支付，重新打开试试~');
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
            $pay = \PicturePayModel::create([
                'uid'        => $member->uid,
                'coins'      => $total,
                'zy_id'      => $comics->id,
                'type'       => 'buy',//购买
                'created_at' => date('Y-m-d H:i:s')
            ]);
            //记录日志
            $tips = "[购买图集]{$comics->title}";
            $rs3 = \UsersCoinrecordModel::createForExpend('buyPic', $member->uid, $member->uid,
                $total,
                $comics->id,
                0,
                0,
                0,
                null,
                $tips);

            \DB::commit();
            //统计
            \SysTotalModel::incrBy('now:picpay:num');
            \SysTotalModel::incrBy('now:picpay',$total);
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
            'product_name'          => "购买图集:" . $comics->title,
            'coin_consume_amount'   => (int)$total,
            'coin_balance_before'   => (int)($member->coins),
            'coin_balance_after'    => (int)$member->coins - $total,
            'consume_reason_key'    => 'picture_purchase',
            'consume_reason_name'   => '图集购买',
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
        /** @var PictureModel $has */
        $has = PictureModel::getRow($comics_id);
        if (!$has) {
            throw new Exception('漫画记录不存在');
        }
        if (!frequencyLimit(10, 3, request()->getMember())) {
            throw new Exception('短时间内赞操作太頻繁了,稍后再试试');
        }
        $user_id = $member->uid;
        /** @var \PictureFavoritesModel $hasLike */
        $hasLike = \PictureFavoritesModel::hasLike($member->uid, $has->id);

        if ($hasLike) {
            \PictureFavoritesModel::where(['id' => $hasLike->id])->delete();
            $msg = "收藏取消";
            return [
                'status' => 0,
                'msg'    => $msg
            ];
        } else {
            \PictureFavoritesModel::insert([
                'uid'        => $user_id,
                'zy_id'      => $has->id,
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
        APP_ENVIRON == 'test' && $limit = 2;
        $query = PictureModel::queryBase();
        $order = 'refresh_at';
        if ($is_filter) {
            $order = $postData['order'] ?? '';
            if (!in_array($order, ['id', 'rating', 'favorites'])) {
                $order = 'refresh_at';
            }
            $type = $postData['type'] ?? '';
            if (!in_array($type, ['high','vip','pay'])) {
                $type = '';
            }
            $query->when($type == 'high', function ($query, $value) {
                return $query->where('recommend', '=', 1);
            })->when(in_array($type,['vip','pay']) , function ($query, $value) use ($type) {
                return $query->where('coins', $type != "vip" ? '>' : '=', 0);
            });
            if ($tab_id = $postData['tab'] ?? '') {
                $tagString = \PictureTabModel::getMatchString($tab_id);
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

    const TAB_LIST_KEY = 'tab:pic:list';
    const TAB_SEARCH_KEY = 'tab:pic:filter';

    static function clearCache($id)
    {
        redis()->del("t:pic:{$id}");
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
            return \PictureTabModel::queryBase(['is_tab' => 1])
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
                return \PictureTabModel::queryBase(['is_category' => 1])
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
            'show_style'  => \PictureTabModel::SHOW_STYLE_DF,
            'show_number' => \PictureTabModel::SHOW_NUMBER_DF,
            'show_type' => \PictureTabModel::SHOW_STYLE_TYPE[\PictureTabModel::SHOW_STYLE_DF],
        ]);
        $data = collect($cateTabData)->map(function ($_cateTab) {
            $data = cached("pic:cat:{$_cateTab['tab_id']}")->clearCached()->fetchJson(function () use ($_cateTab) {
                return PictureModel::queryBase()->where(function ($query) use ($_cateTab) {
                    if ($_cateTab['tab_id'] && $tagStr = $_cateTab['tags_str']) {
                        $tagStr = str_replace(',', ' ', $tagStr);
                        $query->whereRaw("match(tags) against(?)", [$tagStr]);
                    }
                    return $query;

                })->orderByDesc('refresh_at')
                    ->limit(max(\PictureTabModel::SHOW_NUMBER_DF, $_cateTab['show_number']))
                    ->get()
                    ->toArray();
            }, rand(1000, 2000));//过期随机 避免同时穿透
            $_cateTab['items'] = $data;
            $_cateTab['show_style'] = empty($_cateTab['show_style']) ? \PictureTabModel::SHOW_STYLE_DF : $_cateTab['show_style'];
            $_cateTab['show_number'] = max(\PictureTabModel::SHOW_NUMBER_DF, $_cateTab['show_number']);
            $_cateTab['show_type'] = \PictureTabModel::SHOW_STYLE_TYPE[$_cateTab['show_style']];
            return $_cateTab;
        })->toArray();
        return $data;
    }
}