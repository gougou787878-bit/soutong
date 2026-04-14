<?php


use helper\QueryHelper;
use Illuminate\Database\Eloquent\Model;
use tools\CurlService;

/**
 * class MessageModel
 *
 * @property int $id
 * @property string $from_uuid 用户uuid
 * @property string $to_uuid 用户uuid
 * @property string $title 消息标题
 * @property string $description 消息描述
 * @property int $type 消息类型，1 评论 2 关注 3 系统消息 4. 公告
 * @property int $status 0 未启用 1 启用
 * @property int $created_at
 * @property int $is_read
 * @property int $mv_id
 *
 * @author xiongba
 * @date 2020-05-21 19:57:41
 *
 * @mixin \Eloquent
 */
class MessageModel extends Model
{

    protected $table = "message";

    protected $primaryKey = 'id';

    protected $fillable = ['from_uuid','to_uuid', 'title', 'description', 'type', 'status', 'created_at', 'is_read','mv_id'];

    protected $guarded = 'id';

    public $timestamps = false;

    const TYPE_MV = 1;//视频评论
    const TYPE_ATTENTION = 2;//关注
    const TYPE_SYSTEM = 3;//系统消息
    const TYPE_BULLETION = 4;//公告
    const TYPE_MV_LIKE = 5;//视频喜欢

    //类型列表
    const MSG_TYPE = [
        self::TYPE_MV        => '视频评论',
        self::TYPE_ATTENTION => '粉丝关注',
        self::TYPE_SYSTEM    => '系统通知',
        self::TYPE_BULLETION => '官方公告',
        self::TYPE_MV_LIKE   => '视频喜欢',
    ];

    const MSG_TYPE_ADD = [
        //self::TYPE_MV        => '视频评论',
        //self::TYPE_ATTENTION => '粉丝关注',
        self::TYPE_SYSTEM => '系统通知',
        //self::TYPE_BULLETION => '官方公告',
    ];

    const STAT_ENABLE = 1;
    const STAT_DISABLE = 0;

    const STAT = [
        self::STAT_ENABLE  => '启用',
        self::STAT_DISABLE => '禁用',
    ];

    const SYSTEM_FROM = 'system';

    const SYSTEM_MSG_UUID = '0659c7992e268962384eb17fafe88364';//md5('abc123456')

    const MSG_KEY_SET = 'msg:set';// 数据 keys 建值集合

    /**
     * 清楚缓存
     *
     * @return array|bool
     */
    static function clearCachedKeys()
    {
        $keysData = redis()->sMembers(self::MSG_KEY_SET);
        if ($keysData) {
            return array_map(function ($key) {
                redis()->del($key);
            }, $keysData);
        }
        return false;
    }

    /**
     * 插入消息
     * @param $fromUuid
     * @param $toUuid
     * @param $title
     * @param string $description
     * @param int $mv_id
     * @param int $type
     * @param int $status
     * @return \Illuminate\Database\Eloquent\Model|MessageModel
     */
    public static function createMessage(
        $fromUuid,
        $toUuid,
        $title,
        $description = '',
        $mv_id = 0,
        $type = self::TYPE_SYSTEM,
        $status = self::STAT_ENABLE
    ) {
        return self::create([
            'from_uuid'   => $fromUuid,
            'to_uuid'     => $toUuid,
            'title'       => $title,
            'description' => $description,
            'mv_id'       => $mv_id,
            'type'        => $type,
            'status'      => $status,
            'created_at'  => TIMESTAMP,
            'is_read'     => 0
        ]);
    }

    const SYSTEM_MSG_TPL_MV_PASS = 'mv_pass';
    const SYSTEM_MSG_TPL_MV_REFUSE = 'mv_refuse';
    const SYSTEM_MSG_TPL_POST_REFUSE = 'post_refuse';
    const SYSTEM_MSG_TPL_GIRL_REFUSE = 'girl_refuse';
    const SYSTEM_MSG_TPL_WITHDRAW_YES = 'withdraw_yes';
    const SYSTEM_MSG_TPL_WITHDRAW_NO = 'withdraw_no';
    const SYSTEM_MSG_TPL_RECHARGE_ACTIVE_GIFT = 'recharge_gift';
    const SYSTEM_MSG_TPL_MV_REPORT_1 = 'mv_report_1';
    const SYSTEM_MSG_TPL_MV_REPORT_2 = 'mv_report_2';
    const SYSTEM_MSG_TPL_MV_REPORT_3 = 'mv_report_3';
    const SYSTEM_MSG_TPL_CREATOR_APPLY_FAIL = 'creator_apply_fail';
    const SYSTEM_MSG_TPL_CREATOR_CANCEL = 'creator_cancel';
    const SYSTEM_MSG_TPL_SEND_LCG = 'send_vip_lcg';
    const SYSTEM_MSG_AUTH_CANCEL = 'auth_cancel';
    const SYSTEM_MSG_TPL_POST_PASS = 'post_pass';

