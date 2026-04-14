<?php

use service\TabService;

/**
 * Class TabController
 * @author xiongba
 * @date 2020-10-31 15:46:57
 */
class TabController extends BaseController
{

    /**
     * tab 栏目标签
     */
    public function indexAction()
    {
        $_ver = $this->post['version']??'5.0.0';

        $data = [
            [
                'current' => false,
                'id'      => -1,
                'name'    => '关注',
                'type'    => 'follow',
                'api'     => 'api/mv/listOfFollow',
                'params'  => ['_t' => 0],
            ],
            [
                'current' => true,
                'id'      => -1,
                'name'    => '推荐',
                'type'    => 'feature', // api/mv/getFeature
                'api'     => 'api/mv/listOfFeature',
                'params'  => ['_t' => 0],
            ],
            [
                'current' => false,
                'id'      => -1,
                'name'    => '最新',
                'type'    => 'latest', // api/mv/getFeature
                'api'     => 'api/mv/listOfLatest',
                'params'  => ['_t' => 0],
            ],
            [
                'current' => false,
                'id'      => -1,
                'name'    => '最热',
                'type'    => 'hottest', // api/mv/getFeature
                'api'     => 'api/mv/listOfHottest',
                'params'  => ['_t' => 0],
            ],
        ];
        if(version_compare($_ver, '4.7.0', '>=')){
            $data = [
                [
                    'current' => false,
                    'id'      => -2,
                    'name'    => '关注',
                    'type'    => 'follow',
                    'api'     => 'api/mv/listOfFollow',
                    'params'  => ['_t' => 0],
                ],
                [
                    'current' => true,
                    'id'      => -1,
                    'name'    => '推荐',
                    'type'    => 'feature', // api/mv/getFeature
                    'api'     => 'api/mv/listOfFeature',
                    'params'  => ['_t' => 0],
                ],
                [
                    'current' => true,
                    'id'      => -3,
                    'name'    => '发现',
                    'type'    => 'find', // api/mv/index
                    'api'     => 'api/mv/index',
                    'params'  => ['_t' => 0],
                ]
            ];
        }
        $tabs = (new \service\TabService())->getTabList();

        /** @var TabModel[] $tabs */
        $rs = $data;
        $_data = [];
        foreach ($tabs as $tab) {
            $_data['current'] = false;
            $_data['id'] = $tab->tab_id;
            $_data['name'] = $tab->tab_name;
            $_data['type'] = 'tab';
            $_data['api'] = 'api/mv/listOfTab';
            $_data['params'] = ['tabId' => $tab->tab_id];
            $rs[] = $_data;
        }
        $this->showJson($rs);
    }

    /**
     * tab 栏目标签
     */
    public function index_awAction()
    {
        $tabs = (new \service\TabService())->getAwTabList();
        /** @var TabModel[] $tabs */
        $rs = [];
        $_data = [];
        foreach ($tabs as $k => $tab) {
            $_data['current'] = $k == 0 ? true : false;
            $_data['id'] = $tab->tab_id;
            $_data['name'] = $tab->tab_name;
            $_data['type'] = 'tab';
            $_data['api'] = 'api/mv/list_of_aw_tab_list';
            $_data['params'] = ['tabId' => $tab->tab_id, 'is_aw' => 1];
            $rs[] = $_data;
        }
        $this->showJson($rs);
    }

    public function categoryAction()
    {
        $data = TabService::getCateList();
        return $this->showJson($data);
    }

    public function categoryNewAction()
    {
        $data = TabService::getCateList();
        foreach ($data as &$val){
            array_unshift($val['tags_ary'], "全部");
        }

        return $this->showJson($data);
    }

    public function tuwenAction()
    {
        $data[] = [
            'current' => true,
            'id'      => -1,
            'name'    => '漫画',
            'type'    => 'mh',
            'api'     => 'api/manhua/index',
            'params'  => ['_t' => 0],
        ];
        $data[] = [
            'current' => false,
            'id'      => -1,
            'name'    => '图集',
            'type'    => 'image',
            'api'     => 'api/image/index',
            'params'  => ['_t' => 0],
        ];
        if (version_compare(@$_POST['version'], '4.0.0', '>=')) {
            $data[] = [
                'current' => false,
                'id'      => -1,
                'name'    => '小说',
                'type'    => 'story',
                'api'     => 'api/story/index',
                'params'  => ['_t' => 0],
            ];
        }
        $this->showJson($data);
    }


}