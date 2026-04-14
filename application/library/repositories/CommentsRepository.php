<?php


namespace repositories;


use tools\RedisService;
use service\EventTrackerService;

trait CommentsRepository
{
    /**
     * 发布评论
     * @param \MvModel $mv
     * @param string $cID
     * @param string $comment
     * @return bool
     */
    public function handleCreateComment(\MvModel $mv, string $cID, string $comment)
    {
        $mvID = $mv->id;

        //$maybesample = \AdsampleModel::checkTextSimilar($comment);
        //$status = $maybesample ? 0 : 1;
        $status = \CommentModel::STATUS_WAIT;

        $data = [
            'mv_id'    => $mvID,
            'c_id'     => $cID,
            'uid'      => $this->member['uid'],
            'comment'  => $comment,
            'ipstr'    => USER_IP,
            'cityname' => $this->position['country'] == '中国' ? ($this->position['city'] ?? $this->position['province'])
                : '火星',
            'status'   => $status
        ];

        \CommentModel::create($data);
        if ($cID == 0) {
            if ($_mvUser = $mv->user) {
                \MessageModel::createMessage($this->member['uuid'], $_mvUser->uuid, "[{$this->member['nickname']}]评论了您~", $comment, $mvID,
                    \MessageModel::TYPE_MV);
            }
        }

        $cID == 0 and \MvModel::where('id', $mvID)->increment('comment');
        RedisService::redis()->zIncrBy(\CommentModel::REDIS_COMMENT_TODAY_COUNT, 1, date('Ymd'));
        RedisService::redis()->del(\CommentModel::REDIS_COMMENT_LIST . $mvID . '_1');

        RedisService::set(\CommentModel::REDIS_COMMENT_LAST_KEY . $this->member['uuid'], $mvID, 60);
        RedisService::redis()->sAdd(\CommentModel::REDIS_COMMENT_5MIN_KEY . $this->member['uuid'], $mvID);
        RedisService::expire(\CommentModel::REDIS_COMMENT_5MIN_KEY . $this->member['uuid'], 300);
        return true;
    }

    /**
     * 评论列表
     * @param string $id
     * @return array
     */
    public function getCommentList(string $id)
    {
        $items = RedisService::get(\CommentModel::REDIS_COMMENT_LIST . $id . '_' . $this->page);
        if (true || !$items) {
            $comments = \CommentModel::query()
                ->where('mv_id', $id)
                ->where('status', \CommentModel::STATUS_SUCCESS)
                ->where('c_id', 0)
                ->orderByDesc('like_num')
                ->orderBy('created_at', 'desc')
                ->with('user:uid,nickname,thumb,vip_level,sexType,expired_at,uuid,auth_status')
                ->with('child')
                ->offset($this->offset)->limit($this->limit)->get();
            $items = $comments->toArray();
            RedisService::set(\CommentModel::REDIS_COMMENT_LIST . $id . '_' . $this->page, $items, 600);
        }

        $items = $this->fetchComment($items);

        return $items;
    }

    /**
     * 评论点赞
     * @param $id
     * @return bool
     */
    public function handleCreateCommentLiking($id,&$msg)
    {
        $has = RedisService::sIsMember(\CommentModel::REDIS_COMMENT_LIKED . $this->member['uuid'], $id);
        if ($has) {
            RedisService::redis()->sRem(\CommentModel::REDIS_COMMENT_LIKED . $this->member['uuid'], $id);
            \CommentModel::query()->where('id', $id)->decrement('like_num');
            $msg = '取消点赞成功';
        } else {
            RedisService::redis()->sAdd(\CommentModel::REDIS_COMMENT_LIKED . $this->member['uuid'], $id);
            \CommentModel::query()->where('id', $id)->increment('like_num');
            $msg = '点赞成功';
        }
        return true;
    }

    /**
     * 格式化评论输出
     * @param $comments
     * @param bool $isChild
     * @return array
     */
    public function fetchComment($comments, $isChild = false)
    {
        $data = [];
        $commentLikeList = RedisService::redis()->sMembers(\CommentModel::REDIS_COMMENT_LIKED . $this->member['uuid']);
        foreach ($comments as $key => $comment) {
            if (!empty($comment['user']['thumb'])) {
                $comment['user']['thumb'] = $this->fetchUserThumb($comment['user']['thumb']);
            }
            $result = [
                'id'           => $comment['id'],
                'mvID'         => $comment['mv_id'],
                'cID'          => $comment['c_id'],
                'comment'      => $comment['comment'],
                'likes'        => $comment['like_num'],
                'hasLike'      => in_array($comment['id'], $commentLikeList),
                'createdAt'    => $comment['created_at'],
                'createdAtStr' => (isset($comment['cityname']) ? $comment['cityname'] : '') . '·' . $this->formatTimestamp($comment['created_at']),
                'user'         => [
                    //'isVV'      => $comment['user']['expired_at'] > TIMESTAMP,
                    'auth_status' => $comment['user']['auth_status']??0,
                    'is_vip'      => $comment['user']['expired_at'] > TIMESTAMP,
                    'vip_level'   => $comment['user']['vip_level'] ?? 0,
                    'uuid'        => $comment['user']['uuid'],
                    'uid'         => $comment['user']['uid'],
                    'sexType'     => $comment['user']['sexType'],
                    'thumb'       => $comment['user']['thumb'],
                    'nickname'    => $comment['user']['nickname'],
                ],
            ];
            if (!$isChild) {
                $result['child'] = $this->fetchComment($comment['child'], true);
            }
            $data[] = $result;
        }
        return $data;
    }
}