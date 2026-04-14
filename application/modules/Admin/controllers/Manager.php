<?php

/**
 * 后台权限管理
 * Class ManagerController
 */
class ManagerController  extends AdminController
{
    private $action_name = 'manager';
    private $member_table = 'members';//用户表
//    private $manager_table = TABLE_PREFIX . 'managers';//管理员用户表
//    private $role_table = TABLE_PREFIX . 'role';//用户角色表
//    private $role_action_table = TABLE_PREFIX . 'role_action';//角色权限表

    public function init()
    {
        parent::init();
    }



    /**
     *  管理员列表
     */
    public function indexAction()
    {
        $flag = trim($this->_request['flag'] ?? '');
        if ($flag) {
            $query_link = "d.php?mod=$this->action_name&flag=$flag";
        } else {
            $query_link = "d.php?mod=$this->action_name";
        }
        $table = \ManagersModel::$tableName;
        $joinTable = \RoleModel::$tableName;
        $query = \DB::table("{$table} as a")->select('a.uid', 'a.username', 'a.role_id','a.regdate','b.role_name')
            ->join("{$joinTable} as b", function ($join) {
                $join->on('a.role_id', '=', 'b.role_id');
            })
            ->orderBy('b.role_id', 'ASC')
            ->offset($this->pageStart)
            ->limit($this->perPageNum);
        if ($flag) {
            $query->where('a.username','like',"%{$flag}%");
        }
        $topic = BaseModel::processArray($query->get(['*'],false)->toArray());

        if (count($topic) < $this->perPageNum) {
            $page_arr['html'] = sitePage($query_link, 1);
        } else {
            $page_arr['html'] = sitePage($query_link);
        }
        $this->getView()
            ->assign('topic', $topic)
            ->assign('page_arr', $page_arr)
            ->assign('flag', $flag)
            ->display('manager/list.phtml');
    }

    /**
     * 编辑后台管理员
     */
    public function editManagerAction()
    {
        $uid = $this->Get['uid'] ?? 0;
        //新增或者编辑页面展示
        if (empty($this->post)) {
            $data = array(
                'uid' => '',
                'username' => '',
                'password' => '',
                'role_id' => '',
            );
            if ($uid) {
                $manager = ManagersModel::where('uid','=',$uid)->first(['*'],false)->orderBy('uid','desc')->toArray();
                if (!$manager) {
                    $this->Messager('未找到管理员信息', -1);
                }
                $data['uid'] = $uid;
                $data['username'] = $manager['username'];
                $data['password'] = $manager['password'];
                $data['role_id'] = $manager['role_id'];
            }

            $role_list = RoleModel::select('role_id','role_name')->get(['*'],false)->toArray();

            $this->getView()
                ->assign('data', $data)
                ->assign('role_list', $role_list)
                ->display('manager/edit.phtml');
            return;
        }

        //编辑
        $post_data = $this->post;
       // \DB::beginTransaction();
        \DB::beginTransaction();
        if ($post_data['uid']) {//编辑
            //简单判断是否已经加密
            if (strlen($post_data['password']) != 32) {
                $post_data['password'] = $this->MakePasswordHash($post_data['password']);
            }
            //$sql = "update " . $this->manager_table . " set username = '{$post_data['username']}',password = '{$post_data['password']}', role_id = '{$post_data['role_id']}' where uid = '{$post_data['uid']}'";
            //$re = $this->Db->Query($sql);
            $re = ManagersModel::where('uid','=',$post_data['uid'])
                ->update([
                    'username'=>$post_data['username'],
                    'password'=>$post_data['password'],
                    'role_id'=>$post_data['role_id'],
                    ]);

            if ($re !== false) {
                // \DB::commit();
                \DB::commit();
                $this->Messager('操作成功', 'd.php?mod=manager');
            } else {
                // \DB::rollBack();
                \DB::rollback();
                $this->Messager('操作失败', 'd.php?mod=manager');
            }
        } else {//新增
            $oauth_id = md5(time());
            $post_data['password'] = $this->MakePasswordHash($post_data['password']);
           // $this->Db->SetTable($this->manager_table);
            $data = array(
                'oauth_type' => 'adminManager',
                'oauth_id' => $oauth_id,
                'username' => $post_data['username'],
                'password' => $post_data['password'],
                'role_id' => $post_data['role_id'],
                'uuid' => md5('adminManager' . $oauth_id),
            );
          //  $uid = $this->Db->Insert($data);
            $uid = ManagersModel::insert($data);

            $aff_data = array(
                'aff' => $uid,
            );
            if ($uid) {
                //$info_result = $this->Db->Update($aff_data, "uid='{$uid}'");

                $info_result = ManagersModel::where('uid','=',$uid)->update($aff_data);

            } else {
                $info_result = false;
            }
            if ($info_result !== false) {
                // \DB::commit();
                \DB::commit();
                $this->Messager('操作成功', 'd.php?mod=manager');
            } else {
                // \DB::rollBack();
                \DB::rollback();
                $this->Messager('操作失败', 'd.php?mod=manager');
            }
        }
    }

