<?php

/**
 * 用户
 * Class UserController
 */

class UserController extends AdminController
{

    public function init()
    {
        parent::init();

    }

    public $user_roles = array(
        2  => '广告推广用户',
        3  => '高级代理',
        /* 7 => '官网单独用', *///没带aff 默认一个值  区别与从别的app直接下载的app(aff为0)
        4  => '黑名单',//直接不能使用软件功能
        8  => '普通用户',
        9  => '禁言用户',
        10 => '后台资源用户',
        14 => '限时VIP',
        15 => '永久VIP'
    );

    /*
     * 列表
     */
    public function indexAction()
    {
        $uidOrUuid = $_REQUEST['uuid'] ?? '';
        $username = $_REQUEST['username'] ?? '';
        $phone = $_REQUEST['phone'] ?? '';
        $vip_level = $_REQUEST['vip_level'] ?? '';
        $aff = $_REQUEST['aff'] ?? '';
        $invited_by = $_REQUEST['invited_by'] ?? '';
        $role_id = $_REQUEST['role_id'] ?? '';

        $is_live_super = $_REQUEST['is_live_super'] ?? '';

        $query_link = 'd.php?mod=user&code=index&';

        $query = MemberModel::offset($this->pageStart)->limit($this->perPageNum);
        $query->orderBy('uid', 'desc');
        if ($username != '') {
            $query->where('nickname', '=', $username);
            $query_link .= "&nickname={$username}";
        }if ($vip_level != '') {
            $query->where('vip_level', '=', $vip_level);
            $query_link .= "&vip_level={$vip_level}";
        }

        if ($uidOrUuid != '') {
            $query->where('uuid', $uidOrUuid);
            $query_link .= "&uuid={$uidOrUuid}";
        }
        if ($role_id != '') {
            $query->where('role_id', $role_id);
            $query_link .= "&role_id={$role_id}";
        }
        if ($is_live_super) {
            $query->where('is_live_super', '=', $is_live_super);
            $query_link .= "&is_live_super={$is_live_super}";
        }
        if ($phone != '') {
            $query->where('phone', '=', $phone);
            $query_link .= "&phone={$phone}";
        }
        if ($invited_by != '') {
            $query->where('invited_by', '=', $invited_by);
            $query_link .= "&invited_by={$invited_by}";
        }

        if ($aff != '') {
            $query->where('aff', '=', $aff);
            $query_link .= "&aff={$phone}";
        }
        /*if ($oauth_type != '') {
            $query->where('oauth_type', '=', $oauth_type);
            $query_link .= "&oauth_type={$oauth_type}";
        }*/
        if ($aff != '') {
            $query->where('aff', '=', $aff);
            $query_link .= "&aff={$aff}";
        }
        /*if ($share != '') {
            $share = get_num($share);
            $query->where('aff', '=', $share);
            $query_link .= "&share={$share}";
        }*/

        $data = $query->get()->toArray();
        $topic = [];
        foreach ($data as $value) {
            //$value['role_name'] = isset($this->user_roles[$value['role_id']]) ? $this->user_roles[$value['role_id']] : '';
            //$value['regdate'] = date('Y-m-d H:i:s', $value['regdate']);
            //$value['payment'] = $value['all_money'] - $value['balance'];
            //if ($value['expired_at']) {
            //    $value['expired_at'] = date('Y-m-d', $value['expired_at']);
            //}
            $topic[] = $value;
        }


        if (count($topic) < $this->perPageNum) {
            $page_arr['html'] = sitePage($query_link, 1);
        } else {
            $page_arr['html'] = sitePage($query_link);
        }
        $this->getView()
            ->assign('user_roles', $this->user_roles)
            ->assign('topic', $topic)
            ->assign('page_arr', $page_arr)
            ->display('member/list.phtml');
    }

    /**
     * 用户活跃日志
     */
    public function userLogAction()
    {
        $where = '';
        $flag = trim($this->Request['flag'] ?? '');
        $topic = [];
        $query = MembersLogModel::orderBy('id', 'desc')->offset($this->pageStart)->limit($this->perPageNum);

        if ($flag) {
            $where->where(function ($sql) use ($flag) {
                $sql->where(\DB::raw('BINARY uuid'), '=', $flag);
                $sql->where(\DB::raw('BINARY lastip'), '=', $flag);

            });
            $query_link = "d.php?mod=user&code=userLog&flag=$flag";
        } else {
            $query_link = 'd.php?mod=user&code=userLog';
        }

        $data = $query->get(['*'], false)->toArray();
        foreach ($data as $value) {
            $value['lastactivity'] = date('Y-m-d H:i:s', $value['lastactivity']);
            $topic[] = $value;
        }


        if (count($topic) < $this->perPageNum) {
            $page_arr['html'] = sitePage($query_link, 1);
        } else {
            $page_arr['html'] = sitePage($query_link);
        }
        $this->getView()
            ->assign('topic', $topic)
            ->assign('page_arr', $page_arr)
            ->display('member/user-log.phtml');
    }

