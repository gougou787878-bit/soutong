<?php


namespace type;


/**
 * Class DeviceInfoType
 *
 * @property-read string $SystemVersion
 * @property-read string $bundleId
 * @property-read string $version
 * @property-read string $channel
 * @property-read string $via
 * @property-read string $build_id
 * @property-read string $Language
 * @property-read string $deviceModel
 * @property-read string $oauth_type
 * @property-read string $deviceBrand
 * @property-read string $app_type
 * @property-read string $oauth_new_id
 * @property-read string $oauth_id
 *
 * @package type
 * @author xiongba
 * @date 2019-12-18 21:30:16
 */
class DeviceInfoType implements \JsonSerializable
{
    private $data = [];

    public function __construct($data)
    {
        $lz = [
            'SystemVersion',
            'bundleId',
            'channel',
            'via',
            'build_id',
            'Language',
            'deviceModel',
            'deviceBrand',
            'app_type',
            'oauth_new_id',

            'oauth_type',
            'oauth_id',
            'version',
            'token',
        ];
        foreach ($lz as $v) {
            $this->data[$v] = $data[$v] ?? null;
        }
    }


    public function __get($name)
    {
        return $this->data[$name] ?? null;
    }

    public function jsonSerialize()
    {
        return $this->data;
    }
}