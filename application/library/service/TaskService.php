<?php


namespace service;


use helper\OperateHelper;
use UsersCoinrecordModel;

class TaskService extends \AbstractBaseService
{

    const REDIS_DATA_KEY = 'task_info';


    /**
     * 任务中心-展示配置处理 、自动签到返回
     *
     * @param \MemberModel $member
     * @param $signInfo 如果有的话 签到信息
     * @return array|null
     */
    public static function getTaskInfo(\MemberModel $member, &$signInfo)
    {
        $data = self::getTask();
        if (empty($data)) {
            return null;
        }
        $groupTask = self::getTaskGroup();

        $taskGNormal = $groupTask[\TaskModel::TASK_GROUP_NORMAL_ID] ?? [];
        $taskGActive = $groupTask[\TaskModel::TASK_GROUP_ACTIVE_ID] ?? [];
        $taskGNewer = $groupTask[\TaskModel::TASK_GROUP_NEWER_ID] ?? [];
        $taskGSign = $groupTask[\TaskModel::TASK_GROUP_SGIN_ID] ?? [];

        $return = [];


        //日常任务
        $taskGNormalReturnData = self::taskGNormalReturnData($member, $taskGNormal);
        //print_r($taskGNormalReturnData);die;
        //return $taskGNormalReturnData;
        //活跃度数据
        $taskGActiveReturnData = self::taskGActiveReturnData($member, $taskGActive);
        //print_r($taskGActiveReturnData);
        //return $taskGActiveReturnData;
        //新手数据
        $taskGNewerReturnData = self::taskGNewerReturnData($member, $taskGNewer);
        //print_r($taskGNewerReturnData);die;
        //return $taskGNewerReturnData;
        //签到任务
        $taskGSignReturnData = self::taskGSignReturnData($member, $taskGSign);
        //print_r($taskGSignReturnData);die;
        //return $taskGSignReturnData;
        $return[\TaskModel::TASK_GROUP_NORMAL_KEY] = $taskGNormalReturnData;
        $return[\TaskModel::TASK_GROUP_NEWER_KEY] = $taskGNewerReturnData;
        $return[\TaskModel::TASK_GROUP_ACTIVE_KEY] = $taskGActiveReturnData;
        $return[\TaskModel::TASK_GROUP_SGIN_KEY] = $taskGSignReturnData[0];
        $taskGSignReturnData[1] && $signInfo = $taskGSignReturnData[1];
        return $return;
    }


    /**
     * 任务中心数据
     * @param int $status
     * @return array
     * @author yihe
     * @date 2020/3/18 15:57
     */
    public static function getTask($status = \TaskModel::STATUS_YES)
    {
        $key = self::REDIS_DATA_KEY . $status;
        $data = cached($key)
            ->expired(3600)->serializerJSON()->fetch(
                function () use ($status) {
                    return \TaskModel::query()->where('status', $status)->get()->toArray();
                }
            );
        $data && $data = array_map(function ($item) {
            $item['icon'] = url_live($item['icon']);
            return $item;
        }, $data);
        return $data;
    }

    public static function arrayGroup($arr, $key)
    {
        $result = []; //初始化一个数组
        foreach ($arr as $k => $v) {
            $_t = $v;
            unset($_t['parent_id'], $_t['type'], $_t['add_diamond'], $_t['diamond_scale'], $_t['experience'], $_t['active_cnt'], $_t['gift_id'], $_t['gift_type'],$_t['status']);
            $result[$v[$key]][] = $_t; //把$key对应的值作为键 进行数组重新赋值
        }
        return $result;
    }

    static function getTaskById($task_id)
    {
        $data = self::getTask();
        $row = null;
        if ($data) {
            $task = array_column($data, null, 'id');
            $row = isset($task[$task_id]) ? $task[$task_id] : [];
        }
        return $row;
    }

    static function getTaskByVarName($varname)
    {
        $data = self::getTask();
        $row = null;
        if ($data) {
            $task = array_column($data, null, 'varname');
            $row = isset($task[$varname]) ? $task[$varname] : [];
        }
        return $row;
    }

