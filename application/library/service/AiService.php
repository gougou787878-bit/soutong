<?php

namespace service;

use AiNavModel;
use CURLFile;
use DB;
use Carbon\Carbon;
use helper\QueryHelper;
use FaceCateModel;
use FaceMaterialModel;
use MemberFaceModel;
use MemberModel;
use PrivilegeModel;
use FaceMaterialCommentModel;
use UsersProductPrivilegeModel;
use FaceMaterialUserLikeModel;
use MemberStripModel;

class AiService
{

    public function ai_nav(){
        return AiNavModel::list();
    }

    //检查图片格式
    /*****************************************AI脱衣*********************************************/
    public function getStripPreData(MemberModel $member){
        $free_num = (int)UsersProductPrivilegeModel::hasPrivilege(
            USER_PRIVILEGE ,
            PrivilegeModel::RESOURCE_TYPE_AI_TY,
            PrivilegeModel::PRIVILEGE_TYPE_UNLOCK
        );

        return [
            'max_size'       => '2M',
            'free_num'       => (string)$free_num,
            'coins'          => $member->coins,
            'times'          => '60',
            'ai_ty_coins'    => setting('ai_ty_coins', 19),
            'ai_ty_tips'     => setting('ai_ty_tips', ''),
            'exp_before_img' => url_cover('/upload_01/ads/20260120/2026012012584120655.png'),
            'exp_after_img'  => url_cover('/upload_01/ads/20260120/2026012012584968044.png'),
        ];
    }

