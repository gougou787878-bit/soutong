<?php


use Illuminate\Database\Eloquent\Model;

/**
 * class ShangwuModel
 *
 * @property int $id
 * @property string $title 标题
 * @property string $icon 图标
 * @property string $text 描述
 * @property string $url 联系方式
 * @property string $group 分组标识
 * @property int $status 状态1 生效
 *
 *
 * @date 2022-03-24 18:46:52
 *
 * @mixin \Eloquent
 */
class ShangwuModel extends Model
{

    protected $table = "shangwu";

    protected $primaryKey = 'id';

    protected $fillable = ['title', 'icon', 'text', 'url', 'group', 'status'];

    protected $guarded = 'id';

    public $timestamps = false;


    protected $appends = [
        'icon_full',
    ];

    /**
     * 图片地址
     * @return string
     */
    public function getIconFullAttribute()
    {
        return $this->icon? url_ads($this->icon):'';
    }
    const STATUS_SUCCESS = 1;
    const STATUS_FAIL = 0;
    const STATUS = [
        self::STATUS_FAIL    => '禁用',
        self::STATUS_SUCCESS => '启用',
    ];

    const GROUP = [
        [
            'title' => '官方交流群',
            'text'  => '点击加群，一起看片一起骚',
            'key'   => 'jlq'

        ],
        [
            'title' => '求片专享',
            'text'  => '大片/经典/新奇，啥都可以帮你找',
            'key'   => 'qpq'

        ],
        [
            'title' => '官方招募',
            'text'  => '裸聊/楼风/经纪人',
            'key'   => 'zmq'

        ],
        [
            'title' => '广告商务合作',
            'text'  => 'cps/cpa/cpt/广告位',
            'key'   => 'swq'
        ],
        [
            'title' => '下载工具',
            'text'  => 'tg/ants/potota',
            'key'   => 'down'
        ],
    ];

    static function getYuePaoContact(){
        $key = 'zmq';
        static $data = null;
        if(is_null($data)){
            $data = self::getDataList();
        }

        return isset($data[$key])?$data[$key]:[];
    }
    static function getDownTool(){
        $key = 'down';
        static $data = null;
        if(is_null($data)){
            $data = self::getDataList();
        }
        return isset($data[$key])?$data[$key]:[];
    }
    static function getShangwu(){
        $key = 'swq';
        static $data = null;
        if(is_null($data)){
            $data = self::getDataList();
        }
        return isset($data[$key])?$data[$key]:[];
    }

    static function groupData(){
        return array_column(self::GROUP,'title','key');
    }

    static function getDataList(){
        $data =  self::query()->where('status','=',self::STATUS_SUCCESS)
            ->orderBy('id')->get()->mapToGroups(function (ShangwuModel $item){
               return [$item->group=>$item];
            })->toArray();
        return $data;
    }
    static function groupDataList(){
        static $data = null;
        if(is_null($data)){
            $data = self::getDataList();
        }
        $groupDataList = collect(self::GROUP)->map(function ($item)use($data){
            $item['list'] = [];
           if(isset($data[$item['key']])){
               $item['list'] = $data[$item['key']];
           }
           return $item;

        })->toArray();
        return $groupDataList;
    }


}
