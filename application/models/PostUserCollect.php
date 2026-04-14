<?php

use Illuminate\Database\Eloquent\Model;

/**
 * class PostUserCollectModel
 * 
 * 
 * @property int $id  
 * @property int $aff  
 * @property int $related_id  
 * @property int $type  
 * @property string $created_at  
 * @property string $updated_at  
 * 
 * 
 *
 * @mixin \Eloquent
 */
class PostUserCollectModel extends Model
{
    protected $table = 'post_user_collect';
    protected $primaryKey = 'id';
    protected $fillable = ['id', 'aff', 'related_id', 'type', 'created_at', 'updated_at'];
    protected $guarded = 'id';
    public $timestamps = false;

    CONST TYPE_MV = 1;
    CONST TYPE_BOOK = 2;
    CONST TYPE_STORY = 3;
    CONST TYPE_LINK = 4;
    CONST TYPE_SOUND_STORY = 5;
    CONST TYPE_PIC = 6;
    CONST TYPE_GIRL = 7;
    CONST TYPE_CHAT = 8;
    CONST TYPE_UNLOCK_CHAT = 9;
    CONST TYPE_UNLOCK_GIRL = 10;
    CONST TYPE_SHORT_MV = 11;
    CONST TYPE_PUA_COURSE = 12;
    CONST TYPE_PUA_TEACHER = 13;
    CONST TYPE_POST = 14;
    CONST TYPE_MVPACKAGE = 99;

    const TYPE = [
        self::TYPE_MV => '长视频',
        self::TYPE_SHORT_MV => '短视频',
        self::TYPE_BOOK => '漫画',
        self::TYPE_STORY => '小说',
        self::TYPE_LINK => '链接',
        self::TYPE_SOUND_STORY => '有声小说',
        self::TYPE_PIC => '美图',
        self::TYPE_GIRL => '嫖娼',
        self::TYPE_CHAT => '聊天',
        self::TYPE_UNLOCK_CHAT => '解锁聊天',
        self::TYPE_UNLOCK_GIRL => '解锁嫖娼',
        self::TYPE_MVPACKAGE => '视频打折包',
        self::TYPE_POST => '帖子',
    ];

    public function post(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(PostModel::class, 'id', 'related_id');
    }

    public static function listFavoritePostIds($aff)
    {
        $data = self::with(['post' => function($query){
            $query->with('topic:id,name')
                ->with('medias:pid,media_url,cover,thumb_width,thumb_height,type')
                ->with('user:aff,uid,nickname,thumb,aff,expired_at,vip_level,uuid,sexType')
                ->where('status',PostModel::STATUS_PASS)
                ->where('is_deleted',PostModel::DELETED_NO)
                ->where('is_finished',PostModel::FINISH_OK);
             }])
            ->where('aff', $aff)
            ->where('type', self::TYPE_POST)
            ->orderByDesc('created_at')
            ->get()->pluck('post');

        return $data;
    }
}