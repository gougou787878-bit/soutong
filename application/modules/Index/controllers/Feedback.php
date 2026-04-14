<?php
/**
 * 用户反馈
 */

class FeedbackController extends IndexController
{
    private $uploadPath = './upload/feedback';
    private $urlPrefix = '/upload/feedback/';

    function indexAction()
    {
        $uid = $_REQUEST["uid"];
        $token = $_REQUEST["token"];
        $model = $_REQUEST["model"]??'';
        $version = $_REQUEST["version"]??'';
        $this->view->assign("uid", $uid);
        $this->view->assign("token", $token);
        $this->view->assign("version", $version);
        $this->view->assign("model", $model);
        //http://new_img.ycomesc.com/img.live/
        $base = config('img.img_live_url');
        $base = str_ireplace('/img.live/', '', $base);
        $tipK91live = cached('feedback:member:' . $uid)
            ->expired(86400)
            ->serializerJSON()
            ->fetch(function () use ($uid) {
                $member = MemberModel::find($uid);
                if ($member && $member->build_id == 'k91live') {
                    return 'k91live';
                }
                return '';
            });
        if ($tipK91live == 'k91live'){
            $this->getView()->assign('show_tip' , true);
        }else{
            $this->getView()->assign('show_tip' , false);
        }

        $this->view->assign("picDomain",  $base);
        //$this->view->assign("picDomain",  \Yaf\Registry::get('config')->img->img_live_url);

        $this->view->assign("picUpload",  \Yaf\Registry::get('config')->upload->img_upload);

        $this->show("feedback");
    }

    function feedbackSaveAction()
    {
        $uid = $_REQUEST["uid"];
        $token = $_REQUEST["token"];

        $time = \tools\RedisService::redis()->ttl('feedback_' . $uid);
        if ($time > 0) {
            return $this->ej(["status" => 400, 'errormsg' => '發送太頻繁，你还有' . $time . '秒可以再次發送反饋']);
        } else {
            $content = $_REQUEST['content'] ?? '';
            if(mb_strlen($content)<5){
                return $this->ej(["status" => 400, 'errormsg' => '详细描述下问题，方便快速解决哦']);
            }
            $data['uid'] = intval($_REQUEST['uid']);
            $data['version'] = checkNull($_REQUEST['version']);
            $data['model'] = checkNull($_REQUEST['model']);
            $data['content'] = $content;
            $data['thumb'] = checkNull($_REQUEST['thumb']);
            $data['addtime'] = time();
            $result = FeedbackModel::insert($data);
            if ($result) {
                \tools\RedisService::redis()->set('feedback_' . $uid,'1',120);
                return $this->ej(["status" => 0, 'msg' => '']);
            } else {
                return $this->ej(["status" => 400, 'errormsg' => '提交失败']);
            }

        }


    }

    /**
     * 反馈列表
     */
    public function feedbackListAction()
    {
        $uid = $_REQUEST["uid"];
        $token = $_REQUEST["token"];

        $list = FeedbackModel::leftJoin('feedback_reply', 'feedback.id', '=', 'fid')
          ->where(function ($query) use ($uid) {
              $query->where('feedback.uid', $uid);
          })
            ->select('feedback.*', 'feedback_reply.content as reply_content', 'created_at')
            ->get()
            ->toArray();
        $this->view->assign('list', $list);
        $this->show('feedbacklist');
    }

    /**
     * 图片上传
     */
    public function uploadAction()
    {
        $img = $_FILES['image'];
        $result = $this->upload($img,'live','image');
        if ($result['success']??false) {
            return $this->ej([
                "ret" => 200, 'data' => ['url' =>$result['cover'] ], 'msg' => ''
            ]);
        } else {
            return $this->ej([
                "ret" => 0, 'file' => '', 'msg' => '上传失败'
            ]);
        }
    }
}