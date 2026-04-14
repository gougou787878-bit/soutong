<?php


use helper\OperateHelper;
use Illuminate\Database\Eloquent\Model;

/**
 * class TaskModel
 *
 * @property int $id
 * @property int $parent_id 父id
 * @property string $icon 图标
 * @property string $tip tip
 * @property string $name 任务名称
 * @property string $varname 配置简拼
 * @property int $type 1普通任务 2每日任务
 * @property int $add_diamond 奖励金币
 * @property string $diamond_scale 金币比例
 * @property int $experience 获得经验值
 * @property int $active_cnt 获得活跃度
 * @property int $gift_id 礼物ID
 * @property int $gift_type 礼物类型
 * @property int $sort 排序
 * @property int $status 1显示 0 隐藏 2 删除
 *
 * @author xiongba
 * @date 2020-03-19 19:45:08
 *
 * @mixin \Eloquent
 */
class TaskModel extends Model
{

    protected $table = "task";

    protected $primaryKey = 'id';

    protected $fillable = [
        'id',
        'parent_id',
        'icon',
        'tip',
        'name',
        'varname',
        'type',
        'add_diamond',
        'diamond_scale',
        'experience',
        'active_cnt',
        'gift_id',
        'gift_type',
        'sort',
        'status'
    ];

    protected $guarded = 'id';

    public $timestamps = false;


    const STATUS_YES = 1;
    const STATUS_NO = 0;
    const STATUS = [
        self::STATUS_YES => '显示',
        self::STATUS_NO  => '隐藏'
    ];
    const T_NORMAL = 1;
    const T_EVERY_DAY = 2;
    const TYPE = [
        self::T_NORMAL    => '普通任务',
        self::T_EVERY_DAY => '日常任务',
    ];

    //礼包类型
    const GIFT_TYPE_NORMAL = 0;
    const GIFT_TYPE_CAR = 1;
    const GIFT_TYPE = [
        self::GIFT_TYPE_NORMAL => '正常礼包',
        self::GIFT_TYPE_CAR    => '坐骑礼包',
    ];


    const TASK_GROUP_ACTIVE_ID = 22;//任务组-活跃度（每日重复）
    const TASK_GROUP_ACTIVE_KEY = 'active';

    const TASK_GROUP_SGIN_ID = 14;//任务组-每日签到（每日重复）
    const TASK_GROUP_SGIN_KEY = 'day_sign';

    const TASK_GROUP_NEWER_ID = 8;//任务组-新手（1次）
    const TASK_GROUP_NEWER_KEY = 'tiro';

    const TASK_GROUP_NORMAL_ID = 1;//任务组-日常任务（每日重复）
    const TASK_GROUP_NORMAL_KEY = 'daily';

    const TASK_TYPE_CLICK_AD = 8;


    //新手
    const ID_NEW_REGISTER = 9; //注册账号 task表的id
    const ID_NEW_PERFECT_INFORMATION = 10;//完善资料
    const ID_NEW_FIRST_GIFT = 11;//首次赠送礼物
    const ID_NEW_FIRST_BUY_MV = 12;//首次购买金币视频
    const ID_NEW_FOLLOW_ANCHOR = 13; //关注一位主播
    //日常
    const ID_EVERY_PAY = 2; //每日首次充值 task表的id
    const ID_EVERY_SEND_GIFT_500 = 3; //送礼物价值超过500钻
    const ID_EVERY_SEND_GIFT_ANY = 4; //观看直播并送出任意礼物
    const ID_EVERY_UPLOAD_VIDEO = 5; //上传一个视频并通过审核
    const ID_EVERY_PRAISE_MV_10 = 6; //累计点赞10个视频
    const ID_EVERY_INVITER_FRIEND = 7; //邀请好友并注册



    /**
     * 获取奖励
     * @return array
     * @author xiongba
     */
    public function getReward()
    {
        // /new/live/20200404/2020040420445856919.jpeg 活跃
        $icons = [];
        if ($this->add_diamond > 0) {
            //砖石
            $icons[] = [
                'icon' => url_live('/new/live/20200414/2020041421492466250.png'),
                'tip'  => $this->add_diamond . ''
            ];
        }
        if ($this->experience > 0) {
            //经验
            $icons[] = [
                'icon' => url_live('/new/live/20200414/2020041421503013628.png'),
                'tip'  => $this->experience . ''
            ];
        }
        if ($this->active_cnt > 0) {
            //活跃
            $icons[] = [
                'icon' => url_live('/new/live/20200414/2020041421500235381.png'),
                'tip'  => $this->experience . ''
            ];
        }

        //没有礼物时候
        if (empty($this->gift_id)) {
            return $icons;
        }

        if ($this->gift_type == self::GIFT_TYPE_CAR) {
            //礼物是坐骑
            /** @var CarModel $model */
            $model = CarModel::where('id', $this->gift_id)->first();
            if (!empty($model)) {
                $icons[] = [
                    'icon' => url_live($model->thumb),
                    'tip'  => '1'
                ];
            }
        } elseif ($this->gift_type == self::GIFT_TYPE_NORMAL) {
            //正常发送的礼物
            /** @var GiftModel $model */
            $model = GiftModel::where('id', $this->gift_id)->first();
            if (!empty($model)) {
                $icons[] = [
                    'icon' => url_live($model->gifticon),
                    'tip'  => '1'
                ];
            }
        }
        return $icons;
    }

}
