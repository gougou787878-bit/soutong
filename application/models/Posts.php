<?php


use Illuminate\Database\Eloquent\Model;

/**
 * class PostsModel
 *
 * @property int $comment_count
 * @property int $comment_status 评论状态，1允许，0不允许
 * @property int $id
 * @property int $istop 置顶 1置顶； 0不置顶
 * @property int $orderno
 * @property int $post_author 发表者id
 * @property string $post_content post内容
 * @property string $post_content_filtered
 * @property string $post_date post创建日期，永久不变，一般不显示给用户
 * @property string $post_excerpt post摘要
 * @property int $post_hits post点击数，查看数
 * @property string $post_keywords seo keywords
 * @property int $post_like post赞数
 * @property string $post_mime_type
 * @property string $post_modified post更新时间，可在前台修改，显示给用户
 * @property int $post_parent post的父级post id,表示post层级关系
 * @property string $post_source 转载文章的来源
 * @property int $post_status post状态，1已审核，0未审核
 * @property string $post_title post标题
 * @property int $post_type
 * @property int $recommended 推荐 1推荐 0不推荐
 * @property string $smeta post的扩展字段，保存相关扩展属性，如缩略图；格式为json
 * @property int $type
 *
 * @author xiongba
 * @date 2020-03-13 18:51:32
 *
 * @mixin \Eloquent
 */
class PostsModel extends Model
{

    protected $table = "posts";
    public static $tableName = 'posts';

    protected $primaryKey = 'id';

    protected $fillable = [
        'comment_count',
        'comment_status',
        'istop',
        'orderno',
        'post_author',
        'post_content',
        'post_content_filtered',
        'post_date',
        'post_excerpt',
        'post_hits',
        'post_keywords',
        'post_like',
        'post_mime_type',
        'post_modified',
        'post_parent',
        'post_source',
        'post_status',
        'post_title',
        'post_type',
        'recommended',
        'smeta',
        'type'
    ];

    protected $guarded = 'id';

    public $timestamps = false;


    const COMMENT_STATUS_NO = 0;
    const COMMENT_STATUS_YES = 1;
    const COMMENT_STATUS = [
        self::COMMENT_STATUS_NO  => '不允许',
        self::COMMENT_STATUS_YES => '允许',
    ];
    const IS_TOP_NO = 0;
    const IS_TOP_YES = 1;
    const IS_TOP = [
        self::IS_TOP_NO  => '不置顶',
        self::IS_TOP_YES => '置顶',
    ];

    const RECOMMENDED_NO = 0;
    const RECOMMENDED_YES = 1;
    const RECOMMENDED = [
        self::RECOMMENDED_NO  => '推荐',
        self::RECOMMENDED_YES => '不推荐',
    ];

    const STATUE_NO = 0;
    const STATUE_YES = 1;
    const STATUE = [
        self::STATUE_NO  => '未审核',
        self::STATUE_YES => '已审核',
    ];

}