    public function listUuidAction()
    {//用户设备更换记录

        $flag = $this->Request['flag'] ?? '';

        $query = UuidLogModel::offset($this->pageStart)->limit($this->perPageNum);
        if ($flag) {
            $query->where(function ($sql) use ($flag) {
                $sql->where(\DB::raw('old_uuid'), '=', $flag);
                $sql->where(\DB::raw('new_uuid'), '=', $flag);

            });
            $query_link = "d.php?mod=user&code=listUuid&flag=$flag";
        } else {
            $query_link = 'd.php?mod=user&code=listUuid';
        }
        $data = $query->get(['*'], false)->toArray();

        $topic = [];
        foreach ($data as $value) {
            $value['created_at'] = date('Y-m-d H:i:s', $value['created_at']);
            $topic[] = $value;
        }

        if (count($topic) < $this->perPageNum) {
            $page_arr['html'] = sitePage($query_link, 1);
        } else {
            $page_arr['html'] = sitePage($query_link);
        }

        $this->getView()
            ->assign('topic', $topic)
            ->assign('page_arr', $page_arr)
            ->display('member/uuid.phtml');
    }

    /**
     * 用户收藏记录
     */
    public function listUserSavedAction()
    {
        $id = trim($_REQUEST['uuid'] ?? '');

        $query = UserCollectionModel::orderBy('id', 'desc')->offset($this->pageStart)->limit($this->perPageNum);
        if ($id) {
            $query->where('uuid', '=', $id);
            $query_link = "d.php?mod=user&code=listUserSaved&uuid=$id";
        } else {
            $query_link = 'd.php?mod=user&code=listUserSaved';
        }
        $data = $query->get(['*'], false)->toArray();
        $topic = [];
        foreach ($data as $value) {
            $value['created_at'] = date('Y-m-d H:i:s', $value['created_at']);
            $id = $value['m_id'];
            $table = $value['type'] == 'dm' ? 'mv' : 'mh';
            $value['title'] = BaseModel::processArray(\DB::table("{$table}")->where('id', '=', $id)->first(['*'],
                false))['title'];
            $topic[] = $value;
        }
        if (count($topic) < $this->perPageNum) {
            $page_arr['html'] = sitePage($query_link, 1);
        } else {
            $page_arr['html'] = sitePage($query_link);
        }
        $this->getView()
            ->assign('topic', $topic)
            ->assign('page_arr', $page_arr)
            ->display('member/collectionList.phtml');
    }

    /**
     * 反馈日志
     */
    public function listUserFeedAction()
    {
        $feed_status = $this->Request['feed_status'] ?? '999';
        $type = $this->Request['type'] ?? 0;
        $system = $this->Request['system'] ?? '';
        $start = $this->Request['start'] ?? '';
        $end = $this->Request['end'] ?? '';
        $flag = $this->Request['flag'] ?? '';

        $query_link = 'd.php?mod=user&code=listUserFeed';


        $query = UserFeedModel::orderBy('id', 'desc');
        if ($flag) {
            $query->where('uuid', '=', $flag);
            $query_link .= '&flag=' . $flag;
        }

        if ($feed_status != '999') {
            $query->where('status', '=', $feed_status);
            $query_link .= '&feed_status=' . $feed_status;
        }

        if ($system) {
            $query->where('oauth_type', '=', $system);
            $query_link .= "&oauth_type ='{$system}'";
        }

        if ($start) {
            $query->where('created_at', '>=', strtotime($start));
            $query_link .= "&start ={$start} ";
        }

        if ($end) {
            $query->where('created_at', '<=', strtotime($end));
            $query_link .= "&end ={$end} ";
        }

        if ($type > 0) {
            if ($type == 2) {
                $query->where(function ($sql) {
                    $sql->where('question', 'like', '%充值%');
                    $sql->orWhere('question', 'like', '%vip%');
                    $sql->orWhere('question', 'like', '%到账%');
                });
            } else {
                // $where .= " and `type` = '{$type}' ";
                $query->where('type', '=', $type);
            }
            $query_link .= '&type=' . $type;
        }

        $data = $query->offset($this->pageStart)->limit($this->perPageNum)->get(['*'], false)->toArray();
        $topic = [];
        foreach ($data as $value) {
            $value['created_at'] = date('Y-m-d H:i:s', $value['created_at']);
            $value['updated_at'] = date('Y-m-d H:i:s', $value['updated_at']);
            $value['status_name'] = $this->user_feed_status[$value['status']] ?? '';
            $value['evaluation_name'] = $this->user_feed_evaluation[$value['evaluation']] ?? '';
            $userInfo = MemberModel::select('oauth_type', 'app_version')
                ->where('uuid', '=', $value['uuid'])
                ->first(['*'], false)
                ->toArray();

            $value['version'] = $userInfo['oauth_type'] ? $userInfo['oauth_type'] . ': ' . $userInfo['app_version'] : '';
            $topic[] = $value;
        }
        if (count($topic) < $this->perPageNum) {
            $page_arr['html'] = sitePage($query_link, 1);
        } else {
            $page_arr['html'] = sitePage($query_link);
        }
        $this->getView()
            ->assign('topic', $topic)
            ->assign('page_arr', $page_arr)
            ->display('member/feed.phtml');
    }

