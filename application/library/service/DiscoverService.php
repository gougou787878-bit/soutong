<?php


namespace service;

/**
 * 发现业务层
 * @package service
 * @author xiongba
 */
class DiscoverService
{

    /**
     * 人气榜
     * @author xiongb
     * @date 2020-05-23 17:32:42
     */
    public function popularityList()
    {
        /** @var \MemberModel $fabulousMostUser */
        /** @var \MemberModel $shareMostUser */
        $fabulousMostUser = cached('top:user:video')
            ->serializerPHP()
            ->expired(86400)
            ->fetch(function () {
                //视频最多的用户
                return \MemberModel::orderByDesc('fabulous_count')->first();
            });
        $inviteMostUser = cached('user:top:share')
            ->serializerPHP()
            ->expired(86400)
            ->fetch(function () {
                //推荐最多的用户
                return \MemberModel::orderByDesc('invited_num')->first();
            });
        $list = [
            [
                'nickname'   => $fabulousMostUser->nickname,
                'thumb'      => url_avatar($fabulousMostUser->thumb),
                'type'       => 'praised',
                'background' => url_live('/new/head/20200525/2020052522154756791.png'),
            ],
            [
                'nickname'   => $inviteMostUser->nickname,
                'thumb'      => url_avatar($inviteMostUser->thumb),
                'type'       => 'invite',
                'background' => url_live('/new/head/20200525/2020052522150268852.png'),
            ]
        ];

        return [
            'name' => '人气榜单',
            'type' => 'discover:popularityList',
            "icon" => url_live('/new/head/20200525/2020052522095695448.png'),
            'list' => $list
        ];
    }

    /**
     * 发现精彩
     * @author xiongb
     * @date 2020-05-23 17:32:42
     */
    public function wonderful()
    {
        $list = [];
        $tagStr = setting('search:discover:tag', '自拍');
        if (!empty($tagStr)){
            $tagMvService = new TagMvService();
            $tagNameAry = explode(',', $tagStr);
            foreach ($tagNameAry as $item) {
                $list[] = [
                    'name'    => $item,
                    'type'    => 'tag',
                    'flag'    => $item,
                    'refresh' => true,
                    'list'    => $tagMvService->videoForVideo($item, 6),
                ];
            }
        }

        $albumIdList = setting('search:discover:album', '');
        if (!empty($albumIdList)) {
            $albumIdAry = explode(',', $albumIdList);
            $service = (new TopicService());
            foreach ($albumIdAry as $item) {
                $info = TopicService::getTopicInfo($item);
                $info && $list[] = [
                    'name'     => $info['title'],
                    'type'     => 'collection',
                    'topic_id' => $item,
                    'flag'     => $item,
                    'refresh'  => false,
                    //'list'    => $tagMvService->videoForVideo($item, 36),
                    'list'     => $service->getMVList($item, 6), //合集列表
                ];
            }
        }



        return [
            'name' => '发现精彩',
            'type' => 'discover:wonderful',
            "icon" => url_live('/new/head/20200525/2020052522103760450.jpeg'),
            'list' => $list
        ];
    }


}