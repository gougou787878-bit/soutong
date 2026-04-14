<?php

/**
 * 广告样本
 * Class SampleController
 */

class SampleController extends AdminController
{

    public function indexAction()
    {

        $query_link = 'd.php?mod=sample&code=index';

        $data = AdsampleModel::query()
            ->offset($this->pageStart)
            ->limit($this->perPageNum)
            ->get()
            ->toArray();
        if (count($data) < $this->perPageNum)
            $page_arr['html'] = sitePage($query_link, 1);
        else
            $page_arr['html'] = sitePage($query_link);

        $this->getView()
            ->assign('data', $data)
            ->assign('page_arr', $page_arr)
            ->display('sample/list.phtml');
    }


    /**
     * 编辑
     */
    public function editAction()
    {
        $id = $_REQUEST['id'] ?? '';
        $data['id']=$id;
        $data['content']='';
        if ($id) {
            $data = AdsampleModel::where('id', '=', $id)->first()->toArray();
        }
        $this->getView()
            ->assign('id', $id)
            ->assign('data', $data)
            ->display('sample/edit.phtml');
    }

    /**
     * 保存更改
     */
    public function doEditAction()
    {
        if(!$_POST['content']){
            $this->showJson("广告样本不能为空.", 0);
        }
        $data = [
            'content' => $_POST['content'],
            'created_at'=>time()
        ];
        $re = false;
        ( 0== $_POST['id']) && $re = AdsampleModel::addAdsample($data);
        ( 0!= $_POST['id']) && $re = AdsampleModel::updateAdsample($_POST['id'],$data);
        if ($re) {
            $this->showJson("修改成功.", 1);
        } else {
            $this->showJson("处理是吧，确认样本是否已存在", 0);
        }
    }
    public function delAction() {
        $id = intval($this->get['id']);
        if ($id) {
            $result = AdsampleModel::delAdsample($id);
            if ($result) {
                return $this->showJson('删除成功', 1);
            } else {
                return $this->showJson('删除失败', 0);
            }
        } else {
            return $this->showJson('数据传入失败！', 0);
        }
    }

}



