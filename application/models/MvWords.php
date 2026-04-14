<?php


/**
 * class MvWordsModel
 *
 * @property int $id
 * @property int $mv_id 视频id
 * @property string $word 关键词
 * @property string $word_hash 关键词hash
 * @property int $weight 权重，数值越大，匹配度应该越高
 *
 * @author xiongba
 * @date 2019-12-26 20:48:41
 *
 * @mixin \Eloquent
 */
class MvWordsModel extends EloquentModel
{

    protected $table = "mv_words";

    protected $primaryKey = 'id';

    protected $fillable = ['mv_id', 'word', 'word_hash', 'weight'];

    protected $guarded = 'id';

    public $timestamps = false;


    public static function createWord($vid, $word, $weight)
    {
        return self::create([
            'mv_id'     => $vid,
            'word'      => $word,
            'weight'    => $weight,
            'word_hash' => self::word2hash($word)
        ]);
    }


    /**
     * 创建多个
     * @param $vid
     * @param $words
     * @return \Illuminate\Support\Collection
     * @author xiongba
     * @date 2019-12-26 19:55:06
     */
    public static function createWords($vid, $words)
    {
        $data = collect([]);
        foreach ($words as $item) {
            list($word, $weight) = $item;
            $data->push(self::createWord($vid, $word, $weight));
        }
        return $data;
    }


    public static function createForTitle($vid, $title)
    {
        $words = \helper\Util::participle($title);
        return self::createWords($vid, $words);
    }


    public static function word2hash($word)
    {
        return substr(md5($word), 8, 16);
    }

    /**
     * 使用关键词获取关键词对应的视频id
     * @param array $words
     * @return array
     * @author xiongba
     * @date 2020-01-18 14:37:17
     */
    public static function getVidByWords(array $words)
    {
        $words = array_map([self::class, 'word2hash'], $words);
        $data = self::whereIn('word_hash', $words)
            ->distinct()
            ->orderBy('weight', 'desc')
            ->orderBy('id', 'desc')
            ->get('mv_id');
        return array_column($data->toArray(), 'mv_id');
    }


}
