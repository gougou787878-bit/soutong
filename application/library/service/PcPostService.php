<?php

namespace service;

use PcPostModel;
use PcPostTopicModel;
use PostMediaModel;
use MemberModel;
use PostUserLikeModel;
use PcPostCommentModel;

class PcPostService
{

    public function topicDetail($member, $topicId)
    {
        /** @var PcPostTopicModel $topic */
        $topic = PcPostTopicModel::getTopicById($topicId);
        test_assert($topic, '无此话题模块');
        $topic->watchByUser($member);
        return $topic;
    }

    public function listPosts($topicId, $sort, $page, $limit)
    {
        $cate = PcPostTopicModel::getTopicById($topicId);
        test_assert($cate, '不存在的导航');
        $posts = PcPostModel::listTopicPosts($topicId, $sort, $page, $limit);
        $posts['list'] = $this->formatPost($posts['list']);

        return $posts;
    }

    public function getPostDetail($postId, MemberModel $member = null)
    {
        $post = PcPostModel::getPostById($postId);
        test_assert($post, '帖子已经被删除');
        return [
            'post' => $this->formatPost([$post],$member),
            'prev'   => PcPostModel::prevPost($postId),
            'next'   => PcPostModel::nextPost($postId),
        ];
    }

    public function like(MemberModel $member, $postId)
    {
        /** @var PcPostModel $post */
        $post = PcPostModel::getPostById($postId);
        test_assert($post,"帖子不存在");
        $peer_uuid = MemberModel::getUuidByUid($post->aff);
        /** @var PostUserLikeModel $record */
        $record = PostUserLikeModel::getIdsById($member->aff, $postId);
        if ($record) {
            if ($record->created_at > date('Y-m-d')){
                \MemberRankModel::reduceMemberRank($peer_uuid,\MemberRankModel::FIELD_PRAIZE);
            }
            $record->delete();
            if ($post->like_num > 0){
                $post->decrement('like_num');
            }
            return ['is_like' => 0,'msg' => '取消点赞成功'];
        } else {
            $data = [
                'aff'        => $member->aff,
                'type'       => PostUserLikeModel::TYPE_POST,
                'related_id' => $postId,
                'created_at' => \Carbon\Carbon::now()
            ];
            PostUserLikeModel::create($data);
            $post->increment('like_num');
            //获赞排行榜
            \MemberRankModel::addMemberRank($peer_uuid,\MemberRankModel::FIELD_PRAIZE);
            return ['is_like' => 1,'msg' => '点赞成功'];
        }
    }

    private function getVideo($medias)
    {
        $video = '';
        collect($medias)->map(function ($media) use (&$video) {
            if ($media->type == PostMediaModel::TYPE_VIDEO) {
                $video = $media->media_url;
            }
            return '';
        });
        return $video;
    }

    public function listLike(MemberModel $member, $page, $limit)
    {
        $list = PostUserLikeModel::listLikePosts($member->aff, $page, $limit);
        return $this->formatPost($list);
    }

    public function listTopicFollow($member, $page, $limit)
    {
        return \PostTopicUserLikeModel::listTopicFollow($member, $page, $limit);
    }

    public function listUserPosts($aff, $page, $limit)
    {
        $list = PcPostModel::listUserPosts($aff, $page, $limit);
        return $this->formatPost($list);
    }

    public function listSearch($topic_id, $word, $page, $limit)
    {
        $list = PcPostModel::listSearch($topic_id ,$word, $page, $limit);
        return $this->formatPost($list);
    }

    public function topicFollow($member, $topicId)
    {
        $topic = PcPostTopicModel::getTopicById($topicId);
        test_assert($topic, '话题已经被删除');
        $has = \PostTopicUserLikeModel::getRecordByParam($member->aff, $topicId);
        if ($has) {
            $has->delete();
            \PostTopicModel::where('id',$topicId)->decrement('follow_num');
            return [
                'is_follow' => 0,
                'msg' => '取消关注成功',
            ];
        } else {
            $data = [
                'aff'        => $member->aff,
                'related_id' => $topicId,
            ];
            $data['created_at'] =date('Y-m-d H:i:s');
            \PostTopicUserLikeModel::create($data);
            \PostTopicModel::where('id',$topicId)->increment('follow_num');
        }
        return [
            'is_follow' => 1,
            'msg' => '关注成功',
        ];
    }