    static function getTaskGroup()
    {
        $data = self::getTask();
        static $groupData;
        $group = null;
        if (!$groupData) {
            $groupData = self::arrayGroup($data, 'parent_id');
            $group = array_column($groupData[0], null, 'id');
        }
        return $group;
    }

    /**
     * 活跃度任务列表
     *
     * @return array|mixed
     */
    static function getActiveGroupTask()
    {
        $data = self::getTask();
        static $groupData;
        if (!$groupData) {
            $groupData = self::arrayGroup($data, 'parent_id');
        }
        return $groupData[\TaskModel::TASK_GROUP_ACTIVE_ID] ?? [];
    }

    /**
     * 新手任务列表
     *
     * @return array|mixed
     */
    static function getNewerGroupTask()
    {
        $data = self::getTask();
        static $groupData;
        if (!$groupData) {
            $groupData = self::arrayGroup($data, 'parent_id');
        }
        return $groupData[\TaskModel::TASK_GROUP_NEWER_ID] ?? [];
    }

    /**
     * 签到任务列表
     *
     * @return array|mixed
     */
    static function getSignDayGroupTask()
    {
        $data = self::getTask();
        static $groupData;
        if (!$groupData) {
            $groupData = self::arrayGroup($data, 'parent_id');
        }
        return $groupData[\TaskModel::TASK_GROUP_SGIN_ID] ?? [];
    }

    /**
     * 日常任务列表
     *
     * @return array|mixed
     */
    static function getNormalGroupTask()
    {
        $data = self::getTask();
        static $groupData;
        if (!$groupData) {
            $groupData = self::arrayGroup($data, 'parent_id');
        }
        return $groupData[\TaskModel::TASK_GROUP_NORMAL_ID] ?? [];
    }

    static function formatReturnStruct($task)
    {
        return [
            'name' => $task['name'],
            'icon' => $task['icon'],
            'tip'  => $task['tip'],
            'data' => []
        ];
    }

    /**
     * 活跃度返回数据封装
     * @param $member
     * @param $task
     * @return array
     */
    static function taskGActiveReturnData($member, $task)
    {
        $returnData = self::formatReturnStruct($task);
        $activeCnt = self::getActiveCnt($member);//活跃度
        $groupData = self::getActiveGroupTask();
        if ($groupData) {
            foreach ($groupData as &$item) {
                if ($activeCnt < $item['sort']) {//注意sort 值不能乱改 打屁股
                    $item['status'] = 0;
                } elseif (self::hasTodayReceiveTaskReward($member, $item['varname'])) {
                    $item['status'] = 2;
                } else {
                    $item['status'] = 1;
                }
            }
            $returnData['data'] = $groupData;
            $returnData['active'] = $activeCnt;
        }
        return $returnData;
    }

    /**
     * 新手任务返回数据封装
     * @param $member
     * @param $task
     * @return array
     */
    static function taskGNewerReturnData($member, $task)
    {
        $returnData = self::formatReturnStruct($task);
        $groupData = self::getNewerGroupTask();
        if ($groupData) {
            $_data = [];
            foreach ($groupData as &$item) {
                $item['status'] = 0;
                if (\TaskLogModel::firstLog($member->aff, $item['varname'])) {
                    $item['status'] = 2;
                } elseif (self::checkTask($item, $member)) {
                    $item['status'] = 1;
                    $_data[] = $item;
                }else{
                    $_data[]= $item;
                }
            }
            //$returnData['data'] = $groupData;
            $returnData['data'] = $_data;//隐藏新手任务
        }
        return $returnData;
    }

