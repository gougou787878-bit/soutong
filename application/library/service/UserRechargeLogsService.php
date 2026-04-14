<?php
/**
 * Sample file comment
 *
 * PHP version 7.1.0
 *
 * This file demonstrates the rich information that can be included in
 * in-code documentation through DocBlocks and tags.
 *
 * @file ${FILE_NAME}
 * @author xiongba
 * @version 1.0
 * @package
 */

namespace service;


use helper\QueryHelper;

class UserRechargeLogsService
{

    /**
     * @var \MemberModel
     */
    protected $member;

    /**
     * UserRechargeLogsService constructor.
     * @param $member
     * @author xiongba
     */
    public function __construct($member)
    {
        $this->member = $member;
    }

    /**
     * 用户充值记录
     * @param int $lastIndex
     * @param null $total
     * @return array
     * @author xiongba
     */
    public function getLogs( &$lastIndex, &$total = null)
    {
        list($limit, $offset) = QueryHelper::restLimitOffset();
        $query = \OrdersModel::queryMember($this->member->uuid)
            ->orderBy('id', 'desc')->limit($limit)->offset($offset);
        if (func_num_args() >= 2) {
            $total = $query->count();
        }

        if ($lastIndex) {
            $query->where('id', '<', $lastIndex);
            $query->offset(0);
        }
        return $query->get()->map(function ($item) use (&$lastIndex) {
            /** @var \OrdersModel $item */
            $item->addHidden(['pay_url', 'channel', 'uuid', 'app_order']);
            $item->updated_str = date('Y-m-d H:i:s', $item->updated_at);
            $item->created_str = date('Y-m-d H:i:s', $item->created_at);
            $item->amount_rmb = sprintf("%.02f", $item->amount / 100);
            $item->pay_amount_rmb = sprintf("%.02f", $item->pay_amount / 100);
            $item->payway = $item->getOldPayType(); //适应app的
            /*$item->url = sprintf("%sindex.php?m=Feedback&a=index&uid=%s&token=%s&orderSn=%d",
                setting('HTML5.site'),
                $this->member->uid,
                $this->member->token(),
                $item->order_id
            );*/
            $lastIndex = $item->id;
            return $item;
        })->toArray();

    }


}