    public function createPost(\MemberModel $member, $topicId, $categoryId, $content, $title, $medias, $cityname, $ipstr, $price)
    {
        transaction(function () use ($member,$topicId,$categoryId,$content,$ipstr,$cityname,$title,$price,$medias){
            $data = [
                'topic_id'   => $topicId,
                'category'   => $categoryId,
                'content'    => $content,
                'aff'        => $member->aff,
                'ipstr'      => $ipstr,
                'cityname'   => $cityname,
                'refresh_at' => date('Y-m-d H:i:s'),
                'created_at' => date('Y-m-d H:i:s'),
                'title'      => $title,
                'status'     => PcPostModel::STATUS_WAIT,
                'price'      => $price
            ];
            /** @var PcPostModel $new */
            $new = PcPostModel::create($data);
            test_assert($new, '系统异常,异常码:1001');
            $isFinished = \PcPostModel::FINISH_OK;
            foreach ($medias as $val) {
                $media_url = strip_tags($val['media_url']??'');
                if(empty($media_url)){
                    continue;
                }
                $extension = pathinfo($media_url, PATHINFO_EXTENSION);
                if(!in_array($extension, ['mp4' , 'gif' , 'png' , 'jpeg' , 'jpg' , 'swf' , 'icon' , 'm3u8' ])){
                    continue ;
                }
                $media = [
                    'aff'          => $new->aff,
                    'relate_type'  => \PostMediaModel::TYPE_RELATE_POST,
                    'pid'          => $new->id,
                    'media_url'    => $media_url,
                    'thumb_width'  => intval($val['thumb_width'] ?? 0),
                    'thumb_height' => intval($val['thumb_height'] ?? 0),
                    'created_at'   => date('Y-m-d H:i:s'),
                    'updated_at'   => date('Y-m-d H:i:s'),
                ];
                if ($extension == 'mp4') {
                    if (isset($val['cover']) && !empty($val['cover'])) {
                        $media['cover'] = $val['cover'];
                    }
                    $media['type'] = \PostMediaModel::TYPE_VIDEO;
                    $media['status'] = \PostMediaModel::STATUS_NO;
                    $isFinished = \PcPostModel::FINISH_NO;
                } else {
                    $media['cover'] = $val['media_url'];
                    if (isset($val['cover']) && !empty($val['cover'])) {
                        $media['cover'] = $val['cover'];
                    }
                    $media['type'] = \PostMediaModel::TYPE_IMG;
                    $media['status'] = \PostMediaModel::STATUS_OK;
                }
                $media = \PostMediaModel::create($media);
                test_assert($media,"系统异常");
                if ($media->type == \PostMediaModel::TYPE_VIDEO) {
                    $new->increment('video_num');
                } else {
                    $new->increment('photo_num');
                }
            }
            $new->update([
                'is_finished' => $isFinished
            ]);
        });
        return true;
    }

    public function comment(MemberModel $member, $id, $text, $cityname)
    {
        // 文章评论
        $post = PcPostModel::getPostById($id);
        test_assert($post, '文章已被删除');

        $data = [
            'post_id'       => $post->id,
            'pid'           => 0,
            'aff'           => $member->aff,
            'comment'       => $text,
            'status'        => PcPostModel::STATUS_WAIT,
            'refuse_reason' => '',
            'ipstr'         => USER_IP,
            'is_top'        => \PostCommentModel::TOP_NO,
            'is_finished'   => \PostCommentModel::FINISH_OK,
            'cityname'      => $cityname,
            'created_at'    => \Carbon\Carbon::now()
        ];
        $comment = \PostCommentModel::create($data);
        test_assert($comment,'系统异常,异常码:1001');

        return true;
    }

    public function createComComment(MemberModel $member, $commentId, $content, $cityname)
    {
        $parentComment = PcPostCommentModel::getCommentById($commentId,$member);
        test_assert($parentComment,'此评论不存在');

        $data = [
            'post_id'       => $parentComment->post_id,
            'pid'           => $parentComment->id,
            'aff'           => $member->aff,
            'comment'       => $content,
            'status'        => PcPostCommentModel::STATUS_WAIT,
            'refuse_reason' => '',
            'ipstr'         => USER_IP,
            'is_top'        => PcPostCommentModel::TOP_NO,
            'is_finished'   => PcPostCommentModel::FINISH_OK,
            'cityname'      => $cityname,
            'created_at'    => \Carbon\Carbon::now(),
        ];
        $comment = PcPostCommentModel::create($data);
        test_assert($comment,'系统异常,异常码:1001');

        return true;
    }

    public function listComments($postId, $page, $limit)
    {
        $post = PcPostModel::getPostById($postId);
        test_assert($post, '帖子不存在');
        return PcPostCommentModel::listFirstComments($postId, $page, $limit);
    }

    public function listCommentsByComment($comment_id, $page, $limit)
    {
        $post = PcPostCommentModel::getCommentById($comment_id);
        test_assert($post, '评论不存在');
        return PcPostCommentModel::listSecondComments($comment_id, $page, $limit);
    }

    public function listMyComments(MemberModel $member, $page, $limit)
    {
        return  PcPostCommentModel::listMyComments($member->aff, $page, $limit);
    }

    public function listReply($member, $page, $limit)
    {
        return PcPostCommentModel::listReply($member->aff, $page, $limit);
    }

    public function listMyPosts(MemberModel $member, $status, $page, $limit)
    {
         $list = PcPostModel::listMyPosts($member->aff, $status, $page, $limit);
         return $this->formatPost($list, $member);
    }

    function formatPost($postData,\MemberModel $member =null){
        if(!$postData){
            return $postData;
        }
        foreach ($postData as $key=>&$post){
            /** @var \PcPostModel $post */

            if(!is_null($member)){
                $post->watchByUser($member);
                if (!is_null($post->user)){
                    $post->user->watchByUser($member);
                }
            }
            $medias = [];
            if ($medias = $post->medias) {
                $medias = collect($medias)->map(function ($media) use ($post) {
                    /** @var \PostMediaModel $media */
                    if ($media->type == \PostMediaModel::TYPE_IMG) {
                        $media->media_url_full = url_cover($media->media_url);
                    } elseif ($media->type == \PostMediaModel::TYPE_VIDEO) {
                        $extension = pathinfo($media->media_url, PATHINFO_EXTENSION);
                        if ($extension == 'm3u8') {
                            if ($post->is_pay){
                                $media->media_url_full = '/' . trim(parse_url($media->media_url, PHP_URL_PATH), '/') . '?t=0';
                            }else{
                                //10秒预览地址
                                $media->media_url_full = '/' . trim(parse_url($media->media_url, PHP_URL_PATH), '/') . '?t=10';
                            }
                        } else {
                            return null;//非法视频或 没有切完 等下放出去
                        }
                    }
                    return $media;
                })->filter()->values()->toArray();
            }
            unset($post->medias);//取消关系
            $post->medias = $medias;
        }
        return $postData;
    }
}