    /**
     * 用户反馈详情
     */
    public function getUserFeedDetailAction()
    {
        $feed_id = (int)$_REQUEST['feed_id'] ?? 0;
        $data = UserFeedModel::where('id', '=', $feed_id)->first(['*'], false)->toArray();

        $data['created_at'] = date('Y-m-d H:i:s', $data['created_at']);
        $data['updated_at'] = date('Y-m-d H:i:s', $data['updated_at']);
        $data['status'] = $data['status'];
        $data['status_name'] = $this->user_feed_status[$data['status']] ?? '';
        $data['evaluation_name'] = $this->user_feed_evaluation[$data['evaluation']] ?? '';

        $detail = [];
        $items = UserFeedItemsModel::where('pid', '=', $feed_id)
            ->orderBy('created_at', 'asc')
            ->get(['*'], false)
            ->toArray();
        foreach ($items as $value) {
            $value['created_at'] = date('Y-m-d H:i:s', $value['created_at']);
            $detail[] = $value;
        }
        $data['detail'] = $detail;

        $this->getView()
            ->display('member/feedDetail.phtml');
    }

    /**
     * 回复客户反馈
     */
    public function replyUserFeedAction()
    {
        $feed_id = (int)$_REQUEST['feed_id'] ?? 0;
        $reply = $_REQUEST['reply'] ?? '';
        $reply = JAddSlashes($reply);

        $data = array(
            'status'     => $this->feed_status_admin_replied,
            'updated_at' => TIMESTAMP
        );

        \DB::beginTransaction();
        $re = UserFeedModel::where('id', '=', $feed_id)
            ->update($data);

        $reply_data = array(
            'pid'        => $feed_id,
            'type'       => $this->feed_admin_reply,
            'replied_by' => MEMBER_NAME,
            'reply'      => $reply,
            'created_at' => TIMESTAMP
        );

        $re1 = UserFeedItemsModel::insert($reply_data);

        if ($re !== false && $re1 !== false) {
            \DB::commit();
            $msg = '回复成功';
        } else {
            \DB::rollBack();
            $msg = '回复失败';
        }

        $this->showJson($msg);

    }

    public function replyall()
    {
        $data = UserModelModel::orderBy('id', 'desc')->get(['*'], false)->toArray();
        $replymodel = [];
        foreach ($data as $value) {
            $replymodel[$value['id']] = $value['title'];
        }

        $this->getView()
            ->display('member/replyall.phtml');
    }

    public function doreplyall()
    {
        $ids = $this->post['ids'];
        $reply = $this->post['reply'];
        $content = $this->post['content'];

        \DB::beginTransaction();
        $data = array(
            'status'     => $this->feed_status_admin_replied,
            'updated_at' => TIMESTAMP
        );
        if ($content) {
            $reply = $content;
        }

        $re = UserFeedModel::where('id', explode(',', $ids))
            ->update($data);

        $idsarr = explode(",", $ids);
        foreach ($idsarr as $feed_id) {
            $reply_data = array(
                'pid'        => $feed_id,
                'type'       => $this->feed_admin_reply,
                'replied_by' => MEMBER_NAME,
                'reply'      => $reply,
                'created_at' => TIMESTAMP
            );

            UserFeedModel::insert($reply_data);
        }

        if ($re !== false) {
            \DB::commit();
            $msg = '回复成功';
        } else {
            \DB::rollBack();
            $msg = '回复失败';
        }

        $this->showJson($msg);
    }

    public function replylist()
    {

        $topic = UserModelModel::select('*')->get(['*'], false)->toArray();

        $this->getView()
            ->assign('topic', $topic)
            ->display('member/replylist.phtml');
    }

    public function replymodelDel()
    {
        $id = (int)$_REQUEST['id'] ?? '';
        UserModelModel::where('id', '=', $id)->delete();
        $this->showJson('删除成功');
    }

