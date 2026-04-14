<?php
/**
 *
 * @date 2020/2/27
 * @author
 * @copyright kuaishou by KS
 * @todo 广告相关业务处理
 *
 */

namespace service;

use AdsModel;

/**
 * Class AdService
 * @package service
 */
class AdService
{
    const AD_EXPIRED = 7200;

    /**
     * 根据广告位置获取广告列表 渠道
     *
     * @param string $position
     * @param string $channel
     * @param string $isLive
     * @return array
     */
    static function getADsByPosition($position = '1', $channel = '',$isLive = false)
    {
        $member = request()->getMember();
        $uid = is_null($member)?0:\ActiveInviteModel::getID2Code($member->uid);
        $old_uid = $member->uid;
//        $channel = '';
//        $adData = [];
//        if (\AdsModel::POSITION_SCREEN == $position && $channel) {
//            $adData = self::_getADsByPosition($position, $channel);
//        }
//        if (!$adData) {
//            $adData = self::_getADsByPositionNormal($position);//default ad
//        }

        if (version_compare($_POST['version'], AdsModel::ADS_VERSION, '>') && !in_array($position, AdsModel::NEW_TIPS)) {
            $position = AdsModel::POSITION_TEM_LIST;
        }
        $adData = AdsModel::getPositionByRemote($position);//default ad

        //wf("adData banner:",$adData);
        if ($adData) {
            $adData = collect($adData)->map(function ($_itemAd)use($uid, $old_uid){
                $_itemAd['url'] = replace_share($_itemAd['url']);
                // 1 => '外部跳转连接', 如果外部广告过期就不显示了
                if ($_itemAd['type'] == 1 && $_itemAd['is_expired']) {
                    return null;
                }
//                if ($_itemAd['type'] == 3 && strpos($_itemAd['url'],'blue_invite')!==false ) {//内部加“用户信息”
//                    $_itemAd['url'] = "{$_itemAd['url']}?code={$uid}";
//                }
                //需要带用户信息
                if (strpos($_itemAd['url'],'{token}')!==false ) {//内部加“用户信息”
                    $_itemAd['url'] = str_replace('{token}',$uid,$_itemAd['url']);
                }
                //处理 &
                if (str_contains($_itemAd['url'],"&")){
                    $_itemAd['url'] = htmlspecialchars_decode($_itemAd['url']);
                }
                $_itemAd['img_url'] = $_itemAd['img_url_full'];
                unset($_itemAd['img_url_full']);
                return $_itemAd;
            })->filter()->values()->toArray();
            // shuffle($adData);
        }

        return $adData;
    }
    static function getIosPopAds(){
        $where[] = ['id', '=', '892'];
        $key = 'getIosPopAds';
        $data = cached($key)->expired(self::AD_EXPIRED)->serializerJSON()->fetch(function () use ($key, $where) {
            $data = \AdsModel::query()
                ->select(['id', 'title','description', 'mv_m3u8','img_url', 'url', 'type', 'ios_url', 'android_url', 'value'])
                ->where($where)
                ->orderByDesc('id')
                ->get();
            \CacheKeysModel::createOrEdit($key, '广告列表');
            return is_null($data) ? [] : $data->toArray();
        });
        if ($data) {
            $data = collect($data)->map(function ($_itemAd){
                $_itemAd['img_url'] = $_itemAd['img_url_full'];
                unset($_itemAd['img_url_full']);
                return $_itemAd;
            })->toArray();
            shuffle($data);
        }
        return $data;
    }


    static function getDiamondPlaza($position, $member, $token)
    {
        $adsData = self::getADsByPosition($position);
        if ($adsData) {
            foreach ($adsData as &$_ad) {
                if (in_array($_ad['type'], [2, 4])) {//不是外部链接
                    $_ad['url'] = getDataByExplode('#', $_ad['url']);//81592#81589
                }
            }
        }
        return $adsData;
    }


    protected static function _getADsByPositionNormal($position)
    {
        $redisKey = \AdsModel::REDIS_ADS_KEY . $position;
        //$w[] = ['channel', '=', ''];
        return self::getDBADS($redisKey, $position);
    }

    protected static function _getADsByPosition($position, $channel)
    {
        $w[] = ['channel', '=', $channel];
        $redisKey = \AdsModel::REDIS_ADS_KEY . $position . $channel;
        return self::getDBADS($redisKey, $position, $w);
    }

    protected static function getDBADS($key, $position, $where = [])
    {
        $where[] = ['status', '=', \AdsModel::STATUS_SUCCESS];
        $where[] = ['position', '=', $position];
        $data = cached($key)
            ->fetchJson(function () use ($key, $where) {
                $data = \AdsModel::query()
                    ->select(['id', 'title','description', 'mv_m3u8','img_url', 'url', 'type', 'ios_url', 'android_url', 'value','expired_date'])
                    ->where($where)
                    ->orderByDesc('show_user')
                    ->orderByDesc('id')
                    ->get();
                \CacheKeysModel::createOrEdit($key, '广告列表');
                return is_null($data) ? [] : $data->toArray();
        },self::AD_EXPIRED);
        return $data;
    }


    /**
     * 应用中心 应用列表获取
     */
    static function getAdsAppList()
    {
        return AdsModel::getAppByRemote();

        $data = cached(\AdsAppModel::REDIS_ADS_KEY)
            ->serializerJSON()
            ->expired(86000)
            ->fetch(function () {
                $rs = \AdsAppModel::getDataList();
                $return = [];
                if (!is_null($rs)) {
                    foreach ($rs as $item) {
                        //$replace_url = \AdsAppModel::convertURLHOST($item->short_name,$item->link_url);
                        $replace_url = $item->link_url;
                        if ($item->short_name){
                            if (str_contains($item->short_name,"https://")){
                                $replace_url = replace_share($item->short_name);
                            }else{
                                $replace_url = "https://".replace_share($item->short_name);
                            }
                        }
                        $return[] = [
                            'id'          => $item->id,
                            'title'       => $item->title,
                            'short_name'  => $item->short_name,
                            'description' => $item->description,
                            'img_url_2'   => $item->img_url,//原来
                            'img_url'     => $item->img_url_full,//完整链接
                            'link_url'    => $replace_url,
                            'clicked'     => $item->clicked,
                            'created_at'  => date('Y/m/d', $item->created_at),
                        ];
                    }
                }
                return $return;
            });
        return $data;
    }

    /**
     * 应用中心 应用列表获取
     */
    static function getNoticeAppList(\MemberModel $member)
    {
        $rs_data = AdsModel::getNoticeAppByRemote();

        $channel_username = '';
        //渠道用户
        if (!empty($member->build_id) && $member->build_id != 'GW'){
            $channel_username = \MemberModel::info($member->invited_by);
        }
        $list = [];
        if (!is_null($rs_data)) {
            foreach ($rs_data as $item) {
                if ($channel_username && $item['app_type'] == AdsModel::NT_APP_IN){
                    $item['link_url'] = $item['link_url'] . '?channel_code=' . $channel_username;
                }
                $item['link_url'] = replace_share($item['link_url']);
                unset($item['app_type']);
                $list[] = $item;
            }
        }

        return $list;
    }

}