    /**
     * 日常任务返回数据封装
     * @param $member
     * @param $task
     * @return array
     */
    static function taskGNormalReturnData($member, $task)
    {
        $returnData = self::formatReturnStruct($task);
        $groupData = self::getNormalGroupTask();
        if ($groupData) {
            foreach ($groupData as &$item) {
                $item['status'] = 0;
                if (self::hasTodayReceiveTaskReward($member, $item['varname'])) {
                    $item['status'] = 2;
                } elseif (self::checkTask($item, $member)) {
                    $item['status'] = 1;
                }
            }
            $returnData['data'] = $groupData;
        }
        return $returnData;
    }

    /**
     * 签到任务返回数据封装
     * @param $member
     * @param $task
     * @return array  备注  只有签到返回 有区别
     */
    static function taskGSignReturnData($member, $task)
    {
        $returnData = self::formatReturnStruct($task);
        $groupData = self::getSignDayGroupTask();
        $signDayInfo = null;//奖励信息如果有的话
        self::getSignDayInfo($member, $signDayInfo);//签到判断
        $keySet = 'task:sign:' . $member->aff;
        $daySinInfo = redis()->sMembers($keySet);//[day_1_20200409,day_2_20200410]
        //var_dump($daySinInfo);die;
        $_td = [];
        if ($daySinInfo) {
            foreach ($daySinInfo as $day_num_date) {
                $_t = explode('_', $day_num_date);
                $_td[] = $_t[0] . '_' . $_t[1];
            }
            $_td = array_unique($_td);
        }
        if ($groupData) {
            foreach ($groupData as $key=>&$item) {
                $item['status'] = 0;
                if ($_td && in_array($item['varname'],$_td)) {
                    $item['status'] = 2;
                } elseif (\TaskLogModel::lastLog($member->aff, $item['varname'])) {
                    $item['status'] = 2;
                }
            }
            $returnData['data'] = $groupData;
        }
        return [$returnData, $signDayInfo];
    }

    /**
     *  获取或设置用户签到信息
     *
     * @param $taskChildrenInfo
     * @param $member
     * @param $signDayInfo
     * @return array
     */
    public static function getSignDayInfo($member, &$signDayInfo)
    {
        $keySet = 'task:sign:' . $member->aff;
        $hasSign = \TaskLogModel::lastSignLog($member->aff);
        if ($hasSign) {
            //签到直接返回信息
            return;
        }
        //没签到
        $daySinInfo = redis()->sMembers($keySet);//eg [day_1_20200202]
        $signDay = 'day_1';
        $isContinue = true;//是否连续
        if (count($daySinInfo) < 1 || count($daySinInfo) == 7) {
            //签到 day1
            $isContinue = false;
        } else {
            $_tmpdaySinInfo = $daySinInfo;
            sort($_tmpdaySinInfo);//加入之后 自动排序 顺序不对 导致不能连续签到
            $pop_sign = array_pop($_tmpdaySinInfo);
            list($_t, $num, $day) = explode('_', $pop_sign);
            $lastDay = date('Ymd', strtotime('-1 days', TIMESTAMP));
            if ($day == $lastDay) {
                $signDay = 'day_' . ($num + 1);
            } else {
                $isContinue = false;
            }
        }
        $task = self::getTaskByVarName($signDay);
        if (!$task) {
            return;
        }
        self::receiveReward($task, $member, $isContinue, $signDayInfo);// 获得签到奖励
    }


