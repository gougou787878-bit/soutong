<?php
/**
 *
 * @example     白票活动逻辑处理
 *
 * @date 2020/3/16
 * @author
 * @copyright kuaishou by KS
 *
 */

namespace service;

class BaipiaoService
{

    const AT_DAY = 7;//过期日期限制

    const TOTAL_BP_KEY = 'bp:sm';
    const BP_RD_KEY = 'bp:rd';//随机白票会员列表
    const BP_HR_KEY = 'bp:hr:';//英雄榜
    const BP_ROW_KEY = 'bp:inf:';//白票信息
    const MAX_PRICE = 55;

    /**
     * 白票进入链接
     * @param $data [
     *              :uid
     *              :huid
     *              ]
     * @return string
     */
    static function getBaiPiaoURL($data)
    {
        $base = getShareURL() . '/index.php?m=bp&a=index&uid=:uid&huid=:huid';
        $base = str_ireplace(array_keys($data), array_values($data), $base);
        return $base;
    }


    const BP_H_WORD = [
        '助力好友，大显身手',
        '路见砍价，随手一砍',
        '看来老夫宝刀已老',
        '吃我一记屠龙宝刀'
    ];


    static function formatPrice($number)
    {
        return number_format($number / 100, 2, '.', '');
    }

    /**
     * 获取倒计时 提示
     * @param $endTime  eg:2020-02-02 20:20:20
     * @return string
     */
    static function getDaoJiShi($endTime)
    {
        $now = TIMESTAMP;
        $sec = strtotime($endTime) - $now;//余下s数
        if ($sec <= 0) {
            return '已过期，砍价金额失效';
        }
        $day = floor($sec / (3600 * 24));
        $hour = floor($sec / 3600 % 24);
        $min = floor($sec / 60 % 60);
        $second = floor($sec % 60);
        $str = '';
        $day && $str .= $day . '天';
        $hour && $str .= $hour . '时';
        $min && $str .= $min . '分';
        $second && $str .= $second . '秒';
        return $str;
    }

    /**
     *  是否绑定电话
     * @param $member
     * @return mixed
     */
    static function checkBindingPhone($member)
    {
        return $member['phone'];
    }

    /**
     * 用户砍价 返回-分
     *
     * @param string $flag
     * @return int
     */
    static function gainBargainPrice($flag = '')
    {
        if ($flag == 'first') {//自砍
            $rand = rand(4000, 4300);
            return $rand;
        } elseif ($flag == 'two') {//首次助力
            $rand = rand(500, 600);
            return $rand;
        } elseif ($flag == 'new') {//新用户助力
            $rand = rand(100, 160);
            return $rand;
        }
        $rand = rand(10, 20);
        return $rand;

    }

    /**
     * 用户第一次参加白票活动
     *
     * @param $member  String
     * @param $bargain String
     * @return \BaipiaoModel|\Illuminate\Database\Eloquent\Model
     */
    static function crateJoinBaipiao($member, $bargain)
    {
        //$bargain = self::gainBargainPrice();
        $at = self::AT_DAY;
        $data = [
            'uid'           => $member['uid'],
            'nickname'      => $member['nickname'] ? $member['nickname'] : \MemberRand::randNickname(),
            'avater'        => $member['thumb'],
            'puid'          => 0,
            'status'        => \BaipiaoModel::STAT_DEFAULT,
            'bargain'       => $bargain,
            'total_bargain' => $bargain,
            'total_invite'  => 1,
            'created_at'    => date('Y-m-d H:i:s', TIMESTAMP),
            'end_at'        => date('Y-m-d H:i:s', strtotime("+{$at} days", TIMESTAMP))
        ];
        return \BaipiaoModel::create($data);
    }

    static function checkJoin($member)
    {
        $uid = $member['uid'];
        return cached(self::BP_ROW_KEY . $uid)->serializerJSON()->expired(900)->fetch(function () use ($uid) {
            return \BaipiaoModel::query()->where('uid', $uid)->first();
        });
    }

    /**
     * @param int $id
     * @param int $uid
     */
    static function checkUpdateBaiPiao($id = 0, $uid = 0)
    {
        $row = \BaipiaoModel::query()->where('id', $id)->first();
        if (is_null($row)) {
            return;
        }
        $update = [];

        if ($row->total_bargain >= self::MAX_PRICE * 100) {
            $update['status'] = \BaipiaoModel::STAT_AT;
        } elseif (strtotime($row->end_at) < TIMESTAMP) {
            $update['status'] = \BaipiaoModel::STAT_EXP;
        }
        if ($update) {
            $row->update($update);
            self::clearRedisCache($row->uid, $uid);
        }
    }

    static function clearRedisCache($uid, $huid)
    {
        redis()->del(self::BP_RD_KEY);//白票会员
        redis()->del(self::TOTAL_BP_KEY);//白票总计砍价
        redis()->del(self::BP_HR_KEY . $uid);//白票英雄榜单
        redis()->del(self::BP_ROW_KEY . $uid);//白票信息
        redis()->del(self::BP_ROW_KEY . $huid);//白票信息
    }


