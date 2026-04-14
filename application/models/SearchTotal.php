<?php


use Illuminate\Database\Eloquent\Model;

/**
 * class SearchTotalModel
 *
 * @property string $date_at 日期
 * @property int $id
 * @property string $kwy 关键词
 * @property int $num 日期
 * @property string $word 视频id
 *
 * @author xiongba
 * @date 2020-04-21 15:49:14
 *
 * @mixin \Eloquent
 */
class SearchTotalModel extends Model
{

    protected $table = "search_total";

    protected $primaryKey = 'id';

    protected $fillable = ['date_at', 'kwy', 'num', 'word'];

    protected $guarded = 'id';

    public $timestamps = false;


    /**
     * 添加或者修改总搜索的统计
     * @param string $word 要搜索的文本
     * @param array $kwy 通过文本分析出来的关键词
     * @return int
     * @author xiongba
     * @date 2020-03-17 11:45:35
     */
    public static function addOrUpdate($word, $kwy)
    {
        $kwyStr = join(',', $kwy);
        $word = trim($word);
        $prefix = DB::getTablePrefix();
        $date = date('Y-m-d');
        return DB::update("insert into {$prefix}search_total (word, kwy, num, date_at) values (?,?,1,?) on duplicate key update num=num+1",
            [$word, $kwyStr, $date]);
    }
}
