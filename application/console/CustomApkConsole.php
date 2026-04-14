<?php

namespace App\console;


use VersionModel;

class CustomApkConsole extends AbstractConsole
{
    public $name = "apk-download";

    public $description = '防毒包下载';

    public function process($argc, $argv)
    {
        $antivirus_android = VersionModel::get_main_android_least_version_v2(VersionModel::CUSTOM_OK);
        if ($antivirus_android) {
            $version_and = $antivirus_android->apk;
            VersionModel::defend_apk($version_and, 1);
        }
    }

}