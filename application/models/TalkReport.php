<?php


use Illuminate\Database\Eloquent\Model;

/**
 * class TalkReportModel
 *
 * @property int $id
 * @property string $uuid 用户uuid
 * @property string $to_uuid 投诉人的uuid
 * @property string $value 投诉类型
 * @property int $status
 * @property string $msg_list 投诉的消息
 * @property int $created_at 投诉时间
 * @property int $updated_at 修改时间
 *
 * @author xiongba
 * @date 2021-08-04 15:26:54
 *
 * @mixin \Eloquent
 */
class TalkReportModel extends Model
{

    protected $table = "talk_report";

    protected $primaryKey = 'id';

    protected $fillable = ['uuid', 'to_uuid', 'value', 'status', 'msg_list', 'created_at', 'updated_at'];

    protected $guarded = 'id';

    public $timestamps = false;

    const STATUS_NO = 0;
    const STATUS_YES = 1;
    const STATUS = [
        self::STATUS_NO  => '为处理',
        self::STATUS_YES => '已处理',
    ];


    public function frommember()
    {
        return $this->hasOne(MemberModel::class, 'uuid', 'uuid');
    }

    public function tomember()
    {
        return $this->hasOne(MemberModel::class, 'uuid', 'to_uuid');
    }


}
