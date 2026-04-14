<?php
/**
 * 我的明细
 */

class DetailController extends IndexController
{

    use \repositories\LiveRepository;
    function indexAction()
    {
        $uid = $_REQUEST["uid"];
        $token = $_REQUEST["token"];
        //$this->verifyToken($uuid,$token)
        $list = $this->pageRec($uid, 0, 50);
        $send_list = $this->pageSend($uid, 0, 50);
        $list_live = $this->pageLive($uid, 0, 50);
        $this->view->assign("uid", $uid);
        $this->view->assign("token", $token);
        $this->view->assign("list", $list);
        $this->view->assign("send_list", $send_list);
        $this->view->assign("list_live", $list_live);

        $this->show("detail");

    }

    public function receive_moreAction()
    {
        $uid = $_REQUEST['uid'];
        $token = $_REQUEST['token'];

        $p = $_REQUEST['page'];
        $pnums = 50;
        $start = ($p - 1) * $pnums;


        $list = $this->pageRec($uid, $start, $pnums);


        $nums = count($list);
        if ($nums < $pnums) {
            $isscroll = 0;
        } else {
            $isscroll = 1;
        }

        $result = array(
            'data' => $list,
            'nums' => $nums,
            'isscroll' => $isscroll,
        );


        return $this->ej($result);
    }

    public function send_moreAction()
    {
        $uid = $_REQUEST['uid'];
        $token = $_REQUEST['token'];


        $p = $_REQUEST['page'];
        $pnums = 50;
        $start = ($p - 1) * $pnums;

        $list = $this->pageSend($uid, $start, $pnums);
        $nums = count($list);
        if ($nums < $pnums) {
            $isscroll = 0;
        } else {
            $isscroll = 1;
        }

        $result = array(
            'data' => $list,
            'nums' => $nums,
            'isscroll' => $isscroll,
        );
        return $this->ej($result);
    }

    public function liverecord_moreAction()
    {
        $uid = $_REQUEST['uid'];
        $token = $_REQUEST['token'];

        $result = array(
            'data' => array(),
            'nums' => 0,
            'isscroll' => 0,
        );

        $p = $_REQUEST['page'];
        $pnums = 50;
        $start = ($p - 1) * $pnums;

        $list = $this->pageLive($uid, $start, $pnums);
        $nums = count($list);
        if ($nums < $pnums) {
            $isscroll = 0;
        } else {
            $isscroll = 1;
        }


        $result = array(
            'data' => $list,
            'nums' => $nums,
            'isscroll' => $isscroll,
        );

        return $this->ej($result);
    }

    private function pageRec($uid, $offset, $limit)
    {
        $list = $this->_coinLog($uid,$offset,$limit,'rec');
        if(empty($list)){
            return [];
        }
        foreach ($list as $k => $v) {
            $giftinfo = $this->_giftRow($v['giftid']);
            if (!$giftinfo) {
                $giftinfo = array(
                    "giftname" => '礼物'
                );
            }
            $list[$k]['giftinfo'] = $giftinfo;
            $userinfo = self::getMember($v['touid']);
            if (!$userinfo) {
                $userinfo = array(
                    "nickname" => '游客-x'
                );
            }
            $list[$k]['userinfo'] = $userinfo;
        }

        return $list;
    }

    private function _coinLog($uid, $offset = 0, $limit = 50, $type = 'rec')
    {
        $key = "coinlog:{$type}:{$uid}:{$limit}-{$offset}";
        return cached($key)->serializerJSON()->fetch(function ($cached) use ($uid, $offset, $limit, $type) {
            $where = [
                'action' => 'sendgift'
            ];
            if ($type == 'rec') {
                $where['touid'] = $uid;
            } else {
                $where['uid'] = $uid;
            }
            return UsersCoinrecordModel::select(["touid", "giftid", "giftcount as giftcounts", "totalcoin as total"])
                ->where($where)
                ->orderBy("addtime", "desc")
                ->offset($offset)
                ->limit($limit)
                ->get()
                ->toArray();
        });
    }

    private function _giftRow($giftid)
    {
        return cached('dgitf:' . $giftid)->expired(3600)->serializerJSON()->fetch(function () use ($giftid) {
            $row = GiftModel::select("giftname")
                ->where("id", $giftid)
                ->first();
            if (is_null($row)) {
                return [];
            }
            return $row->toArray();
        });
    }


    private function pageSend($uid, $offset, $limit)
    {
        $send_list = $this->_coinLog($uid,$offset,$limit,'send');
        if(empty($send_list)){
            return [];
        }
        foreach ($send_list as $k => $v) {
            $giftinfo = $this->_giftRow($v['giftid']);
            if (!$giftinfo) {
                $giftinfo = array(
                    "giftname" => '礼物'
                );
            }
            $send_list[$k]['giftinfo'] = $giftinfo;
            $userinfo = self::getMember($v['touid']);
            if (!$userinfo) {
                $userinfo = array(
                    "nickname" => '游客'
                );
            }
            $send_list[$k]['userinfo'] = $userinfo;
        }
        return $send_list;
    }

    private function pageLive($uid, $offset, $limit)
    {
        // 播放历史
        $list_live = $this->getLiveLog($uid, $offset, $limit);
        foreach ($list_live as $k => $v) {
            $list_live[$k]['endtime']   = $v['updated_at'];
            $list_live[$k]['starttime'] = $v['created_at'];
            $cha = $list_live[$k]['endtime'] - $list_live[$k]['starttime'];
            $list_live[$k]['length'] = getSeconds($cha, 1);
        }
        return $list_live;
    }

}
