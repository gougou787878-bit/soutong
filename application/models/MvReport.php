<?php


/**
 * class MvModel
 *
 * @property int $id
 * @property int $mv_id
 * @property int $mv_uid
 * @property string $content
 * @property string $uuid
 * @property int $status
 * @property int $created_at
 *
 * @property MvModel $mv
 *
 * @author xiongba
 *
 * @mixin \Eloquent
 */
class MvReportModel extends EloquentModel
{

    protected $table = "mv_report";

    protected $primaryKey = 'id';

    protected $fillable = [
        'mv_id',
        'mv_uid',
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

    public function mv()
    {
        return self::hasOne(MvModel::class, 'id', 'mv_id');
    }

    public static function createBy($mvId,$mvUid, $content, $uuid)
    {
        return self::create([
            'mv_id'      => $mvId,
            'mv_uid'     => intval($mvUid),
            'content'    => $content,
            'uuid'       => $uuid,
            'status'     => self::STATUS_INIT,
            'created_at' => time(),
        ]);
    }


}
