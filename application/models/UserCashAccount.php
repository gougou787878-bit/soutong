<?php


/**
 * class UsersCashAccountModel
 *
 * @property string $account 账号
 * @property string $account_bank 银行名称
 * @property int $addtime 添加时间
 * @property int $id
 * @property string $name 姓名
 * @property int $type 类型，1表示支付宝，2表示微信，3表示银行卡
 * @property int $uid 用户ID
 *
 * @author xiongba
 * @date 2020-03-05 20:05:22
 *
 * @mixin \Eloquent
 */
class UserCashAccountModel extends EloquentModel
{
    protected $table = 'users_cash_account';

    protected $fillable = [
        'uid',
        'type',
        'account_bank',
        'name',
        'account',
        'addtime'
    ];
}