    /**
     * 检测任务是否达标
     * @param $taskArr
     * @param $member
     * @return bool|null
     */
    public static function checkTask($taskArr, $member)
    {
        $uid = $member->uid;
        $uuid = $member->uuid;
        $taskId = $taskArr['id'];
        $todayTime = strtotime(date('Y-m-d',TIMESTAMP));
        $keyNormal = 'task:ck:' . $uid . ':' . $taskId . ':' . date('d', TIMESTAMP);
        $keyNewer = 'task:ck:' . $uid . ':' . $taskId;
        $keyNormalExp = strtotime('+1 days', $todayTime) - TIMESTAMP;
        /** @var \MemberModel $member */
        switch ($taskId) {
            /**
             * 新手任务开始
             */
            case \TaskModel::ID_NEW_FIRST_BUY_MV:
                $model = cached($keyNewer)->serializerPHP()->expired(99999)->fetch(function () use ($uid) {
                    return \MvPayModel::where('uid', $uid)->where('coins', '>', 0)->first();
                });
                if (is_null($model)) {
                    return false;
                }
                break;
            case \TaskModel::ID_NEW_FIRST_GIFT:
                $model = cached($keyNewer)->serializerPHP()->expired(99999)->fetch(function () use ($uid) {
                    return UsersCoinrecordModel::where([
                        ['uid', '=', $uid],
                        ['action', '=', 'sendgift']
                    ])->first();
                });
                if (is_null($model)) {
                    return false;
                }
                break;
            case \TaskModel::ID_NEW_REGISTER:
                if (empty($member->is_reg)) {
                    return false;
                }
                break;
            case \TaskModel::ID_NEW_PERFECT_INFORMATION:
                if (
                    (!$member->sexType) || (!$member->person_signnatrue) || (stripos($member->thumb, 'new') === false)
                ) {
                    return false;
                }
                break;
            case \TaskModel::ID_NEW_FOLLOW_ANCHOR:
                //关注主播
                $uids = (new FollowedService())->getFollowAnchorUid($member);
                if (empty($uids)) {
                    return false;
                }
                break;
            /**
             * 每日任务重复做 开始
             */
            case \TaskModel::ID_EVERY_PAY:
                //每日首次充值
                $model = cached($keyNormal)->serializerPHP()->expired($keyNormalExp)->fetch(function () use (
                    $todayTime,
                    $uuid
                ) {
                    return \OrdersModel::where([
                        ['uuid', '=', $uuid],
                        ['status', '=', \OrdersModel::STATUS_SUCCESS],
                        ['updated_at', '>=', $todayTime]
                    ])->first();
                });
                if (is_null($model)) {
                    return false;
                }
                break;
            case \TaskModel::ID_EVERY_SEND_GIFT_500:
                //每日送礼物价值超过500钻
                //errLog("checktask:{$keyNormal} time:{$todayTime}");
                $has = cached($keyNormal)->serializerPHP()->expired($keyNormalExp)->fetch(function ($cache) use (
                    $uid,
                    $todayTime
                ) {
                    $totalCoin = UsersCoinrecordModel::where([
                        ['uid', '=', $uid],
                        ['action', '=', 'sendgift'],
                        ['addtime', '>=', $todayTime]
                    ])->sum('totalcoin');
                    if ($totalCoin < 500) {
                        $cache->expired(600);
                        return false;
                    }
                    return true;
                });
                if (!$has) {
                    return false;
                }
                break;
            case \TaskModel::ID_EVERY_UPLOAD_VIDEO:
                //每日上传视频并通过审核
                $model = cached($keyNormal)
                    ->serializerPHP()
                    ->expired($keyNormalExp)
                    ->fetch(function () use ($uid, $todayTime) {
                        return \MvModel::where([
                            ['uid', '=', $uid],
                            ['status', '=', \MvModel::STAT_CALLBACK_DONE],
                            ['refresh_at', '>=', $todayTime],
                            ['created_at', '>=', $todayTime],
                        ])->first();
                    });
                if (is_null($model)) {
                    return false;
                }
                break;
            case \TaskModel::ID_EVERY_INVITER_FRIEND:
                //邀请好友注册
                $model = cached($keyNormal)
                    ->serializerPHP()
                    ->expired($keyNormalExp)
                    ->fetch(function () use ($uid, $todayTime) {
                        return \MemberModel::where([
                            ['invited_by', '=', $uid],
                            ['is_reg', '=', 1],
                            ['regdate', '>=', $todayTime]
                        ])->first();
                });
                if (is_null($model)) {
                    return false;
                }
                break;
            case \TaskModel::ID_EVERY_PRAISE_MV_10:
                //累计点赞10个视频
                $has = cached($keyNormal)
                    ->serializerPHP()
                    ->expired($keyNormalExp)
                    ->fetch(function ($cache) use ($uid, $todayTime) {
                        $totalMv = \UserLikeModel::where([
                            ['uid', '=', $uid],
                            ['created_at', '>=', date('Y-m-d 00:00:00', $todayTime)]
                        ])->count('id');
                        if ($totalMv < 10) {
                            $cache->expired(120);
                            return false;
                        }
                        return true;
                    });
                if (!$has) {
                    return false;
                }
                break;
            case \TaskModel::ID_EVERY_SEND_GIFT_ANY:
                //直播间送出任意礼物
                $model = cached($keyNormal)->serializerPHP()->expired($keyNormalExp)->fetch(function ($cache) use (
                    $uid,
                    $todayTime
                ) {
                    return UsersCoinrecordModel::where([
                        ['uid', '=', $uid],
                        ['action', '=', 'sendgift'],
                        ['addtime', '>=', $todayTime]
                    ])->first();
                });
                if (is_null($model)) {
                    return false;
                }
                break;
            default:
                return false;
                break;
        }
        return true;
    }


