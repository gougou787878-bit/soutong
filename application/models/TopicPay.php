<?php


use Illuminate\Database\Eloquent\Model;

/**
 * class TopicPayModel
 *
 * @property int $id 
 * @property int $uid 用户id
 * @property int $coins 购买时的价格
 * @property int $topic_id 合集id
 * @property string $type 类型 购买
 * @property string $created_at 购买时间
 * @property MhModel $manhua
 *
 * @author xiongba
 * @date 2022-05-17 17:35:31
 *
 * @mixin \Eloquent
 */
class TopicPayModel extends Model
{

    protected $table = "topic_pay";

    protected $primaryKey = 'id';

    protected $fillable = ['uid', 'coins', 'topic_id', 'type', 'created_at'];

    protected $guarded = 'id';

    public $timestamps = false;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function topic()
    {
        return $this->hasOne(TopicModel::class, 'id', 'topic_id');
    }

    /**
     * @param $uid
     * @param $mh_id
     * @return bool
     */
    static function hasBuy($uid,$topic_id){
        return self::where(['uid'=>$uid,'topic_id'=>$topic_id])->exists();
    }

    /**
     * @param $uid
     * @param $page
     * @param $limit
     * @return array
     */
    public static function getUserBuyData($uid, $page, $limit,$member)
    {

        return self::query()
            ->where(['uid' => $uid])
            ->with('topic')
            ->forPage($page, $limit)
            ->orderByDesc('id')
            ->get()
            ->map(function ($item)use($member) {
                /** @var TopicModel $topic */
                if (is_null($item) || is_null($topic = $item->topic)) {

                    return null;
                }
                return $topic->watchByUser($member);
            })->filter()->toArray();

    }
    public static function getIdsArrByUser($uid)
    {
        return redis()->sMembers("topic_pay:list:" . $uid);
    }

    public static function addIdArr($uid, $idArr)
    {
        $idArr = (array)$idArr;
        return redis()->sAddArray("topic_pay:list:" . $uid, $idArr);
    }



}
