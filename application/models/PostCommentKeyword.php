<?php


use Illuminate\Database\Eloquent\Model;

/**
 * class PostCommentKeywordModel
 *
 * @property int $id 
 * @property string $keyword 关键词
 * @property string $created_at 
 * @property string $updated_at 
 *
 * @author xiongba
 * @date 2023-06-16 15:59:31
 *
 * @mixin \Eloquent
 */
class PostCommentKeywordModel extends Model
{

    protected $table = "post_comment_keyword";

    protected $primaryKey = 'id';

    protected $fillable = ['keyword', 'created_at', 'updated_at'];

    protected $guarded = 'id';

    public $timestamps = false;


    const REDIS_SAMPLE_KEY = 'cmt:smp';
    const SAMPLE_LIMITT = 1000;
    static function getListAdsample() {
        return cached(self::REDIS_SAMPLE_KEY)->fetchJson(function (){
            return self::query()->select(['keyword'])->orderByDesc('id')
                ->limit(self::SAMPLE_LIMITT)
                ->get()
                ->toArray();
        },600);
    }
    static function clearRedisCache(){
        return redis()->del(self::REDIS_SAMPLE_KEY);
    }

    // 不用词分析 直接包含就禁止
    public static function filterKeyword($content)
    {
        $content = html_entity_decode($content);
        $lowerText = strtolower(str_replace(' ', '', $content));
        $keywords = self::getListAdsample();
        foreach ($keywords as $keyword) {
            if ($keyword['keyword'] && stripos($lowerText,$keyword['keyword'])!==false) {
                return false;
            }
        }
        return true;
    }

    // 只允许发中文
    public static function filterChinese($content): bool
    {
        $content = html_entity_decode($content);
        $permitPregs = [
            "/[\p{Han}]/u",//中文字符
            "/[\p{P}]/u" // 中英文标点
        ];
        $lastStr = trim(preg_replace($permitPregs, '', $content));
        $len = strlen($lastStr);
        if ($len > 0){
            return false;
        }
        return true;
    }
    // 过滤URL
    public static function filterUrl($text)
    {
        // 转化为小写
        $tmpText = strtolower($text);
        // 是否含有.
        if (
            strpos($tmpText, '点') === false &&
            strpos($tmpText, '丶') === false &&
            strpos($tmpText, '·') === false &&
            strpos($tmpText, '.') === false &&
            strpos($tmpText, '。') === false
        ) {
            return true;
        }
        if (!preg_match_all('/[a-z0-9]/', $tmpText)) {
            return true;
        }

        $tmpText = str_replace(' ', '', $tmpText);
        $tmpText = str_replace(['点', '.', '。', '丶', '·'], '.', $tmpText);
        $tmpText = str_replace(['http://', 'https://', '://'], '', $tmpText);
        preg_match_all('/[a-z0-9\-\.]/', $tmpText, $matches);
        $uri = trim(implode('', $matches[0]), '.');

        # 匹配成功直接就是URL
        $url = 'https://' . trim($uri, '.');
        $preg = "/^(https?:\/\/(([a-zA-Z0-9]+-?)+[a-zA-Z0-9]+\.)+(([a-zA-Z0-9]+-?)+[a-zA-Z0-9]*))(:\d+)?(\/.*)?(\?.*)?(#.*)?$/";
        return !preg_match($preg, $url);
    }


    // 特殊的
    public static function filterUrl2($text)
    {
        $text = strtolower($text);

        if (!preg_match_all('/[a-z0-9]/', $text)) {
            return true;
        }

        $tmpText = str_replace(' ', '', $text);
        $tmpText = str_replace(['点', '.', '。', '丶', '·'], '.', $tmpText);
        $tmpText = str_replace(['http://', 'https://', '://'], '', $tmpText);
        preg_match_all('/[a-z0-9\-\.]/', $tmpText, $matches);
        $uri = trim(implode('', $matches[0]), '.');

        if (strpos($uri, '.') === false) {
            return true;
        }

        $uriMd5 = md5($uri);
        $setKey = 'lock_comment_md5';
        $cacheKey = 'comment:30m:' . $uriMd5;
        if (redis()->sIsMember($setKey, $uriMd5)) {
            trigger_log('禁止的评论:' . $text);
            return false;
        }

        $num = redis()->get($cacheKey);
        $ttl = redis()->ttl($cacheKey);
        if ($num > 50) {
            redis()->sAdd($setKey, $uriMd5);
            redis()->del($cacheKey);
            return false;
        }
        if (redis()->exists($cacheKey)) {
            redis()->incrByTtl($cacheKey, 1, $ttl);
        } else {
            redis()->incrByTtl($cacheKey, 1, 1800);
        }
        return true;
    }

    // 过滤字体
    public static function filterFont($content)
    {
        $content = html_entity_decode($content);
        $permitPregs = [
            "/\x{00a9}|\x{00ae}|[\x{2000}-\x{3300}]|[\x{1e400}-\x{1f3ff}]|[\x{1e800}-\x{1f7ff}]|[\x{1ec00}-\x{1fbff}]/u",// 表情包
            "/[\x{4e00}-\x{9fa5}]/u", //中文
            "/[\x{3400}-\x{4db5}]/u", //繁体不常见中文
            "/[a-zA-Z0-9]/u", // 普通字符
            "/[\ |\/|\~|\!|\@|\#|\\$|\%|\^|\&|\*|\(|\)|\_|\+|\{|\}|\:|\<|\>|\?|\[|\]|\,|\.|\/|\;|\'|\`|\-|\=|\\\|\|，|？|！|：|．|·||️|～|（|）|ಡ|ω|𣎴]/u", // 特殊字符
            "/[；|˘|｀|ᕦ|ᕤ|䊾|효|๓|´|شش |＼]/u"
        ];
        $lastStr = trim(preg_replace($permitPregs, '', $content));
        $len = strlen($lastStr);
        if ($len > 0)
            return false;

        $notPermitedPregs = [
            "/[\x{2460}-\x{24ff}]/u", // 带圈的字符
            "/[\x{2070}-\x{208e}]/u",
            "/[\x{278a}-\x{2793}]/u",
            "/[\x{2776}-\x{277f}]/u",
            "/[\x{2780}-\x{2789}]/u",
            "/[\x{24eb}-\x{24ff}]/u",
            "/[\x{0370}-\x{03ff}]/u" // 希腊字母
        ];
        foreach ($notPermitedPregs as $preg) {
            $pregCount = preg_match($preg, $content);
            if ($pregCount)
                return false;
        }
        return true;
    }

    // 过滤账户
    public static function filterStrNumber($content): bool
    {
        $pregs = [
            "/[\p{P}]/u" // 中英文标点
        ];
        $lastStr = trim(str_replace(' ', '', preg_replace($pregs, '', $content)));
        $pinyin = new Overtrue\Pinyin\Pinyin();//composer require overtrue/pinyin
        $pys = $pinyin->convert($lastStr, PINYIN_DEFAULT);
        $pyStr = implode('', $pys);
        $strNums = ['ling', 'yi', 'er', 'san', 'si', 'wu', 'liu', 'qi', 'ba', 'jiu', 'shi'];
        $num = 0;
        foreach ($strNums as $v) {
            $num += mb_substr_count($pyStr, $v);
        }
        if ($num > 5) {
            return false;
        }
        return true;
    }
}
