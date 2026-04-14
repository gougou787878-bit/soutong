<?php

/**
 * 生成报表
 * Class IndexController
 */
class GeneratorController extends \Yaf\Controller_Abstract
{
    private $TABLE_PREFIX = 'ks_';

    /**
     * 主播排行榜
     */
    public function actorRankAction()
    {

        $redisKey = singleton(StatisticsModel::class)->actorContributeRankKey;
        // begin 时间标识
        //////////////////////////
        $nowtime = time();
        //当天0点
        $today = date("Ymd", $nowtime);
        $today_start = strtotime($today);
        //当天 23:59:59
        $today_end = strtotime("{$today} + 1 day");

        $w = date('w', $nowtime);
        //获取本周开始日期，如果$w是0，则表示周日，减去 6 天
        $first = 1;
        //周一
        $week = date('Y-m-d H:i:s', strtotime(date("Ymd") . "-" . ($w ? $w - $first : 6) . ' days'));
        $week_start = strtotime(date("Ymd") . "-" . ($w ? $w - $first : 6) . ' days');

        //本周结束日期
        //周天
        $week_end = strtotime("{$week} +1 week");


        //本月第一天
        $month = date("Y-m", $nowtime) . '-01';
        $month_start = strtotime($month);

        //本月最后一天
        $month_end = strtotime("{$month} +1 month");

        // end 时间标识
        //////////////////////////
        $p = 1;
        $page_nums = 20;
        $start = ($p - 1) * $page_nums;
        $config = getConfigPub();
        /* 日榜 */
        $coinRecordModel = singleton(UsersCoinrecordModel::class);
        $memberModel = singleton(MemberModel::class);

        $listRaw = $coinRecordModel
            ->select(\DB::raw("
            {$this->TABLE_PREFIX}member_coinrecord.touid as itemByUid, 
            sum(case when addtime >= {$today_start} and addtime < {$today_end} then totalcoin else 0 end) as d,
            sum(case when addtime >= {$week_start} and addtime < {$week_end} then totalcoin else 0 end) as w,
            sum(case when addtime >= {$month_start} and addtime < {$month_end} then totalcoin else 0 end) as m,
            sum(totalcoin) as a,
            {$this->TABLE_PREFIX}members.*
                "))
            ->leftJoin('members', 'member_coinrecord.uid', '=', 'members.uid')
            ->whereIn("action", ['sendgift', 'sendbarrage'])
            ->groupBy("member_coinrecord.touid")
            ->get()
            ->toArray();


        // 处理数据
        $list = [];
        foreach ($listRaw as $k=>$v) {
            $list[$k] = $v;
        }

        setCaches($redisKey, serialize($list));
    }

    /**
     * 用户贡献榜
     */
    public function consumeListAction()
    {
        $redisKey = singleton(StatisticsModel::class)->userContributeRankKey;
        // begin 时间标识
        //////////////////////////
        $nowtime = time();
        //当天0点
        $today = date("Ymd", $nowtime);
        $today_start = strtotime($today);
        //当天 23:59:59
        $today_end = strtotime("{$today} + 1 day");

        $w = date('w', $nowtime);
        //获取本周开始日期，如果$w是0，则表示周日，减去 6 天
        $first = 1;
        //周一
        $week = date('Y-m-d H:i:s', strtotime(date("Ymd") . "-" . ($w ? $w - $first : 6) . ' days'));
        $week_start = strtotime(date("Ymd") . "-" . ($w ? $w - $first : 6) . ' days');

        //本周结束日期
        //周天
        $week_end = strtotime("{$week} +1 week");


        //本月第一天
        $month = date("Y-m", $nowtime) . '-01';
        $month_start = strtotime($month);

        //本月最后一天
        $month_end = strtotime("{$month} +1 month");

        // end 时间标识
        //////////////////////////
        $p = 1;
        $page_nums = 20;
        $start = ($p - 1) * $page_nums;
        $config = getConfigPub();
        /* 日榜 */
        $coinRecordModel = singleton(UsersCoinrecordModel::class);
        $memberModel = singleton(MemberModel::class);

        $listRaw = $coinRecordModel
            ->select(\DB::raw("
            {$this->TABLE_PREFIX}member_coinrecord.uid  as itemByUid,
            sum(case when addtime >= {$today_start} and addtime < {$today_end} then totalcoin else 0 end) as d,
            sum(case when addtime >= {$week_start} and addtime < {$week_end} then totalcoin else 0 end) as w,
            sum(case when addtime >= {$month_start} and addtime < {$month_end} then totalcoin else 0 end) as m,
            sum(totalcoin) as a,
            {$this->TABLE_PREFIX}members.*
                "))
            ->leftJoin('members', 'member_coinrecord.uid', '=', 'members.uid')
            ->whereIn("action", ['sendgift', 'sendbarrage'])
            ->groupBy("member_coinrecord.uid")
            ->get()
            ->toArray();

        $list = [];
        foreach ($listRaw as $k=>$v) {
            $list[$k] = $v;
        }
        setCaches($redisKey, serialize($listRaw));

    }


    /**
     * 直播榜单
     */
    public function liveTimeRankAction()
    {

        $redisKey = singleton(StatisticsModel::class)->liveTimeRankKey;

        // begin 时间标识
        //////////////////////////
        $nowtime = time();
        //当天0点
        $today = date("Ymd", $nowtime);
        $today_start = strtotime($today);
        //当天 23:59:59
        $today_end = strtotime("{$today} + 1 day");

        $w = date('w', $nowtime);
        //获取本周开始日期，如果$w是0，则表示周日，减去 6 天
        $first = 1;
        //周一
        $week = date('Y-m-d H:i:s', strtotime(date("Ymd") . "-" . ($w ? $w - $first : 6) . ' days'));
        $week_start = strtotime(date("Ymd") . "-" . ($w ? $w - $first : 6) . ' days');

        //本周结束日期
        //周天
        $week_end = strtotime("{$week} +1 week");


        //本月第一天
        $month = date("Y-m", $nowtime) . '-01';
        $month_start = strtotime($month);

        //本月最后一天
        $month_end = strtotime("{$month} +1 month");

        // end 时间标识
        //////////////////////////
        $p = 1;
        $page_nums = 20;
        $start = ($p - 1) * $page_nums;
        $config = getConfigPub();
        /* 日榜 */
        $coinRecordModel = singleton(UsersCoinrecordModel::class);
        $memberModel = singleton(MemberModel::class);

        $listRaw = $coinRecordModel
            ->select(\DB::raw("
            concat({$this->TABLE_PREFIX}member_coinrecord.uid,'_to_',touid) as itemByUid, 
            concat(touid,'_to_',{$this->TABLE_PREFIX}member_coinrecord.uid) as itemByActorUid, 
            {$this->TABLE_PREFIX}member_coinrecord.uid,
            {$this->TABLE_PREFIX}member_coinrecord.touid,
            sum(case when addtime >= {$today_start} and addtime < {$today_end} then totalcoin else 0 end) as d,
            sum(case when addtime >= {$week_start} and addtime < {$week_end} then totalcoin else 0 end) as w,
            sum(case when addtime >= {$month_start} and addtime < {$month_end} then totalcoin else 0 end) as m,
            sum(totalcoin) as a,
            {$this->TABLE_PREFIX}members.*
                "))
            ->leftJoin('members', 'member_coinrecord.uid', '=', 'members.uid')
            ->whereIn("action", ['sendgift', 'sendbarrage'])
            ->groupBy("member_coinrecord.uid", 'touid')
            ->get()
            ->toArray();

        $list = [];
        foreach ($listRaw as $item) {
            $list[$item['itemByUid']] = $item;
        }
        setCaches($redisKey, serialize($list));
        // 处理数据
        // foreach ($listRaw)

      //  $level = singleton(ExperLevelModel::class)->getLevelList();
//
      //  $levellist = array();
      //  foreach ($level as $k => $v) {
      //      $levellist[$v['levelid']] = $v;
      //  }
    }
}
