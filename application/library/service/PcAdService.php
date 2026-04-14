<?php
/**
 * @description  PC广告相关业务处理
 *
 */

namespace service;

use PcAdsModel;

/**
 * Class PcAdService
 * @package service
 */
class PcAdService
{
    const AD_EXPIRED = 7200;

    /**
     * 根据广告位置获取广告列表
     *
     * @param string $position
     * @return array
     */
    static function getADsByPosition($position)
    {
        $adData = self::getDBADS($position);
        if ($adData) {
            $adData = collect($adData)->map(function ($_itemAd){
                // 1 => '外部跳转连接', 如果外部广告过期就不显示了
                if ($_itemAd['type'] == 1 && $_itemAd['is_expired']) {
                    return null;
                }
                $_itemAd['img_url'] = $_itemAd['img_url_full'];
                unset($_itemAd['img_url_full']);
                $_itemAd['url'] = replace_share($_itemAd['url']);
                return $_itemAd;
            })->filter()->values()->toArray();
        }

        return $adData;
    }

    protected static function getDBADS($position)
    {
        $redisKey = PcAdsModel::REDIS_PC_ADS_KEY . $position;
        return cached($redisKey)
            ->group(PcAdsModel::REDIS_PC_ADS_GROOUP_KEY)
            ->chinese('PC广告列表')
            ->fetchJson(function () use ($position){
                return PcAdsModel::query()
                    ->select(['id', 'title','description', 'mv_m3u8','img_url', 'url', 'type', 'ios_url', 'android_url', 'value','expired_date'])
                    ->where('position',$position)
                    ->where('status',PcAdsModel::STATUS_SUCCESS)
                    ->orderByDesc('show_user')
                    ->orderByDesc('id')
                    ->get()
                    ->toArray();
            });
    }

}