<?php


use Illuminate\Database\Eloquent\Model;

/**
 * class TopicRelationModel
 *
 * @property int $id 
 * @property int $topic_id 
 * @property int $mv_id 
 *
 * @author xiongba
 * @date 2020-05-25 22:14:44
 *
 * @mixin \Eloquent
 */
class TopicRelationModel extends Model
{

    protected $table = "topic_relation";

    protected $primaryKey = 'id';

    protected $fillable = ['topic_id', 'mv_id'];

    protected $guarded = 'id';

    public $timestamps = false;


    public function topic(){
        return self::hasOne(TopicModel::class,'id','topic_id');
    }
    public function mv(){
        return self::hasOne(MvModel::class,'id','mv_id');
    }

    /**
     * @param $data
     * @return bool
     */
    static function addTopicMv($data){
        return self::insert($data);
    }


    static function getSumCoins($topicId){
        $total = 0;
        $items =  self::where('topic_id',$topicId)->with('mv:coins,id')->get();
        if($items){
            foreach ($items as $item){
                $total +=$item->mv->coins;
            }
        }
        return $total;
    }




}
