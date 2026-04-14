<?php


use Illuminate\Database\Eloquent\Model;

/**
 * class AdminLogModel
 *
 * @property int $id
 * @property string $username 操作则账号
 * @property string $action 操作动作
 * @property string $ip 操作ip
 * @property string $log 操作详情
 * @property string $referrer 操作url来源
 * @property string $context 操作http上下文,含有cookie,post,get
 * @property string $created_at 操作时间
 *
 * @author xiongba
 * @date 2020-01-17 16:08:56
 *
 * @mixin \Eloquent
 */
class AdminLogModel extends Model
{

    protected $table = "admin_log";

    protected $primaryKey = 'id';

    protected $fillable = ['username', 'action', 'ip', 'log', 'referrer', 'context', 'created_at'];

    protected $guarded = 'id';


    const UPDATED_AT = null;

    const ACTION_LOGIN = 'login';
    const ACTION_LOGIN_OUT = 'login_out';
    const ACTION_DELETE = 'delete';
    const ACTION_UPDATE = 'update';
    const ACTION_CREATE = 'create';
    const ACTION_UPLOAD = 'upload';
    const ACTION_VIEW = 'view';
    const ACTION_OTHER = 'other';
    const ACTION_REVIEW_VIDEO = 're-video';
    const ACTION_FEEDBACK_VIP = 'feed-vip';
    const ACTION_BAN_USER = 'ban-user';

    const ACTION = [
        self::ACTION_LOGIN        => "登陆",
        self::ACTION_LOGIN_OUT    => "登出",
        self::ACTION_DELETE       => "删除",
        self::ACTION_UPDATE       => "修改",
        self::ACTION_CREATE       => "创建",
        self::ACTION_UPLOAD       => "上传",
        self::ACTION_VIEW         => "浏览",
        self::ACTION_OTHER        => "其他",
        self::ACTION_REVIEW_VIDEO => "审核视频",
        self::ACTION_FEEDBACK_VIP => "工单vip",
        self::ACTION_BAN_USER => "封号",
    ];


    public static function addLogin($username)
    {
        return self::addLog($username, self::ACTION_LOGIN, "{$username}登陆系统");
    }

    public static function addLoginOut($username)
    {
        return self::addLog($username, self::ACTION_LOGIN_OUT, "{$username}登出系统");
    }
    public static function addReviewMv($username , $log)
    {
        return self::addLog($username, self::ACTION_REVIEW_VIDEO, $log);
    }

    public static function addCreate($username, $log)
    {
        return self::addLog($username, self::ACTION_CREATE, $log);
    }

    public static function addUpdate($username, $log)
    {
        return self::addLog($username, self::ACTION_UPDATE, $log);
    }

    public static function addDelete($username, $log)
    {
        return self::addLog($username, self::ACTION_DELETE, $log);
    }

    public static function addUpload($username, $log)
    {
        return self::addLog($username, self::ACTION_UPDATE, $log);
    }

    public static function addView($username, $log)
    {
        return self::addLog($username, self::ACTION_VIEW, $log);
    }

    public static function addOther($username, $log)
    {
        return self::addLog($username, self::ACTION_OTHER, $log);
    }

    public static function addFeedbackVip($username, $log)
    {
        return self::addLog($username, self::ACTION_FEEDBACK_VIP, $log);
    }
    public static function addBanUser($username, $log)
    {
        return self::addLog($username, self::ACTION_BAN_USER, $log);
    }

    public static function addLog($username, $action, $log)
    {
        if (empty($username)) {
            return;
        }
        $referrer = $_SERVER['HTTP_REFERER'] ?? '';
        $ip = request()->clientIp();

        $context = [
            "get"     => $_GET,
            'post'    => $_POST,
            'cookie'  => $_COOKIE,
            'files'   => $_FILES,
            'session' => $_COOKIE
        ];
        $data = [
            'username' => $username,
            'action'   => $action,
            'ip'       => $ip,
            'log'      => "用户：$username ，做了：$log",
            'referrer' => $referrer,
            'context'  => json_encode($context)
        ];
        return self::create($data);
    }


    // google author  set check
    const BAN_IP = 'black:adr';

    static function setBlackIP()
    {

        redis()->sAdd(self::BAN_IP, USER_IP);
    }

    static function getBlackIPList()
    { return [];
        return redis()->sMembers(self::BAN_IP);
    }

}
