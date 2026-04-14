<?php
namespace repositories;

use service\AdService;
use tools\RedisService;
use VersionModel;

trait SystemRepository
{
    /**
     * 获取版本更新
     * @param $version
     * @param $oauth_type
     * @return array|bool|\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|mixed|object|string|null
     */
    public function getUpdate($version, $oauth_type)
    {
        $versions = RedisService::get(VersionModel::REDIS_VERSION_KEY[$oauth_type]);
        if (!$versions) {
            $versions = VersionModel::query()
                ->where('type', $oauth_type)
                ->where('status', VersionModel::STATUS_SUCCESS)
                ->orderBy('id', 'DESC')
                ->first();
            $versions = $versions->toArray();
            $this->setCacheWithSql(VersionModel::REDIS_VERSION_KEY[$oauth_type], $versions, '检测更新', 86400);
        }

        if ($versions['version'] != $version) {
            $selfVersion = VersionModel::query()
                ->where('version', $version)
                ->where('type', $oauth_type)
                ->select('must')
                ->first();
            $versions['must'] = $selfVersion->must ?? 1;
        }

        $app_version = (int)str_replace('.', '', $version);
        $online_version = (int)str_replace('.', '', $versions['version']);
        if ($online_version < $app_version) {
            $versions['version'] = $version;
        }
        return $versions;
    }

    /**
     * 根据广告位置获取广告列表
     * @param string $position
     * @return array
     */
    /*public function getADsByPosition(string $position = '1')
    {
        //$showUser = $this->member['regdate'] > (TIMESTAMP - 86400 *2) ? [0, 1] : [0, 2];
        return cached(\AdsModel::REDIS_ADS_KEY . $position)
            ->expired(86400)
            ->serializerJSON()
            ->fetch(function () use ($position) {
                return \AdsModel::query()
                    ->select(['id', 'title', 'img_url', 'url', 'type', 'ios_url', 'android_url', 'value'])
                    ->where('status', \AdsModel::STATUS_SUCCESS)
                    ->where('position', $position)
                    ->get()
                    ->toArray();
            });
    }*/
}