<?php


use Illuminate\Database\Eloquent\Model;

/**
 * class WeekModel
 *
 * @property int $id 
 * @property string $title 名称
 * @property string $mv_title 视频标题
 * @property int $status 状态 
 * @property int $created_at 
 * @property int $sort_number
 *
 * @author xiongba
 * @date 2020-11-10 18:32:46
 *
 * @mixin \Eloquent
 */
class WeekModel extends Model
{

    protected $table = "week";

    protected $primaryKey = 'id';

    protected $fillable = ['title', 'mv_title', 'status', 'created_at','sort_number'];

    protected $guarded = 'id';

    public $timestamps = false;

    const STAT_ENABLE = 1;
    const STAT_DISABLE = 0;

    const STAT = [
        self::STAT_ENABLE  => '启用',
        self::STAT_DISABLE => '禁用',
    ];

    protected $appends = ['series_txt', 'update_txt' , 'created_at_txt'];

    public function getSeriesTxtAttribute(){

        return "第{$this->sort_number}期";

    }
    public function getUpdateTxtAttribute(){
        $date = date('m-d',$this->created_at);
        return "{$date}更新";
    }
    public function getCreatedAtTxtAttribute(){
        $date = date('Y-m-d',$this->created_at);
        return $date;
    }
    public static function queryBase()
    {
        return self::where('status', self::STAT_ENABLE);
    }

    public static function getTopicList()
    {
        return self::queryBase()->orderByDesc('id')->get();
    }

    /**
     * 根据topic 获取mv_id 列表
     * @param $topic_id
     * @return \Illuminate\Support\Collection
     */
    static function getMVIDList($topic_id)
    {
        return WeekRelationModel::select(['mv_id'])->where('topic_id', $topic_id)->get();
    }


}