    //检查图片格式
    protected function check_type($file)
    {
        $url = TB_IMG_ADM_US . $file;
        $image = file_get_contents($url);
        test_assert($image, '请求远程异常:' . $url);
        $md5 = substr(md5($url), 0, 16);
        $from = APP_PATH . '/storage/data/images/' . $md5 . '_fr';
        $dirname = dirname($from);
        if (!is_dir($dirname) || !file_exists($dirname)) {
            mkdir($dirname, 0755, true);
        }
        $rs = file_put_contents($from, $image);
        test_assert($rs, '无法写入文件:' . $from);
        $cover = new CURLFile(realpath($from), mime_content_type($from));
        test_assert($cover, '仅支持JPEG|JPG|PNG|GIF|BMP|WEBP|AVIF图片格式,其他格式请自行转码');
        //删除图片
        unlink($from);
        if (!in_array($cover->mime,  ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/bmp', 'image/webp', 'image/avif'])){
            test_assert(false, '仅支持JPEG|JPG|PNG|GIF|BMP|WEBP|AVIF图片格式,其他格式请自行转码');
        }
    }

    public function strip(MemberModel $member, $thumb, $thumb_w, $thumb_h)
    {
        //检查格式
        $this->check_type($thumb);
        transaction(function () use ($member, $thumb, $thumb_w, $thumb_h) {
            //判断VIP权限
            $has = UsersProductPrivilegeModel::hasPrivilege(
                USER_PRIVILEGE,
                PrivilegeModel::RESOURCE_TYPE_AI_TY,
                PrivilegeModel::PRIVILEGE_TYPE_UNLOCK
            );

            if ($has){
                //使用VIP权限的次数
                $value =UsersProductPrivilegeModel::hasPrivilegeAndSubTimePrivilege(
                    USER_PRIVILEGE,
                    PrivilegeModel::RESOURCE_TYPE_AI_TY,
                    PrivilegeModel::PRIVILEGE_TYPE_UNLOCK,
                    $member->aff
                );
                test_assert($value, '权限次数不足');

                $rs = MemberStripModel::create_record($member->aff, $thumb, $thumb_w, $thumb_h, MemberStripModel::AI_PAY_TYPE_FREE, 0);
                test_assert($rs, '系统异常，请稍后再试');
            }else{

                $need_coins = setting('ai_ty_coins',190);
                $discount = UsersProductPrivilegeModel::hasPrivilege(
                    USER_PRIVILEGE,
                    PrivilegeModel::RESOURCE_TYPE_AI_TY,
                    PrivilegeModel::PRIVILEGE_TYPE_DISCOUNT
                );
                //折扣
                if ($discount){
                    $need_coins = ceil($discount / 100 * $need_coins);
                }
                if ( $member->coins < $need_coins) {
                    throw new \Exception('余额不足', \Constant::COINS_INSUFFICIENT);
                }

                $isOk = MemberModel::where('aff', $member->aff)
                    ->where('coins', '>=', $need_coins)
                    ->decrement('coins', $need_coins);
                test_assert($isOk, '扣款失败,请确认您的余额是否足够', \Constant::COINS_INSUFFICIENT);

                //记录日志
                $tips = "[AI脱衣扣除]#金币： $need_coins";
                $rs3 = \UsersCoinrecordModel::createForExpend('aiTy', $member->uid, 0,
                    $need_coins,
                    0,
                    0,
                    0,
                    0,
                    null,
                    $tips);

                # 新增记录
                $rs = MemberStripModel::create_record($member->aff, $thumb, $thumb_w, $thumb_h, MemberStripModel::AI_PAY_TYPE_COINS, $need_coins);
                test_assert($rs, '系统异常，请稍后再试');

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
                    'product_id'            => '0',
                    'product_name'          => "AI脱衣",
                    'coin_consume_amount'   => (int)$need_coins,
                    'coin_balance_before'   => (int)($member->coins),
                    'coin_balance_after'    => (int)$member->coins - $need_coins,
                    'consume_reason_key'    => 'ai_strip',
                    'consume_reason_name'   => 'AI脱衣',
                    'order_id'              => (string)$rs3->id,
                    'create_time'           => to_timestamp($rs3->addtime),
                ]);
            }

        });
    }

    public function list_my_strip(MemberModel $member, $status, $page, $limit)
    {
        return MemberStripModel::list_my_strip($member->aff, $status, $page, $limit);
    }

    /*****************************************AI图片换脸*********************************************/
    public function list_face_nav()
    {
        $list = FaceCateModel::list_cate();
        $first = [
            'id' => 0,
            'name' => '全部素材'
        ];
        return collect($list)->prepend($first);
    }

    public function list_face_material(MemberModel $member, $id, $page, $limit,$searchId)
    {
        //FaceMaterialModel::setWatchUser($member);
        if ($id){
            $cat = FaceCateModel::find($id);
            test_assert($cat, '分类不存在');
        }
        return FaceMaterialModel::list_material($id, $page, $limit, $searchId );
    }

    public function getFacePreData(MemberModel $member, $id){
        $free_num = (int)UsersProductPrivilegeModel::hasPrivilege(
            USER_PRIVILEGE ,
            PrivilegeModel::RESOURCE_TYPE_AI_HL,
            PrivilegeModel::PRIVILEGE_TYPE_UNLOCK
        );

        $detail = null;
        if ($id > 0){
            FaceMaterialModel::setWatchUser($member);
            $detail = FaceMaterialModel::get_detail($id);
            test_assert($detail, '素材不存在');
        }

        return [
            'max_size'       => '2M',
            'free_num'       => $free_num,
            'coins'          => $member->coins,
            'ai_ht_coins'    => setting('ai_ht_coins', 9),
            'ai_ht_tips'     => setting('ai_ht_tips', ''),
            'exp_correct_img'=> url_cover('/upload_01/ads/20250212/2025021218285664497.png'),
            'exp_error1_img' => url_cover('/upload_01/ads/20250212/2025021218291187299.png'),
            'exp_error2_img' => url_cover('/upload_01/ads/20250212/2025021218292398581.png'),
            'detail'         => $detail,
        ];
    }

    public function change_face(MemberModel $member, $material_id, $type, $thumb, $thumb_w, $thumb_h)
    {
        $material = FaceMaterialModel::get_detail($material_id);
        test_assert($material, '素材已被删除');
        transaction(function () use ($material, $member, $material_id, $type, $thumb, $thumb_w, $thumb_h) {
            if ($type == 0){
                $need_coins = $material->coins;
                if (!$need_coins){
                    //金币数未配置 走默认的
                    $need_coins = setting('ai_ht_coins',190);
                }

                $discount = UsersProductPrivilegeModel::hasPrivilege(
                    USER_PRIVILEGE,
                    PrivilegeModel::RESOURCE_TYPE_AI_HL,
                    PrivilegeModel::PRIVILEGE_TYPE_DISCOUNT
                );
                //折扣
                if ($discount){
                    $need_coins = ceil($discount / 100 * $need_coins);
                }

                if ($member->coins < $need_coins) {
                    throw new \Exception('余额不足，不能进行支付');
                }

                $itOk = MemberModel::where([
                    ['uid', '=', $member->uid],
                    ['coins', '>=', $need_coins],
                ])->update([
                    'coins'       => DB::raw("coins-{$need_coins}"),
                    'consumption' => DB::raw("consumption+{$need_coins}")
                ]);
                if (empty($itOk)) {
                    throw new \Exception('扣款失败,请确认您的金币是否足够', 1008);
                }

                $tips = "[AI换脸扣除]#金币： $need_coins";
                $rs3 = \UsersCoinrecordModel::createForExpend('aiHl', $member->uid, 0,
                    $need_coins,
                    $material_id,
                    0,
                    0,
                    0,
                    null,
                    $tips);

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
                    'product_id'            => (string)$material->id,
                    'product_name'          => "AI图片换脸:" . $material->title,
                    'coin_consume_amount'   => (int)$material->coins,
                    'coin_balance_before'   => (int)($member->coins),
                    'coin_balance_after'    => (int)$member->coins - $need_coins,
                    'consume_reason_key'    => 'ai_image_face',
                    'consume_reason_name'   => 'AI图片换脸',
                    'order_id'              => (string)$rs3->id,
                    'create_time'           => to_timestamp($rs3->addtime),
                ]);

                $rs = MemberFaceModel::create_record($member->aff, $material_id, $type, $need_coins, $material->thumb, $material->thumb_w, $material->thumb_h, $thumb, $thumb_w, $thumb_h);
                test_assert($rs, '系统异常，请稍后再试');
            }else{
                if ($material->type == FaceMaterialModel::TYPE_FIX){
                    $value = UsersProductPrivilegeModel::hasPrivilege(
                        USER_PRIVILEGE,
                        PrivilegeModel::RESOURCE_TYPE_AI_HL,
                        PrivilegeModel::PRIVILEGE_TYPE_UNLOCK
                    );
                    test_assert($value, '免费解锁次数不足');
                }else{
                    test_assert(false, '此素材不能用次数解锁');
                }

                $rs = MemberFaceModel::create_record($member->aff, $material_id, $type, 0, $material->thumb, $material->thumb_w, $material->thumb_h, $thumb, $thumb_w, $thumb_h);
                test_assert($rs, '系统异常，请稍后再试');

                //使用VIP权限的次数
                UsersProductPrivilegeModel::hasPrivilegeAndSubTimePrivilege(
                    USER_PRIVILEGE,
                    PrivilegeModel::RESOURCE_TYPE_AI_HL,
                    PrivilegeModel::PRIVILEGE_TYPE_UNLOCK,
                    $member->aff
                );
            }
            // 使用次数维护
            $isOk = $material->increment('use_ct');
            test_assert($isOk, '系统异常,请稍后重试');
            MemberModel::clearFor($member);
        });
    }

    public function customize_face(MemberModel $member, $type, $ground, $ground_w, $ground_h, $thumb, $thumb_w, $thumb_h)
    {
        transaction(function () use ($member, $type, $ground, $ground_w, $ground_h, $thumb, $thumb_w, $thumb_h) {
            if ($type == 0){
                $need_coins = setting('ai_ht_coins',190);

                $discount = UsersProductPrivilegeModel::hasPrivilege(
                    USER_PRIVILEGE,
                    PrivilegeModel::RESOURCE_TYPE_AI_HL,
                    PrivilegeModel::PRIVILEGE_TYPE_DISCOUNT
                );
                //折扣
                if ($discount){
                    $need_coins = ceil($discount / 100 * $need_coins);
                }

                if ($member->coins < $need_coins) {
                    throw new \Exception('余额不足', 1008);
                }

                $itOk = MemberModel::where([
                    ['uid', '=', $member->uid],
                    ['coins', '>=', $need_coins],
                ])->update([
                    'coins'       => DB::raw("coins-{$need_coins}"),
                    'consumption' => DB::raw("consumption+{$need_coins}")
                ]);
                if (empty($itOk)) {
                    throw new \Exception('扣款失败,请确认您的金币是否足够', 1008);
                }

                $tips = "[AI换脸扣除]#金币： $need_coins";
                $rs3 = \UsersCoinrecordModel::createForExpend('aiHl', $member->uid, 0,
                    $need_coins,
                    0,
                    0,
                    0,
                    0,
                    null,
                    $tips);
                
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
                    'product_id'            => '0',
                    'product_name'          => "AI图片换脸",
                    'coin_consume_amount'   => (int)$need_coins,
                    'coin_balance_before'   => (int)($member->coins),
                    'coin_balance_after'    => (int)$member->coins - $need_coins,
                    'consume_reason_key'    => 'ai_image_face',
                    'consume_reason_name'   => 'AI图片换脸',
                    'order_id'              => (string)$rs3->id,
                    'create_time'           => to_timestamp($rs3->addtime),
                ]);

                $rs = MemberFaceModel::create_customize_record($member->aff, $type, $need_coins, $ground, $ground_w, $ground_h, $thumb, $thumb_w, $thumb_h);
                test_assert($rs, '系统异常，请稍后再试');
            }else{
                $rs = MemberFaceModel::create_customize_record($member->aff, $type, 0, $ground, $ground_w, $ground_h, $thumb, $thumb_w, $thumb_h);
                test_assert($rs, '系统异常，请稍后再试');

                //使用VIP权限的次数
                UsersProductPrivilegeModel::hasPrivilegeAndSubTimePrivilege(
                    USER_PRIVILEGE,
                    PrivilegeModel::RESOURCE_TYPE_AI_HL,
                    PrivilegeModel::PRIVILEGE_TYPE_UNLOCK,
                    $member->aff
                );
            }
//            $ty_id = $rs->id;
//            bg_run(function () use ($ty_id){
//                AiSdkService::image_face($ty_id);
//            });
        });
    }

    public function list_my_face(MemberModel $member, $status, $page, $limit)
    {
        return MemberFaceModel::list_my_face($member->aff, $status, $page, $limit);
    }

    public function createComComment(MemberModel $member, $commentId, $content, $cityname)
    {
        $aff = $member->aff;
    
        $parentComment = FaceMaterialCommentModel::getCommentByIds($member, $commentId);
        test_assert($parentComment,'此评论不存在');

        $data = [
            'material_id'       => $parentComment->material_id,
            'pid'           => $parentComment->id,
            'aff'           => $aff,
            'comment'       => $content,
            'status'        => FaceMaterialCommentModel::STATUS_WAIT,
            'refuse_reason' => '',
            'ipstr'         => USER_IP,
            'is_top'        => FaceMaterialCommentModel::TOP_NO,
            'is_finished'   => FaceMaterialCommentModel::FINISH_OK,
            'cityname'      => $cityname,
            'created_at'    => \Carbon\Carbon::now(),
        ];
        $comment = FaceMaterialCommentModel::create($data);
        test_assert($comment,'系统异常,异常码:1001');
        bg_run(function () use ($member, $content, $comment){
            //检查评论
            FilterService::checkFaceMateriaComment($member, $content, $comment);
        });

        return true;
    }

      /**
     * @throws \Exception
     */
    public function createPostCommentNew(MemberModel $member, $id, $content, $cityname)
    {
        $aff = $member->aff;
       
        $post = FaceMaterialModel::find($id);

        test_assert($post,'此素材不存在');
        $status = FaceMaterialCommentModel::STATUS_WAIT;
        //年卡及以上会员直接通过
//        if (in_array($member->vip_level,[\MemberModel::VIP_LEVEL_YEAR,\MemberModel::VIP_LEVEL_LONG]) && $member->is_vip){
//            $status = \FaceMaterialCommentModel::STATUS_PASS;
//        }
        $data = [
            'material_id'       => $post->id,
            'pid'           => 0,
            'aff'           => $aff,
            'comment'       => $content,
            'status'        => $status,
            'refuse_reason' => '',
            'ipstr'         => USER_IP,
            'is_top'        => FaceMaterialCommentModel::TOP_NO,
            'is_finished'   => FaceMaterialCommentModel::FINISH_OK,
            'cityname'      => $cityname,
            'created_at'    => \Carbon\Carbon::now()
        ];
        $comment = FaceMaterialCommentModel::create($data);
        test_assert($comment,'系统异常,异常码:1001');

        bg_run(function () use ($member, $content, $comment){
            //检查评论
            FilterService::checkPostComment($member, $content, $comment);
        });

        return true;
    }


    /**
     * 评论点赞/取消点赞
     * @throws \Exception
     */
     function likeComment(MemberModel $member,$post_id, $type,$action_type)
    {
        $aff = $member->aff;
        if($type == FaceMaterialUserLikeModel::TYPE_COMMENT){
            $comment = FaceMaterialCommentModel::find($post_id);
            test_assert($comment,'此评论不存在');
        }
        if($type == FaceMaterialUserLikeModel::TYPE_MATERIAL){
            $post = FaceMaterialModel::find($post_id);
            test_assert($post,'此素材不存在');
        }
        $record = FaceMaterialUserLikeModel::getIdsById($aff,$post_id,$type,$action_type);
        if (!$record) {
            $data = [
                'aff'        => $aff,
                'related_id' => $post_id,
                'type' => $type,
                'action_type' => $action_type,
                'created_at' => date('Y-m-d H:i:s'),
            ];
            FaceMaterialUserLikeModel::create($data);
            $res_fields = 'is_like';
            if($action_type == FaceMaterialUserLikeModel::ACTION_LIKE){
                if($type == FaceMaterialUserLikeModel::TYPE_COMMENT){
                    FaceMaterialCommentModel::where('id', $post_id)->increment('like_num');
                }
                if($type == FaceMaterialUserLikeModel::TYPE_MATERIAL){
                    FaceMaterialModel::where('id', $post_id)->increment('like_count');
                }
            }

            if($action_type == FaceMaterialUserLikeModel::ACTION_COLLECT){
               FaceMaterialModel::where('id', $post_id)->increment('favorite_count');
                $res_fields = 'is_favorite';
            }
            return [
                'message' => FaceMaterialUserLikeModel::TYPE_TIPS[$type].FaceMaterialUserLikeModel::ACTION_TIPS[$action_type].'成功',
                $res_fields => 1,
            ];
        } else {
            $record->delete();

            $res_fields = 'is_like';
            if($action_type == FaceMaterialUserLikeModel::ACTION_LIKE){
                if($type == FaceMaterialUserLikeModel::TYPE_COMMENT){
                    FaceMaterialCommentModel::where('id', $post_id)->where('like_num','>',0)->decrement('like_num');
                }
                if($type == FaceMaterialUserLikeModel::TYPE_MATERIAL){
                    FaceMaterialModel::where('id', $post_id)->where('like_count','>',0)->decrement('like_count');
                }
            }

            if($action_type == FaceMaterialUserLikeModel::ACTION_COLLECT){
                $res_fields = 'is_favorite';
               FaceMaterialModel::where('id', $post_id)->where('favorite_count','>',0)->decrement('favorite_count');
            }

            return [
                'message' => '已取消'.FaceMaterialUserLikeModel::TYPE_TIPS[$type].FaceMaterialUserLikeModel::ACTION_TIPS[$action_type],
                $res_fields => 0,
            ];
        }
    }


    /**
     * @throws \RedisException
     * @throws \Exception
     */
    public function listCommentsByPostIdNew(MemberModel $member, $postId)
    {
        FaceMaterialCommentModel::setWatchUser($member);
        list($page,$limit) = QueryHelper::pageLimit();
        $post = FaceMaterialModel::find($postId);
        test_assert($post,"此素材不存在");
        return FaceMaterialCommentModel::getCommentById($postId, $page, $limit);
    }


    public function listCommentsByCommentId(MemberModel $member, $commentId, $page, $limit)
    {
        FaceMaterialCommentModel::setWatchUser($member);
        $comment = FaceMaterialCommentModel::find($commentId);
        test_assert($comment,'此评论不存在');
        $post = FaceMaterialModel::find($comment->material_id);
        test_assert($post,'此素材不存在');
        return FaceMaterialCommentModel::listCommentsByCommentId($comment->id, $comment->material_id, $page, $limit);
    }

}