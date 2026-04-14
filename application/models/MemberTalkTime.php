<?php


use Illuminate\Database\Eloquent\Model;

/**
 * class MemberTalkTimeModel
 *
 * @property int $id
 * @property string $icon 图标
 * @property string $name 名称
 * @property int $price 原价
 * @property int $promo_price 推广价格
 * @property int $duration 时长
 * @property int $free_duration 赠送时长
 * @property int $status 状态
 * @property int $sale_count 销量
 * @property int $sale_income 销售额
 * @property int $created_at
 * @property int $updated_at
 *
 * @author xiongba
 * @date 2021-06-26 17:29:30
 *
 * @mixin \Eloquent
 */
class MemberTalkTimeModel extends Model
{

    protected $table = "member_talk_time";

    protected $primaryKey = 'id';

    protected $fillable = ['icon', 'name', 'price', 'promo_price', 'duration', 'free_duration', 'status', 'sale_count',
                           'sale_income', 'created_at', 'updated_at'];

    protected $hidden = ['sale_income', 'icon', 'created_at', 'updated_at', 'status', 'sale_count', 'icon'];

    protected $appends = ['icon_url'];

    protected $guarded = 'id';

    public $timestamps = false;

    const STATUS_NO = 0;
    const STATUS_YES = 1;
    const STATUS = [
        self::STATUS_NO  => '否',
        self::STATUS_YES => '是',
    ];


    /**
     * @param array $where
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function queryBase($where = [])
    {
        return self::where('status', self::STATUS_YES)->where($where);
    }


    public function getIconUrlAttribute($key)
    {
        return url_cover($this->attributes['icon'] ?? '');
    }


}