    /**
     * 重置管理员密码
     */
    public function resetManagerPassAction()
    {
        $uid = $this->Get['uid'] ?? '';
        if ($uid) {
            $password = $this->User->MakePasswordHash(123456);
            //$sql = "update `" . $this->manager_table . "` set `password` = '{$password}' where `uid` = '{$uid}'";
            //$re = $this->Db->Query($sql);
            $re = ManagersModel::where('uid','=',$uid)->update(['password'=>$password]);
            if ($re !== false) {
                $this->Messager('操作成功', 'd.php?mod=manager');
            } else {
                $this->Messager('操作失败', 'd.php?mod=manager');
            }
        }
    }

    /**
     * 删除管理员
     */
    public function delManagerAction()
    {
        $id = (int)$this->Get['id'] ?? '';
        //$sql = "select role_id from `" . $this->manager_table . "` where uid = '{$id}'";
        //$role = $this->Db->FetchFirst($sql);
        $role = ManagersModel::where('uid','=',$id)->select('role_id')
            ->first(['*'],false)->toArray();

        if (!empty($role) && $role['role_id'] == 1) {
            $this->Messager("不能删除超级管理员", -1);
        } else {
            ManagersModel::where('uid','=',$id)->delete() && $this->Messager("删除成功", -1);
        }
    }

    /**
     * 角色列表
     */
    public function roleListAction()
    {
        $flag = trim($this->_request['flag'] ?? '');
        /*if ($flag) {
            $where = " WHERE `role_name` like '%{$flag}%'";
            $query_link = "d.php?mod=$this->action_name&code=roleList&flag=$flag";
        } else {
            $query_link = "d.php?mod=$this->action_name&code=roleList";
        }
        $sort = $this->Get['sort'] ?? '';
        $order = 'role_id ASC';
        if ($sort) {
            switch ($sort) {
                case 'numasc':
                    $order = 'invited_num ASC';
                    break;
                case 'numdesc':
                    $order = 'invited_num DESC';
                    break;
                default:
                    break;
            }
            $query_link = url_set_value($query_link, 'sort', $sort);
        }*/
       // $role_sql = "SELECT * FROM $this->role_table $where ORDER BY $order LIMIT $this->pageStart,$this->perPageNum";
       // $role_query = $this->Db->Query($role_sql);

        $query = RoleModel::offset($this->pageStart)->limit($this->perPageNum);
        if ($flag) {
            $query->where('role_name','like',"%{$flag}%");
            $query_link = "d.php?mod=$this->action_name&code=roleList&flag=$flag";
        } else {
            $query_link = "d.php?mod=$this->action_name&code=roleList";
        }
        $sort = $this->Get['sort'] ?? '';
        if ($sort) {
            switch ($sort) {
                case 'numasc':
                    $query->orderBy('invited_num','asc');
                    break;
                case 'numdesc':
                    $query->orderBy('invited_num','desc');
                    break;
                default:
                    $query->orderBy('role_id','asc');
                    break;
            }
            $query_link = url_set_value($query_link, 'sort', $sort);
        }


        $data = $query->get(['*'],false)->toArray();

        $topic = [];
        foreach ($data as $value) {
            $role_actions = $value['role_action_ids'];
            if ($role_actions == '*') {
                $value['permissions'] = '所有权限';
            } else {
               // $role_actions = explode(',', $role_actions);
               // $role_actions = "'" . implode("','", $role_actions) . "'";
               // $sql = "select name,module,action from `" . $this->role_action_table . "` where `id` in({$role_actions})";
               // $query = $this->Db->Query($sql);
               // $permissions = [];
               // while ($valuec = $query->GetRow()) {
               //     $permissions [] = $valuec['name'] ?: $valuec['module'] . '/' . $valuec['action'];
               // }
                $role_actions = explode(',', $role_actions);
                $data = RoleActionModel::whereIn('id',$role_actions)->select('name','module','action')->get(['*'],false)->toArray();
                $permissions = [];
                foreach ($data as $valuec) {
                    $permissions [] = $valuec['name'] ?: $valuec['module'] . '/' . $valuec['action'];
                }


                $value['permissions'] = implode(' , ', $permissions);
            }
            $topic[] = $value;
        }
        if (count($topic) < $this->perPageNum) {
            $page_arr['html'] = sitePage($query_link, 1);
        } else {
            $page_arr['html'] = sitePage($query_link);
        }

        $this->getView()
            ->assign('topic', $topic)
            ->assign('flag', $flag)
            ->assign('page_arr', $page_arr)
            ->display('manager/role.phtml');
    }

