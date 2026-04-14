<?php


use Illuminate\Database\Eloquent\Model;

/**
 * class TopicLikeModel
 *
 * @property int $id 
 * @property int $topic_id 合集id
 * @property int $uid 用户uid
 *
 * @property UserTopicModel $topic
 *
 * @author xiongba
 * @date 2021-02-23 16:17:49
 *
 * @mixin \Eloquent
 */
class TopicLikeModel extends EloquentModel
{

    protected $table = "topic_like";

    protected $primaryKey = 'id';

    protected $fillable = ['topic_id', 'uid'];

    protected $guarded = 'id';

    public $timestamps = false;


    public function topic(){
        return $this->hasOne(TopicModel::class , 'id' , 'topic_id');
    }



}
