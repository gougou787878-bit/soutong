<?php


use Illuminate\Database\Eloquent\Model;

/**
 * class PostCommentUserLikeModel
 *
 * @property int $id 
 * @property int $aff 
 * @property int $related_id 
 * @property int $post_id
 * @property string $created_at 
 * @property string $updated_at 
 *
 * @author xiongba
 * @date 2023-06-09 20:10:45
 *
 * @mixin \Eloquent
 */
class PostCommentUserLikeModel extends Model
{

    protected $table = "post_comment_user_like";

    protected $primaryKey = 'id';

    protected $fillable = ['aff', 'related_id', 'post_id', 'created_at', 'updated_at'];

    protected $guarded = 'id';

    public $timestamps = false;

    public static function getIdsById($aff, $id)
    {
        return self::where('aff', $aff)
            ->where('related_id', $id)
            ->first();
    }



}
