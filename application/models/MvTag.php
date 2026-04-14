<?php


use Illuminate\Database\Eloquent\Model;

/**
 * class MvTagsModel
 *
 * @property int $id
 * @property int $mv_id 视频id
 * @property string $tag 标签名
 *
 * @author xiongba
 * @date 2020-03-04 20:13:00
 *
 * @mixin \Eloquent
 */
class MvTagModel extends Model
{

    protected $table = "mv_tags";

    protected $primaryKey = 'id';

    protected $fillable = ['mv_id', 'tag'];

    protected $guarded = 'id';

    public $timestamps = false;


    /**
     * 标签入库，会进入缓存
     * @param $mvId
     * @param $tag
     * @return Model|MvTagModel|null
     */
    public static function createBy($mvId, $tag)
    {
        $tag = trim($tag);
        if ($tag) {
            $model = self::create(['mv_id' => $mvId, 'tag' => $tag]);
            redis()->sAdd("mv:tag:" . $tag, $mvId);
            return $model;
        } else {
            return null;
        }
    }

    /**
     * 标签入库，会进入缓存
     * @param $mvId
     * @param $tags
     * @return array
     */
    public static function createByAll($mvId, $tags)
    {
        if (is_string($tags)){
            $tags = explode(',' , $tags);
        }
        if(empty($tags)){
            return [];
        }
        $results = [];
        $oldTags = self::where('mv_id', $mvId)->pluck('tag')->toArray();
        $oldTags && $tags = array_diff($tags , $oldTags);
        foreach ($tags as $tag) {
            $results[] = self::createBy($mvId, $tag);
        }
        return $results;
    }


    /**
     * 在标签表里面，删除mv没有的标签
     * @param $mvId
     * @param null $mvTags
     */
    public static function deleteMvNoTag($mvId , $mvTags = null){
        $oldTag = self::where('mv_id', $mvId)->pluck('tag')->toArray();
        if (empty($mvTags)){
            $mvTags = MvModel::useWritePdo()->find($mvId)->value('tags');
            if (is_string($mvTags)){
                $mvTags = explode(',' , $mvTags);
            }
        }
        $tags = array_diff($oldTag , $mvTags);

        self::deleteByAll($tags, $mvId);
    }

    public static function deleteBy($tag, $mvId)
    {
        $tag = trim($tag);
        if ($tag) {
            $model = self::where(['mv_id' => $mvId, 'tag' => $tag])->delete();
            redis()->sRem("mv:tag:" . $tag, $mvId);
            return $model;
        } else {
            return null;
        }
    }

    public static function deleteByAll($tags, $mvId)
    {
        $results = [];
        foreach ($tags as $tag) {
            $results[] = self::deleteBy($tag,$mvId);
        }
        return $results;
    }



    public static function getMvIdsByTag($tag){
        return redis()->sMembers("mv:tag:" . $tag);
    }

    public static function addMvIds2Members($tag, $ids)
    {
        return redis()->sAddArray("mv:tag:" . $tag, $ids);
    }

    /**
     *
     * @param $tag
     * @param null $iterator
     * @param int $offset
     * @param int $limit
     * @return array|bool|false
     * @author xiongba
     * @date 2020-03-04 19:34:21
     */
    public static function getMvIdByTag($tag, &$iterator = null, &$offset = 0, $limit = 20)
    {
        $redis = redis();
        try {
            $object = new \helper\ScanPagination($redis, "mv:tag:" . $tag, [
                "totalSize" => 335,
                "curPage"   => 16,
                "hasNext"   => 1,
                "iterator"  => 0,
                "limit"     => 20
            ]);
            $all = $object->get();
            $offset = $object->config();
            return $all;
        } catch (Throwable $e) {
            errLog($e);
            return [];
        }
    }


}
