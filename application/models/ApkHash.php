<?php


use Illuminate\Database\Eloquent\Model;

/**
 * class ApkHashModel
 *
 * @property int $id
 * @property string $package_name 包名
 * @property string $package_hash apk的md5值
 * @property int $created_at
 *
 * @mixin \Eloquent
 */
class ApkHashModel extends Model
{

    protected $table = "apk_hash";

    protected $primaryKey = 'id';

    protected $fillable = ['package_name', 'package_hash', 'created_at'];

    protected $guarded = 'id';

    public $timestamps = false;

    /**
     * @param $package_name
     * @return \Illuminate\Database\Eloquent\Builder|Model|object|null
     */
    static function hasPackage($package_name)
    {
        return self::where(['package_name' => trim($package_name)])->first();
    }

    /**
     * @param $package_name
     * @param $package_hash
     * @return ApkHashModel|\Illuminate\Database\Eloquent\Builder|Model|object|null
     */
    static function addPackage($package_name, $package_hash)
    {
        $package_name = trim($package_name);
        $package_hash = trim($package_hash);
        if ($pk = self::hasPackage($package_name)) {
            return $pk;
        }
        return self::create([
            'package_name' => $package_name,
            'package_hash' => $package_hash,
            'created_at'   => time()
        ]);
    }

}
