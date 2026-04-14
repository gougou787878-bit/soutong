<?php

/**
 * 短信类
 * Class MsgController
 */

class MsgController  extends AdminController
{
    public $action_name = 'msg';
    public function init()
    {
        parent::init();

    }



    public function indexAction()
    {
        $flag = trim($this->Request['flag'] ?? '');
        $status = $this->Request['status']??-1;
        $where = ' where 1=1';
        $query_link = 'd.php?mod=msg&code=index';


      // $sql = "SELECT * FROM " . TABLE_PREFIX . "sms_log $where ORDER BY id DESC LIMIT $this->pageStart,$this->perPageNum";
      // $query = $this->Db->Query($sql);

        $query = SmsLogModel::orderBy('id','desc')->offset($this->pageStart)
            ->limit($this->perPageNum);

        if ($flag) {
            //$where .= " and (`mobile` = '$flag' or `ip` = '{$flag}' or `uuid` = '{$flag}') ";
            $query->where(function ($sql) use ($flag) {
                $sql->where('mobile','=',$flag);
                $sql->orWhere('ip','=',$flag);
                $sql->orWhere('uuid','=',$flag);
            });
            $query_link .= "&flag=$flag";
        }

        if ($status != -1) {
           //  $where .= " and `status` = '$status'";
            $query->where('status','=',$status);
            $query_link .= "&status=$status";
        }

        $data = $query->get(['*'],false)->toArray();
        $topic = [];
        foreach ($data as $value) {
            $topic[] = $value;
        }
        if (count($topic) < $this->perPageNum) {
            $page_arr['html'] = sitePage($query_link, 1);
        } else {
            $page_arr['html'] = sitePage($query_link);
        }
        //include(template('admin/sms/list'));
        $this->getView()
            ->assign('topic', $topic)
            ->assign('page_arr', $page_arr)
            ->display('sms/list.phtml');
    }

    public function delAction()
    {
        $id = (int)$this->Get['id'] ?? '';
        $this->Db->SetTable(TABLE_PREFIX . 'sms_log');
        $this->Db->Delete($id) && $this->showJson("删除成功");
    }


}


