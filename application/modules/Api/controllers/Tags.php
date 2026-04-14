<?php

use service\TagsService;

/**
 * Class TopicController
 */
class TagsController extends BaseController
{


    /**
     * 专题列表 合集列表
     * @return bool|void
     */
    public function listAction()
    {
        $page = $this->post['page'] ?? 1;
        $limit = $this->post['limit'] ?? 10;
        $data = TagsService::getList($page, $limit);
        return $this->showJson($data);
    }


}