    const SYSTEM_MSG_TPL_AUTH_APPLY_FAIL = 'auth_apply_fail';

    //系统消息模板话术
    const SYSTEM_MSG_TPL = [
        self::SYSTEM_MSG_TPL_MV_PASS              => '您的视频：%s 已通过审核并发布~',
        self::SYSTEM_MSG_TPL_MV_REFUSE            => '您的视频：%s 未通过审核,原因：%s',
        self::SYSTEM_MSG_TPL_POST_REFUSE            => '您的帖子：%s 未通过审核,原因：%s',
        self::SYSTEM_MSG_TPL_WITHDRAW_YES         => '您的提现申请已经打款成功,打款金额：%s',
        self::SYSTEM_MSG_TPL_WITHDRAW_NO          => '您的提现申请未通过,失败原因：%s',
        self::SYSTEM_MSG_TPL_RECHARGE_ACTIVE_GIFT => '您参与的充值活动，赠送： %s',
        self::SYSTEM_MSG_TPL_MV_REPORT_1          => '你的视频《%s》被投诉[%s]受理成功，注意，被投诉次数达到%d次后，系统将会扣除您视频收益的10倍蓝票',
        self::SYSTEM_MSG_TPL_MV_REPORT_2          => '你的视频《%s》被投诉[%s]受理成功，注意，被投诉次数达到%d次后，系统将会扣除您视频收益的10倍蓝票并且会取消您的创作者身份',
        self::SYSTEM_MSG_TPL_MV_REPORT_3          => '你的视频《%s》被投诉[%s]受理成功，被投诉次数达到%d次后，系统将会扣除您视频收益的10倍蓝票，被投诉次数达到%d次后，系统将会扣除您视频收益的10倍蓝票并且会取消您的制片人身份',
        self::SYSTEM_MSG_TPL_CREATOR_APPLY_FAIL   => '您的制片人申请被拒绝，原因：%s',
        self::SYSTEM_MSG_TPL_CREATOR_CANCEL       => '您的制片人资格被取消，原因：%s',
        self::SYSTEM_MSG_TPL_SEND_LCG             => '恭喜你获得了「%s」APP的3天体验会员兑换码:%s，下载地址：{%s}";',
        self::SYSTEM_MSG_AUTH_CANCEL       => '您的%s被取消，原因：%s',
        self::SYSTEM_MSG_TPL_AUTH_APPLY_FAIL       => '您的%s申请被拒绝，原因：%s',
        self::SYSTEM_MSG_TPL_GIRL_REFUSE            => '您的约炮帖子：%s 未通过审核,原因：%s',
        self::SYSTEM_MSG_TPL_POST_PASS            => '您的帖子：%s 通过审核',
    ];

    /**
     * @param string $uuid 通知谁
     * @param string $sys_msg_tpl 通知模板
     * @param array $params 通知参数绑定
     * @return Model|MessageModel
     */
    public static function createSystemMessage($uuid, $sys_msg_tpl, $params = [])
    {
        $description = isset(self::SYSTEM_MSG_TPL[$sys_msg_tpl]) ? vsprintf(self::SYSTEM_MSG_TPL[$sys_msg_tpl],
            $params) : '系统通知';
        return self::createMessage(self::SYSTEM_FROM, $uuid, '系统通知', $description, 0, self::TYPE_SYSTEM,
            self::STAT_ENABLE);
    }

    const MSG_LAST_IDS_KEY = 'msgset';

    /**
     * 获取用户最后一条消息ID
     * @param $uuid
     * @return int
     */
    static function getMessageLastID($uuid)
    {
        return (int)redis()->zScore(self::MSG_LAST_IDS_KEY, $uuid);
    }

    /**
     * 设置用户最后msg_id
     *
     * @param $uuid
     * @param $msg_id_score
     * @return int
     */
    static function setMessageLastID($uuid, $msg_id_score)
    {
        return redis()->zAdd(self::MSG_LAST_IDS_KEY, $msg_id_score, $uuid);
    }

    /**
     * 获取用户未读消息数量
     * @param $uuid
     * @param int $type
     * @return int
     */
    public static function getMessageCount($uuid,$type = self::TYPE_SYSTEM)
    {
        $where = [
            ['type','=',$type],
            ['status', '=', self::STAT_ENABLE],
        ];
        if($type == self::TYPE_SYSTEM){//系统消息 根据标识  其他类型根据是否查看
            $last_msg_id = self::getMessageLastID($uuid);
            $where[]=  ['id', '>', $last_msg_id];
            $where[]=['to_uuid', '=', self::SYSTEM_MSG_UUID];
        }else{
            $where[]=['is_read', '=', 0];
            $where[]=['to_uuid', '=', $uuid];
        }
        return cached("msg:{$type}:{$uuid}")->serializerJSON()
            ->setSaveEmpty(true)
            ->expired(300)
            ->fetch(function ()use($where){
            return MessageModel::where($where)->count('id');
        });
    }

