<?php

use Illuminate\Database\Eloquent\Model;

/**
 * Class FaceMaterialUserLikeModel
 *
 * @property int $id
 * @property int|null $aff            用户ID（或其它关联ID）
 * @property int|null $related_id     素材或评论的ID
 * @property int $type               0 表示素材, 1 表示评论
 * @property int $action_type        0 表示点赞, 1 表示收藏
 * @property string|null $created_at
 * @property string|null $updated_at
 *
 * @author 
 * @date 
 *
 * @mixin \Eloquent
 */
class FaceMaterialUserLikeModel extends Model
{
    // 指定表名
    protected $table = "face_material_user_like";

    // 主键字段
    protected $primaryKey = 'id';

    // 允许批量赋值的字段
    protected $fillable = [
        'aff',
        'related_id',
        'type',
        'action_type',
        'created_at',
        'updated_at'
    ];

    // 保护主键不被批量赋值（可选）
    protected $guarded = ['id'];

    // 如果不使用 Laravel 自动维护的 created_at 和 updated_at，则设置为 false
    public $timestamps = false;

    // 类型常量定义
    const TYPE_MATERIAL = 0;
    const TYPE_COMMENT  = 1;

    // 行为类型常量定义
    const ACTION_LIKE    = 0;
    const ACTION_COLLECT = 1;

    // 类型对应提示文字
    const TYPE_TIPS = [
        self::TYPE_MATERIAL => '素材',
        self::TYPE_COMMENT  => '评论'
    ];

    // 行为类型对应提示文字
    const ACTION_TIPS = [
        self::ACTION_LIKE    => '点赞',
        self::ACTION_COLLECT => '收藏'
    ];

    // 缓存键规则（根据需要可进行扩展）
    const USER_MATERIAL_LIKE_LIST  = 'user:face_material:like:list:%s';
    const USER_MATERIAL_COLLECT_LIST  = 'user:face_material:collect:list:%s';
    const USER_MATERIAL_COLLECT_LIST_PAGE  = 'user:face_material:collect:list:page:%s';
    const USER_COMMENT_LIKE_LIST   = 'user:face_comment:like:list:%s';
    const USER_COMMENT_COLLECT_LIST   = 'user:face_comment:collect:list:%s';

    /**
     * 示例关联关系：
     * 如果有对应的素材模型，可定义一对一或一对多关联（需替换 FaceMaterialModel 为实际模型类名）
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function material()
    {
        // 假设 related_id 对应素材表的 id 字段
        return $this->hasOne(FaceMaterialModel::class, 'id', 'related_id');
    }

    /**
     * 根据用户ID获取用户对素材的点赞记录的 related_id 列表
     * （仅针对素材且行为为点赞的记录）
     *
     * @param int $aff
     * @return array
     */
    public static function listMaterialLikeIds($aff)
    {
        $cacheKey = sprintf(self::USER_MATERIAL_LIKE_LIST, $aff);
        return cached($cacheKey)
            ->expired(3600)
            ->serializerPHP()
            ->fetch(function () use ($aff) {
                $data = self::where('type', self::TYPE_MATERIAL)
                    ->where('action_type', self::ACTION_LIKE)
                    ->where('aff', $aff)
                    ->get()
                    ->toArray();
                return array_column($data, 'related_id');
            });
    }

    /**
     * 根据用户ID获取用户对素材的收藏记录的 related_id 列表
     *
     * @param int $aff
     * @return array
     */
    public static function listMaterialCollectIds($aff)
    {
        $cacheKey = sprintf(self::USER_MATERIAL_COLLECT_LIST, $aff);
        return cached($cacheKey)
            ->expired(3600)
            ->serializerPHP()
            ->fetch(function () use ($aff) {
                $data = self::where('type', self::TYPE_MATERIAL)
                    ->where('action_type', self::ACTION_COLLECT)
                    ->where('aff', $aff)
                    ->get()
                    ->toArray();
                return array_column($data, 'related_id');
            });
    }

    /**
     * 根据用户ID获取用户对评论的点赞记录的 related_id 列表
     *
     * @param int $aff
     * @return array
     */
    public static function listCommentLikeIds($aff)
    {
        $cacheKey = sprintf(self::USER_COMMENT_LIKE_LIST, $aff);
        return cached($cacheKey)
            ->expired(3600)
            ->serializerPHP()
            ->fetch(function () use ($aff) {
                $data = self::where('type', self::TYPE_COMMENT)
                    ->where('action_type', self::ACTION_LIKE)
                    ->where('aff', $aff)
                    ->get()
                    ->toArray();
                return array_column($data, 'related_id');
            });
    }

    /**
     * 根据用户ID获取用户对评论的收藏记录的 related_id 列表
     *
     * @param int $aff
     * @return array
     */
    public static function listCommentCollectIds($aff)
    {
        $cacheKey = sprintf(self::USER_COMMENT_COLLECT_LIST, $aff);
        return cached($cacheKey)
            ->expired(3600)
            ->serializerPHP()
            ->fetch(function () use ($aff) {
                $data = self::where('type', self::TYPE_COMMENT)
                    ->where('action_type', self::ACTION_COLLECT)
                    ->where('aff', $aff)
                    ->get()
                    ->toArray();
                return array_column($data, 'related_id');
            });
    }

    /**
     * 获取单条记录（根据用户ID和目标ID）
     *
     * @param int $aff
     * @param int $relatedId
     * @return FaceMaterialUserLikeModel|null
     */
    public static function getByAffAndRelatedId($aff, $relatedId)
    {
        return self::where('aff', $aff)
            ->where('related_id', $relatedId)
            ->first();
    }

    /**
     * 清除缓存记录
     *
     * @param int $type      类型：0 素材，1 评论
     * @param int $actionType 行为：0 点赞，1 收藏
     * @param int $aff
     * @throws RedisException
     */
    public static function clearCacheByAff($type, $actionType, $aff)
    {
        if ($type == self::TYPE_MATERIAL) {
            $cacheKey = $actionType == self::ACTION_LIKE
                ? sprintf(self::USER_MATERIAL_LIKE_LIST, $aff)
                : sprintf(self::USER_MATERIAL_COLLECT_LIST, $aff);
        } else {
            $cacheKey = $actionType == self::ACTION_LIKE
                ? sprintf(self::USER_COMMENT_LIKE_LIST, $aff)
                : sprintf(self::USER_COMMENT_COLLECT_LIST, $aff);
        }
        redis()->del($cacheKey);
    }

    public static function getIdsById($aff,$id,$type,$action_type)
    {
        return self::where('aff', $aff)
            ->where('related_id', $id)
            ->where('type', $type)
            ->where('action_type', $action_type)
            ->first();
    }

    public static function getIdsByAff($aff,$type,$action_type)
    {
        return self::where('aff', $aff)
            ->where('type', $type)
            ->where('action_type', $action_type)
            ->pluck('related_id')->toArray();
    }

    public static function getUserData($uid, $page, $limit = 20)
    {
        return self::with('material')
            ->where('type', self::TYPE_MATERIAL)
            ->where('action_type', self::ACTION_COLLECT)
            ->where('aff', $uid)
            ->orderByDesc('id')
            ->forPage($page, $limit)
            ->get()
            ->pluck('material')
            ->filter()->values();
    }
}
