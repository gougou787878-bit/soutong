<?php

/**
 * IP类
 * Class IpController
 */

class IpController  extends AdminController
{
    public function init()
    {
        parent::init();

    }


    public function searchIpAction()
    {
        $this->post['ip'] = $this->post['ip'] ?? '';
        $ip = trim($this->post['ip']);
        $topic = [];
        if ($ip) {
          //  $sql = "SELECT * FROM " . TABLE_PREFIX . 'ipbanned' . " WHERE `ip`='$ip' ";
          //  $query = $this->Db->Query($sql);

            $query = IpbannedModel::where('ip','=',$ip);
            $data = $query->get(['*'],false)->toArray();
            foreach ($data as $value) {
                $value['dateline'] = my_date_format($value['dateline']);
                $value['expiration'] = date('Y-m-d H:i:s', $value['expiration']);
                $topic[] = $value;
            }
        }
        //include(template('admin/ipbanned'));
        $this->getView()
            ->assign('topic', $topic)
            ->display('ip/banned.phtml');
    }

    public function removeIpAction()
    {
        $id = (int)$this->Get['id'];
        $sql = "DELETE FROM " . TABLE_PREFIX . 'ipbanned' . " WHERE id='$id'";
        $this->Db->Query($sql);
        $this->bannedIp();
    }

    /**
     * 添加封禁IP--封禁IP页
     */
    public function addIpAction()
    {
        $this->post['ip'] = $this->post['ip'] ?? '';
        $ip = trim($this->post['ip']);
        if ($ip) {
            $data['ip1'] = $ip;
            $data['admin'] = MEMBER_NAME;
            $data['dateline'] = TIMESTAMP;
            $data['expiration'] = TIMESTAMP + (int)$this->post['expiration'] * 86400;
            //$this->Db->SetTable(TABLE_PREFIX . 'ipbanned');
            //$uid = $this->Db->Insert($data);
            $uid = IpbannedModel::insert($data);
            $uid && $this->showJson("添加成功");
        }
    }

    public function bannedIpAction()
    {
        $where = '';
        $this->Get['groupid'] = $this->Get['groupid'] ?? 0;
        if ($this->Get['groupid'] > 0) {
            $groupid = (int)$this->Get['groupid'];
            $where = "WHERE expiration<='" . TIMESTAMP . "'";
            $query_link = "d.php?mod=user&code=bannedIp&expirated=1";
        } else {
            $query_link = 'd.php?mod=user&code=bannedIp';
        }
       // $sql = "SELECT * FROM " . TABLE_PREFIX . "ipbanned $where ORDER BY id DESC LIMIT $this->pageStart,$this->perPageNum";
        $topic = [];
       // $query = $this->Db->Query($sql);
        $items = IpbannedModel::orderBy('id','desc')->offset($this->pageStart)
            ->limit($this->perPageNum);
        foreach ($items as $value) {
            $value['dateline'] = my_date_format($value['dateline']);
            $value['expiration'] = date('Y-m-d H:i:s', $value['expiration']);
            $topic[] = $value;
        }
        if (count($topic) < $this->perPageNum) {
            $page_arr['html'] = sitePage($query_link, 1);
        } else {
            $page_arr['html'] = sitePage($query_link);
        }
        //include(template('admin/ip/banned'));
        $this->getView()
            ->assign('topic', $topic)
            ->assign('page_arr', $page_arr)
            ->display('ip/banned.phtml');
    }

    /**
     * 添加封禁IP--用户页
     */
    public function doAddAction()
    {
        $this->Request['ip'] = $this->Request['ip'] ?? '';
        $ip = trim($this->Request['ip']);
        if ($ip) {
            $data['ip'] = $ip;
            $data['admin'] = MEMBER_NAME;
            $data['dateline'] = TIMESTAMP;
            $data['expiration'] = TIMESTAMP + 86400;
           // $this->Db->SetTable(TABLE_PREFIX . 'ipbanned');
           // $uid = $this->Db->Insert($data);
            $uid = IpbannedModel::insert($data);
            //0
            $uid && $this->showJson("添加成功");
        }
    }

    /**
     * 错误登陆日志列表
     */
    public function errorLoginAction()
    {
        $query_link = 'd.php?mod=ip&code=errorLogin';
       //$sql = "SELECT * FROM " . TABLE_PREFIX . "failedlogins ORDER BY lastupdate DESC LIMIT $this->pageStart,$this->perPageNum";
       //$query = $this->Db->Query($sql);
        $query = FailedloginsModel::orderBy('lastupdate','desc')->offset($this->pageStart)
            ->limit($this->perPageNum);
        $data = $query->get(['*'],false)->toArray();
        $topic = [];
        foreach ($data as $value) {
            $value['lastupdate'] = date('Y-m-d H:i:s', $value['lastupdate']);
            $topic[] = $value;
        }
        if (count($topic) < $this->perPageNum) {
            $page_arr['html'] = sitePage($query_link, 1);
        } else {
            $page_arr['html'] = sitePage($query_link);
        }
        //include(template('admin/ip/loginError'));
        $this->getView()
            ->assign('topic', $topic)
            ->assign('page_arr', $page_arr)
            ->display('ip/loginError.phtml');
    }
}


