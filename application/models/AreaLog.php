<?php


use Illuminate\Database\Eloquent\Model;

/**
 * class AreaLogModel
 *
 * @property int $id 
 * @property string $uuid 
 * @property string $url 检测域名
 * @property string $ip 检测ip
 * @property int $sick 返回状态
 * @property string $created_at 检测时间
 * @property string $updated_at 
 *
 * @author xiongba
 * @date 2020-03-07 15:22:30
 *
 * @mixin \Eloquent
 */
class AreaLogModel extends Model
{

    protected $table = "area_log";

    protected $primaryKey = 'id';

    protected $fillable = ['uuid', 'url', 'ip', 'sick', 'created_at', 'updated_at'];

    protected $guarded = 'id';

    public $timestamps = true;




}
