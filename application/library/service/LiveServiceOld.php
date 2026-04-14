<?php


namespace service;


use DB;
use Exception;
use ExperLevelModel;
use GiftModel;
use LiveModel;
use MemberModel;
use StatisticsModel;
use TaskModel;
use UsersCoinrecordModel;
use UserVoterecordModel;

class LiveServiceOld
{


    /**
     * 获取多少个排行榜的用户头像
     * @param int $limit 获取多少个
     * @return array 返回获取到用户的头像
     */
    public function getTopUserAvatar(int $limit)
    {
        $model = new StatisticsModel;
        $face = $model->userFace();
        $uuidArray = array_slice($face['day'], 0, $limit);
        return $this->getUsersAvatar($uuidArray);
    }


    /**
     * 获取指定用户的头像，并且保持uuid的索引顺序
     * @param array $uuidArray
     * @return array
     * @author xiongba
     * @date 2020-03-07 20:49:48
     */
    protected function getUsersAvatar(array $uuidArray)
    {
        $members = MemberModel::whereIn('uuid', $uuidArray)->get(['thumb', 'uuid'])->toArray();
        $members = array_reindex($members, 'uuid');
        $results = [];
        foreach ($uuidArray as $uuid) {
            if (isset($members[$uuid])) {
                $results[] = url_avatar($members[$uuid]['thumb']);
            }
        }
        return $results;
    }





}