    public function replyedit()
    {
        $id = $_REQUEST['id'] ?? 0;
        $data = UserModelModel::where('id', '=', $id)->first(['*'], false)->toArray();
        $this->getView()
            ->assign('data', $data)
            ->display('member/replyedit.phtml');
    }

    public function doreplyedit()
    {
        $title = $_REQUEST['title'];
        $id = $_REQUEST['id'] ?? 0;
        $data['title'] = $title;
        if ($id) {
            $data['updated_at'] = time();
            UserModelModel::where(['id' => $id])->update($data);
            $this->Messager("保存成功", -2);
        } else {
            $data['updated_at'] = $data['created_at'] = time();
            UserModelModel::insert($data);
            $this->showJson('编辑成功');
        }

    }

    public function closeUserFeedAction()
    {
        $id = (int)$_REQUEST['feed_id'] ?? 0;
        $data = array(
            'status' => $this->feed_status_closed
        );
        UserFeedModel::where('id', '=', $id)->update($data);

        $this->showJson('关闭成功');
    }

    /**
     * 删除反馈
     */
    public function delFeedAction()
    {
        $id = (int)$_REQUEST['id'] ?? 0;
        $row = UserFeedModel::where('id', '=', $id)->first(['*'], false)->toArray();
        if (!empty($row['image_1'])) {//上传图片
            $return1 = $this->deleteImg($row['image_1'], 'ads', IMG_UPLOAD . 'dp.php');
            if ($return1['code'] != 1) {
                $this->showJson("图片1删除失败" . $return1['msg'], -1);
            }
        }
        if (!empty($row['image_2'])) {//上传图片
            $return2 = $this->deleteImg($row['image_2'], 'ads', IMG_UPLOAD . 'dp.php');
            if ($return2['code'] != 1) {
                $this->showJson("图片2删除失败" . $return2['msg'], -1);
            }
        }
        UserFeedModel::where('id', '=', $id)->delete();

        UserFeedItemsModel::where('pid', '=', $id)->delete();

        $this->showJson('删除成功');
    }

    /**
     * 用户观影日志
     */
    public function listUserMvLogAction()
    {
        $id = trim($_REQUEST['uuid'] ?? '');
        $query = UserWatchModel::orderBy('id', 'desc')
            ->offset($this->pageStart)
            ->limit($this->perPageNum);
        if ($id) {
            $query->where('uuid', '=', $id);
            $query_link = "d.php?mod=user&code=listUserMvLog&uuid=$id";
        } else {
            $query_link = 'd.php?mod=user&code=listUserMvLog';
        }

        $data = $query->get(['*'], false)->toArray();

        $topic = [];
        foreach ($data as $value) {
            $value['created_at'] = date('Y-m-d H:i:s', $value['created_at']);
            $id = $value['m_id'];
            $table = $value['type'] == 'mh' ? 'mh' : 'mv';
            $value['title'] = BaseModel::processArray(\DB::table($table)->select('title')
                ->where('id', '=', $id)
                ->first(['*'], false))['title'];
            $topic[] = $value;
        }
        if (count($topic) < $this->perPageNum) {
            $page_arr['html'] = sitePage($query_link, 1);
        } else {
            $page_arr['html'] = sitePage($query_link);
        }
        $this->getView()
            ->assign('topic', $topic)
            ->assign('page_arr', $page_arr)
            ->display('member/watchList.phtml');
    }

    /**
     * 用户搜索日志
     */
    public function listUserSearchLogAction()
    {
        $id = trim($_REQUEST['uuid'] ?? '');
        $topic = [];

        $query = UserSearchModel::orderBy('id', 'desc')->offset($this->pageStart)
            ->limit($this->perPageNum);

        if ($id) {
            $query->where('uuid', '=', $id);
            $query_link = "d.php?mod=user&code=listUserSearchLog&uuid=$id";
        } else {
            $query_link = 'd.php?mod=user&code=listUserSearchLog';
        }
        $data = $query->get(['*'], false)->toArray();
        foreach ($data as $value) {
            $value['created_at'] = date('Y-m-d H:i:s', $value['created_at']);
            $topic[] = $value;
        }
        if (count($topic) < $this->perPageNum) {
            $page_arr['html'] = sitePage($query_link, 1);
        } else {
            $page_arr['html'] = sitePage($query_link);
        }
        $this->getView()
            ->assign('topic', $topic)
            ->assign('page_arr', $page_arr)
            ->display('member/usersearchlog.phtml');
    }

