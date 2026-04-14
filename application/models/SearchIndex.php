<?php


use Illuminate\Database\Eloquent\Model;

/**
 * class KeywordSearchModel
 *
 * @property int $id
 * @property string $word 搜索使用的词
 * @property string $keyword 关键词
 * @property string $word_hash 搜索使用的词hash 用来建立索引 substr(md5(word),8,16)
 * @property int $num 搜索次数
 * @property int $total 匹配多少结果
 * @property string $results 搜索vid的结果，多个使用,分割
 * @property string $created_at 初次搜索时间
 * @property string $updated_at 自后搜索时间
 *
 * @author xiongba
 * @date 2019-12-27 20:34:47
 *
 * @mixin \Eloquent
 */
class SearchIndexModel extends EloquentModel
{

    protected $table = "search_index";

    protected $primaryKey = 'id';

    protected $fillable = ['word', 'word_hash', 'keyword', 'num', 'total', 'results', 'created_at', 'updated_at'];

    protected $guarded = 'id';

    public $timestamps = true;

    const TYPE_BOOK = 1;
    const TYPE_MV = 2;
    const TYPE_STORY = 3;
    const TYPE_PIC = 4;
    const TYPE_GIRL = 5;
    const TYPE_VLOG = 6;
    const TYPE_CHAT = 7;
    const TYPE_POST = 8;
    const TYPE_TOPIC = 9;
    const TYPE = [
        self::TYPE_MV => '视频',
        self::TYPE_VLOG => '小视频',
        self::TYPE_BOOK => '漫画',
        self::TYPE_STORY => '小说',
        self::TYPE_PIC => '美图',
        self::TYPE_GIRL => '约妹',
        self::TYPE_CHAT => '裸聊',
        self::TYPE_POST => '帖子',
        self::TYPE_TOPIC => '漫剧',
    ];

    public static function generateWordHash($keyword)
    {
        return substr(md5($keyword), 8, 16);
    }



    /**
     * 添加或者修改总搜索的记录
     * @param string $word 要搜索的文本
     * @param int $total 搜索出了多少条数据
     * @param array $kwy 通过文本分析出来的关键词
     * @param array $results 通过关键字搜索出来的结果
     * @param bool $onlyUpdateNum 是否至少影响搜索的次数
     * @return int
     * @author xiongba
     * @date 2020-03-17 11:45:35
     */
    public static function addOrUpdate($word, $total, $kwy, $results, $onlyUpdateNum)
    {
        $kwyStr = join(',', $kwy);
        $resultStr = join(',', $results);
        $word = trim($word);
        $prefix = DB::getTablePrefix();
        $datetime = date('Y-m-d H:i:s');
        if ($onlyUpdateNum) {
            return DB::update("update {$prefix}search_index set num=num+1,updated_at='{$datetime}' where word_hash=?",
                [self::generateWordHash($word)]);
        } else {
            return DB::update("insert into {$prefix}search_index (word, word_hash, num, total, keyword , results,created_at,updated_at) 
values (?,?,1,?,?,?,'{$datetime}','{$datetime}') on duplicate key update  num=num+1,keyword=?,results=?,updated_at='{$datetime}'",
                [
                    $word,
                    self::generateWordHash($word),
                    $total,
                    $kwyStr,
                    $resultStr,
                    $kwyStr,
                    $resultStr
                ]);
        }

    }

    /**
     * 热搜词
     * @param $limit
     * @return \Illuminate\Database\Eloquent\Builder[]|self[]
     * @author xiongba
     * @date 2020-03-17 10:48:53
     */
    public static function getHotSearch($limit)
    {
        return self::query()->orderBy('num', 'desc')->limit($limit)->get(['word']);
    }

    public static function queryWord($word)
    {
        return self::where('word_hash', self::generateWordHash($word));
    }
    public static function firstValue($word)
    {
        $results = self::queryWord($word)->first(['id','results']);
        if (is_null($results)) {
            return null;
        }
        self::where('id',$results->id)->increment('num',2);
        //$ary = explode(',', $results->results);
        //return array_keys(array_flip(array_filter(array_map('intval', $ary))));
    }

}
