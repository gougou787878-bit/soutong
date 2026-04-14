<?php

class ConfigModel
{

    public static function instance(){
        return new self();
    }


    private $config = [
        'apk_ver' => '1.0.3',
        'apk_url' => 'https://download.xxingqu.com/kslive/1.0.3.apk',
        'apk_des' => '优化短视频观看体验，直播体验',
        'ipa_ver' => '1.0.0',
        'ipa_url' => 'https://a.kslive.tv',
        'ipa_des' => '有新版本，是否更新',
        //'site' => 'http://store.ksapi001.me:2052',
        'site' => 'https://a.kslive.tv',
        'name_coin' => '金币',
        'name_votes' => '妹币',
        'maintain_switch' => '0',
        'maintain_tips' => '',
        // 'live_time_coin' => '5,10,20,30,40,50,60',
        //'live_time_coin' => '5,10,15,20',
        //'live_type' => '0;普通房间,1;密码房间,2;门票房间,3;计时房间',
        'ios_forced_update' => '0',
        'android_forced_update' => '0',
        'limit' => 24,
        'pay_sort' => 'online|agent'
    ];

    /**
     * @param bool $key
     * @return array|mixed
     */
    public function getConfig($key = false)
    {
        $config = getCaches('ks_config');
        if ($config){
           $this->config = array_merge($this->config,$config);
        }
        if ($key) {
            return $this->config[$key];
        }
        //version
        $android = VersionModel::getleastVersion(VersionModel::TYPE_ANDROID,VersionModel::STATUS_SUCCESS);
        if($android){
            $this->config['apk_ver'] = $android['version'];
            $this->config['apk_url'] = $android['apk'];
            $this->config['apk_des'] = $android['tips'];
        }

        $version_ios = VersionModel::getleastVersion(VersionModel::TYPE_IOS,VersionModel::STATUS_SUCCESS,VersionModel::CHAN_TF);
        if($version_ios){
            $this->config['ipa_ver'] = $version_ios['version'];
            $this->config['ipa_url'] = $version_ios['apk'];
            $this->config['ipa_des'] = $version_ios['tips'];
            $deprecatedVersion = setting('ios.deprecated.version');
            if ($version_ios['must'] == VersionModel::MUST_UPDATE ||
                !empty($deprecatedVersion) &&
                version_compare($deprecatedVersion, $version_ios['version'], '<') &&
                version_compare(request()->getDevice()->version, $deprecatedVersion, '<=')) {
                $this->config['ios_forced_update'] = '1';
            }
        }

        if (($_POST['build_id'] ?? 0) == '103' and $_POST['oauth_type'] == 'ios') {
            $this->config['ipa_ver'] = '1.0.2';
            $this->config['ipa_des'] = '修复iOS系统退出账号被重置的问题';
            $this->config['ios_forced_update'] = '1';
        }
        $this->config['site'] = getShareURL();
        return $this->config;
    }

    public function setConfig($data){
        setCaches('ks_config',$data);
    }
}