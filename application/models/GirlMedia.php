<?php


use Illuminate\Database\Eloquent\Model;

/**
 * class PostMediaModel
 *
 * @property int $id 
 * @property string $media_url 视频或图片地址
 * @property string $cover 视频封面
 * @property int $thumb_width 封面宽
 * @property int $thumb_height 封面高
 * @property int $pid 帖子ID
 * @property string $aff 上传用户AFF
 * @property int $type 类型 1图片 2视频
 * @property string $created_at 创建时间
 * @property int $status 0 未转换 1 已转换 2 转换中
 * @property int $duration 视频持续时间
 * @property string $updated_at 更新时间
 * @property int $relate_type 关联类型 1帖子 2评论
 *
 * @author xiongba
 * @date 2023-06-09 20:11:01
 *
 * @mixin \Eloquent
 */
class GirlMediaModel extends Model
{

    protected $table = "girl_media";

    protected $primaryKey = 'id';

    protected $fillable = ['media_url', 'cover', 'thumb_width',
        'thumb_height', 'pid', 'aff', 'type', 'created_at', 'status', 'duration', 'updated_at', 'relate_type'];

    protected $guarded = 'id';

    public $timestamps = false;

    const TYPE_IMG = 1;
    const TYPE_VIDEO = 2;
    const TYPE = [
        self::TYPE_IMG   => '图片',
        self::TYPE_VIDEO => '视频',
    ];

    public function girl(){
        return self::hasOne(GirlModel::class,'id','pid');
    }

    const STATUS_NO = 0;
    const STATUS_OK = 1;
    const STATUS_ING = 2;
    const STATUS_TIPS = [
        self::STATUS_NO  => '未转换',
        self::STATUS_OK  => '已转换',
        self::STATUS_ING => '转换中'
    ];


    const TYPE_RELATE_POST = 1;
    const TYPE_RELATE_COMMENT = 2;
    const TYPE_RELATE_TIPS = [
        self::TYPE_RELATE_POST    => '帖子',
        self::TYPE_RELATE_COMMENT => '评论',
    ];

    protected $appends = ['cover_url_full'];
    public function getCoverUrlFullAttribute(){
        return url_cover($this->getAttribute("cover"));
    }


    /**
     * @param GirlModel $model
     * @return bool|string
     * @author xiongba
     * @date 2020-03-03 19:53:48
     */
    static function approvedMv($model)
    {
        $data = [
            'uuid'    => 'fasdfddfasdfdjfajkodfs09ds0r23089df',
            'm_id'    => $model->id,
            'needMp3' => 0,
            'needImg' => empty($model->cover) ? 1 : 0,
            'playUrl' => $model->media_url,
        ];
        $crypt = new \tools\CryptService();
        $sign = $crypt->make_sign($data);
        $data['sign'] = $sign;
        $data['notifyUrl'] =SYSTEM_NOTIFY_SLICE_GIRL_URL;
        if ('test' == APP_ENVIRON){
            $data['notifyUrl'] ='https://sky.hyys.info/index.php?&m=mv&a=girl_media';
        }
        $curl = new \tools\CurlService();
        $return = $curl->request(config('mp4.accept'), $data);

        errLog("post reslice req:" . var_export([$data, $return], true));
        return $return;
    }

}
