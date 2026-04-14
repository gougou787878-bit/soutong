<?php

/**
 * 主播收益
 * Class IndexController
 */
class ProfitController extends \Yaf\Controller_Abstract
{
    use \repositories\UsersRepository;

    private $TABLE_PREFIX = 'ks_';

    /**
     * 主播排行榜
     */
    public function indexAction()
    {
        //昨天到当前时间开过直播的主播
        $starttime = strtotime(date('Y-m-d'))-86400;
        $allAchor = \AuthModel::query()->where('uptime',">=",$starttime)->get()->toArray();

        foreach ($allAchor as $row){
            $member = \MemberModel::query()->where('uid',$row['uid'])->first();
            $anchorWithdraw = \UserWithdrawAnchorModel::query()->where('uid',$row['uid'])->first();
            $family = \FamilyMemberModel::query()->where('uid' , $row['uid'])->where('state',2)->first();
            \DB::beginTransaction();
            try {
                $data = ['votes'=>$member->votes,'votes_total'=>$member->votes_total];
                if ($family){  //当前角色家族主播
                    if ($anchorWithdraw){
                        // 主播分成
                        $addvotes       = intval(($member->votes-$anchorWithdraw->votes)*(100-$family->divide_family)/100);
                        $addfamilyvotes = intval(($member->votes-$anchorWithdraw->votes)*$family->divide_family/100);
                        $data['withdraw_votes']         = DB::raw("withdraw_votes + {$addvotes}");
                        $data['withdraw_votes_total']   = DB::raw("withdraw_votes_total + {$addvotes}");
                        $data['updated_at']             = time();
                        $addvotes && \UserWithdrawAnchorModel::query()->where('uid',$row['uid'])->update($data);
                    }else{
                        $addvotes       = intval($member->votes * (100 - $family->divide_family)/100);
                        $addfamilyvotes = intval($member->votes * $family->divide_family/100);
                        $data['withdraw_votes']         = $addvotes;
                        $data['withdraw_votes_total']   = $addvotes;
                        $data['uid']                    = $member->uid;
                        $data['updated_at']             = time();
                        $data['created_at']             = time();
                        $member->votes && \UserWithdrawAnchorModel::query()->insert($data);
                    }
                    $addfamilyvotes && $this->addFamilyVotes($addvotes,$row['uid'],$family->familyid,$addfamilyvotes);
                }else{   //当前角色主播
                    if ($anchorWithdraw){
                        $addvotes = $member->votes-$anchorWithdraw->votes;
                        $data['withdraw_votes']         = DB::raw("withdraw_votes + {$addvotes}");
                        $data['withdraw_votes_total']   = DB::raw("withdraw_votes_total + {$addvotes}");
                        $data['updated_at']             = time();
                        $addvotes && \UserWithdrawAnchorModel::query()->where('uid',$row['uid'])->update($data);
                    }else{
                        $data['withdraw_votes']         = $member->votes;
                        $data['withdraw_votes_total']   = $member->votes;
                        $data['uid']                    = $member->uid;
                        $data['updated_at']             = time();
                        $data['created_at']             = time();
                        $member->votes && \UserWithdrawAnchorModel::query()->insert($data);
                    }
                }
                \DB::commit();
            } catch (Exception $exception) {
                \DB::rollBack();
            }
        }
    }

    // 主播收益
    public function addFamilyVotes($addvotes,$anchorid,$familyid,$addfamilyvotes){
        $member = \MemberModel::query()->where('uid',$familyid)->first();
        $anchorWithdraw = \UserWithdrawAnchorModel::query()->where('uid',$familyid)->first();
        if ($anchorWithdraw){
            $anchorWithdrawData['withdraw_votes']         =  DB::raw("withdraw_votes + {$addfamilyvotes}");
            $anchorWithdrawData['withdraw_votes_total']   =  DB::raw("withdraw_votes + {$addfamilyvotes}");
            $anchorWithdrawData['votes_total']            =  DB::raw("withdraw_votes + {$addfamilyvotes}");
            $anchorWithdrawData['votes']                  =  DB::raw("withdraw_votes + {$addfamilyvotes}");
            $anchorWithdrawData['updated_at']             = time();
            \UserWithdrawAnchorModel::query()->where('uid',$familyid)->update($anchorWithdrawData);
        }else{
            $anchorWithdrawData['withdraw_votes']         = $addfamilyvotes;
            $anchorWithdrawData['withdraw_votes_total']   = $addfamilyvotes;
            $anchorWithdrawData['votes_total']            = $addfamilyvotes;
            $anchorWithdrawData['votes']                  = $addfamilyvotes;
            $anchorWithdrawData['uid']                    = $familyid;
            $anchorWithdrawData['created_at']             = time();
            $anchorWithdrawData['updated_at']             = time();
            \UserWithdrawAnchorModel::query()->insert($anchorWithdrawData);
        }
        $data['votes']         = DB::raw("votes + {$addfamilyvotes}");
        $data['votes_total']   = DB::raw("votes_total + {$addfamilyvotes}");
        \MemberModel::query()->where('uid',$familyid)->update($data);

        $familyprofit = [
            'uid'            => $anchorid,
            'familyid'       => $familyid,
            'time'           => date('Y-m-d'),
            'profit_anthor'  => $addvotes,
            'total'          => $addfamilyvotes + $addvotes,
            'profit'         => $addfamilyvotes,
            'addtime'        => time(),
        ];
        \UserFamilyProfitModel::query()->insert($familyprofit);
    }
}