    /**
     * 获取最新类型消息
     * @param $uuid
     * @param int $type
     * @return \Illuminate\Database\Eloquent\Builder|Model|object|null
     */
    public static function getLeastMessage($uuid,$type = self::TYPE_SYSTEM){
        $where = [
            ['type','=',$type],
            ['status', '=', self::STAT_ENABLE],
        ];
        if($type == self::TYPE_SYSTEM){//系统消息 根据标识  其他类型根据是否查看
            $where[]=['to_uuid', '=', self::SYSTEM_MSG_UUID];
        }else{
            $where[]=['is_read', '=', 0];
            $where[]=['to_uuid', '=', $uuid];
        }
        return self::where($where)->orderByDesc('id')->first();
    }
    static function converMessageRow($row){
        if(is_null($row)){
            return [
                    'content'=>'',
                    'date'=>'',
            ];
        }
        return [
            'content'=>$row->title,
            'date'=>date('H:i',$row->created_at),
        ];
    }

    static function getMessageList($uuid)
    {

        list($limit, $offset) = QueryHelper::restLimitOffset();
        //errLog("lastMsg:".$last_msg_id);
        $key = "mymsg:{$uuid}:{$limit}-{$offset}";
        redis()->sAdd(self::MSG_KEY_SET, $key);
        $data = cached($key)->serializerJSON()->expired(1800)->fetch(function () use ($uuid, $limit, $offset) {
            $data = self::select([
                'id',
                'title',
                'description',
                'type',
                'created_at'
            ])->where([
                ['status', '=', self::STAT_ENABLE],
                ['type', '=', self::TYPE_SYSTEM],
            ])
                ->whereIn('to_uuid', [$uuid, self::SYSTEM_MSG_UUID])
                ->orderByDesc('id')->limit($limit)->offset($offset)->get();
            if (!$data) {
                return [];
            }
            $data = $data->toArray();
            $data = array_map(function ($item) {
                $item['created_at'] = date('m-d H:i', $item['created_at']);

                $item['image'] = '';
                if ($item['type'] == self::TYPE_SYSTEM) {
                    //$item['image'] = 'http://new_img.ycomesc.com/new/ads/20200528/2020052820132759992.png';
                }
                return $item;
            }, $data);
            $new_last_msg_id = isset($data[0]) ? $data[0]['id'] : '';
            $new_last_msg_id && self::setMessageLastID($uuid, $new_last_msg_id);
            return $data;
        });
        return $data;
        //return array_reverse($data);

    }

    const MSG_PRODUCT = [
        [
            'value'     => '50',
            'title'     => '50金币',
            'sub_title' => '10条',
            'key'       => '1',
        ],
        [
            'value'     => '98',
            'title'     => '98金币',
            'sub_title' => '20条',
            'key'       => '2',
        ],
        [
            'value'     => '138',
            'title'     => '138金币',
            'sub_title' => '30条',
            'key'       => '3',
        ],
        [
            'value'     => '228',
            'title'     => '228金币',
            'sub_title' => '50条',
            'key'       => '4',
        ],
        [
            'value'     => '488',
            'title'     => '488金币',
            'sub_title' => '100条',
            'key'       => '5',
        ],
        [
            'value'     => '888',
            'title'     => '888金币',
            'sub_title' => '200条',
            'key'       => '6',
        ],
    ];

    public function mv()
    {
        return $this->hasOne(MvModel::class, 'id', 'mv_id');
    }
    public function user()
    {
        return $this->hasOne(MemberModel::class, 'uuid', 'from_uuid');
    }


    static function createSendAppVIPMessage($uuid,$app='gv')
    {
        if (true) {
            $url = 'https://app.gtav.info/index.php?m=home&a=create_code';
            $timestamp = time();
            $data = (new CurlService())->curlPost($url,[
                'app'=>SYSTEM_ID,
                'sign'=>md5(hash('sha256', $timestamp.'YJgzi43IutF4DgST')),
                'timestamp'=>$timestamp,
            ]);
            /*{
            "status": 200,
            "msg": "success",
            "data": {
            "code": "253f4104",
            "download": "https://cg.pj3ss.com/",
            "app_name": "茶馆儿"
            }
            }*/
            //print_r($data);die;
            trigger_log("code:".$data);
            $data = json_decode($data,true);
            if($data && $data['code'] == 200){
                $code = $data['data']['code'];//
                $app_name = $data['data']['app_name'];
                $download = $data['data']['download'];
                self::createSystemMessage($uuid, self::SYSTEM_MSG_TPL_SEND_LCG, [
                    'name' => $app_name,
                    'code' => $code,
                    'url'  => $download,
                ]);
            }
        }



    }


}
