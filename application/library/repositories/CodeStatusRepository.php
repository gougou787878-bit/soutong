<?php
/**
 * 状态码
 */

namespace repositories;


trait CodeStatusRepository
{
    // 常用码表
    private static $codeStatus = [
        'LOGIN_FIRST'          => ['code' => 0, 'msg' => '请先登录', 'no' => 0],
        'NORMAL_SUCCESS'       => ['code' => 1, 'msg' => '成功', 'no' => 1],
        'NORMAL_ERROR'         => ['code' => 0, 'msg' => '失败', 'no' => 0],
        'OPR_ERROR'            => ['code' => 0, 'msg' => '操作失败，请重试', 'no' => 0],
        'PASSWORD_NO_EMPTY'    => ['code' => 0, 'msg' => '密码不能为空', 'no' => 0],
        'DENY_ONE_WEEK'        => ['code' => 0, 'msg' => '你已經禁止開播一週', 'no' => 0],
        'LIVE_LIVE_NO_REQUEST' => ['code' => 0, 'msg' => '等级不够，不能直播', 'no' => 0],
        'PRICE_NO_LESS_ZERO'   => ['code' => 0, 'msg' => '价格不能小于等于0', 'no' => 0],
        'PRICE_NO_MORE_TEN'    => ['code' => 0, 'msg' => '价格不能大于20', 'no' => 0],
        'CLOSE_ERROR_AGAIN'    => ['code' => 1011, 'msg' => '开播失败，请重试', 'no' => 0],
        'ACTOR_INFO_ERROR'     => ['code' => 0, 'msg' => '主播信息不存在', 'no' => 0],
        'DENY_SUCCESS'         => ['code' => 1, 'msg' => '封禁成功', 'no' => 1],
        'DENY_NO_HONOUR'       => ['code' => 0, 'msg' => '对方是尊贵守护，不能被封禁', 'no' => 0],
        'DENY_NO_ADMIN'        => ['code' => 0, 'msg' => '对方是管理员，不能被封禁', 'no' => 0],
        'DENY_NO_SUPER'        => ['code' => 0, 'msg' => '对方是超管，不能被封禁', 'no' => 0],
        'DENY_NO_POWER'        => ['code' => 0, 'msg' => '无权操作', 'no' => 0],

        'DENY_NO_ENTER' => ['code' => 0, 'msg' => '在该房间你被封禁', 'no' => 0],

        'KICK_SUCCESS'   => ['code' => 1, 'msg' => '踢出成功', 'no' => 1],
        'KICK_NO_POWER'  => ['code' => 0, 'msg' => '无权操作', 'no' => 0],
        'KICK_NO_SUPER'  => ['code' => 0, 'msg' => '对方是超管，不能被踢出', 'no' => 0],
        'KICK_NO_ADMIN'  => ['code' => 0, 'msg' => '对方是管理员，不能被踢出', 'no' => 0],
        'KICK_NO_HONOUR' => ['code' => 0, 'msg' => '对方是尊贵守护，不能被踢出', 'no' => 0],

        'SHUT_UP_SUCCESS'   => ['code' => 1, 'msg' => '禁言成功', 'no' => 1],
        'SHUT_UP_NO_POWER'  => ['code' => 0, 'msg' => '无权操作', 'no' => 0],
        'SHUT_UP_NO_SUPER'  => ['code' => 0, 'msg' => '对方是超管，不能被禁言', 'no' => 0],
        'SHUT_UP_NO_ADMIN'  => ['code' => 0, 'msg' => '对方是管理员，不能被禁言', 'no' => 0],
        'SHUT_UP_NO_ACTOR'  => ['code' => 0, 'msg' => '对方是主播，不能被禁言', 'no' => 0],
        'SHUT_UP_NO_HONOUR' => ['code' => 0, 'msg' => '对方是尊贵守护，不能被禁言', 'no' => 0],
        'SHUT_UP_ALREADY'   => ['code' => 0, 'msg' => '对方已被禁言', 'no' => 0],
        'SHUT_UP_FOREVER'   => ['code' => 1, 'msg' => '对方已被永久禁言', 'no' => 1],
        'SHUT_UP_AD'        => ['code' => 1, 'msg' => '禁言广告', 'no' => 1],


        'NO_ADMIN_POWER'      => ['code' => 0, 'msg' => '你不是超管，无权操作', 'no' => 0],
        'LIVE_CLOSE_SUCCESS'  => ['code' => 1, 'msg' => '关闭成功', 'no' => 1],
        'NO_BALANCE'          => ['code' => 1008, 'msg' => '余额不足', 'no' => 1008],
        'FEE_ERROR'           => ['code' => 0, 'msg' => '房间费用有误', 'no' => 1007],
        'ROOM_NO_FEE'         => ['code' => 0, 'msg' => '该房间非扣费房间', 'no' => 1006],
        'LIVE_OVER'           => ['code' => 0, 'msg' => '直播已结束', 'no' => 1005],
        'ADMIN_NO_ENTER_1V1'  => ['code' => 0, 'msg' => '超管不能进入1v1房间', 'no' => 1007],
        'NO_ENTER_SELF'       => ['code' => 0, 'msg' => '不能进入自己直播间', 'no' => 0],
        'KICK_YOU'            => ['code' => 0, 'msg' => '您已被踢出或封禁该房间，剩余%s秒', 'no' => 0],
        'ROOM_INPUT_PASSWORD' => ['code' => 0, 'msg' => '主播修改房间为密码房间，请输入密码', 'no' => 0],
        'ROOM_FEE_NEED'       => ['code' => 0, 'msg' => '主播修改房间为收费房间，需支付%s金币', 'no' => 0],
        'ROOM_FEE_TIME'       => ['code' => 0, 'msg' => '主播修改房间为计时房间，每分钟需支付%s金币', 'no' => 0],
        'GIFT_NO_INFO'        => ['code' => 1002, 'msg' => '礼物信息不存在', 'no' => 1002],
        'GIFT_NO_FEE'         => ['code' => 1001, 'msg' => '余额不足', 'no' => 1001],
        'REPORT_SUCCESS'      => ['code' => 1, 'msg' => '举报成功', 'no' => 1],
        'REPORT_ERROR'        => ['code' => 0, 'msg' => '举报失败请重试', 'no' => 0],
        'REPORT_NO_CONTENT'   => ['code' => 0, 'msg' => '举报内容不能为空', 'no' => 0],
        'CLOSE_ROOM_SUCCESS'  => ['code' => 1, 'msg' => '关播成功', 'no' => 1],
        'CLOSE_ROOM_ERROR'    => ['code' => 0, 'msg' => '关播失败', 'no' => 0],
        'HONOUR_ONLY'         => ['code' => 0, 'msg' => '该礼物是守护专属礼物奥', 'no' => 0],

        'NO_ACTOR_IS_ROOM' => ['code' => 0, 'msg' => '你不是该房间主播，无权操作', 'no' => 0],
        'ADMIN_LIMIT'      => ['code' => 0, 'msg' => '最多设置50个管理员', 'no' => 0],
    ];
    // 通用语言提示
    private static $langInfo = [
        'DEFAULT_CITY' => '好像在火星',
    ];

}