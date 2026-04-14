<?php

namespace service;


use ConfigModel;
use ExperLevelAnchorModel;
use ExperLevelModel;

/**
 *
 * Class UserLevelService
 * @package service
 * @author xiongba
 */
class UserLevelService
{


    public function getUserLevel(\MemberModel $member)
    {
        $configUrl = ConfigModel::instance()->getConfig('site');
        $curredExp = (int)$member->consumption;

        $level = ExperLevelModel::getLevel($curredExp);
        $config = array_reindex(ExperLevelModel::getConfig(), 'levelid');
        $upExp = $config[$level]['level_up'];

        $percent = $this->callPercent($config, $level, $curredExp);

        return [
            'exp'       => $curredExp,
            'upExp'     => $upExp,
            'level'     => $level,
            'percent'   => $percent * 100,
            'nextlevel' => $level + 1,
            'distance'  => $upExp - $curredExp,
            'thumb'     => url_avatar($member->thumb),
            'url'       => $configUrl . '/index.php?&m=Page&a=level',
        ];
    }


    public function getAnchorLevel(\MemberModel $member)
    {
        $configUrl = ConfigModel::instance()->getConfig('site');

        $curredExp = (int)$member->votes_total;
        $level = ExperLevelAnchorModel::getLevel($curredExp);
        $config = array_reindex(ExperLevelAnchorModel::getConfig(), 'levelid');
        $upExp = $config[$level]['level_up'];

        $percent = $this->callPercent($config, $level, $curredExp);

        return [
            'exp'       => $curredExp,
            'upExp'     => $upExp,
            'level'     => $level,
            'percent'   => $percent * 100,
            'nextlevel' => $level + 1,
            'distance'  => $upExp - $curredExp,
            'thumb'     => url_avatar($member->thumb),
            'url'       => $configUrl . '/index.php?&m=Page&a=levelAnchor',
        ];
    }


    protected function callPercent($expConfig, $level, $curredExp)
    {
        $prevUpExp = $level == 1 ? 0 : $expConfig[$level - 1]['level_up'];
        //2. 获取用户升级需要的累计经验值
        $upExp = $expConfig[$level]['level_up'];
        //3. 计算出当前等级升级所需要的经验值
        $needExp = $upExp - $prevUpExp;
        //4. 计算出在升级后从新累积的经验
        $hasExp = $curredExp - $prevUpExp;
        //5. 算出占比
        return div_allow_zero($hasExp, $needExp);
    }
}