<?php

use service\FileService;

class FilecacheController extends BackendBaseController
{

    public function listAjaxAction()
    {
        $res = [
            ['id' => 1, 'name' => '基础信息', 'obj' => 'config'],
            ['id' => 2, 'name' => '视频首页', 'obj' => 'mv_index'],
            ['id' => 3, 'name' => '视频列表', 'obj' => 'mv_list_mvs'],
            ['id' => 4, 'name' => '视频详情', 'obj' => 'mv_detail'],
            ['id' => 5, 'name' => '视频导航详情', 'obj' => 'mv_tab_detail'],
            ['id' => 6, 'name' => '视频推荐', 'obj' => 'mv_recommend'],
            ['id' => 7, 'name' => '视频评论', 'obj' => 'mv_comment'],
            ['id' => 8, 'name' => '帖子结构', 'obj' => 'post_construct'],
            ['id' => 9, 'name' => '帖子详情', 'obj' => 'post_detail'],
            ['id' => 10, 'name' => '社区话题详情', 'obj' => 'post_topic_detail'],
            ['id' => 11, 'name' => '他人帖子', 'obj' => 'post_user_post'],
            ['id' => 12, 'name' => '帖子评论', 'obj' => 'post_comment'],
            ['id' => 13, 'name' => '漫画列表', 'obj' => 'manhua_list'],
            ['id' => 14, 'name' => '漫画详情', 'obj' => 'manhua_detail'],
            ['id' => 15, 'name' => '漫画推荐', 'obj' => 'manhua_recommend'],
            ['id' => 16, 'name' => '漫画导航详情', 'obj' => 'manhua_tab_detail'],
            ['id' => 17, 'name' => '全部缓存', 'obj' => 'all'],
        ];
        $result = [
            'count' => count($res),
            'data'  => $res,
            "msg"   => '',
            "desc"  => '',
            'code'  => 0
        ];
        return $this->ajaxReturn($result);
    }

    public function indexAction()
    {
        $this->display();
    }

    public function getModelClass():string
    {
        return '';
    }

    public function getPkName():string
    {
        return '';
    }

    public function clearAction()
    {
        try {
            $obj = $_POST['obj'] ?? '';
            FileService::publishJob('delete', $obj, []);
            return $this->ajaxSuccess('成功加入文件缓存清理频道');
        } catch (Throwable $e) {
            return $this->ajaxError($e->getMessage());
        }
    }
}