    /**
     * 判断指定的任务奖励今天有没有被领取
     * @param string $aff 用户的aff
     * @param int $taskId
     * @return bool
     */
    protected static function hasTodayReceiveTaskReward($memeber, $taskvarname)
    {
        $aff = $memeber->aff;
        $today = strtotime(date('Y-m-d', TIMESTAMP));
        $where = [
            ['aff', '=', $aff],
            ['varname', '=', $taskvarname],
            ['created_at', '>=', $today]
        ];
        $key = 'task:log:' . date('d') . ':' . $aff . ':' . $taskvarname;
        $exp = strtotime('+1 days', $today) - TIMESTAMP;
        $flag = cached($key)->serializerPHP()->expired($exp)->fetch(function () use ($where) {
            return \TaskLogModel::where($where)->orderByDesc('id')->exists();
        });
        //errLog("todayTask:aff {$aff} key:{$key} flag:{$flag} \r\n");
        return $flag;
    }

    /**
     *  用户活跃度处理
     * @return string
     */
    protected static function todayActiveKey()
    {
        return 'task:active:' . date('Ymd', TIMESTAMP);
    }

    /**
     * 更新活跃度
     * @param $aff
     * @param $cnt
     * @author xiongba
     */
    protected static function incrByActiveCnt($aff, $cnt)
    {
        $key = self::todayActiveKey();
        if (redis()->hIncrBy($key, $aff, $cnt) <= $cnt) {
            redis()->expire($key, 86500);
        }
    }

    /**
     * 获取活跃度
     * @param $member
     * @return int
     * @author xiongba
     */
    protected static function getActiveCnt($member)
    {
        $aff = $member->aff;
        $key = self::todayActiveKey();
        $cnt = redis()->hGet($key, $aff);
        if (empty($cnt)) {
            return 0;
        }
        return intval($cnt);
    }

