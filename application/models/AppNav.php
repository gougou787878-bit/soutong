<?php


use Illuminate\Database\Eloquent\Model;

/**
 * class AppNavModel
 *
 * @property int $pk
 * @property int $id 导航缩影
 * @property string $name 名称
 * @property string $pos 位置
 * @property int $type 类型
 * @property string $icon 图标
 * @property string $icon_press_transition 图标按下的过渡svg
 * @property string $icon_press 图标按下
 * @property string $icon_release_transition 图标松开的过渡svg
 * @property int $status
 * @property string $url 链接
 * @property string $exp 附加说明
 * @property string $tip 新消息数量
 * @property int $pid
 * @property int $user_level 用户等级
 * @property int $vip_level 用户vip等级
 * @property int $live_role 直播角色，-1，全部，0用户，1主播
 * @property int $ver 导航版本
 * @property int $sort 排序
 *
 * @author xiongba
 * @date 2020-03-12 11:41:13
 *
 * @mixin \Eloquent
 */
class AppNavModel extends Model
{

    protected $table = "app_nav";

    protected $primaryKey = 'pk';

    protected $fillable = [
        'pk',
        'id',
        'name',
        'pos',
        'type',
        'icon',
        'icon_press_transition',
        'icon_press',
        'icon_release_transition',
        'status',
        'url',
        'exp',
        'tip',
        'pid',
        'vip_level',
        'user_level',
        'sort',
        'live_role',
        'ver'
    ];

    protected $hidden = [];

    protected $guarded = 'id';

    public $timestamps = false;

    const TYPE_APP = 0;
    const TYPE_H5 = 1;
    const TYPE_DIALOG = 2; //类型
    const TYPE = [
        self::TYPE_APP    => 'app类型',
        self::TYPE_H5     => 'h5链接',
        self::TYPE_DIALOG => 'dialog',
    ];


    const STATUS_BAN = 0;
    const STATUS_OK = 1;
    const STATUS = [
        self::STATUS_BAN => '关闭',
        self::STATUS_OK  => '正常',
    ];

    const POS_NULL = 'null';
    const POS_LEFT = 'left';
    const POS_TOP = 'top';
    const POS_RIGHT = 'right';
    const POS_BOTTOM = 'bottom';
    const POS_TOP_ICON = 'top_icon';
    const POS_CENTER = 'center';
    const POS = [
        self::POS_NULL   => '默认',
        self::POS_LEFT   => '左测',
        self::POS_TOP    => '上部',
        self::POS_RIGHT  => '右侧',
        self::POS_BOTTOM => '底部',
        self::POS_TOP_ICON => '顶部图标',
        self::POS_CENTER => '个人中心-中间',
    ];


    public static function queryBasis(MemberModel $memberModel, int $ver)
    {
        $where = [
            'status' => self::STATUS_OK,
            'ver'    => $ver
        ];
        return self::whereIn('user_level', [-1, $memberModel->level])
            ->whereIn('vip_level', [-1, $memberModel->vip_level])
            ->whereIn('live_role', [-1, $memberModel->auth_status])
            ->where($where)
            ->orderBy('sort', 'asc');
    }


    public static function getLeft(MemberModel $memberModel, int $ver)
    {
        return self::formatNav(self::queryBasis($memberModel, $ver)
            ->where('pos', self::POS_LEFT)
            ->where('pid', -1), $memberModel, $ver);
    }


    public static function getRight(MemberModel $memberModel, int $ver)
    {
        return self::formatNav(self::queryBasis($memberModel, $ver)
            ->where('pos', self::POS_RIGHT)
            ->where('pid', -1), $memberModel, $ver);
    }

    public static function getTop(MemberModel $memberModel, int $ver)
    {
        return self::formatNav(self::queryBasis($memberModel, $ver)
            ->where('pos', self::POS_TOP)
            ->where('pid', -1), $memberModel, $ver);
    }

    public static function getBottom(MemberModel $memberModel, int $ver)
    {
        return self::formatNav(self::queryBasis($memberModel, $ver)
            ->where('pos', self::POS_BOTTOM)
            ->where('pid', -1), $memberModel, $ver);
    }

    public static function getPos(MemberModel $memberModel, int $ver , $pos)
    {
        return self::formatNav(self::queryBasis($memberModel, $ver)
            ->where('pos', $pos)
            ->where('pid', -1), $memberModel, $ver);
    }


    /**
     * 获取指定版本的最近的老版本，含指定版本
     * @param $ver
     * @return int
     * @author xiongba
     */
    public static function latelyOldVer($ver){
        $model = \AppNavModel::where('ver', $ver)
            ->groupBy('ver')
            ->orWhere('ver', '<', $ver)
            ->orderBy('ver', 'desc')
            ->first('ver');
        return $model->ver;
    }

    /**
     * @param \Illuminate\Database\Query\Builder $queryBuilder
     * @param MemberModel $memberModel
     * @param int $ver
     * @return array
     * @author xiongba
     * @date 2020-03-25 14:55:45
     */
    protected static function formatNav($queryBuilder, MemberModel $memberModel, int $ver)
    {
        $all = $queryBuilder->get();
        /** @var self $item */
        if ($all->isNotEmpty()) {
            foreach ($all as $item) {
                $item->addHidden('pk', 'pos', 'pid', 'user_level', 'vip_level', 'live_role', 'ver', 'sort');
                $list = self::formatNav(self::queryBasis($memberModel, $ver)->where('pid', $item->id), $memberModel,
                    $ver);
                //$item->icon = url_resource($item->icon, config('img.img_live_url'));
                $item->icon = url_live($item->icon);
                //$item->icon_press = url_resource($item->icon_press, config('img.img_live_url'));
                $item->icon_press = url_live($item->icon_press);
                //$item->icon_press_transition = url_resource($item->icon_press_transition, config('img.img_live_url'));
                $item->icon_press_transition = url_live($item->icon_press_transition);
                $item->icon_release_transition = url_live($item->icon_release_transition);
                //$item->icon_release_transition = url_resource($item->icon_release_transition, config('img.img_live_url'));
                $item->list = $list;
                $item->have_list = !empty($list);
            }
        }
        if (!empty($all)) {
            return $all->toArray();
        } else {
            return [];
        }
    }


}