    static function getBaseInfo($baipiaoModel, $member)
    {
        $data = is_object($baipiaoModel) ? $baipiaoModel->toArray() : $baipiaoModel;
        $data['has_phone'] = $member['phone'] ? 1 : 0;
        //$data['has_phone'] = 0; //test
        $data['avater'] = url_avatar($data['avater']);
        $data['daojishi'] = self::getDaoJiShi($data['end_at']);
        $data['bargain'] = self::formatPrice($data['bargain']);
        $data['total_bargain'] = self::formatPrice($data['total_bargain']);
        $data['can_bargain'] = $data['total_bargain'] >= self::MAX_PRICE ? 0 : self::formatPrice(self::MAX_PRICE - $data['total_bargain']);
        return $data;
    }

    /**
     *  助力好友砍价处理
     *
     * @param $member  谁砍
     * @param $helpUser 看谁
     * @param $helpUserBarginInfo 白票信息
     * @param $bargain
     * @param 谁  帮谁  砍多少分钱
     */
    static function helpMemberBaipiao($member, $helpUser, $helpUserBarginInfo, $bargain)
    {
        try {
            $data = [
                'uid'           => $member['uid'],
                'nickname'      => $member['nickname'] ?? \MemberRand::randNickname(),
                'avater'        => $member['thumb'],
                'puid'          => $helpUserBarginInfo['uid'],
                'status'        => \BaipiaoModel::STAT_DEFAULT,
                'bargain'       => $bargain,
                'total_bargain' => 0,
                'total_invite'  => 0,
                'created_at'    => date('Y-m-d H:i:s', TIMESTAMP),
                'end_at'        => $helpUserBarginInfo['end_at']
            ];
            \BaipiaoModel::create($data);
            \BaipiaoModel::where('id', $helpUserBarginInfo['id'])->increment('total_invite', 1,
                ['total_bargain' => \DB::raw("total_bargain+{$bargain}")]);

        } catch (\Exception $e) {
            errLog($e->getMessage());
            return false;
        }
        //check update
        self::checkUpdateBaiPiao($helpUserBarginInfo['id'], $member['uid']);
        return true;
    }

    /**
     * @return bool|string
     */
    static function getTotalBaipiaoAmount()
    {
        $sum = redis()->get(self::TOTAL_BP_KEY);
        if (!$sum) {
            $total = \BaipiaoModel::query()->sum('bargain');
            if ($total) {
                $sum = self::formatPrice($total);
                redis()->set(self::TOTAL_BP_KEY, $sum);
            }
        }
        return $sum;
    }

    static function getBaiPiaoList($limit = 10)
    {
        $data = cached(self::BP_RD_KEY)->serializerPHP()->expired(3600)->fetch(function () use ($limit) {
            return \BaipiaoModel::query()->where('status',
                \BaipiaoModel::STAT_END)->orderByDesc('id')->limit($limit)->get()->toArray();
        });
        if (!$data) {
            for ($i = 0; $i < 5; $i++) {
                $data[] = [
                    'uid'           => rand(567340, 999999),
                    'nickname'      => \MemberRand::randNickname(),
                    'avater'        => \MemberRand::randAvatar(),
                    'puid'          => 0,
                    'status'        => 1,
                    'bargain'       => self::MAX_PRICE * 100,
                    'total_bargain' => self::MAX_PRICE * 100,
                    'total_invite'  => rand(6, 15),
                    'created_at'    => date('Y-m-d H:i:s', TIMESTAMP),
                    'end_at'        => date('Y-m-d H:i:s', TIMESTAMP)
                ];
                usleep(100);
            }
        }
        $data && $data = array_map(function ($item) {
            $item['avater'] = url_avatar($item['avater']);
            $item['bargain'] = self::formatPrice($item['bargain']);
            $item['total_bargain'] = self::formatPrice($item['total_bargain']);
            return $item;
        }, $data);
        return $data;
    }

    static function getBaiPiaoHeroList($uid, $limit = 100)
    {
        $data = cached(self::BP_HR_KEY . $uid)->serializerPHP()->expired(3600)->fetch(function () use ($uid, $limit) {
            return \BaipiaoModel::query()->where('uid', $uid)->orWhere('puid',
                $uid)->orderByDesc('id')->limit($limit)->get()->toArray();
        });
        $data && $data = array_map(function ($item) {
            $item['avater'] = url_avatar($item['avater']);
            $item['word'] = self::BP_H_WORD[array_rand(self::BP_H_WORD)];
            $item['bargain'] = self::formatPrice($item['bargain']);
            $item['total_bargain'] = self::formatPrice($item['total_bargain']);
            return $item;
        }, $data);
        return $data;
    }


}