    /**
     * 领取任务奖励
     * @param $task
     * @param $member
     * @param $isContinue //签到必须
     * @param $signDayInfo //签到必须
     * @return array
     */
    public static function receiveReward($task, $member, $isContinue = true, &$signDayInfo = null)
    {
        $aff = $member->aff;

        try {
            \DB::beginTransaction();

            $addCoins = $task['add_diamond'];
            $consumption = $task['experience'];
            $w = [];
            $task['add_diamond'] && $w['coins'] = $task['add_diamond'];
            $task['add_diamond'] && $w['coins_total'] = $task['add_diamond'];
            $task['experience'] && $w['consumption'] = $task['experience'];
            if ($w) {
                //更新金币和经验
                $member->incrMustGE_raw(
                    [
                        'coins' => $addCoins,
                        'coins_total' => $addCoins,
                        'consumption' => $consumption,
                    ]
                );
            }
            //更新活跃度
            $task['active_cnt'] && self::incrByActiveCnt($aff, $task['active_cnt']);
            //礼物
            if ($task['gift_id']) {
                if (\TaskModel::GIFT_TYPE_NORMAL == $task['gift_type']) {
                    //普通礼物赠送到活动礼物 1个
                    //\GiftActivityModel::addActivitySendGift($member->uid, [$task['gift_id'] => 1]);
                } elseif (\TaskModel::GIFT_TYPE_CAR == $task['gift_type']) {
                    //赠送的坐骑未装备 7天
                    \UserCarModel::addUserCarData($member->uid, $task['gift_id'],
                        ['endtime' => 7 * 24 * 3600 + TIMESTAMP]);
                }
            }
            //任务日志
            \TaskLogModel::createTaskLog($aff, $task);

            \DB::commit();
            \MemberModel::clearFor($member);
            if ($task['parent_id'] == \TaskModel::TASK_GROUP_SGIN_ID) {
                //签到标记缓存
                $keySet = 'task:sign:' . $member->aff;
                if (!$isContinue) {
                    //不连续 先清空
                    redis()->del($keySet);
                }
                $keySetValue = $task['varname'] . '_' . date('Ymd', TIMESTAMP);
                redis()->sAdd($keySet, $keySetValue);
                $signDayInfo = \TaskModel::find($task['id'])->getReward();//当前任务
            }
            return ['code' => 1, 'msg' => '领取成功'];
        } catch (\Throwable $e) {
            \DB::rollBack();
            return ['code' => 400, 'msg' => $e->getMessage()];
        }
    }

    /**
     * 前端请求任务领取判断验证
     * 普通任务 1 为已完成 2为奖励已领取
     * 每日任务 当日时间+1 为已完成 当日时间+2 为已领取 （逗号分割）
     * @param int $id
     * @param array $member
     * @return array
     * @author yihe
     * @date 2020/3/18 20:58
     */
    public static function receive($id = 0, $member)
    {
        $aff = $member->aff;
        $task = self::getTaskById($id);
        if (empty($task)) {
            return ['code' => 400, 'msg' => '非法任务,请稍后重试'];
        }
        $pid = $task['parent_id'];
        if ($pid == \TaskModel::TASK_GROUP_NORMAL_ID || $pid == \TaskModel::TASK_GROUP_NEWER_ID) {
            //新手任务  每日任务
            if (!self::checkTask($task, $member)) {
                return ['code' => 400, 'msg' => '还未达到任务领取条件'];
            }
        } elseif ($pid == \TaskModel::TASK_GROUP_ACTIVE_ID) {
            //活跃度任务
            $cnt = self::getActiveCnt($member);
            //errLog("active:task:cnt".$cnt);
            if ($cnt < $task['sort']) {
                //如果任务的sort 实现活跃度限制
                return ['code' => 400, 'msg' => '活跃度不够'];
            }
            $isReceive = self::hasTodayReceiveTaskReward($member, $task['varname']);
            //errLog("active:task:is".$isReceive);
            if ($isReceive) {
                return ['code' => 400, 'msg' => '奖励已领取,快去领取其他奖励吧.'];
            }
        } elseif ($pid == \TaskModel::TASK_GROUP_SGIN_ID) {
            //签到任务  不提供前端签到处理 自动签到
            return ['code' => 400, 'msg' => '今天已签过到咯'];
        }
        if ($pid == \TaskModel::TASK_GROUP_NEWER_ID) {
            $taskLog = \TaskLogModel::firstLog($aff, $task['varname']);
            if ($taskLog) {
                return ['code' => 400, 'msg' => '奖励已领取,快去领取其他奖励吧~'];
            }
        } else {
            //每日任务
            $isReceive = self::hasTodayReceiveTaskReward($member, $task['varname']);
            if ($isReceive) {
                return ['code' => 400, 'msg' => '奖励已领取,快去领取其他奖励吧.'];
            }
        }
        $receiveReward = self::receiveReward($task, $member);//单纯处理获得任务奖励
        return $receiveReward;
    }

}