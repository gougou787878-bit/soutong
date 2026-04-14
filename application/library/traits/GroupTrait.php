<?php

namespace traits;

trait GroupTrait
{


    //群类型   普通群 实习精主群 正式群 和 滋生网黄
    static $GROUP_TYPE_NOR = 'normal';
    static $GROUP_TYPE_PRA = 'practice';
    static $GROUP_TYPE_FOR = 'formal';
    static $GROUP_TYPE_SEN = 'senior';

    static function getGroupTypes($key = null)
    {
        $data = [
            self::$GROUP_TYPE_NOR => [
                'text' => '普通群',
                'number' => 100,
            ],
            self::$GROUP_TYPE_PRA => [
                'text' => '实习群',
                'number' => 500,
            ],
            self::$GROUP_TYPE_FOR => [
                'text' => '正式群',
                'number' => 1000,
            ],
            self::$GROUP_TYPE_SEN => [
                'text' => '网黄群',
                'number' => 2000,
            ],
        ];
        if ($key === null) {
            return $data;
        }
        //get info of key
        return isset($data[$key]) ? $data[$key] : '';
    }

    //群状态，0 默认建群待审核，1-正常,2-群禁言 3.解散
    static $GROUP_STAT_TO_CHECK = 0;
    static $GROUP_STAT_NORMAL = 1;
    static $GROUP_STAT_BAN = 2;
    static $GROUP_STAT_DISBAND = 3;

    static function getGroupStatus($key = null)
    {
        $data = [
            self::$GROUP_STAT_TO_CHECK => '审核',
            self::$GROUP_STAT_NORMAL => '正常',
            self::$GROUP_STAT_BAN => '禁言',
            self::$GROUP_STAT_DISBAND => '解散',
        ];
        if ($key === null) {
            return $data;
        }
        //check has key
        return array_key_exists($key, $data);
    }

    //群成员角色 normal','admin','founder
    static $GROUP_ROLE_NOR = 'normal';
    static $GROUP_ROLE_ADM = 'admin';
    static $GROUP_ROLE_FOU = 'founder';

    static function getGroupRoles($key = null)
    {
        $data = [
            self::$GROUP_ROLE_NOR => '群众',
            self::$GROUP_ROLE_ADM => '管理员',
            self::$GROUP_ROLE_FOU => '创建者',
        ];
        if ($key === null) {
            return $data;
        }
        //check has key
        return array_key_exists($key, $data);
    }

    //群成员状态 0申请中 1正常 2拉黑 3已退出 4未通过
    static $GROUP_USER_TO_CHECK = 0;
    static $GROUP_USER_NORMAL = 1;
    static $GROUP_USER_BLACK = 2;
    static $GROUP_USER_QUIT = 3;
    static $GROUP_USER_DID_NOT_PASS = 4;



}