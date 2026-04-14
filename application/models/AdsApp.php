<?php


use Illuminate\Database\Eloquent\Model;

/**
 * class AdsAppModel
 *
 * @property int $id 
 * @property string $title 标题
 * @property string $short_name app分享标识
 * @property string $description 描述
 * @property string $img_url ico地址
 * @property string $link_url 跳转地址
 * @property int $status 0-禁用，1-启用
 * @property int $clicked 点击次数
 * @property int $sort 排序
 * @property int $created_at 创建时间
 *
 * @author xiongba
 * @date 2020-10-21 12:30:57
 *
 * @mixin \Eloquent
 */
class AdsAppModel extends Model
{

    protected $table = "ads_app";

    protected $primaryKey = 'id';

    protected $fillable = ['title', 'description', 'img_url', 'link_url', 'status', 'clicked', 'sort', 'created_at','short_name'];

    protected $guarded = 'id';

    public $timestamps = false;

    const STATUS_SUCCESS = 1;
    const STATUS_FAIL = 0;
    const STATUS = [
        self::STATUS_FAIL    => '禁用',
        self::STATUS_SUCCESS => '启用'
    ];

    const REDIS_ADS_KEY = 'adsapp';
    const REDIS_LANPORN_KEY = 'porn';
    const REDIS_SHARE_KEY = 'shareapp';
    public static function clearRedisCache()
    {
        redis()->del(self::REDIS_SHARE_KEY);
        redis()->del(self::REDIS_LANPORN_KEY);
        return redis()->del(self::REDIS_ADS_KEY);
    }

    static function getDataList($limit = 50)
    {
        return self::where('status',
            self::STATUS_SUCCESS)->orderByDesc('sort')->orderByDesc('id')->limit($limit)->get();
    }

    static function incrDownLoadNumber($id, $number = 1)
    {
        return self::where('id', '=', $id)->increment('clicked', $number);
    }


    protected $appends = [
        'img_url_full',
    ];

    /**
     * 替换图片地址
     * @param $value
     * @return string
     */
    public function getImgUrlFullAttribute()
    {
        return $this->img_url ? url_ads($this->img_url) : '';
    }

    static function getShareData(){
        return cached(self::REDIS_SHARE_KEY)->fetchJson(function (){
            try{
                $data = file_get_contents(SYSTEM_SHARE_LINK);
                return json_decode($data,true);
            }catch (Throwable $exception){

                errLog("公共配置分享获取超时".$exception->getMessage());
            }
            return [];
        });
    }


    static function convertURLHOST($short_name,$link_url){
        if(empty($short_name)){
            return $link_url;
        }
        $data = self::getShareData();

        if($data){
            $origin_host = parse_url($link_url,PHP_URL_HOST);
            $replace_host = isset($data[$short_name])?$data[$short_name]:'';
            if($replace_host){
                return str_ireplace($origin_host,$replace_host,$link_url);
            }
        }
        return $link_url;
    }


}
