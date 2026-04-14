<?php

use Illuminate\Database\Eloquent\Model;

/**
 * class MemberMuteLogModel
 * 
 * 
 * @property int $id  
 * @property int $aff  
 * @property string $content 发布的信息 
 * @property int $type  
 * @property string $created_at  
 * @property string $updated_at  
 * 
 * 
 *
 * @mixin \Eloquent
 */
class MemberMuteLogModel extends Model
{
    protected $table = 'member_mute_log';
    protected $primaryKey = 'id';
    protected $fillable = ['id', 'aff', 'content', 'type', 'created_at', 'updated_at'];
    protected $guarded = 'id';
    public $timestamps = false;

    const TYPE_URL = 0;
    const TYPE_FONT = 1;
    const TYPE_WORD = 2;
    const TYPE_PINYIN = 3;

    const TYPE_TIPS = [
        self::TYPE_URL => '网站',
        self::TYPE_FONT => '特殊字体',
        self::TYPE_WORD => '关键字',
        self::TYPE_PINYIN => '拼音',
    ];

    public static function addLog($type, $aff, $content)
    {
        self::create([
            'aff' => $aff,
            'type' => $type,
            'content' => $content,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }
}