    /**
     * 添加用户
     */
    public function userEditAction()
    {
        $id = $_REQUEST['id'] ?? '';
        if (!$id) {
            $sql = "SHOW FULL COLUMNS from " . TABLE_PREFIX . "members";
            $rows = \DB::select($sql);
            foreach ($rows as $val) {
                $data[$val['Field']] = $val['Default'];
            }
        } else {
            $data = MemberModel::where('uid', '=', $id)->first(['*'], false)->toArray();
        }
        if (intval($data['expired_at']) >= 0) {
            $data['expired_at'] = date('Y-m-d H:i:s', intval($data['expired_at']));
        } else {
            $data['expired_at'] = date('Y-m-d H:i:s');
        }

        $this->getView()
            ->assign('id', $id)
            ->assign('data', $data)
            ->assign('user_roles', $this->user_roles)
            // ->assign('user_roles', [])
            ->display('member/edit.phtml');
    }

    public function doEditUserAction()
    {
        $data = $this->post['data'];
        $extra = $this->post['extra'];
        $data = JAddSlashes($data);

        if (!empty($data['password']) && (strlen($data['password']) != 32)) {
            $data['password'] = $this->User->MakePasswordHash($data['password']);
        }
        $update = time();
        \DB::beginTransaction();
        if (empty($data['uid'])) {
            unset($data['uid']);
            //新增
            //去重
            $data['oauth_type'] = !empty($data['oauth_type']) ? $data['oauth_type'] : 'ios';
            $data['oauth_id'] = !empty($data['oauth_id']) ? $data['oauth_id'] : md5(time());
            $data['uuid'] = md5($data['oauth_type'] . $data['oauth_id']);
            $data['expired_at'] = strtotime($data['expired_at']);
            $id = MemberModel::where('username', '=', $data['username'])
                ->where('username', '<>', '')
                ->where('uid', '<>', $data['uid'])
                ->pluck('uid')[0];

            $id && $this->showJson("用户名已存在！", -1);
            $data['regdate'] = $update;
            $re = $data['aff'] = $uid = MemberModel::insert($data);
            MemberModel::where('uid', '=', $uid)->update($data);
        } else {//编辑
            $data['expired_at'] = strtotime($data['expired_at']);
            $userinfo = MemberModel::where('uid', '=', $data['uid'])->first()->toArray();
            $re = MemberModel::where('uid', '=', $data['uid'])->update($data);
            // $datalog['name'] = "用户修改";
            // $datalog['handle_user'] = $this->Member['uuid'];
            // $datalog['operated_user'] = $data['uuid'];
            // $datalog['created_at'] = time();
            // $datalog['extra'] = $extra;
            // $datalog['after_value'] = base64_encode(json_encode($data));
            // $datalog['before_value'] = base64_encode(json_encode($userinfo));
            // if ($data['balance'] != $userinfo['balance'] || $data['expired_at'] != $userinfo['expired_at']) {
            //     $datalog['description'] = "修改前金币余额:" . $userinfo['balance'];
            //     $datalog['description'] .= ";修改后金币余额:" . $data['balance'];
            //     $datalog['description'] .= "修改前VIP到期:" . date("Y-m-d", $userinfo['expired_at']);
            //     $datalog['description'] .= ";修改后到期:" . date("Y-m-d", $data['expired_at']);
            //     ActionLogModel::insert($datalog);
            // }
        }

        $id = UserProxyModel::where('aff', '=', $data['aff'])->first();

        $re1 = true;
        if (!$id) {
            $userproxy = array(
                'root_aff'    => $data['aff'],
                'aff'         => $data['aff'],
                'proxy_level' => 1,
                'proxy_node'  => $data['aff'],
                'created_at'  => time(),
            );
            $re1 = UserProxyModel::insert($userproxy);
        }
        if ($re !== false && $re1 !== false) {
            if (!empty($userinfo)){
                changeMemberCache(MemberModel::hashByAry($userinfo), $data);
            }
            \DB::commit();
            $this->showJson("修改成功.");
        } else {
            \DB::rollBack();
            $this->showJson("修改失败", 0);
        }
    }


