<?php


use Illuminate\Database\Eloquent\Model;

/**
 * class UsersVoterecordModel
 *
 * @property int $id
 * @property string $type 收支类型
 * @property string $action 收支行为
 * @property int $uid 用户ID
 * @property string $votes 收益映票
 * @property int $addtime 添加时间
 *
 * @author xiongba
 * @date 2020-03-02 12:47:46
 *
 * @mixin \Eloquent
 */
class UserVoterecordModel extends Model
{

    protected $table = "users_voterecord";

    protected $primaryKey = 'id';

    protected $fillable = ['type', 'action', 'uid', 'votes', 'addtime'];

    protected $guarded = 'id';

    public $timestamps = false;


    public static function exchangeCoinsFreeze($uid, $votes)
    {
        $data = [
            'type'    => 'freeze', // 提现冻结
            'action'  => 'ex-coins',
            'uid'     => $uid,
            'votes'   => -$votes,
            'addtime' => time()
        ];
        return self::create($data);
    }

    public static function exchangeVotesFreeze($uid, $votes)
    {
        $data = [
            'type'    => 'freeze', // 提现冻结
            'action'  => 'ex-votes',
            'uid'     => $uid,
            'votes'   => -$votes,
            'addtime' => time()
        ];
        return self::create($data);
    }

    /**
     * 添加收益
     * @param int $liveUid 主播id
     * @param string $action 动作
     * @param int $total 金额
     * @return \Illuminate\Database\Eloquent\Model|UserVoterecordModel
     * @author xiongba
     * @date 2020-03-02 11:29:41
     */
    public static function addIncome($liveUid, $action, $total)
    {
        $insert_votes = [
            'type'    => 'income',
            'action'  => $action,
            'uid'     => $liveUid,
            'votes'   => $total,
            'addtime' => time(),
        ];
        return UserVoterecordModel::create($insert_votes);
    }

    /**
     * 添加收益
     * @param $uid
     * @param string $action 动作
     * @param int $total 金额
     * @return \Illuminate\Database\Eloquent\Model|UserVoterecordModel
     * @author xiongba
     * @date 2020-03-02 11:29:41
     */
    public static function addExpend($uid, $action, $total)
    {
        $insert_votes = [
            'type'    => 'expend',
            'action'  => $action,
            'uid'     => $uid,
            'votes'   => $total,
            'addtime' => time(),
        ];
        return UserVoterecordModel::create($insert_votes);
    }

}
