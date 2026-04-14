<?php


namespace service;


class NavConfigService
{


    public function builderPos(\MemberModel $member, $ver, $pos)
    {
        $cached = cached('app:nav:' . $member['uid'] . ':' . $ver)->expired(3600)
            ->compress([config('img.img_live_url')]);
        $jsonStr = $cached->suffix($pos)->fetch(function () use ($pos, $member, $ver) {
            return json_encode(\AppNavModel::getPos($member, $ver, $pos));
        });
        return $this->builderNav($member, $jsonStr);
    }

    /**
     * @param \MemberModel $memberModel
     * @param string $navJson
     * @param boolean $run34_44
     * @return array
     */
    public function builderNav(\MemberModel $memberModel, string $navJson , $run34_44 = true): array
    {
        $memberAttr = $memberModel->toArray();
        foreach ($memberAttr as $k => $v) {
            $navJson = str_replace('{' . $k . '}', $v, $navJson);
        }
        $navJson = preg_replace_callback('#\{->([^\}]+)\}#i', function ($v) use ($memberModel) {
            list($s, $d) = $v;
            if (method_exists($memberModel, $d)) {
                return $memberModel->$d();
            } elseif (0 === strpos($d, 'setting.')) {
                return setting(substr($d, 8), $s);
            } else {
                return $s;
            }
        }, $navJson);
        $ary = json_decode($navJson, 1);
        if ($run34_44) {
            $resetValue = false;
            foreach ($ary as $k => $item) {
                //在用户是代理推广的情况下，不限时代理赚钱
                if ($item['id'] == 4 && !empty($memberModel->build_id)) {
                    unset($ary[$k]);
                    $resetValue = true;
                    break;
                }
            }
            if ($resetValue) {
                $ary = array_values($ary);
            }
        }

        return  $ary;
    }
    
    public function topNav(\MemberModel $member, $ver)
    {
        return json_encode(\AppNavModel::getTop($member, $ver));
        //return $this->builderNav($member, json_encode(\AppNavModel::getTop($member, $ver)));
    }

    public function leftNav(\MemberModel $member, $ver)
    {
        return json_encode(\AppNavModel::getLeft($member, $ver));
        //return $this->builderNav($member, json_encode(\AppNavModel::getLeft($member, $ver)));
    }

    public function bottomNav(\MemberModel $member, $ver)
    {
        return json_encode(\AppNavModel::getBottom($member, $ver));
        //return $this->builderNav($member, json_encode(\AppNavModel::getBottom($member, $ver)));
    }

    public function rightNav(\MemberModel $member, $ver)
    {
        return json_encode([]);
        //return $this->builderNav($member, json_encode(\AppNavModel::getRight($member, $ver)));
    }

}