    public function importAction()
    {
        $app_type = intval($this->post['apptype'] ?? 0);
        if (!in_array($app_type, array(1, 2))) {
            $this->Messager("app类型有误", -1);
        }
        if (!$_FILES['csv']) {
            $this->Messager("导入文件不能为空", -1);
        }
        $name = $_FILES['csv']['name'];
        $extension = pathinfo($name, PATHINFO_EXTENSION);
        if ($extension != 'csv') {
            $this->Messager("文件类型有误，请导入csv文件", -1);
        }
        $file_path = 'data/' . $name;
        $status = move_uploaded_file($_FILES['csv']['tmp_name'], $file_path);
        if (!$status) {
            $this->Messager("上传文件失败", -1);
        }
        $handle = fopen($file_path, "r");
        \DB::beginTransaction();
        $uuidGroup = [];
        while (($row = fgetcsv($handle, 1000, ",")) !== false) {
            $username = addslashes($row[0]);
            $uuid = addslashes($row[1]);
            $aff = MemberModel::where('uuid', '=', $uuid)->pluck('aff')[0];
            if (!$aff) {
                $uuidGroup[] = $uuid;
                continue;
            }
            $userData = array(
                'role_id'  => 2,
                'username' => $username,
            );
            MemberModel::where('uuid', '=', $uuid)->update($userData);
            $id = UserProxyModel::where('aff', '=', $aff)->pluck('id')[0];

            if ($id) {
                UserProxyModel::where('id', '=', $id)->delete();
            }
            $proxyData = array(
                'root_aff'   => $aff,
                'aff'        => $aff,
                'proxy_node' => $aff,
                'created_at' => TIMESTAMP
            );
            UserProxyModel::insert($proxyData);
            $ads_id = AdsModel::where('belong_id', '=', $aff)->pluck('id')[0];
            if ($ads_id) {
                continue;
            }
            $url1 = addslashes($row[2]);
            $url2 = addslashes($row[3]);
            $adsData = AdsModel::where([
                'belong_id'   => 0,
                'proxy_level' => 0,
                'status'      => 1,
                'apptype'     => $app_type,
            ])->get(['*'], false)->toArray();
            foreach ($adsData as $value) {
                if ($value['level'] == 3 || $value['level'] == 5) {
                    continue;
                }
                $data1 = array(
                    'title'       => $value['title'],
                    'type'        => $value['type'],
                    'apptype'     => $app_type,
                    'width'       => $value['width'],
                    'height'      => $value['height'],
                    'url'         => $url1,
                    'level'       => $value['level'],
                    'proxy_level' => 1,
                    'belong_id'   => $aff,
                    'img_url'     => $value['img_url'],
                    'created_at'  => TIMESTAMP,
                );
                $data2 = array(
                    'title'       => $value['title'],
                    'type'        => $value['type'],
                    'apptype'     => $app_type,
                    'width'       => $value['width'],
                    'height'      => $value['height'],
                    'url'         => $url2,
                    'level'       => $value['level'],
                    'proxy_level' => 2,
                    'belong_id'   => $aff,
                    'img_url'     => $value['img_url'],
                    'created_at'  => TIMESTAMP,
                );
                AdsModel::insert($data1) && AdsModel::insert($data2);
            }
        }
        if ($uuidGroup) {
            \DB::rollBack();
            fclose($handle);
            unlink($file_path);
            print_r($uuidGroup);
            return;
        }
        if (\DB::getPdo()->GetLastErrorNo()) {
            \DB::commit();
        } else {
            \DB::rollBack();
            $this->Messager("导入失败" . $this->Db->GetLastErrorNo(), -1);
        }
        fclose($handle);
        unlink($file_path) && $this->Messager("导入成功", -1);
    }

    public function writeDataToCsv($filename, $data)
    {
        header("Content-type:text/csv");
        header("Content-Disposition:attachment;filename=" . $filename);
        header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
        header('Expires:0');
        header('Pragma:public');
        echo $data;
    }

    public function exportAction()
    {
        $time = $_POST['time'] ?? '';
        if ($time) {
            $start = strtotime($time);
            $end = $start + 86399;
            $str = '';
            $proxyData = UserProxyModel::where('proxy_level', '=', 1)->pluck('aff');
            $dataArr = [];
            foreach ($proxyData as $row) {
                $sql = "SELECT uuid,username FROM " . TABLE_PREFIX . "members WHERE aff='{$row['aff']}'";
                $info = $this->Db->FetchFirst($sql);
                $username = $info['username'];
                $uuid = $info['uuid'];
                preg_match("/xg(.*)-(.*)/i", $username, $groups);
                if (!$groups) {
                    continue;
                }
                $group = (int)$groups[1];
                $num = (int)$groups[2];
                $dataArr[$group][$num]['uuid'] = $uuid;
                $total = 0;
                for ($i = 1; $i < 4; $i++) {
                    $sql = UserProxyModel::where([
                        'proxy_level' => $i,
                        'root_aff'    => $row['aff'],
                    ])->select('aff', 'proxy_level');
                    $dayNum = $sql->whereBetween('created_at', [$start, $end])->count();
                    $totalNum = $sql->count();
                    //$totalNum = $this->Db->Query($sql)->GetNumRows();
                    $dataArr[$group][$num][$i]['day'] = $dayNum;
                    $dataArr[$group][$num][$i]['total'] = $totalNum;
                    $total += $totalNum;
                }
                if (MEMBER_ROLE == 1) {
                    $totalNum = UserProxyModel::where('proxy_level', '>', 3)->where('root_aff', '=',
                        $row['aff'])->count();
                    $dataArr[$group][$num][4]['total'] = $totalNum;
                    $total += $totalNum;
                }
                $dataArr[$group][$num]['total'] = $total;
            }
            ksort($dataArr);
            if (MEMBER_ROLE == 1) {
                $header = mb_convert_encoding('组别,代号,app账号,2级新增,2级总计,3级新增,3级总计,其余总计,总代理数' . "\n", "GBK", "UTF-8");
            } else {
                $header = mb_convert_encoding('组别,代号,app账号,2级新增,2级总计,3级新增,3级总计,总代理数' . "\n", "GBK", "UTF-8");
            }
            foreach ($dataArr as $k => $v) {
                ksort($v);
                $str .= mb_convert_encoding('第' . $k . '组：' . "\n", "GBK", "UTF-8");
                foreach ($v as $kk => $vv) {
                    if (MEMBER_ROLE == 1) {
                        $arr = "," . $kk . "," . $vv['uuid']
                            . "," . $vv[2]['day']
                            . "," . $vv[2]['total']
                            . "," . $vv[3]['day']
                            . "," . $vv[3]['total']
                            . "," . $vv[4]['total']
                            . "," . $vv['total'] . "\n";
                    } else {
                        $arr = "," . $kk . "," . $vv['uuid']
                            . "," . $vv[2]['day']
                            . "," . $vv[2]['total']
                            . "," . $vv[3]['day']
                            . "," . $vv[3]['total']
                            . "," . $vv['total'] . "\n";
                    }
                    $str .= mb_convert_encoding($arr, "GBK", "UTF-8");
                }
            }
            $data = $header . $str;
            $filename = date('Y-m-d', $start) . '.csv';
            $this->writeDataToCsv($filename, $data);//将数据导出
        } else {
            $this->Messager("请输入时间", -1);
        }
    }

