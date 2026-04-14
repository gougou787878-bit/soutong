<?php


use App\type\ImageType;
use App\type\MvResourceType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\JoinClause;

/**
 * class MvModel
 *
 * @property int $id
 * @property int $find_id
 * @property int $find_reply_id
 * @property string $content
 * @property string $uuid
 * @property int $status
 * @property int $created_at
 *
 * @property FindModel $withFind
 * @property FindReplyModel $find_reply
 *
 * @author xiongba
 *
 * @mixin \Eloquent
 */
class FindReplyReportModel extends EloquentModel
{

    protected $table = "find_reply_report";

    protected $primaryKey = 'id';

    protected $fillable = [
        'find_id',
        'find_reply_id',
        'content',
        'uuid',
        'status',
        'created_at',
    ];

    protected $guarded = 'id';


    public $timestamps = false;

    const STATUS_INIT = 0;
    const STATUS_SUCCESS = 1;
    const STATUS_FAIL = 2;
    const STATUS = [
        self::STATUS_INIT    => '待处理',
        self::STATUS_SUCCESS => '成功',
        self::STATUS_FAIL    => '失败',
    ];

    public function withFind()
    {
        return self::hasOne(FindModel::class, 'id', 'find_id');
    }

    public function findReply()
    {
        return self::hasOne(FindReplyModel::class, 'id', 'find_reply_id');
    }

    public static function createBy($findId, $findReplyId, $content, $uuid)
    {
        return self::create([
            'find_id'       => $findId,
            'find_reply_id' => $findReplyId,
            'content'       => $content,
            'uuid'          => $uuid,
            'status'        => self::STATUS_INIT,
            'created_at'    => time(),
        ]);
    }


}
