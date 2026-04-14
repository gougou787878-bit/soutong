<?php


use Illuminate\Database\Eloquent\Model;

/**
 * class MhTagsModel
 *
 * @property int $id 
 * @property string $name 标签
 * @property int $sort_num 排序
 * @property int $created_at 创建时间
 * @property int $updated_at 
 * @property string $img_url 标签封面图
 * @property int $home 首页显示
 * @property int $status 列表显示状态
 * @property int $user_up 允许用户上传
 * @property string $horizontal_img 横向图片
 * @property string $description 描述
 *
 * @author xiongba
 * @date 2022-05-17 17:36:58
 *
 * @mixin \Eloquent
 */
class MhTagsModel extends Model
{

    protected $table = "mh_tags";

    protected $primaryKey = 'id';

    protected $fillable = ['name', 'sort_num', 'created_at', 'updated_at', 'img_url', 'home', 'status', 'user_up', 'horizontal_img', 'description'];

    protected $guarded = 'id';

    public $timestamps = false;




}