    /**
     * 用户下载列表
     */
    public function listDownloadAction()
    {
        $id = trim($this->Request['uuid'] ?? '');
        $query = DownloadLogModel::orderBy('id', 'desc')->offset($this->pageStart)
            ->limit($this->perPageNum);
        if ($id) {
            $query->where('uuid', '=', $id);
            $query_link = "d.php?mod=user&code=listDownload&uuid=$id";
        } else {
            $query_link = 'd.php?mod=user&code=listDownload';
        }

        $topic = [];
        $data = $query->get(['*'], false)->toArray();
        foreach ($data as $value) {
            $value['created_at'] = date('Y-m-d H:i:s', $value['created_at']);
            $id = $value['m_id'];
            $table = $value['type'] == 'mh' ? 'mh' : 'mv';
            $value['title'] = BaseModel::processArray(\DB::table($table)->where('id', '=', $id)->first(['*'],
                false))['title'];
            $topic[] = $value;
        }
        if (count($topic) < $this->perPageNum) {
            $page_arr['html'] = sitePage($query_link, 1);
        } else {
            $page_arr['html'] = sitePage($query_link);
        }
        $this->getView()
            ->assign('topic', $topic)
            ->assign('page_arr', $page_arr)
            ->display('member/downloadList.phtml');
    }

    public function delDownloadAction()
    {
        $id = (int)$_REQUEST['id'] ?? '';
        DownloadLogModel::where('id', '=', $id)->delete() && $this->Messager("删除成功", -1);
    }


    public function helplistAction()
    {
        $type = $_REQUEST['type'] ?? 0;
        $flag = $_REQUEST['flag'] ?? '';
        $where = " where 1=1 ";
        $query = UserHelpModel::select('*');
        if ($type) {
            $query->where('type', '=', $type);
        }
        if ($flag) {
            $query->where(function ($sql) use ($flag) {
                $sql->where('question', 'like', "%$flag%");
                $sql->where('answer', 'like', "%$flag%");
            });
        }

        $topic = $query->get(['*'], false)->toArray();
        foreach ($topic as $key => $row) {
            $topic[$key]['typename'] = isset($this->user_help_type[$row['type']]) ? $this->user_help_type[$row['type']] : '';
            $topic[$key]['statusname'] = $row['status'] == 1 ? "关闭" : "开启";
        }

        $this->getView()
            ->assign('topic', $topic)
            ->display('member/helplist.phtml');
    }

    public function helpedit()
    {
        $id = $_REQUEST['id'];
        $data = array(
            "question" => "",
            "answer"   => "",
            "status"   => "",
            "type"     => ""
        );
        if ($id) {
            $data = UserHelpModel::where('id', '=', $id)->first(['*'], false)->toArray();
        }

        $this->getView()
            ->assign('data', $data)
            ->display('member/helpedit.phtml');
    }

