<?php


/**
 * class CartoonCommentLikesModel
 *
 * @property int $id 
 * @property int $uid
 * @property int $comment_id
 * @property string $created_at
 * @property string $updated_at
 *
 * @author xiongba
 * @date 2023-06-09 20:10:45
 *
 * @mixin \Eloquent
 */
class CartoonCommentLikesModel extends EloquentModel
{

    protected $table = "cartoon_comment_likes";

    protected $primaryKey = 'id';

    protected $fillable = ['uid', 'comment_id', 'created_at', 'updated_at'];

    protected $guarded = 'id';

    public $timestamps = false;

    public static function getIdsById($uid, $comment_id)
    {
        return self::where('uid', $uid)
            ->where('comment_id', $comment_id)
            ->first();
    }



}