    /**
     * 编辑角色
     */
    public function editRoleAction()
    {
        $role_id = $this->Get['role_id'] ?? 0;
        //新增或者编辑页面展示
        if (empty($this->post)) {
            $data = array(
                'role_id' => '',
                'role_name' => '',
                'role_action_ids' => '',
            );
            $role_action_ids = [];
            if ($role_id) {
                if ($role_id == 1) {
                    $this->Messager('超级管理员权限禁止编辑', -1);
                    return;
                }
                // $sql = "select * from `" . $this->role_table . "` where `role_id` = '{$role_id}'";
                // $role = $this->Db->FetchFirst($sql);
                $role = RoleModel::where('role_id','=',$role_id)
                    ->first(['*'],false)->toArray();

                if (!$role) {
                    $this->Messager('未找到角色信息', -1);
                }
                $role_action_ids = $role['role_action_ids'];
                $data['role_id'] = $role_id;
                $data['role_name'] = $role['role_name'];
                $data['role_action_ids'] = $role['role_action_ids'];
                $role_action_ids = explode(',', $role_action_ids);
            }

           // $actionSql = 'select id,name,pid,module,action from `' . $this->role_action_table . '` ';
           // $action = $this->Db->Query($actionSql);
            $action = RoleActionModel::select('id','name','pid','module','action')->get(['*'],false)->toArray();

            $action_list = [];
            foreach ($action as $val) {
                $list = [];
                $list['id'] = (int)$val['id'];
                $list['pId'] = (int)$val['pid'];
                $list['name'] = $val['name'] . '--' . $val['module'] . '/' . $val['action'];
                if (in_array($val['id'], $role_action_ids)) {
                    $list['checked'] = true;
                }
                $action_list[] = $list;
            }
            $action_list = json_encode($action_list);

            //include(template('admin/manager/roleedit'));
            $this->getView()
                ->assign('data', $data)
                ->assign('action_list', $action_list)
                ->display('manager/roleedit.phtml');
            exit;
        }

        //编辑
        $post_data = $this->post;

        if ($post_data['role_id']) {//编辑
            //$sql = "update `" . $this->role_table . "` set `role_name` = '{$post_data['role_name']}',`role_action_ids` = '{$post_data['role_action_ids']}' where `role_id` = '{$post_data['role_id']}'";
            //$re = $this->Db->Query($sql);
            $re = RoleModel::where('role_id','=',$post_data['role_id'])
                ->update([
                    'role_name' => $post_data['role_name'],
                    'role_action_ids' => $post_data['role_action_ids']
                ]);
        } else {//新增
            //$sql = "insert into `" . $this->role_table . "` values(null,'{$post_data['role_name']}','{$post_data['role_action_ids']}')";
            //$re = $this->Db->Query($sql);
            $re = RoleModel::insert([
                'role_name'=>$post_data['role_name'],
                'role_action_ids'=>$post_data['role_action_ids'],
            ]);
        }
        if ($re !== false) {
            $this->Messager('操作成功', 'd.php?mod=manager&code=roleList');
        } else {
            $this->Messager('操作失败', 'd.php?mod=manager&code=roleList');
        }
    }

    /**
     * 删除角色
     */
    public function delRoleAction()
    {
        $id = (int)$this->Get['id'] ?? '';
        if ($id == 1) {
            $this->Messager('超级管理员角色禁止删除', -1);
            return;
        }
        // $this->Db->SetTable($this->role_table);
        // $this->Db->Delete($id) && $this->Messager("删除成功", -1);
        RoleModel::where('id','=',$id)->delete() && $this->Messager("删除成功", -1);
    }

