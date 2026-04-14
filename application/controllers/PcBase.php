<?php

use service\FileService;

class PcBaseController extends \Yaf\Controller_Abstract
{
    public $member = NULL;
    public $data;
    public $position;

    public function init()
    {
        $_POST = !is_array($_POST) ? [] : $_POST;
        $this->data = &$_POST;
        //$this->verifyBanIp();
        $oauthId = $this->data['oauth_id'] ?? '';
        $oauthType = $this->data['oauth_type'] ?? '';
        $token = $this->data['token'] ?? '';
        // var_dump($this->data);die;
        $this->position = IP_POSITION;
        // var_dump($this->initMember($oauthType, $oauthId, $token));die;
        $this->member = $token ? $this->initMember($oauthType, $oauthId, $token) : NULL;
       
        // $this->checkAuth();
    }


    public function initMember($oauthType, $oauthId, $token)
    {
        // try {
            test_assert($oauthType == 'pc', '参数不合法');
            test_assert(strlen($oauthId) == 32, '参数不合法');
            
            $crypt = new LibCryptPwa();
            $token_info = $crypt->decryptToken($token);
            // dd($token_info);
            test_assert($token_info, 'token invalid');
            $aff = $token_info[0];
            $user_key = MemberModel::USER_REIDS_PREFIX . $aff;
            $data = redis()->getWithSerialize($user_key);
            // dd($data);
            if (!$data) {
                /** @var MemberModel $data */
                $data = MemberModel::onWriteConnection()->where('aff', $aff)->first();
                test_assert($data, '参数不合法');
                $rs = redis()->setWithSerialize($user_key, $data);
                test_assert($rs, '系统异常');
            }
            return $data;
        // } catch (Throwable $e) {
        //     throw new \Exception($e->getMessage(), 422);
        // }
    }

    public function showJson(
        $data,
        int $status = 1,
        string $msg = ''
    ): bool
    {
        @header('Content-Type: application/json');
        @header("redis-status: hit");

        $returnData = [
            'data'   => $data,
            'status' => $status,
            'msg'    => $msg,
        ];

        if ($status == 1) {
            $data = json_encode($returnData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $returnData = replace_all($data);
            if (in_array($_SERVER['REQUEST_URI'], FileService::NO_AUTH_RULES) || !$this->member) {
                FileService::genFile($returnData);
            }
            $returnData = json_decode($returnData, true);
        }

        $crypt = new LibCryptPwa();
        $returnData = $crypt->replyData($returnData);
        $this->getResponse()->setBody($returnData);
        return true;
    }

    public function successMsg($msg): bool
    {
        return $this->showJson('', 1, $msg);
    }

    public function failMsg($msg, $code = 0, $data = []): bool
    {
        return $this->showJson($data, $code, $msg);
    }

    public function errorJson($msg, $code = 0, $data = []): bool
    {
        return $this->failMsg($msg, $code, $data);
    }

    public function listJson($list, $column = 'id', $extra = []): bool
    {
        if (is_array($column)) {
            // 当column参数是数组时候，交换column和extra的值，
            if (is_string($extra)) {
                list($extra, $column) = [$column, $extra];
            } else {
                list($extra, $column) = [$column, 'id'];
            }
        }
        $list = collect($list);
        $last_end = $list->last();
        if (is_array($last_end) || $last_end instanceof ArrayAccess) {
            $last_idx = $last_end[$column] ?? '0';
        } else {
            $last_idx = $last_end;
        }

        if (empty($last_idx)) {
            $last_idx = (string)$last_idx;
        }

        $ret = array_merge([
            'list'    => $list,
            'last_ix' => (string)$last_idx
        ], $extra);

        return $this->showJson($ret);
    }

    private function verifyBanIp()
    {
        $controller = $this->getRequest()->getControllerName();
        $action = $this->getRequest()->getActionName();
        $uri = sprintf("%s/%s", $controller, $action);
        if (redis()->sIsMember(BAN_IPS_KEY, USER_IP)) {
            $msg = '已禁-请求:' . $uri . PHP_EOL;
            $msg .= '已禁-IP:' . USER_IP . PHP_EOL;
            $msg .= '已禁-参数:' . PHP_EOL . var_export($_POST, true) . PHP_EOL;
            trigger_log($msg);
            header("Status: 503 Service Unavailable");
            exit();
        }
    }

    protected function verifyMemberSayRole()
    {
        if ($this->member->isBan()) {
            throw new RuntimeException('您已被禁言');
        }
    }

    protected function verifyFrequency(int $ttl = 1, int $num = 1, string $prefix = '', string $msg = '您操作太快了，休息一下再来')
    {
        $debug = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS);
        if (!isset($debug[1])) {
            return;
        }
        if ($ttl == 1) {
            $ttl = 10;
            $num = 10;
        }
        $hash = md5($debug[0]['file'] . $debug[0]['line']);
        $key = 'fr:' . $this->member->aff . ':' . ($prefix ? $prefix . ':' : '') . $hash;
        $tmp = redis()->incrBy($key, 1);
        if ($tmp > $num) {
            throw new RuntimeException($msg);
        }
        if ($tmp <= 1) {
            redis()->expire($key, $ttl);
        }
    }

    private function checkAuth()
    {
        if ($this->member && $this->member->role_id == MemberModel::ROLE_BAN) {
            header("Status: 503 Service Unavailable");
            exit();
        }
    }
}
