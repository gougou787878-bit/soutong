<?php

/**
 * class UserDownloadModel
 *
 * @property int $aff aff
 * @property string $created_at 创建时间
 * @property int $id
 * @property int $total 总下载次数
 * @property int $total_money 累计充值
 * @property string $updated_at 更新时间
 * @property int $val 下载次数
 *
 * @author xiongba
 * @date 2024-03-06 11:58:33
 *
 * @mixin \Eloquent
 */
class UserDownloadModel extends EloquentModel
{
    protected $table = "user_download";
    protected $primaryKey = 'id';
    protected $fillable = [
        'aff',
        'created_at',
        'total',
        'total_money',
        'updated_at',
        'val'
    ];

    protected $guarded = 'id';
    public $timestamps = true;

    public static function findByAff($aff){
        return self::where('aff', $aff)->first();
    }

    public static function addDownloadNum($aff, $num, $amount = 0){
        $amount = intval($amount / 100);
        $row = self::where('aff', $aff)->first();
        if ($row){
            $row->val += $num;
            $row->total += $num;
            $row->total_money += $amount;
            $row->updated_at = \Carbon\Carbon::now();
            $row->save();
        }else{
            $row = self::make();
            $row->aff = $aff;
            $row->val = $num;
            $row->total = $num;
            $row->total_money = $amount;
            $row->created_at = \Carbon\Carbon::now();
            $row->updated_at = \Carbon\Carbon::now();
            $row->save();
        }

        return true;
    }
}
