<?php
/**
 * Sample file comment
 *
 * PHP version 7.1.0
 *
 * This file demonstrates the rich information that can be included in
 * in-code documentation through DocBlocks and tags.
 *
 * @file UpdateVideoSeriice.php
 * @author xiongba
 * @version 1.0
 * @package
 */

namespace service;


use repositories\UsersRepository;

class UpdateVideoService extends \AbstractBaseService
{

    use UsersRepository;

    const play_url = '/useruploadfiles/432c8735f66c8fc045c08f08f682b9c6/432c8735f66c8fc045c08f08f682b9c6.m3u8';


    public function fetchMvList($items)
    {
        $data = [];

        foreach ($items as $key => $item) {
            if (empty($item['id'])) {
                continue;
            }
            if (empty($item['user'])) {
                $item['user'] = \MemberModel::getOfficial();
            }
            $item['isLiked'] = 0;
            $item['isFollowed'] = 0;
            $item['islive'] = false;
            $data[] = $this->fetchMv($item);
        }
        return $data ?? [];
    }


    protected function getUser($mv)
    {
        return [
            'isVV'     => $mv['user']['expired_at'] > TIMESTAMP,
            'vvLevel'  => $mv['user']['vip_level'] ?? 0,
            'uuid'     => $mv['user']['uuid'] ?? '',
            'sexType'  => $mv['user']['sexType'] ?? 0,
            'uid'      => $mv['uid'] ?? 0,
            'thumb'    => $mv['user']['thumb'] ? url_avatar($mv['user']['thumb']) : '',
            'nickname' => setting('replace:pj:nickname:str',empty($mv['user']['nickname']) ? '':$mv['user']['nickname']),
        ];
    }

    public function fetchMv($mv)
    {
        $thumbCover = $mv['cover_thumb'] ?? '';
        if (boolval(setting('replace:pj:playurl', false))) {
            $playUrl = getPlayUrl(setting('replace:pj:playurl:str', self::play_url), true);
        } else {
            $playUrl = getPlayUrl($mv['m3u8'], true);
        }
        if (boolval(setting('replace:pj:cover', false))) {
            $thumbCover = setting('replace:pj:cover:str', null);
            if (empty($thumbCover)){
                $thumbCover = $mv['cover_thumb'];
            }
        }
        return [
            'id'           => $mv['id'],
            'uid'          => $mv['uid'],
            'title'        => $mv['title'],
            'coins'        => $mv['coins'],
            'vipCoins'     => $mv['vip_coins'] == -1 ? $mv['coins'] : $mv['vip_coins'],
            'hasBuy'       => true,
            'duration'     => $mv['duration'],
            'durationStr'  => durationToString(5),
            'like'         => $mv['like'],
            'comment'      => $mv['comment'],
            'tags'         => ['ks.tips', 'http://ks.tips','成人視頻', '成人直播', '黃色視頻'],
            'isRec'        => $mv['is_recommend'] ?? 0,
            'thumbImg'     => $thumbCover,
            'thumbWidth'   => $mv['thumb_width'] ?? 0,
            'thumbHeight'  => $mv['thumb_height'] ?? 0,
            'gifImg'       => $mv['gif_thumb'] ?? '',
            'gifWidth'     => $mv['gif_width'] ?? 0,
            'gifHeight'    => $mv['gif_height'] ?? 0,
            'playURL'      => $playUrl,
            'shareUrl'     => $this->getShareUrl(),
            'hasLongVideo' => $this->hasLongVideo(5),
            'status'       => $mv['status'],
            'isLiked'      => 0,
            'isFollowed'   => 0,
            'isLive'       => false,
            'musicID'      => 0,
            'user'         => $this->getUser($mv),
        ];
    }


    /**
     * 是否有长视频
     * @param string $duration
     * @return bool
     */
    private function hasLongVideo(string $duration)
    {
        if (!$duration or $duration == '') {
            return false;
        }
        return $duration > $this->config->site->long_video_duration;
    }

}