    public function doHelpedit()
    {
        $data = $this->post['data'];
        $data = JAddSlashes($data);
        $id = $data['id'];
        if (empty($data['id'])) {
            $data['updated_at'] = $data['created_at'] = TIMESTAMP;
            $re = UserHelpModel::insert($data);
        } else {
            unset($data['id']);
            $data['updated_at'] = TIMESTAMP;
            // $re = $this->Db->Update($data, "id={$id}");
            $re = UserHelpModel::where('id', '=', $id)->update($data);
        }
        if ($re !== false) {
            $this->showJson("修改成功", 1);
        } else {
            $this->showJson("修改失败", 0);
        }
    }

    /**
     * 发送消息
     */
    public function useractionlogAction()
    {
        $query_link = "d.php?mod=user&code=userActionLog";
        $uuid = $_REQUEST['uuid'] ?? "";
        $where = '';
        if ($uuid) {
            $where = "where operated_user = '{$uuid}' or handle_user='{$uuid}'";
            $query_link .= "&uuid={$uuid}";
        }

        $query = ActionLogModel::orderBy('created_at', 'desc')
            ->offset($this->pageStart)->limit($this->perPageNum);
        if ($uuid) {
            $query->where(function ($sql) use ($uuid) {
                $sql->where('operated_user', '=', $uuid);
                $sql->orWhere('handle_user', '=', $uuid);
            });
            $query_link .= "&uuid={$uuid}";
        }

        $data = $query->get(['*'], false)->toArray();

        foreach ($data as $key => $row) {
            $data[$key]['created_at'] = date("Y-m-d H:i:s", $row['created_at']);
            $manageInfo = BaseModel::processArray(ManagersModel::where('uuid', '=', $row['handle_user'])->first(['*'],
                false));
            $data[$key]['username'] = $manageInfo['username'] ?? '';
        }
        if (count($data) < $this->perPageNum) {
            $page_arr['html'] = sitePage($query_link, 1);
        } else {
            $page_arr['html'] = sitePage($query_link);
        }

        $this->getView()
            ->assign('data', $data)
            ->assign('page_arr', $page_arr)
            ->display('member/activeUserLog.phtml');
    }

    public function user_edit_vipAction()
    {
        $id = $this->get['id'] ?? 0;
        if ($id) {
            $data = MemberModel::where('uid', '=', $id)->first();
        }
        $vip_type = MemberModel::USER_VIP_TYPE;
        $this->getView()
            ->assign('vip_type', $vip_type)
            ->assign('data', $data)
            ->assign('id', $id)
            ->display('member/edit_vip.phtml');
    }

    public function user_edit_vip_postAction()
    {

        if (!$this->post['id']) {
            return $this->showJson(' 请求失败', 0);
        }

        if ($this->post['expired_at']) {
            $this->post['expired_at'] = strtotime($this->post['expired_at']);
        }

        if (MemberModel::where('uid', $this->post['id'])->update([
            'vip_level'     => $this->post['vip_level'],
            'expired_at'    => $this->post['expired_at'],
            'is_live_super' => $this->post['is_live_super']
        ])) {
            $userinfo = MemberModel::where('uid', $this->post['id'])->first()->toArray();
            changeMemberCache(MemberModel::hashByAry($userinfo), [
                'vip_level'     => trim($this->post['vip_level']),
                'expired_at'    => $this->post['expired_at'],
                'is_live_super' => $this->post['is_live_super']
            ]);
            return $this->showJson('编辑成功', 1);
        };
        return $this->showJson('编辑失败', 0);
    }

    public function setRecommendAction()
    {
        if (!$this->get['id']) {
            return $this->showJson(' 请求失败', 0);
        }
        $id = $this->get['id'] ?? -1;
        if (MemberModel::where('uid', $id)->update(['is_recommend' => $this->get['recommend'] ?? 0])) {
            $member = MemberModel::find($id);
            changeMemberCache($member->getDeviceHash(), ['"is_recommend' => $this->get['recommend']]);
            return $this->showJson('设置成功', 1);
        };

        return $this->showJson('设置失败', 0);
    }

    public function uuidLogAction()
    {
        $query_link = "d.php?mod=user&code=uuidLog";
        $query = UuidLogModel::orderBy('id',
            'desc')->with('withMember')->offset($this->pageStart)->limit($this->perPageNum);

        $get = [
            'uid' => '',
        ];
        if (isset($this->get['uid']) && $this->get['uid']) {
            $query_link .= '&uid=' . $this->get['uid'];
            $get['uid'] .= $this->get['uid'];
            $query->where('uid', $this->get['uid']);
        }
        $query = $query->get();
        if (count($query) < $this->perPageNum) {
            $page_arr['html'] = sitePage($query_link, 1);
        } else {
            $page_arr['html'] = sitePage($query_link);
        }
        $this->getView()
            ->assign('pape_arr', $page_arr)
            ->assign('data', $query)
            ->assign('uid', $get['uid'])
            ->display('member/uuid_log.phtml');
    }
}



