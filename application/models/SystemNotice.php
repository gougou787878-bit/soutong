<?php


use Illuminate\Database\Eloquent\Model;

/**
 * class SystemNoticeModel
 *
 * @property int $id 
 * @property string $type 类型
 * @property int $status 0 默认 1 标记
 * @property string $uuid 
 * @property string $description 信息
 * @property string $date_at 
 *
 * @author xiongba
 * @date 2021-07-19 22:12:50
 *
 * @mixin \Eloquent
 */
class SystemNoticeModel extends Model
{

    protected $table = "system_notice";

    protected $primaryKey = 'id';

    protected $fillable = ['type', 'status', 'uuid', 'description', 'date_at'];

    protected $guarded = 'id';


    public $timestamps = false;

    const TYPE_VIDEO = 'video';
    const TYPE_ORDER = 'order';
    const TYPE_DRAW = 'withdraw';
    const TYPE_GAME = 'game';
    const TYPE_COMMENT= 'comment';

    const TYPE = [
        self::TYPE_VIDEO => '视频',
        self::TYPE_ORDER => '订单',
        self::TYPE_DRAW  => '提现',
        self::TYPE_GAME  => '游戏单',
        self::TYPE_COMMENT=> '评论',
    ];

    const STAT_OK = 1;
    const STAT_NO = 0;//默认
    const STAT = [
        self::STAT_NO => '默认',
        self::STAT_OK => '标记',
    ];

    const TYPE_POST = 1;
    const TYPE_TIPS = [
        self::TYPE_POST => '帖子',
    ];


    const AUDIT_POST_PASS_MSG = '您发布的帖子《%s》已通过审核';
    const POST_REWARD_MSG = '用户【%s】解锁了你的帖子 《%s》: 钻石:%s';
    const AUDIT_COMMENT_PASS_MSG = '您发布的评论【%s】已通过审核';
    const COMMENT_POST_MSG = '用户【%s】评论了你的帖子 《%s》: %s';
    const AUDIT_COMMENT_UNPASS_MSG = '您发布的评论【%s】未通过审核,原因:%s';
    const COMMENT_COMMENT_MSG = '用户【%s】评论了你的评论 《%s》: %s';

    /**
     * @param $type
     * @param $uuid
     * @param $desc
     * @return bool
     */
    static function addNotice($type, $uuid, $desc): bool
    {
        return self::insert([
            'type'        => $type,
            'uuid'        => $uuid,
            'description' => $desc,
            'status'      => self::STAT_NO,
            'date_at'     => date('Y-m-d H:i:s'),
        ]);
    }





}
