<?php

/**
 * class FakeCommentModel
 *
 * @property string $content 评论内容
 * @property string $created_at
 * @property int $id
 * @property int $type 评论类型，1-贴子，2-视频
 *
 * @date 2025-04-30 18:00:29
 *
 * @mixin \Eloquent
 */
class FakeCommentModel extends EloquentModel
{
    public $timestamps = false;
    protected $table = 'fake_comment';
    protected $primaryKey = 'id';

    protected $fillable = [
        'id',
        'type',
        'content',
        'created_at',
    ];

    const TYPE_POST = 1;
    const TYPE_MV = 2;
    const TYPE_OTHER = 3;
    const TYPE_PIC = 4;
    const TYPE_NOVEL = 5;

    const TYPE_TIPS = [
        self::TYPE_POST => '社区',
        self::TYPE_MV => '视频',
        self::TYPE_OTHER => '其它',
        self::TYPE_PIC => '图集',
        self::TYPE_NOVEL => '小说',
    ];

    //获取帖子随机评论内容
    static function getRandContentByPost() {
        $total = self::where("type", self::TYPE_POST)->count();
        $offset = mt_rand(0, $total - 1);
        return self::where("type", self::TYPE_POST)->limit(1)->offset($offset)->value('content');
    }

    //获取视频随机评论内容
    static function getRandContentByMv() {
        $total = self::where("type", self::TYPE_MV)->count();
        $offset = mt_rand(0, $total - 1);
        return self::where("type", self::TYPE_MV)->limit(1)->offset($offset)->value('content');
    }

    //获取图集随机评论内容
    static function getRandContentByPic() {
        $total = self::where("type", self::TYPE_PIC)->count();
        $offset = mt_rand(0, $total - 1);
        return self::where("type", self::TYPE_PIC)->limit(1)->offset($offset)->value('content');
    }

    //获取小说随机评论内容
    static function getRandContentByNovel() {
        $total = self::where("type", self::TYPE_NOVEL)->count();
        $offset = mt_rand(0, $total - 1);
        return self::where("type", self::TYPE_NOVEL)->limit(1)->offset($offset)->value('content');
    }

    //获取所有随机评论内容
    static function getRandContentByAll() {
        $total = self::where("type","<>",self::TYPE_PIC)->count();
        $offset = mt_rand(0, $total - 1);
        return self::query()->where("type","<>",self::TYPE_PIC)->limit(1)->offset($offset)->value('content');
    }

    static function getRandContentByNum($limit=1){
        return self::query()->where("type","<>",self::TYPE_PIC)->orderByRaw('RAND()')->limit($limit)->pluck('content')->toArray();
    }

    static function getRandContentByPicNum($limit=1){
        return self::query()->where("type",self::TYPE_PIC)->orderByRaw('RAND()')->limit($limit)->pluck('content')->toArray();
    }

    static function getRandContentByNovelNum($limit=1){
        return self::query()->where("type",self::TYPE_NOVEL)->orderByRaw('RAND()')->limit($limit)->pluck('content')->toArray();
    }
}
