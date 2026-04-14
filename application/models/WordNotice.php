<?php

/**
 * class WordNoticeModel
 *
 * @property int $id
 * @property string $name 标签组
 * @property int $status 状态
 * @property int $sort 排序
 *
 *
 * @date 2022-03-21 10:42:35
 *
 * @mixin \Eloquent
 */
class WordNoticeModel extends EloquentModel
{
    protected $table = "word_notice";
    protected $primaryKey = 'id';
    protected $fillable = [
        'position',
        'content',
        'status'
    ];

    protected $guarded = 'id';
    public $timestamps = false;

    const CK_NOTICE_POS = 'ck:notice:pos:%d';
    const GP_NOTICE_POS = 'ck:notice:pos';
    const CN_NOTICE_POS = '跑马灯';

    const STATUS_SUCCESS = 1;
    const STATUS_FAIL = 0;
    const STATUS = [
        self::STATUS_FAIL => '禁用',
        self::STATUS_SUCCESS => '启用',
    ];

    const POSITION_PAY_VIP = 1;

    const POSITION = [ // 广告位置
        self::POSITION_PAY_VIP => '充值',
    ];

    public static function getNoticeByPosition($position)
    {
        return cached(sprintf(self::CK_NOTICE_POS, $position))
            ->group(self::GP_NOTICE_POS)
            ->chinese(self::CN_NOTICE_POS)
            ->fetchJson(function () use ($position) {
                return self::query()
                    ->where('position', $position)
                    ->value('content');
            });
    }

    public static function clearCache($position)
    {
        cached(sprintf(self::CK_NOTICE_POS, $position))->clearCached();
    }
}
