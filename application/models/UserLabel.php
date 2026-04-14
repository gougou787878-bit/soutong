<?php
class UserLabelModel extends EloquentModel
{
    protected $table= 'member_label';

    protected $guarded = [];

    /* 获取我的标签 */
    public function getMyLabel($uid)
    {
        $rs = array();
        $list = $this
            ->select("label")
            ->where('touid', $uid)
            ->get();
        $label = array();
        foreach ($list as $k => $v) {
            $v_a = preg_split('/,|，/', $v['label']);
            $v_a = array_filter($v_a);
            if ($v_a) {
                $label = array_merge($label, $v_a);
            }

        }

        if (!$label) {
            return $rs;
        }


        $label_nums = array_count_values($label);

        $label_key = array_keys($label_nums);
        $impession = new ImpressionLabelModel();
        $labels = $impession->getImpressionLabel();

        $order_nums = array();

        foreach ($labels as $k => $v) {
            if (in_array($v['id'], $label_key)) {
                $v['nums'] = (string)$label_nums[$v['id']];
                $order_nums[] = $v['nums'];
                $rs[] = $v;
            }
        }

        array_multisort($order_nums, SORT_DESC, $rs);

        return $rs;

    }

    /* 用户标签 */
    public function getUserLabel($uid, $touid)
    {
        $list = $this->select("label")->where('touid',$touid)->where('uid', $uid)->first();
        if ($list){
            $list = $list->toArray();
        }
        return $list;

    }

    /* 设置用户标签 */
    public function setUserLabel($uid, $touid, $labels)
    {
        $nowtime = time();
        $isexist = $this->where('uid',$uid)
            ->where('touid', $touid)
            ->count();
        if ($isexist) {
            $rs = $this->where('uid', $uid)->where('touid',$touid)->update(['label' => $labels, 'uptime' => $nowtime]);
        } else {
            $data = [
                'uid' => $uid,
                'touid' => $touid,
                'label' => $labels,
                'addtime' => $nowtime,
                'uptime' => $nowtime,
            ];
            $rs = $this->create($data);
        }

        return $rs;

    }
}
