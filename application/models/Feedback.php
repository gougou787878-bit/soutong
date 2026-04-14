<?php


use Illuminate\Database\Eloquent\Model;

/**
 * class FeedbackModel
 *
 * @property int $id 
 * @property int $uid 用户ID
 * @property string $title 标题
 * @property string $version 系统版本号
 * @property string $model 设备
 * @property string $content 内容
 * @property int $addtime 提交时间
 * @property int $status 状态
 * @property int $uptime 更新时间
 * @property int $platform
 * @property string $thumb 图片
 *
 * @property MemberModel|null $withMember
 * @property FeedbackReplyModel|null $withReply
 *
 * @author xiongba
 * @date 2020-03-30 17:40:52
 *
 * @mixin \Eloquent
 */
class FeedbackModel extends Model
{

    protected $table = "feedback";

    protected $primaryKey = 'id';

    protected $fillable = ['uid', 'title', 'version', 'model', 'content', 'addtime', 'status', 'uptime', 'thumb','platform'];

    protected $guarded = 'id';

    public $timestamps = false;
    const STATUS_ING = 0;
    const STATUS_DONE = 1;
    const STATUS = [
        self::STATUS_ING  => '待处理',
        self::STATUS_DONE => '已处理',
    ];


    const PLAT_FORM_POLO = 0;
    const PLAT_FORM_AV = 1;
    const PLAT = [
        self::PLAT_FORM_POLO=>'xlan',
        self::PLAT_FORM_AV=>'Game',
    ];
    public function withReply()
    {
        return $this->hasOne(FeedbackReplyModel::class,'fid','id');
    }

    public function withMember()
    {
        return $this->hasOne(MemberModel::class, 'uid','uid');
    }



}