    /**
     * 权限列表
     */
    public function actionListAction()
    {

        $where = '';
        $flag = trim($this->_request['flag'] ?? '');
        if ($flag) {
            $where = " WHERE (`module` like '%$flag%' or `action` like '%$flag%')";
            $query_link = "d.php?mod=$this->action_name&code=actionList&flag=$flag";
        } else {
            $query_link = "d.php?mod=$this->action_name&code=actionList";
        }
        $order = '`module` ASC,`id` asc';

       // $sql = "SELECT * FROM $this->role_action_table $where ORDER BY $order LIMIT $this->pageStart,$this->perPageNum";
       // $query = $this->Db->Query($sql);

        $query = RoleActionModel::offset($this->pageStart)->limit($this->perPageNum)->orderBy('module','asc')->orderBy('id','asc');
        if ($flag) {
           // $where = " WHERE (`module` like '%$flag%' or `action` like '%$flag%')";
            $query->where(function ($sql) use ($flag){
               $sql->where('module','like',"%$flag%");
               $sql->orWhere('action','like',"%$flag%");
            });
            $query_link = "d.php?mod=$this->action_name&code=actionList&flag=$flag";
        } else {
            $query_link = "d.php?mod=$this->action_name&code=actionList";
        }




      // $topic = [];
      // while ($value = $query->GetRow()) {
      //     $topic[] = $value;
      // }
        $topic = $query->get(['*'],false)->toArray();

        if (count($topic) < $this->perPageNum) {
            $page_arr['html'] = sitePage($query_link, 1);
        } else {
            $page_arr['html'] = sitePage($query_link);
        }

        $this->getView()
            ->assign('page_arr', $page_arr)
            ->assign('topic', $topic)
            ->assign('flag', $flag)
            ->display('manager/roleactions.phtml');
    }

    /**
     * 更新权限列表
     */
    public function updateRoleActionsAction()
    {
        //初始化不列入权限的方法列表
        $pub_acton = get_class_methods(get_parent_class());
        array_push($pub_acton, 'Execute');

        $file_dir = dirname(__FILE__);
        $handler = opendir($file_dir);
        $modules = [];

        while (($filename = readdir($handler)) !== false) {
            if ($filename != "." && $filename != ".." && $filename != 'Controller.php') {
                include_once $filename;
                $module_class = str_replace('.php', 'Controller', $filename);
                $module = str_replace('.php', '', $filename);
                $modules[strtolower($module)]['module'] = strtolower($module);
                $mc = get_class_methods($module_class);
                $actions = [];
                foreach ($mc as $item) {
                    $i = stripos($item,'Action');
                    if($i !=0 ) {
                        $action = substr($item,0,$i);
                        $actions[] = $action;
                    }
                }
                $modules[strtolower($module)]['actions'] = $actions;
            }
        }

        foreach ($modules as $module) {
            $arr = array(
                'id' => null,
                'name' => '',
                'pid' => 0,
                'module' => $module['module'],
                'action' => '*',
                'level' => 0,
            );
            $query = RoleActionModel::where([
                'module'=>$module['module'],
                'action'=>'*',
            ]);
            if (!($query->exists())) {
                RoleActionModel::insert($arr);
            } else {
                $pid = $query->pluck('id');
                foreach ($module['actions'] as $action) {
                    $arr = array(
                        'id' => null,
                        'name' => '',
                        'pid' => $pid,
                        'module' => $module['module'],
                        'action' => $action,
                        'level' => 1,
                    );
                    $query = RoleActionModel::where([
                        'module' => $module['module'],
                        'action' => $action
                    ]);
                    if (!($query->exists())) {
                        RoleActionModel::insert($arr);
                    }
                }
            }
        }
        closedir($handler);
        $this->Messager('更新成功', -1);
    }

    /**
     * 更新权限名
     */
    public function updateActionNameAction()
    {
        $a_id = $this->Get['a_id'] ?? '';
        $a_name = $this->Get['a_name'] ?? '';
        if ($a_id && $a_name) {
            //$sql = "update `" . $this->role_action_table . "` set `name` = '{$a_name}' where `id` = '{$a_id}'";
            //$re = $this->Db->Query($sql);
            $re = RoleActionModel::where('id','=',$a_id)->update([
               'name' => $a_name
            ]);
            if ($re !== false) {
                echo 1;
            } else {
                echo 0;
            }
        } else {
            echo 0;
        }
    }

    /**
     * 删除权限
     */
    public function delActionAction()
    {
        $id = (int)$this->Get['id'] ?? '';
        RoleActionModel::where('id','=',$id) && $this->Messager("删除成功", -1);
    }
}