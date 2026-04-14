<?php

/**
 * class LiveModel
 *
 * @property int $id
 * @property string $username 用户名
 * @property string $language 语言
 * @property string $thumb 头像
 * @property string $gender 性别
 * @property string $country 国家
 * @property int $type 收费类型
 * @property int $coins 收费金币
 * @property string $cover 封面
 * @property string $f_cover 原封面
 * @property string $hls 播放地址
 * @property string $model_id 主播ID
 * @property string $show 秀类型
 * @property string $tag 标签
 * @property int $status 状态
 * @property int $favorite_oct 原站收藏
 * @property int $view_oct 原站观看数
 * @property int $favorite_count 假收藏
 * @property int $favorite_ct 真收藏
 * @property int $real_favorite_count 真观看
 * @property int $view_count 假观看
 * @property int $comment_ct 评论数
 * @property int $fr_width 帧宽
 * @property int $fr_height 帧高
 * @property int $pay_ct 支付次数
 * @property int $pay_coins 支付金币数
 * @property int $reward_ct 打赏次数
 * @property int $reward_coins 打赏额
 * @property int $sort 排序
 * @property string $intro 简介
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 * @property int $real_like_count 真点赞
 * @property int $like_count 显示点赞
 * @mixin \Eloquent
 */
class LiveModel extends EloquentModel
{
    protected $primaryKey = 'id';
    protected $table = "live";
    protected $fillable = [
        'username',
        'language',
        'thumb',
        'gender',
        'country',
        'type',
        'coins',
        'cover',
        'f_cover',
        'hls',
        'model_id',
        'show',
        'tag',
        'status',
        'favorite_oct',
        'view_oct',
        'favorite_count',
        'real_favorite_count',
        'view_count',
        'real_view_count',
        'comment_ct',
        'fr_width',
        'fr_height',
        'pay_ct',
        'pay_coins',
        'reward_ct',
        'reward_coins',
        'intro',
        'sort',
        'created_at',
        'updated_at',
        'like_count',
        'real_like_count',
    ];

    public $timestamps = true;

    const SHOW_GROUPSHOW = 'groupShow';
    const SHOW_PUBLIC = 'public';
    const SHOW_OFF = 'off';
    const SHOW_IDLE = 'idle';
    const SHOW_VIRTUALPRIVATE = 'virtualPrivate';
    const SHOW_P2P = 'p2p';
    const SHOW_P2PVOICE = 'p2pVoice';
    const SHOW_PRIVATE = 'private';
    const SHOW_TIPS = [
        self::SHOW_GROUPSHOW      => '群秀',
        self::SHOW_PUBLIC         => '公开',
        self::SHOW_OFF            => '下线',
        self::SHOW_IDLE           => '离开',
        self::SHOW_VIRTUALPRIVATE => 'VR私密秀',
        self::SHOW_P2P            => '一对一秀',
        self::SHOW_P2PVOICE       => '一对一语音',
        self::SHOW_PRIVATE        => '私密秀',
    ];

    const GENDER_MALE = 'male';
    const GENDER_FEMALE = 'female';
    const GENDER_TRANNY = 'tranny';
    const GENDER_MALEFEMALE = 'maleFemale';
    const GENDER_MALES = 'males';
    const GENDER_FEMALES = 'females';
    const GENDER_TRANNIES = 'trannies';
    const GENDER_MALETRANNY = 'maleTranny';
    const GENDER_FEMALETRANNY = 'femaleTranny';
    const GENDER_TIPS = [
        self::GENDER_MALE         => '男性',
        self::GENDER_FEMALE       => '女性',
        self::GENDER_TRANNY       => '变性人',
        self::GENDER_MALEFEMALE   => '男 女',
        self::GENDER_MALES        => '男性',
        self::GENDER_FEMALES      => '女性',
        self::GENDER_TRANNIES     => '变性人',
        self::GENDER_MALETRANNY   => '男变性人',
        self::GENDER_FEMALETRANNY => '女变性人'
    ];

    const STATUS_ON = 1;
    const STATUS_OFF = 2;
    const STATUS_TIPS = [
        self::STATUS_ON  => '启用',
        self::STATUS_OFF => '禁止',
    ];

    const TYPE_FREE = 0;
    const TYPE_VIP = 1;
    const TYPE_COINS = 2;
    const TYPE_TIPS = [
        self::TYPE_FREE  => '免费',
        self::TYPE_VIP   => 'VIP',
        self::TYPE_COINS => '金币',
    ];

    const TAG = [
        'girls'   =>
            [
                'girls/brunettes-young'                 => '黑发美女',
                'girls/fingering-white'                 => '白人-扣逼',
                'girls/cuckold'                         => '绿帽',
                'girls/latin'                           => '拉丁',
                'girls/curvy'                           => '丰乳肥臀',
                'girls/topless-asian'                   => '亚裔-裸体',
                'girls/russian-milfs'                   => '俄罗斯-熟妇',
                'girls/anal'                            => '肛门',
                'girls/athletic-asian'                  => '亚裔-健身',
                'girls/bbw-redheads'                    => '红发-卡比兽',
                'girls/french-teens'                    => '法国-青少年',
                'girls/curvy-redheads'                  => '红发-丰乳肥臀',
                'girls/blondes-teens'                   => '金发-青少年',
                'girls/middle-priced-privates-arab'     => '阿拉伯-私密',
                'girls/colorful-young'                  => '多彩',
                'girls/colorful'                        => '多彩',
                'girls/new'                             => '最新',
                'girls/curvy-asian'                     => '亚洲-丰乳肥臀',
                'girls/new-white'                       => '白人',
                'girls/petite-white'                    => '白人-贫乳',
                'girls/athletic-white'                  => '白人-健身',
                'girls/bdsm-teens'                      => '捆绑-青少年',
                'girls/romanian-milfs'                  => '罗马尼亚-熟妇',
                'girls/german'                          => '德国',
                'girls/thai'                            => '泰国',
                'girls/nylon'                           => '丝袜',
                'girls/jamaican'                        => '牙买加',
                'girls/tattoos-teens'                   => '纹身-青少年',
                'girls/slovenian'                       => '斯洛文尼亚',
                'girls/cheap-privates-asian'            => '亚洲-私密',
                'girls/luxurious-privates-grannies'     => '老太太-私密',
                'girls/athletic-teens'                  => '健身-青少年',
                'girls/luxurious-privates-teens'        => '私密青少年',
                'girls/big-tits-milfs'                  => '巨乳-熟妇',
                'girls/pregnant'                        => '孕妇',
                'girls/bbw'                             => '卡比兽',
                'girls/petite-redheads'                 => '红发-贫乳',
                'girls/mobile-grannies'                 => '老太太',
                'girls/anal-young'                      => '肛门',
                'girls/anal-ebony'                      => '肛门',
                'girls/romanian-grannies'               => '罗马尼亚',
                'girls/american-bbw'                    => '美国-卡比兽',
                'girls/foot-fetish'                     => '恋足',
                'girls/south-african'                   => '南非',
                'girls/spy-curvy'                       => '偷排-丰乳肥臀',
                'girls/ukrainian-mature'                => '乌克兰-熟女',
                'girls/german-young'                    => '德国美女',
                'girls/new-mature'                      => '熟女',
                'girls/petite-latin'                    => '拉丁-娇小',
                'girls/yoga-grannies'                   => '瑜伽-老太太',
                'girls/tattoos-white'                   => '纹身-白人',
                'girls/cheap-privates'                  => '私密',
                'girls/leather'                         => '紧身皮衣',
                'girls/cock-rating'                     => '鸡巴评审会',
                'girls/69-position'                     => '69',
                'girls/new-athletic'                    => '健身',
                'girls/surinamese'                      => '苏里南',
                'girls/turkish'                         => '土耳其人',
                'girls/luxurious-privates-white'        => '私密-白人',
                'girls/russian-petite'                  => '俄罗斯-娇小',
                'girls/outdoor'                         => '户外',
                'girls/piercings'                       => '穿孔',
                'girls/italian'                         => '意大利',
                'girls/curvy-latin'                     => '拉丁-丰乳肥臀',
                'girls/bbw-latin'                       => '拉丁-卡比兽',
                'girls/hardcore-grannies'               => '硬核老太太',
                'girls/hairy-teens'                     => '多毛-青少年',
                'girls/trimmed-grannies'                => '修毛-老太太',
                'girls/ahegao'                          => '高潮脸',
                'girls/colombian'                       => '哥伦比亚',
                'girls/indian-young'                    => '印第安人',
                'girls/asian-milfs'                     => '亚洲人',
                'girls/middle-priced-privates-best'     => '私密',
                'girls/interactive-toys-grannies'       => '老太太-性玩具',
                'girls/small-tits-young'                => '贫乳-年轻',
                'girls/romantic-young'                  => '浪漫',
                'girls/hairy-armpits'                   => '腋毛',
                'girls/goth'                            => '哥特风',
                'girls/recordable-privates'             => '私密',
                'girls/french-grannies'                 => '法国',
                'girls/interactive-toys-mature'         => '互动玩具-熟女',
                'girls/spy-athletic'                    => '健身-偷拍',
                'girls/curvy-ebony'                     => '黑珍珠-丰乳肥臀',
                'girls/luxurious-privates-best'         => '私密',
                'girls/middle-priced-privates-ebony'    => '私密-黑珍珠',
                'girls/squirt-white'                    => '潮喷-白人',
                'girls/trimmed-milfs'                   => '修毛-熟妇',
                'girls/titty-fuck'                      => '乳交',
                'girls/zimbabwean'                      => '津巴布韦',
                'girls/video-games'                     => '视频游戏',
                'girls/spanish-teens'                   => '西班牙青少年',
                'girls/nigerian'                        => '尼日利亚',
                'girls/indian-grannies'                 => '印度-老太太',
                'girls/best-grannies'                   => '老太太-最佳',
                'girls/french-young'                    => '法国',
                'girls/australian'                      => '澳大利亚',
                'girls/puertorican'                     => '波多黎各',
                'girls/russian-bbw'                     => '俄罗斯-卡比兽',
                'girls/cheap-privates-best'             => '私密',
                'girls/best-milfs'                      => '熟妇-最佳',
                'girls/cosplay-young'                   => '角色扮演',
                'girls/cosplay-grannies'                => '角色扮演-老太太',
                'girls/romantic-indian'                 => '浪漫-印度',
                'girls/best'                            => '最佳',
                'girls/new-grannies'                    => '老太太-新',
                'girls/bbw-blondes'                     => '金发-卡比兽',
                'girls/romantic-white'                  => '浪漫-白人',
                'girls/most-affordable-cam2cam'         => '双镜头',
                'girls/latin-teens'                     => '拉丁-青少年',
                'girls/petite-milfs'                    => '娇小-熟妇',
                'girls/big-tits-arab'                   => '阿拉伯-巨乳',
                'girls/small-tits-ebony'                => '贫乳-黑人',
                'girls/argentinian'                     => '阿根廷',
                'girls/flirting'                        => '调情',
                'girls/spy-mature'                      => '偷拍-熟女',
                'girls/american-petite'                 => '美国-娇小',
                'girls/kiiroo'                          => '飞机杯',
                'girls/curvy-young'                     => '丰乳肥臀',
                'girls/small-tits'                      => '贫乳',
                'girls/student'                         => '学生',
                'girls/kenyan'                          => '肯尼亚',
                'girls/canadian'                        => '加拿大',
                'girls/fisting-white'                   => '拳交-白人',
                'girls/big-tits-young'                  => '巨乳',
                'girls/ukrainian-teens'                 => '乌克兰-青少年',
                'girls/cheap-privates-ebony'            => '私密-黑人',
                'girls/cheap-privates-white'            => '私密-白人',
                'girls/middle-priced-privates-asian'    => '私密-亚裔',
                'girls/spy-indian'                      => '偷拍-印度',
                'girls/dildo-or-vibrator-milfs'         => '假鸡巴/跳蛋-熟妇',
                'girls/striptease-ebony'                => '脱衣舞-黑人',
                'girls/lithuanian'                      => '立陶宛',
                'girls/matched'                         => '比赛',
                'girls/new-cheapest-privates'           => '私密',
                'girls/spy-milfs'                       => '偷拍-熟妇',
                'girls/bbw-arab'                        => '阿拉伯-卡比兽',
                'girls/romanian-teens'                  => '罗马尼亚-青少年',
                'girls/anal-indian'                     => '肛门-印度',
                'girls/fingering-young'                 => '扣逼',
                'girls/fingering-milfs'                 => '扣逼-熟妇',
                'girls/italian-grannies'                => '意大利',
                'girls/croatian'                        => '克罗地亚',
                'girls/lovense'                         => '跳蛋',
                'girls/topless-latin'                   => '拉丁-裸体',
                'girls/colorful-teens'                  => '多彩',
                'girls/big-ass-arab'                    => '阿拉伯-巨尻',
                'girls/topless'                         => '裸体',
                'girls/mistresses'                      => '女王',
                'girls/petite-teens'                    => '娇小',
                'girls/tattoos-ebony'                   => '纹身-黑人',
                'girls/mature'                          => '熟女',
                'girls/new-teens'                       => '青少年',
                'girls/luxurious-privates-milfs'        => '私密',
                'girls/piercings-ebony'                 => '穿孔-黑人',
                'girls/romantic'                        => '浪漫',
                'girls/new-luxurious-privates'          => '私密',
                'girls/flirting-grannies'               => '老太太-调情',
                'girls/athletic-milfs'                  => '健身-熟妇',
                'girls/curvy-indian'                    => '印度-丰乳肥臀',
                'girls/fisting-arab'                    => '阿拉伯-拳交',
                'girls/medium'                          => '中等',
                'girls/new-asian'                       => '亚裔-新',
                'girls/petite-mature'                   => '娇小-熟女',
                'girls/role-play-teens'                 => '角色扮演-青少年',
                'girls/striptease-latin'                => '拉丁-脱衣舞',
                'girls/swiss'                           => '瑞士',
                'girls/arab-young'                      => '阿拉伯-青年',
                'girls/topless-arab'                    => '阿拉伯-裸体',
                'girls/housewives'                      => '家庭主妇',
                'girls/yoga-young'                      => '瑜伽',
                'girls/striptease-indian'               => '印度-脱衣舞',
                'girls/german-grannies'                 => '德国',
                'girls/striptease'                      => '脱衣舞',
                'girls/ebony-teens'                     => '青少年-黑人',
                'girls/flirting-white'                  => '调情-白人',
                'girls/cheap-privates-arab'             => '阿拉伯-私密',
                'girls/twerk-grannies'                  => '后入-老太太',
                'girls/romantic-teens'                  => '浪漫-青少年',
                'girls/colombian-mature'                => '哥伦比亚-熟女',
                'girls/french-milfs'                    => '法国-熟妇',
                'girls/latex'                           => '乳胶衣',
                'girls/new-petite'                      => '娇小-新',
                'girls/new-cheap-privates'              => '私密-新',
                'girls/twerk-young'                     => '乳胶衣-年轻',
                'girls/trimmed'                         => '修毛',
                'girls/spy-arab'                        => '阿拉伯-偷拍',
                'girls/curvy-grannies'                  => '丰乳肥臀-老太太',
                'girls/petite-ebony'                    => '娇小-黑人',
                'girls/curvy-milfs'                     => '丰乳肥臀-熟妇',
                'girls/middle-priced-privates-latin'    => '拉丁-私密',
                'girls/small-tits-teens'                => '小鸡巴',
                'girls/doggy-style'                     => '狗交式',
                'girls/pegging'                         => '可穿戴鸡巴',
                'girls/oktoberfest'                     => '啤酒节',
                'girls/luxurious-privates-ebony'        => '私密-黑人',
                'girls/striptease-arab'                 => '阿拉伯-脱衣舞',
                'girls/fingering-latin'                 => '拉丁-扣逼',
                'girls/small-tits-latin'                => '拉丁-小鸡巴',
                'girls/russian-grannies'                => '俄罗斯-老太太',
                'girls/twerk'                           => '后入',
                'girls/asian'                           => '亚裔',
                'girls/cheapest-privates-asian'         => '亚裔-私密',
                'girls/recordable-privates-young'       => '私密',
                'girls/ebony-grannies'                  => '老太太-黑人',
                'girls/cheap-privates-milfs'            => '私密-熟妇',
                'girls/middle-priced-privates-teens'    => '私密-青少年',
                'girls/flirting-milfs'                  => '调情-熟妇',
                'girls/danish'                          => '丹麦',
                'girls/cam2cam'                         => '双镜头',
                'girls/spy-young'                       => '偷拍',
                'girls/swingers'                        => '换妻',
                'girls/belgian'                         => '比利时',
                'girls/bulgarian'                       => '保加利亚',
                'girls/latin-mature'                    => '拉丁',
                'girls/twerk-teens'                     => '后入-青少年',
                'girls/oil-show'                        => '涂油',
                'girls/big-clit'                        => '大阴蒂',
                'girls/big-tits'                        => '巨乳',
                'girls/tattoos-latin'                   => '拉丁-纹身',
                'girls/trimmed-indian'                  => '印度-修毛',
                'girls/twerk-asian'                     => '亚裔-后入',
                'girls/dildo-or-vibrator-teens'         => '假鸡巴/跳蛋-青少年',
                'girls/fingering-teens'                 => '扣逼-青少年',
                'girls/athletic-ebony'                  => '健身-黑人',
                'girls/cheapest-privates-teens'         => '私密-青少年',
                'girls/best-mature'                     => '熟女-最佳',
                'girls/recordable-privates-milfs'       => '私密-熟妇',
                'girls/trimmed-teens'                   => '修毛-青少年',
                'girls/young'                           => '年轻',
                'girls/dildo-or-vibrator'               => '假鸡巴/跳蛋',
                'girls/blondes'                         => '金发',
                'girls/ukrainian-petite'                => '乌克兰-娇小',
                'girls/petite-blondes'                  => '金发—娇小',
                'girls/brunettes-milfs'                 => '黑发',
                'girls/middle-priced-privates-young'    => '私密-青年',
                'girls/role-play-milfs'                 => '角色扮演-熟妇',
                'girls/striptease-asian'                => '亚洲-脱衣舞',
                'girls/grannies'                        => '老太太',
                'girls/spanking'                        => '打屁股',
                'girls/spy-white'                       => '偷拍-白人',
                'girls/luxurious-privates-latin'        => '拉丁-私密',
                'girls/piercings-milfs'                 => '穿孔-熟妇',
                'girls/american-teens'                  => '美国-青少年',
                'girls/fisting'                         => '拳交',
                'girls/arab-grannies'                   => '阿拉伯',
                'girls/asian-young'                     => '亚洲-青年',
                'girls/athletic-latin'                  => '拉丁-健身',
                'girls/flirting-arab'                   => '阿拉伯-调情',
                'girls/striptease-milfs'                => '脱衣舞-熟妇',
                'girls/russian-mature'                  => '俄罗斯-熟女',
                'girls/spanish-grannies'                => '西班牙-老太太',
                'girls/cosplay'                         => '角色扮演',
                'girls/french'                          => '法国',
                'girls/new-curvy'                       => '丰乳肥臀-新',
                'girls/yoga-milfs'                      => '瑜伽-熟妇',
                'girls/german-milfs'                    => '德国-熟妇',
                'girls/fingering'                       => '扣逼',
                'girls/cheapest-privates-white'         => '私密-白人',
                'girls/hardcore-milfs'                  => '硬核熟女',
                'girls/squirt-teens'                    => '潮喷',
                'girls/piercings-young'                 => '穿孔-青年',
                'girls/nipple-toys'                     => '乳夹',
                'girls/pov'                             => '第一人称视角',
                'girls/armenian'                        => '亚美尼亚',
                'girls/luxurious-privates-arab'         => '阿拉伯-私密',
                'girls/trimmed-asian'                   => '亚洲-修毛',
                'girls/luxurious-privates'              => '私密',
                'girls/serbian'                         => '塞尔维亚',
                'girls/petite-grannies'                 => '娇小-老太太',
                'girls/big-ass'                         => '巨尻',
                'girls/albanian'                        => '阿尔巴尼亚',
                'girls/striptease-teens'                => '脱衣舞-青少年',
                'girls/fisting-indian'                  => '印度-拳交',
                'girls/american-milfs'                  => '美国-熟妇',
                'girls/cowgirl'                         => '女牛仔',
                'girls/slovakian'                       => '斯洛伐克',
                'girls/squirt-asian'                    => '潮喷-亚裔',
                'girls/spy-ebony'                       => '偷拍-黑人',
                'girls/bangladeshi'                     => '孟加拉',
                'girls/bbw-white'                       => '卡比兽-白人',
                'girls/flirting-latin'                  => '拉丁-调情',
                'girls/spy-brunettes'                   => '偷拍-黑发',
                'girls/big-ass-latin'                   => '拉丁-巨尻',
                'girls/tattoos-asian'                   => '纹身-亚裔',
                'girls/venezuelan-grannies'             => '委内瑞拉',
                'girls/deepthroat'                      => '深喉',
                'girls/petite'                          => '娇小',
                'girls/july4th'                         => '星条旗',
                'girls/redheads-milfs'                  => '红发-熟妇',
                'girls/twerk-white'                     => '后入',
                'girls/piercings-asian'                 => '穿孔-亚裔',
                'girls/trimmed-ebony'                   => '修毛-黑人',
                'girls/spanish'                         => '西班牙',
                'girls/spy-grannies'                    => '偷拍-老太太',
                'girls/athletic-redheads'               => '红发-健身',
                'girls/bbw-indian'                      => '印度-卡比兽',
                'girls/piercings-white'                 => '穿孔-白人',
                'girls/tattoos-milfs'                   => '纹身-熟妇',
                'girls/venezuelan-petite'               => '委内瑞拉-娇小',
                'girls/facesitting'                     => '骑脸',
                'girls/brazilian'                       => '巴西',
                'girls/portuguese-speaking'             => '葡萄牙语',
                'girls/spy'                             => '偷拍',
                'girls/indian-teens'                    => '印度-青少年',
                'girls/cheapest-privates-grannies'      => '私密-老太太',
                'girls/tattoos-arab'                    => '阿拉伯-纹身',
                'girls/bdsm'                            => '捆绑',
                'girls/polish'                          => '波兰',
                'girls/ugandan'                         => '乌干达',
                'girls/fingering-arab'                  => '阿拉伯-扣逼',
                'girls/office'                          => '办公室',
                'girls/vietnamese'                      => '越南',
                'girls/hardcore-teens'                  => '硬核-青少年',
                'girls/spy-lesbians'                    => '偷拍-女同',
                'girls/luxurious-privates-mature'       => '私密-熟女',
                'girls/estonian'                        => '爱沙尼亚',
                'girls/spanish-speaking'                => '西班牙',
                'girls/vr'                              => 'VR',
                'girls/colombian-petite'                => '哥伦比亚-娇小',
                'girls/mexican'                         => '墨西哥人',
                'girls/striptease-young'                => '脱衣舞',
                'girls/small-tits-arab'                 => '阿拉伯-贫乳',
                'girls/best-young'                      => '最佳',
                'girls/venezuelan-mature'               => '委内瑞拉-熟女',
                'girls/emo'                             => 'emo',
                'girls/norwegian'                       => '挪威',
                'girls/fuck-machine'                    => '打桩机',
                'girls/arab-milfs'                      => '阿拉伯-熟妇',
                'girls/trimmed-latin'                   => '拉丁-修毛',
                'girls/romantic-arab'                   => '阿拉伯-浪漫',
                'girls/spanish-young'                   => '西班牙-青年',
                'girls/shower'                          => '淋浴',
                'girls/hardcore'                        => '硬核',
                'girls/new-blondes'                     => '金发-新',
                'girls/yoga-teens'                      => '瑜伽-青少年',
                'girls/anal-milfs'                      => '肛门-熟妇',
                'girls/fingering-asian'                 => '亚洲-扣逼',
                'girls/ukrainian-young'                 => '乌克兰-青年',
                'girls/double-penetration'              => '双管齐下',
                'girls/cheapest-privates'               => '私密',
                'girls/cheapest-privates-indian'        => '印度-私密',
                'girls/high-priced-spy'                 => '偷拍',
                'girls/white-young'                     => '白人-青年',
                'girls/athletic-mature'                 => '健身-熟女',
                'girls/squirt-indian'                   => '印度-潮喷',
                'girls/big-ass-young'                   => '巨尻',
                'girls/malaysian'                       => '马来西亚',
                'girls/uruguayan'                       => '乌拉圭',
                'girls/new-colorful'                    => '多彩-新',
                'girls/big-ass-asian'                   => '巨尻-亚裔',
                'girls/asian-mature'                    => '熟女-亚裔',
                'girls/piercings-indian'                => '印度-穿孔',
                'girls/blowjob'                         => '口交',
                'girls/cooking'                         => '厨房',
                'girls/glamour'                         => '魅力',
                'girls/new-mobile'                      => '手机',
                'girls/spy-petite'                      => '偷拍-娇小',
                'girls/big-ass-indian'                  => '印度-巨尻',
                'girls/editorial-choice'                => '小编精选',
                'girls/new-young'                       => '年轻-新',
                'girls/cheap-privates-indian'           => '印度-私密',
                'girls/hairy-milfs'                     => '多毛-熟妇',
                'girls/sex-toys'                        => '性玩具',
                'girls/humiliation'                     => '羞辱',
                'girls/finnish'                         => '芬兰',
                'girls/ukrainian-blondes'               => '乌克兰-金发',
                'girls/german-mature'                   => '德语-熟女',
                'girls/ticket-and-group-shows'          => '付费秀',
                'girls/middle-priced-privates-mature'   => '私密-熟女',
                'girls/interactive-toys-young'          => '互动玩具-青年',
                'girls/big-tits-asian'                  => '巨乳-亚裔',
                'girls/trimmed-white'                   => '修毛-白人',
                'girls/german-blondes'                  => '德国-金发',
                'girls/new-middle-priced-privates'      => '私密',
                'girls/asian-teens'                     => '青少年-亚裔',
                'girls/dildo-or-vibrator-young'         => '假鸡巴/跳蛋',
                'girls/athletic-blondes'                => '健身-金发',
                'girls/cheapest-privates-young'         => '私密-青年',
                'girls/recordable-privates-grannies'    => '私密-老太太',
                'girls/big-ass-white'                   => '巨尻-白人',
                'girls/big-tits-indian'                 => '印度-巨乳',
                'girls/strapon'                         => '可穿戴鸡巴',
                'girls/spy-latin'                       => '拉丁-偷拍',
                'girls/ebony-milfs'                     => '熟妇-黑人',
                'girls/interactive-toys-milfs'          => '互动玩具-熟妇',
                'girls/topless-ebony'                   => '裸体',
                'girls/squirt-latin'                    => '潮喷-拉丁',
                'girls/indian'                          => '印度',
                'girls/christmas'                       => '圣诞节',
                'girls/cheapest-privates-latin'         => '私密-拉丁',
                'girls/flashing'                        => '快闪',
                'girls/bbw-milfs'                       => '卡比兽-熟妇',
                'girls/squirt-grannies'                 => '潮喷-老太太',
                'girls/cosplay-teens'                   => '角色扮演-青少年',
                'girls/teens'                           => '青少年',
                'girls/latvian'                         => '拉脱维亚',
                'girls/romanian'                        => '罗马尼亚',
                'girls/moderately-priced-cam2cam'       => '双镜头',
                'girls/flirting-young'                  => '调情',
                'girls/twerk-milfs'                     => '后入-熟妇',
                'girls/venezuelan-teens'                => '委内瑞拉-青少年',
                'girls/venezuelan-young'                => '委内瑞拉-青年',
                'girls/german-teens'                    => '德国-青少年',
                'girls/czech'                           => '捷克',
                'girls/spy-bbw'                         => '偷拍-卡比兽',
                'girls/fingering-grannies'              => '老太太-扣逼',
                'girls/cheap-privates-young'            => '私密-青年',
                'girls/cheap-privates-mature'           => '私密-熟女',
                'girls/piercings-teens'                 => '穿孔-青少年',
                'girls/spanish-bbw'                     => '西班牙-卡比兽',
                'girls/hipsters'                        => '嬉皮士',
                'girls/spy-best'                        => '偷拍-最佳',
                'girls/bbw-ebony'                       => '卡比兽-黑人',
                'girls/big-tits-ebony'                  => '巨乳-黑人',
                'girls/spanish-milfs'                   => '西班牙',
                'girls/spy-redheads'                    => '偷拍-红发',
                'girls/redheads-teens'                  => '红发-青少年',
                'girls/flirting-indian'                 => '调情-印度',
                'girls/anal-arab'                       => '肛门-阿拉伯',
                'girls/dirty-talk'                      => '脏话',
                'girls/middle-priced-privates'          => '私密',
                'girls/petite-arab'                     => '娇小-阿拉伯',
                'girls/american-mature'                 => '美国-熟女',
                'girls/small-tits-grannies'             => '贫乳-老太太',
                'girls/ebony'                           => '黑人',
                'girls/corset'                          => '束胸',
                'girls/bbw-asian'                       => '卡比兽-亚裔',
                'girls/brunettes-grannies'              => '黑发-老太太',
                'girls/role-play-grannies'              => '角色扮演-老太太',
                'girls/big-tits-teens'                  => '巨乳',
                'girls/small-tits-indian'               => '贫乳-印度',
                'girls/romantic-milfs'                  => '浪漫-熟妇',
                'girls/tomboy'                          => '假小子',
                'girls/ebony-young'                     => '青年-黑人',
                'girls/white-grannies'                  => '老太太-白人',
                'girls/big-ass-grannies'                => '巨尻-老太太',
                'girls/italian-mature'                  => '意大利-熟女',
                'girls/colombian-bbw'                   => '哥伦比亚-卡比兽',
                'girls/valentines'                      => '情人',
                'girls/deluxe-cam2cam'                  => '双镜头',
                'girls/piercings-grannies'              => '穿孔-老太太',
                'girls/topless-teens'                   => '裸体',
                'girls/hairy-grannies'                  => '多毛-老太太',
                'girls/creampie'                        => '内射',
                'girls/chinese'                         => '中国',
                'girls/cheap-privates-teens'            => '私密-青少年',
                'girls/taiwanese'                       => '台湾',
                'girls/russian-young'                   => '俄罗斯-青年',
                'girls/ukrainian-milfs'                 => '乌克兰-熟妇',
                'girls/new-arab'                        => '阿拉伯-新',
                'girls/colorful-milfs'                  => '多彩',
                'girls/athletic'                        => '健身',
                'girls/dutch'                           => '荷兰',
                'girls/peruvian'                        => '秘鲁',
                'girls/mobile-young'                    => '手机-青年',
                'girls/anal-asian'                      => '肛门',
                'girls/colombian-milfs'                 => '哥伦比亚-熟妇',
                'girls/yoga'                            => '瑜伽',
                'girls/new-bbw'                         => '卡比兽-新',
                'girls/blondes-young'                   => '金发',
                'girls/cheapest-privates-ebony'         => '私密-黑人',
                'girls/big-ass-ebony'                   => '巨尻',
                'girls/romanian-young'                  => '罗马尼亚-青年',
                'girls/colombian-grannies'              => '哥伦比亚',
                'girls/kiwi'                            => '新西兰',
                'girls/petite-asian'                    => '娇小-亚裔',
                'girls/colorful-mature'                 => '多彩-熟女',
                'girls/athletic-young'                  => '健身-青年',
                'girls/curvy-mature'                    => '丰乳肥臀-熟女',
                'girls/redheads'                        => '红发',
                'girls/srilankan'                       => '斯里兰卡',
                'girls/latin-milfs'                     => '拉丁-熟妇',
                'girls/interactive-toys-teens'          => '互动玩具-青少年',
                'girls/squirt-young'                    => '潮喷-青年',
                'girls/fisting-milfs'                   => '拳交',
                'girls/uk-models'                       => '英国模特',
                'girls/ebony-mature'                    => '熟女-黑人',
                'girls/cheap-privates-grannies'         => '私密-老太太',
                'girls/romanian-mature'                 => '罗马尼亚-熟女',
                'girls/israeli'                         => '以色列',
                'girls/anal-grannies'                   => '肛门-老太太',
                'girls/small-tits-milfs'                => '贫乳-熟妇',
                'girls/romantic-latin'                  => '浪漫-拉丁',
                'girls/cosplay-milfs'                   => '角色扮演-熟妇',
                'girls/masturbation'                    => '打飞机',
                'girls/lesbians'                        => '女同',
                'girls/new-milfs'                       => '熟妇-新',
                'girls/bbw-grannies'                    => '卡比兽-老太太',
                'girls/small-tits-white'                => '贫乳-白人',
                'girls/italian-young'                   => '意大利-青年',
                'girls/arab'                            => '阿拉伯',
                'girls/brunettes'                       => '黑发',
                'girls/dominican'                       => '多米尼加',
                'girls/anal-white'                      => '肛门-白人',
                'girls/hd'                              => '高清',
                'girls/small-audience'                  => '小众',
                'girls/anal-latin'                      => '肛门-拉丁',
                'girls/anal-teens'                      => '肛门-青少年',
                'girls/big-tits-white'                  => '巨乳-白人',
                'girls/american-young'                  => '美国-青年',
                'girls/big-nipples'                     => '大乳头',
                'girls/mid-priced-spy'                  => '偷拍',
                'girls/latin-grannies'                  => '拉丁-老太太',
                'girls/halloween'                       => '万圣节',
                'girls/brunettes-mature'                => '黑发-熟女',
                'girls/fisting-asian'                   => '拳交-亚裔',
                'girls/american-grannies'               => '美国-老太太',
                'girls/redheads-mature'                 => '红发-熟女',
                'girls/squirt-arab'                     => '潮喷-阿拉伯',
                'girls/piercings-arab'                  => '穿孔-阿拉伯',
                'girls/topless-indian'                  => '裸体-印度',
                'girls/twerk-indian'                    => '后入-印度',
                'girls/role-play-young'                 => '角色扮演-青年',
                'girls/upskirt'                         => '真空',
                'girls/middle-priced-privates-grannies' => '私密-老太太',
                'girls/middle-priced-privates-indian'   => '私密-印度',
                'girls/curvy-blondes'                   => '丰乳肥臀-金发',
                'girls/fisting-ebony'                   => '拳交-黑人',
                'girls/big-ass-milfs'                   => '巨尻-熟女',
                'girls/gagging'                         => '深喉',
                'girls/chilean'                         => '智利',
                'girls/new-redheads'                    => '红发',
                'girls/redheads-young'                  => '红发-青年',
                'girls/cheap-privates-latin'            => '拉丁-私密',
                'girls/striptease-white'                => '脱衣舞-白人',
                'girls/topless-young'                   => '裸体-青年',
                'girls/twerk-arab'                      => '后入-阿拉伯',
                'girls/trimmed-young'                   => '修毛-青年',
                'girls/arab-teens'                      => '阿拉伯-青少年',
                'girls/curvy-teens'                     => '丰乳肥臀-青少年',
                'girls/blondes-milfs'                   => '金发-熟妇',
                'girls/petite-young'                    => '娇小-青年',
                'girls/recordable-privates-mature'      => '私密-熟女',
                'girls/topless-milfs'                   => '裸体-熟妇',
                'girls/new-ebony'                       => '黑人-新',
                'girls/spy-colorful'                    => '偷拍-多彩',
                'girls/cheapest-privates-mature'        => '私密-熟女',
                'girls/cheapest-privates-arab'          => '私密-阿拉伯',
                'girls/facial'                          => '颜射',
                'girls/portuguese'                      => '葡萄牙',
                'girls/interactive-toys'                => '互动玩具',
                'girls/erotic-dance'                    => '艳舞',
                'girls/athletic-arab'                   => '健身-阿拉伯',
                'girls/irish'                           => '爱尔兰',
                'girls/new-latin'                       => '拉丁-新',
                'girls/american-blondes'                => '美国-金发',
                'girls/new-brunettes'                   => '黑发-新',
                'girls/brunettes-teens'                 => '黑发-青少年',
                'girls/twerk-ebony'                     => '后入-黑人',
                'girls/african'                         => '非洲',
                'girls/greek'                           => '希腊',
                'girls/bdsm-milfs'                      => '捆绑-熟妇',
                'girls/affordable-cam2cam'              => '双镜头',
                'girls/fisting-teens'                   => '拳交-青少年',
                'girls/romantic-grannies'               => '浪漫-老太太',
                'girls/handjob'                         => '打飞机',
                'girls/hairy'                           => '多毛',
                'girls/asmr'                            => 'ASMR',
                'girls/dildo-or-vibrator-grannies'      => '假鸡巴/跳蛋-老太太',
                'girls/fisting-latin'                   => '拳交-拉丁',
                'girls/russian-teens'                   => '俄罗斯-青少年',
                'girls/mobile-teens'                    => '手机-青少年',
                'girls/mobile'                          => '手机',
                'girls/recordable-publics'              => '公开',
                'girls/low-priced-spy'                  => '偷拍',
                'girls/spy-teens'                       => '偷拍-青少年',
                'girls/asian-grannies'                  => '亚裔-老太太',
                'girls/piercings-latin'                 => '穿孔-拉丁',
                'girls/role-play'                       => '角色扮演',
                'girls/tattoos'                         => '纹身',
                'girls/japanese'                        => '日本',
                'girls/venezuelan-milfs'                => '委内瑞拉-熟妇',
                'girls/venezuelan-bbw'                  => '委内瑞拉-卡比兽',
                'girls/spy-blondes'                     => '偷拍-金发',
                'girls/hardcore-young'                  => '硬核-青年',
                'girls/tattoos-young'                   => '纹身-青年',
                'girls/bbw-young'                       => '卡比兽-青年',
                'girls/big-ass-teens'                   => '巨尻-雅典',
                'girls/big-tits-grannies'               => '巨乳-老太太',
                'girls/trimmed-arab'                    => '修毛-阿拉伯',
                'girls/colombian-teens'                 => '哥伦比亚-青少年',
                'girls/shaven'                          => '剃毛',
                'girls/malagasy'                        => '马尔加什',
                'girls/spy-group-sex'                   => '偷拍-群P',
                'girls/ukrainian-grannies'              => '乌克兰-老太太',
                'girls/squirt'                          => '潮喷',
                'girls/costarican'                      => '哥斯达黎加',
                'girls/white-mature'                    => '白人-熟女',
                'girls/topless-white'                   => '裸体',
                'girls/flirting-asian'                  => '调情-亚洲',
                'girls/russian'                         => '俄罗斯',
                'girls/cheapest-privates-milfs'         => '私密-熟妇',
                'girls/best-teens'                      => '青少年-最佳',
                'girls/petite-indian'                   => '娇小-印度',
                'girls/luxurious-privates-asian'        => '私密-亚洲',
                'girls/tattoos-indian'                  => '纹身-印度',
                'girls/russian-blondes'                 => '俄罗斯-金发',
                'girls/smoking'                         => '抽烟',
                'girls/balds'                           => '光头',
                'girls/ecuadorian'                      => '厄瓜多尔',
                'girls/blondes-mature'                  => '金发-熟女',
                'girls/blondes-grannies'                => '金发-老太太',
                'girls/italian-milfs'                   => '意大利-熟妇',
                'girls/spanish-mature'                  => '西班牙-熟女',
                'girls/orgasm'                          => '高潮',
                'girls/group-sex'                       => '群P',
                'girls/curvy-white'                     => '丰乳肥臀-白人',
                'girls/romantic-ebony'                  => '浪漫-黑人',
                'girls/bbw-teens'                       => '卡比兽-青少年',
                'girls/middle-priced-privates-milfs'    => '私密-熟妇',
                'girls/spanish-petite'                  => '西班牙-娇小',
                'girls/gape'                            => '扩肛',
                'girls/venezuelan'                      => '委内瑞拉',
                'girls/heels'                           => '高跟鞋',
                'girls/colombian-young'                 => '哥伦比亚',
                'girls/swedish'                         => '瑞典',
                'girls/athletic-indian'                 => '健身-印度',
                'girls/bbw-mature'                      => '卡比兽-熟女',
                'girls/nordic'                          => '北欧',
                'girls/latin-young'                     => '拉丁-青年',
                'girls/curvy-arab'                      => '丰乳肥臀-阿拉伯',
                'girls/luxurious-privates-indian'       => '私密-印度',
                'girls/squirt-ebony'                    => '潮喷-黑人',
                'girls/tattoos-grannies'                => '纹身-老太太',
                'girls/small-tits-asian'                => '贫乳-亚裔',
                'girls/romantic-asian'                  => '浪漫-亚裔',
                'girls/american'                        => '美国',
                'girls/athletic-grannies'               => '健身-老太太',
                'girls/cheapest-privates-best'          => '私密-最佳',
                'girls/ukrainian-bbw'                   => '乌克兰-卡比兽',
                'girls/redheads-grannies'               => '红发-女郎',
                'girls/mobile-milfs'                    => '手机-熟妇',
                'girls/fisting-grannies'                => '拳交',
                'girls/hairy-young'                     => '多毛',
                'girls/bdsm-grannies'                   => '捆绑-老太太',
                'girls/white'                           => '白人',
                'girls/sexting'                         => '骚话',
                'girls/spy-asian'                       => '偷拍-亚裔',
                'girls/flirting-teens'                  => '调情-青少年',
                'girls/fisting-young'                   => '拳交',
                'girls/hungarian'                       => '匈牙利',
                'girls/indonesian'                      => '印度尼西亚',
                'girls/luxurious-privates-young'        => '私密-青年',
                'girls/recordable-privates-teens'       => '私密-青少年',
                'girls/topless-grannies'                => '裸体-老太太',
                'girls/anal-toys'                       => '肛门-玩具',
                'girls/interracial'                     => '黑白配',
                'girls/new-indian'                      => '印度-新',
                'girls/indian-milfs'                    => '印度-熟妇',
                'girls/white-teens'                     => '白人',
                'girls/italian-teens'                   => '意大利-青少年',
                'girls/camel-toe'                       => '骆驼趾',
                'girls/ukrainian'                       => '乌克兰',
                'girls/jerk-off-instruction'            => '打飞机',
                'girls/milfs'                           => '熟妇',
                'girls/pornstars'                       => '色情明星',
                'girls/fingering-indian'                => '印度-扣逼',
                'girls/big-tits-latin'                  => '拉丁',
                'girls/bdsm-young'                      => '捆绑-青年',
                'girls/georgian'                        => '格鲁吉亚',
                'girls/korean'                          => '韩国',
                'girls/white-milfs'                     => '白人-熟妇',
                'girls/striptease-grannies'             => '脱衣舞-老太太',
                'girls/ass-to-mouth'                    => '双飞',
                'girls/middle-priced-privates-white'    => '私密-白人',
                'girls/squirt-milfs'                    => '潮喷-熟妇',
                'girls/fingering-ebony'                 => '扣逼-黑人',
                'girls/mobile-mature'                   => '手机-熟女',
                'girls/flirting-ebony'                  => '调情-黑人',
                'girls/twerk-latin'                     => '后入-拉丁',
            ],
        'men'     =>
            [
                'men/gay-couples'            => '同性恋伴侣',
                'men/straight'               => '直男',
                'men/interactive-toys'       => '互动玩具',
                'men/ecuadorian'             => '厄瓜多尔',
                'men/italian'                => '意大利',
                'men/lithuanian'             => '立陶宛',
                'men/sissy'                  => '娘娘腔',
                'men/bears'                  => '熊族',
                'men/spy'                    => '偷拍',
                'men/valentines'             => '情人',
                'men/norwegian'              => '挪威',
                'men/mustache'               => '胡子',
                'men/cuckold'                => '绿帽',
                'men/twerk'                  => '后入',
                'men/rimming'                => '毒龙',
                'men/piercings'              => '穿孔',
                'men/blondes'                => '金发',
                'men/georgian'               => '格鲁吉亚',
                'men/serbian'                => '塞尔维亚',
                'men/ebony'                  => '黑人',
                'men/selfsucking'            => '自吸',
                'men/spanish'                => '西班牙',
                'men/argentinian'            => '阿根廷',
                'men/swiss'                  => '瑞士',
                'men/best'                   => '最佳',
                'men/recordable-publics'     => '公共场所',
                'men/penis-ring'             => '鸡巴环',
                'men/lovense'                => '跳蛋',
                'men/group-sex'              => '群P',
                'men/colorful'               => '多彩',
                'men/kenyan'                 => '肯尼亚',
                'men/polish'                 => '波兰',
                'men/surinamese'             => '苏里南',
                'men/deepthroat'             => '深喉',
                'men/office'                 => '办公室',
                'men/australian'             => '澳大利亚',
                'men/bulgarian'              => '保加利亚',
                'men/hungarian'              => '匈牙利',
                'men/ukrainian'              => '乌克兰',
                'men/blowjob'                => '口交',
                'men/cei'                    => 'CEI',
                'men/gays'                   => '男同',
                'men/dutch'                  => '荷兰',
                'men/french'                 => '法国',
                'men/zimbabwean'             => '津巴布韦',
                'men/flashing'               => '快闪',
                'men/trimmed'                => '修毛',
                'men/jamaican'               => '牙买加',
                'men/uk-models'              => '英国模特',
                'men/yoga'                   => '瑜伽',
                'men/israeli'                => '以色列',
                'men/swingers'               => '换妻',
                'men/bbc'                    => '大黑屌',
                'men/mobile'                 => '手机',
                'men/redheads'               => '红发',
                'men/bottom'                 => '肛交',
                'men/brazilian'              => '巴西',
                'men/dildo-or-vibrator'      => '假鸡巴/跳蛋',
                'men/kiiroo'                 => '飞机杯',
                'men/chinese'                => '中国',
                'men/russian'                => '俄国',
                'men/femboy'                 => '娘娘腔',
                'men/hairy'                  => '多毛',
                'men/romanian'               => '罗马尼亚',
                'men/grandpas'               => '老大爷',
                'men/interracial'            => '黑白配',
                'men/danish'                 => '丹麦',
                'men/kiwi'                   => '新西兰',
                'men/south-african'          => '南非',
                'men/american'               => '美国',
                'men/vr'                     => 'VR',
                'men/gang-bang'              => '黑帮',
                'men/big'                    => '大',
                'men/flexing'                => '肌肉',
                'men/massage'                => '按摩',
                'men/chunky'                 => '矮胖',
                'men/cheap-privates'         => '私密',
                'men/indian'                 => '印度',
                'men/latin'                  => '拉丁',
                'men/old-young'              => '老少配',
                'men/hairy-armpits'          => '腋毛',
                'men/bangladeshi'            => '孟加拉国',
                'men/twinks'                 => '双胞胎',
                'men/white'                  => '白人',
                'men/small-cock'             => '小鸡巴',
                'men/leather'                => '紧身皮衣',
                'men/handjob'                => '打飞机',
                'men/gape'                   => '扩肛',
                'men/flirting'               => '调情',
                'men/shaven'                 => '剃毛',
                'men/hd'                     => '高清',
                'men/young'                  => '青年',
                'men/arab'                   => '阿拉伯',
                'men/spanish-speaking'       => '西班牙语',
                'men/uruguayan'              => '乌拉圭',
                'men/cam2cam'                => '双镜头',
                'men/bdsm'                   => '捆绑',
                'men/oktoberfest'            => '啤酒节',
                'men/top'                    => '上位',
                'men/mexican'                => '墨西哥',
                'men/colombian'              => '哥伦比亚',
                'men/indonesian'             => '印度尼西亚',
                'men/oil-show'               => '涂油',
                'men/albanian'               => '阿尔巴尼亚',
                'men/czech'                  => '捷克',
                'men/christmas'              => '圣诞节',
                'men/cock-rating'            => '鸡巴评审会',
                'men/matched'                => '比赛',
                'men/doggy-style'            => '狗交式',
                'men/skinny'                 => '皮包骨',
                'men/big-cocks'              => '大屌',
                'men/cheapest-privates'      => '私密',
                'men/irish'                  => '爱尔兰',
                'men/korean'                 => '韩国',
                'men/nordic'                 => '北欧',
                'men/srilankan'              => '斯里兰卡',
                'men/gagging'                => '深喉',
                'men/striptease'             => '脱衣舞',
                'men/editorial-choice'       => '小编精选',
                'men/pornstars'              => '色情明星',
                'men/asmr'                   => 'ASMR',
                'men/muscular'               => '肌肉',
                'men/finnish'                => '芬兰',
                'men/thai'                   => '泰国',
                'men/fingering'              => '指法',
                'men/pump'                   => '泵',
                'men/facesitting'            => '骑脸',
                'men/foot-fetish'            => '恋足',
                'men/ticket-and-group-shows' => '付费秀',
                'men/venezuelan'             => '委内瑞拉',
                'men/mature'                 => '熟女',
                'men/spanking'               => '打屁股',
                'men/halloween'              => '万圣节',
                'men/sex-toys'               => '性玩具',
                'men/jerk-off-instruction'   => '打飞机',
                'men/african'                => '非洲',
                'men/armenian'               => '亚美尼亚',
                'men/dominican'              => '多米尼加',
                'men/greek'                  => '希腊语',
                'men/slovenian'              => '斯洛文尼亚',
                'men/fisting'                => '拳交',
                'men/double-penetration'     => '双管齐下',
                'men/portuguese'             => '葡萄牙语',
                'men/swedish'                => '瑞典',
                'men/fuck-machine'           => '打桩机',
                'men/german'                 => '德语',
                'men/bisexuals'              => '双性恋',
                'men/middle-priced-privates' => '私密',
                'men/anal-toys'              => '拉珠',
                'men/beardy'                 => '熊族',
                'men/canadian'               => '加拿大',
                'men/croatian'               => '克罗地亚',
                'men/latvian'                => '拉脱维亚',
                'men/outdoor'                => '户外',
                'men/nipple-toys'            => '乳夹',
                'men/smoking'                => '抽烟',
                'men/sph'                    => '羞辱小鸡巴',
                'men/tattoos'                => '纹身',
                'men/big-balls'              => '大蛋蛋',
                'men/uncut'                  => '包皮',
                'men/japanese'               => '日本',
                'men/malaysian'              => '马来西亚',
                'men/anal'                   => '肛门',
                'men/erotic-dance'           => '艳舞',
                'men/costarican'             => '哥斯达黎加',
                'men/estonian'               => '爱沙尼亚',
                'men/vietnamese'             => '越南',
                'men/orgasm'                 => '高潮',
                'men/medium'                 => '中等',
                'men/brunettes'              => '黑发',
                'men/chilean'                => '智利',
                'men/slovakian'              => '斯洛伐克',
                'men/recordable-privates'    => '私密',
                'men/dirty-talk'             => '脏话',
                'men/shower'                 => '淋浴',
                'men/humiliation'            => '羞辱',
                'men/pov'                    => '第一人称视角',
                'men/balds'                  => '光头',
                'men/portuguese-speaking'    => '葡萄牙语',
                'men/ugandan'                => '乌干达',
                'men/video-games'            => '视频游戏',
                'men/masturbation'           => '打飞机',
                'men/ejaculation'            => '射精',
                'men/malagasy'               => '马尔加什',
                'men/peruvian'               => '秘鲁',
                'men/sexting'                => '骚话',
                'men/luxurious-privates'     => '私密',
                'men/belgian'                => '比利时',
                'men/big-ass'                => '大屁股',
                'men/nigerian'               => '尼日利亚',
                'men/puertorican'            => '波多黎各',
                'men/taiwanese'              => '台湾',
                'men/new'                    => '最新',
                'men/july4th'                => '星条旗',
                'men/daddies'                => '老父亲',
                'men/asian'                  => '亚洲',
            ],
        'trans'   =>
            [
                'trans/striptease'             => '脱衣舞',
                'trans/albanian'               => '阿尔巴尼亚',
                'trans/colombian'              => '哥伦比亚',
                'trans/korean'                 => '韩国',
                'trans/recordable-privates'    => '私密',
                'trans/luxurious-privates'     => '私密',
                'trans/big-balls'              => '大蛋蛋',
                'trans/titty-fuck'             => '乳交',
                'trans/south-african'          => '南非',
                'trans/blondes'                => '金发',
                'trans/video-games'            => '视频游戏',
                'trans/greek'                  => '希腊',
                'trans/spanish-speaking'       => '西班牙',
                'trans/hd'                     => '高清',
                'trans/outdoor'                => '户外的',
                'trans/milfs'                  => '熟女',
                'trans/bulgarian'              => '保加利亚',
                'trans/ticket-and-group-shows' => '付费秀',
                'trans/oil-show'               => '涂油',
                'trans/japanese'               => '日本',
                'trans/russian'                => '俄罗斯',
                'trans/sexting'                => '骚话',
                'trans/ftm'                    => '女转男',
                'trans/facesitting'            => '骑脸',
                'trans/australian'             => '澳大利亚',
                'trans/best'                   => '最佳',
                'trans/latin'                  => '拉丁',
                'trans/flashing'               => '快闪',
                'trans/nigerian'               => '尼日利亚',
                'trans/vr'                     => 'VR',
                'trans/couples'                => '夫妻',
                'trans/smoking'                => '抽烟',
                'trans/topless'                => '裸上身',
                'trans/tomboy'                 => '假小子',
                'trans/fuck-machine'           => '打桩机',
                'trans/malagasy'               => '马尔加什',
                'trans/jamaican'               => '牙买加',
                'trans/shemale'                => '人妖',
                'trans/orgasm'                 => '高潮',
                'trans/interactive-toys'       => '自慰玩具',
                'trans/danish'                 => '丹麦',
                'trans/zimbabwean'             => '津巴布韦',
                'trans/argentinian'            => '阿根廷',
                'trans/malaysian'              => '马来西亚',
                'trans/bbw'                    => '卡比兽',
                'trans/balds'                  => '光头',
                'trans/double-penetration'     => '双管齐下',
                'trans/german'                 => '德国',
                'trans/indonesian'             => '印度尼西亚人',
                'trans/white'                  => '白人',
                'trans/jerk-off-instruction'   => '打飞机',
                'trans/estonian'               => '爱沙尼亚',
                'trans/israeli'                => '以色列',
                'trans/norwegian'              => '挪威',
                'trans/romanian'               => '罗马尼亚',
                'trans/recordable-publics'     => '公共场所',
                'trans/venezuelan'             => '委内瑞拉',
                'trans/creampie'               => '内射',
                'trans/dildo-or-vibrator'      => '假鸡巴/跳蛋',
                'trans/belgian'                => '比利时',
                'trans/american'               => '美国',
                'trans/colorful'               => '全彩',
                'trans/portuguese'             => '葡萄牙',
                'trans/srilankan'              => '斯里兰卡',
                'trans/swedish'                => '瑞典',
                'trans/swingers'               => '换妻',
                'trans/cei'                    => '女王受虐',
                'trans/big-nipples'            => '大乳头',
                'trans/african'                => '非洲',
                'trans/georgian'               => '格鲁吉亚',
                'trans/serbian'                => '塞尔维亚',
                'trans/teens'                  => '青少年',
                'trans/anal'                   => '肛门',
                'trans/spanking'               => '打屁股',
                'trans/yoga'                   => '瑜伽',
                'trans/ukrainian'              => '乌克兰',
                'trans/big-tits'               => '巨乳',
                'trans/queer'                  => '酷儿',
                'trans/mexican'                => '墨西哥',
                'trans/pov'                    => '第一人称视角',
                'trans/ecuadorian'             => '厄瓜多尔',
                'trans/slovenian'              => '斯洛文尼亚',
                'trans/oktoberfest'            => '啤酒节',
                'trans/asian'                  => '亚裔',
                'trans/swallow'                => '吞精',
                'trans/big-cocks'              => '大鸡巴',
                'trans/new'                    => '最新',
                'trans/irish'                  => '爱尔兰',
                'trans/leather'                => '紧身皮衣',
                'trans/slovakian'              => '斯洛伐克',
                'trans/anal-toys'              => '爆菊玩具',
                'trans/dirty-talk'             => '脏话',
                'trans/cd'                     => '异装',
                'trans/selfsucking'            => '自慰',
                'trans/gagging'                => '深喉',
                'trans/bangladeshi'            => '孟加拉',
                'trans/ugandan'                => '乌干达',
                'trans/mobile'                 => '手机',
                'trans/big-clit'               => '大阴蒂',
                'trans/middle-priced-privates' => '私密',
                'trans/pump'                   => '泵',
                'trans/cam2cam'                => '双镜头',
                'trans/redheads'               => '红发',
                'trans/sissy'                  => '娘娘腔',
                'trans/corset'                 => '束胸',
                'trans/july4th'                => '星条旗',
                'trans/pegging'                => '第四爱',
                'trans/indian'                 => '印度',
                'trans/non-binary'             => '非二元性',
                'trans/bdsm'                   => '捆绑',
                'trans/kiiroo'                 => '飞机杯',
                'trans/humiliation'            => '羞辱',
                'trans/lovense'                => '跳蛋',
                'trans/asmr'                   => 'ASMR',
                'trans/ass-to-mouth'           => '双飞',
                'trans/femboy'                 => '假小子',
                'trans/dutch'                  => '荷兰',
                'trans/hungarian'              => '匈牙利',
                'trans/czech'                  => '捷克',
                'trans/pornstars'              => '色情明星',
                'trans/trimmed'                => '修毛',
                'trans/erotic-dance'           => '热舞',
                'trans/thai'                   => '泰国',
                'trans/squirt'                 => '潮喷',
                'trans/athletic'               => '健身',
                'trans/penis-ring'             => '鸡巴环',
                'trans/cosplay'                => '角色扮演',
                'trans/masturbation'           => '打飞机',
                'trans/camel-toe'              => '骆驼趾',
                'trans/nipple-toys'            => '乳夹',
                'trans/sex-toys'               => '性玩具',
                'trans/portuguese-speaking'    => '葡萄牙语',
                'trans/blowjob'                => '口交',
                'trans/small-tits'             => '贫乳',
                'trans/cumshot'                => '颜射',
                'trans/strapon'                => '女用假鸡巴',
                'trans/bbc'                    => '大黑屌',
                'trans/big-ass'                => '巨尻',
                'trans/ahegao'                 => '高潮脸',
                'trans/canadian'               => '加拿大',
                'trans/spanish'                => '西班牙',
                'trans/swiss'                  => '瑞士',
                'trans/peruvian'               => '秘鲁',
                'trans/sph'                    => '羞辱小鸡巴',
                'trans/massage'                => '按摩',
                'trans/mature'                 => '熟女',
                'trans/flirting'               => '调情',
                'trans/brunettes'              => '黑发',
                'trans/costarican'             => '哥斯达黎加',
                'trans/heels'                  => '高跟鞋',
                'trans/medium'                 => '中等身材',
                'trans/christmas'              => '圣诞节',
                'trans/shower'                 => '淋浴',
                'trans/hairy-armpits'          => '腋毛',
                'trans/cheap-privates'         => '便宜私密',
                'trans/cheapest-privates'      => '最便宜私密',
                'trans/croatian'               => '克罗地亚',
                'trans/french'                 => '法国',
                'trans/taiwanese'              => '台湾',
                'trans/tg'                     => '跨性别色情',
                'trans/petite'                 => '萝莉',
                'trans/cuckold'                => '绿帽',
                'trans/italian'                => '意大利',
                'trans/polish'                 => '波兰',
                'trans/valentines'             => '情人',
                'trans/grannies'               => '老太',
                'trans/upskirt'                => '真空',
                'trans/mtf'                    => '男转女',
                'trans/rimming'                => '毒龙',
                'trans/twerk'                  => '后入',
                'trans/fingering'              => '扣逼',
                'trans/tattoos'                => '纹身',
                'trans/editorial-choice'       => '小编精选',
                'trans/ts'                     => '跨性别',
                'trans/arab'                   => '阿拉伯',
                'trans/hairy'                  => '黑森林',
                'trans/young'                  => '年轻',
                'trans/halloween'              => '万圣节',
                'trans/piercings'              => '穿孔',
                'trans/interracial'            => '黑白配',
                'trans/old-young'              => '老少配',
                'trans/uruguayan'              => '乌拉圭',
                'trans/vietnamese'             => '越南',
                'trans/cock-rating'            => '鸡巴评审会',
                'trans/matched'                => '比赛',
                'trans/puertorican'            => '波多黎各',
                'trans/doggy-style'            => '狗交式',
                'trans/chilean'                => '智利',
                'trans/kenyan'                 => '肯尼亚',
                'trans/shaven'                 => '剃毛',
                'trans/deepthroat'             => '深喉',
                'trans/small-cock'             => '小鸡巴',
                'trans/group-sex'              => '群P',
                'trans/brazilian'              => '巴西',
                'trans/nordic'                 => '北欧',
                'trans/fisting'                => '拳交',
                'trans/gape'                   => '扩肛',
                'trans/latvian'                => '拉脱维亚',
                'trans/lithuanian'             => '立陶宛',
                'trans/latex'                  => '乳胶衣',
                'trans/cowgirl'                => '女牛仔',
                'trans/surinamese'             => '苏里南',
                'trans/uk-models'              => '英国模特',
                'trans/foot-fetish'            => '恋足',
                'trans/armenian'               => '亚美尼亚',
                'trans/dominican'              => '多米尼加',
                'trans/finnish'                => '芬兰',
                'trans/spy'                    => '偷拍',
                'trans/ebony'                  => '黑珍珠',
                'trans/facial'                 => '颜射',
                'trans/kiwi'                   => '新西兰',
                'trans/nylon'                  => '丝袜',
                'trans/uncut'                  => '包皮',
                'trans/tv'                     => 'TV秀',
                'trans/ejaculation'            => '射精',
                'trans/handjob'                => '打飞机',
                'trans/role-play'              => '角色扮演',
                'trans/curvy'                  => '丰乳肥臀',
                'trans/chinese'                => '中国',
            ],
        'couples' =>
            [
                'couples/slovakian'              => '斯洛伐克',
                'couples/taiwanese'              => '台湾',
                'couples/halloween'              => '万圣节',
                'couples/luxurious-privates'     => '私密',
                'couples/swingers'               => '换妻',
                'couples/georgian'               => '格鲁吉亚',
                'couples/ukrainian'              => '乌克兰',
                'couples/outdoor'                => '户外的',
                'couples/albanian'               => '阿尔巴尼亚',
                'couples/double-penetration'     => '双管齐下',
                'couples/strapon'                => '可穿戴鸡巴',
                'couples/big-cocks'              => '大鸡巴',
                'couples/hairy-armpits'          => '腋毛',
                'couples/cuckold'                => '绿帽',
                'couples/goth'                   => '哥特',
                'couples/japanese'               => '日本',
                'couples/nigerian'               => '尼日利亚',
                'couples/vr'                     => 'VR',
                'couples/editorial-choice'       => '小编精选',
                'couples/tattoos'                => '纹身',
                'couples/rimming'                => '毒龙',
                'couples/role-play'              => '角色扮演',
                'couples/kenyan'                 => '肯尼亚',
                'couples/leather'                => '紧身皮衣',
                'couples/spanish-speaking'       => '西班牙语',
                'couples/oktoberfest'            => '啤酒节',
                'couples/valentines'             => '情人',
                'couples/bangladeshi'            => '孟加拉国',
                'couples/german'                 => '德语',
                'couples/israeli'                => '以色列',
                'couples/romanian'               => '罗马尼亚',
                'couples/lithuanian'             => '立陶宛',
                'couples/mexican'                => '墨西哥',
                'couples/vietnamese'             => '越南',
                'couples/cock-rating'            => '鸡巴评审会',
                'couples/smoking'                => '抽烟',
                'couples/spanking'               => '打屁股',
                'couples/cowgirl'                => '女牛仔',
                'couples/hungarian'              => '匈牙利',
                'couples/anal'                   => '肛门',
                'couples/anal-toys'              => '拉珠',
                'couples/cooking'                => '厨房',
                'couples/lesbians'               => '女同',
                'couples/big-ass'                => '大屁股',
                'couples/bbc'                    => '大黑屌',
                'couples/chinese'                => '中国',
                'couples/peruvian'               => '秘鲁',
                'couples/surinamese'             => '苏里南',
                'couples/doggy-style'            => '狗交式',
                'couples/malaysian'              => '马来西亚',
                'couples/nordic'                 => '北欧的',
                'couples/portuguese'             => '葡萄牙',
                'couples/latvian'                => '拉脱维亚',
                'couples/portuguese-speaking'    => '葡萄牙语',
                'couples/old-young'              => '老少配',
                'couples/penis-ring'             => '鸡巴环',
                'couples/blowjob'                => '口交',
                'couples/malagasy'               => '马尔加什',
                'couples/uk-models'              => '英国模特',
                'couples/recordable-privates'    => '私密',
                'couples/fingering'              => '扣逼',
                'couples/argentinian'            => '阿根廷',
                'couples/korean'                 => '韩国',
                'couples/american'               => '美国',
                'couples/italian'                => '意大利',
                'couples/slovenian'              => '斯洛文尼亚',
                'couples/srilankan'              => '斯里兰卡',
                'couples/thai'                   => '泰国',
                'couples/topless'                => '裸体',
                'couples/gang-bang'              => '黑帮',
                'couples/croatian'               => '克罗地亚',
                'couples/finnish'                => '芬兰',
                'couples/recordable-publics'     => '公共场所',
                'couples/sexting'                => '骚话',
                'couples/trimmed'                => '修毛',
                'couples/pregnant'               => '孕妇',
                'couples/small-tits'             => '贫乳',
                'couples/oil-show'               => '涂油',
                'couples/australian'             => '澳大利亚',
                'couples/dominican'              => '多米尼加',
                'couples/kiwi'                   => '新西兰',
                'couples/interracial'            => '黑白配',
                'couples/african'                => '非洲',
                'couples/canadian'               => '加拿大',
                'couples/czech'                  => '捷克',
                'couples/dirty-talk'             => '脏话',
                'couples/masturbation'           => '打飞机',
                'couples/sex-toys'               => '性玩具',
                'couples/ahegao'                 => '高潮脸',
                'couples/serbian'                => '塞尔维亚',
                'couples/uruguayan'              => '乌拉圭',
                'couples/cam2cam'                => '双镜头',
                'couples/best'                   => '最佳',
                'couples/piercings'              => '穿孔',
                'couples/mistresses'             => '情妇',
                'couples/erotic-dance'           => '艳舞',
                'couples/orgasm'                 => '高潮',
                'couples/yoga'                   => '瑜伽',
                'couples/colombian'              => '哥伦比亚',
                'couples/mobile'                 => '手机',
                'couples/hairy'                  => '多毛',
                'couples/swiss'                  => '瑞士',
                'couples/facial'                 => '面部的',
                'couples/big-nipples'            => '大乳头',
                'couples/deepthroat'             => '深喉',
                'couples/armenian'               => '亚美尼亚',
                'couples/brazilian'              => '巴西',
                'couples/danish'                 => '丹麦',
                'couples/estonian'               => '爱沙尼亚',
                'couples/greek'                  => '希腊语',
                'couples/69-position'            => '69',
                'couples/office'                 => '办公室',
                'couples/pussy-licking'          => '舔逼',
                'couples/pov'                    => '第一人称视角',
                'couples/latex'                  => '乳胶衣',
                'couples/matched'                => '比赛',
                'couples/norwegian'              => '挪威',
                'couples/puertorican'            => '波多黎各',
                'couples/zimbabwean'             => '津巴布韦',
                'couples/spy'                    => '偷拍',
                'couples/dildo-or-vibrator'      => '假鸡巴/跳蛋',
                'couples/cumshot'                => '射液',
                'couples/titty-fuck'             => '乳交',
                'couples/ecuadorian'             => '厄瓜多尔',
                'couples/shaven'                 => '剃毛',
                'couples/swedish'                => '瑞典',
                'couples/new'                    => '最新',
                'couples/kissing'                => '接吻',
                'couples/group-sex'              => '群P',
                'couples/ticket-and-group-shows' => '付费秀',
                'couples/irish'                  => '爱尔兰',
                'couples/selfsucking'            => '自吸',
                'couples/hardcore'               => '铁杆',
                'couples/flirting'               => '调情',
                'couples/nylon'                  => '丝袜',
                'couples/south-african'          => '南非',
                'couples/ugandan'                => '乌干达',
                'couples/camel-toe'              => '骆驼趾',
                'couples/venezuelan'             => '委内瑞拉',
                'couples/russian'                => '俄国',
                'couples/emo'                    => 'emo',
                'couples/middle-priced-privates' => '私密',
                'couples/swallow'                => '吞精',
                'couples/foot-fetish'            => '恋足',
                'couples/indonesian'             => '印度尼西亚',
                'couples/humiliation'            => '羞辱',
                'couples/costarican'             => '哥斯达黎加',
                'couples/big-tits'               => '巨乳',
                'couples/creampie'               => '内射',
                'couples/squirt'                 => '潮喷',
                'couples/striptease'             => '脱衣舞',
                'couples/handjob'                => '打飞机',
                'couples/chilean'                => '智利',
                'couples/jamaican'               => '牙买加',
                'couples/jerk-off-instruction'   => '打飞机',
                'couples/cheapest-privates'      => '私密',
                'couples/fisting'                => '拳交',
                'couples/facesitting'            => '骑脸',
                'couples/video-games'            => '视频游戏',
                'couples/belgian'                => '比利时',
                'couples/cheap-privates'         => '私密',
                'couples/ass-to-mouth'           => '双飞',
                'couples/small-cock'             => '小鸡巴',
                'couples/bdsm'                   => '捆绑',
                'couples/french'                 => '法国',
                'couples/corset'                 => '紧身胸衣',
                'couples/christmas'              => '圣诞节',
                'couples/shower'                 => '淋浴',
                'couples/upskirt'                => '真空',
                'couples/pegging'                => '固定',
                'couples/cosplay'                => '角色扮演',
                'couples/nipple-toys'            => '乳夹',
                'couples/bulgarian'              => '保加利亚',
                'couples/spanish'                => '西班牙',
                'couples/heels'                  => '高跟鞋',
                'couples/gagging'                => '深喉',
                'couples/gape'                   => '扩肛',
                'couples/hd'                     => '高清',
                'couples/polish'                 => '波兰',
                'couples/july4th'                => '星条旗',
                'couples/fuck-machine'           => '打桩机',
                'couples/twerk'                  => '后入',
                'couples/big-clit'               => '大阴蒂',
                'couples/interactive-toys'       => '互动玩具',
                'couples/dutch'                  => '荷兰',
            ],
    ];

    const LANGUAGE = [
        "en" => "en",
        "de" => "de",
        "es" => "es",
        "fr" => "fr",
        "it" => "it",
        "sq" => "sq",
        "ar" => "ar",
        "zh" => "zh",
        "hr" => "hr",
        "cs" => "cs",
        "nl" => "nl",
        "fi" => "fi",
        "hu" => "hu",
        "id" => "id",
        "ja" => "ja",
        "ko" => "ko",
        "ms" => "ms",
        "nn" => "nn",
        "no" => "no",
        "pt" => "pt",
        "ro" => "ro",
        "ru" => "ru",
        "sr" => "sr",
        "sv" => "sv",
        "th" => "th",
        "tr" => "tr",
        "vi" => "vi",
        "pl" => "pl",
    ];

    const COUNTRY = [
        "af" => "af",
        "ax" => "ax",
        "al" => "al",
        "dz" => "dz",
        "as" => "as",
        "ad" => "ad",
        "ao" => "ao",
        "ai" => "ai",
        "ag" => "ag",
        "ar" => "ar",
        "am" => "am",
        "aw" => "aw",
        "au" => "au",
        "at" => "at",
        "az" => "az",
        "bs" => "bs",
        "bh" => "bh",
        "bd" => "bd",
        "bb" => "bb",
        "by" => "by",
        "be" => "be",
        "bz" => "bz",
        "bj" => "bj",
        "bm" => "bm",
        "bt" => "bt",
        "bo" => "bo",
        "ba" => "ba",
        "bw" => "bw",
        "br" => "br",
        "io" => "io",
        "vg" => "vg",
        "bn" => "bn",
        "bg" => "bg",
        "bf" => "bf",
        "bi" => "bi",
        "kh" => "kh",
        "cm" => "cm",
        "ca" => "ca",
        "cv" => "cv",
        "ky" => "ky",
        "cf" => "cf",
        "td" => "td",
        "cl" => "cl",
        "cn" => "cn",
        "cx" => "cx",
        "cc" => "cc",
        "co" => "co",
        "km" => "km",
        "cg" => "cg",
        "cd" => "cd",
        "ck" => "ck",
        "cr" => "cr",
        "ci" => "ci",
        "hr" => "hr",
        "cu" => "cu",
        "cy" => "cy",
        "cz" => "cz",
        "dk" => "dk",
        "dj" => "dj",
        "dm" => "dm",
        "do" => "do",
        "ec" => "ec",
        "eg" => "eg",
        "sv" => "sv",
        "gq" => "gq",
        "er" => "er",
        "ee" => "ee",
        "et" => "et",
        "fk" => "fk",
        "fo" => "fo",
        "fj" => "fj",
        "fi" => "fi",
        "fr" => "fr",
        "gf" => "gf",
        "pf" => "pf",
        "ga" => "ga",
        "gm" => "gm",
        "ge" => "ge",
        "de" => "de",
        "gh" => "gh",
        "gi" => "gi",
        "gr" => "gr",
        "gl" => "gl",
        "gd" => "gd",
        "gp" => "gp",
        "gu" => "gu",
        "gt" => "gt",
        "gg" => "gg",
        "gn" => "gn",
        "gw" => "gw",
        "gy" => "gy",
        "ht" => "ht",
        "hn" => "hn",
        "hk" => "hk",
        "hu" => "hu",
        "is" => "is",
        "in" => "in",
        "id" => "id",
        "ir" => "ir",
        "iq" => "iq",
        "ie" => "ie",
        "im" => "im",
        "il" => "il",
        "it" => "it",
        "jm" => "jm",
        "jp" => "jp",
        "je" => "je",
        "jo" => "jo",
        "kz" => "kz",
        "ke" => "ke",
        "ki" => "ki",
        "xk" => "xk",
        "kw" => "kw",
        "kg" => "kg",
        "la" => "la",
        "lv" => "lv",
        "lb" => "lb",
        "ls" => "ls",
        "lr" => "lr",
        "ly" => "ly",
        "li" => "li",
        "lt" => "lt",
        "lu" => "lu",
        "mo" => "mo",
        "mk" => "mk",
        "mg" => "mg",
        "mw" => "mw",
        "my" => "my",
        "mv" => "mv",
        "ml" => "ml",
        "mt" => "mt",
        "mh" => "mh",
        "mq" => "mq",
        "mr" => "mr",
        "mu" => "mu",
        "yt" => "yt",
        "mx" => "mx",
        "fm" => "fm",
        "md" => "md",
        "mc" => "mc",
        "mn" => "mn",
        "me" => "me",
        "ms" => "ms",
        "ma" => "ma",
        "mz" => "mz",
        "mm" => "mm",
        "na" => "na",
        "nr" => "nr",
        "np" => "np",
        "nl" => "nl",
        "nc" => "nc",
        "nz" => "nz",
        "ni" => "ni",
        "ne" => "ne",
        "ng" => "ng",
        "nu" => "nu",
        "nf" => "nf",
        "kp" => "kp",
        "mp" => "mp",
        "no" => "no",
        "om" => "om",
        "pk" => "pk",
        "pw" => "pw",
        "ps" => "ps",
        "pa" => "pa",
        "pg" => "pg",
        "py" => "py",
        "pe" => "pe",
        "ph" => "ph",
        "pn" => "pn",
        "pl" => "pl",
        "pt" => "pt",
        "pr" => "pr",
        "qa" => "qa",
        "re" => "re",
        "ro" => "ro",
        "ru" => "ru",
        "rw" => "rw",
        "bl" => "bl",
        "sh" => "sh",
        "kn" => "kn",
        "lc" => "lc",
        "mf" => "mf",
        "pm" => "pm",
        "vc" => "vc",
        "ws" => "ws",
        "sm" => "sm",
        "st" => "st",
        "sa" => "sa",
        "sn" => "sn",
        "rs" => "rs",
        "sc" => "sc",
        "sl" => "sl",
        "sg" => "sg",
        "sk" => "sk",
        "si" => "si",
        "sb" => "sb",
        "so" => "so",
        "za" => "za",
        "kr" => "kr",
        "es" => "es",
        "lk" => "lk",
        "sd" => "sd",
        "sr" => "sr",
        "sj" => "sj",
        "sz" => "sz",
        "se" => "se",
        "ch" => "ch",
        "sy" => "sy",
        "tw" => "tw",
        "tj" => "tj",
        "tz" => "tz",
        "th" => "th",
        "tl" => "tl",
        "tg" => "tg",
        "tk" => "tk",
        "to" => "to",
        "tt" => "tt",
        "tn" => "tn",
        "tr" => "tr",
        "tm" => "tm",
        "tc" => "tc",
        "tv" => "tv",
        "um" => "um",
        "vi" => "vi",
        "ug" => "ug",
        "ua" => "ua",
        "ae" => "ae",
        "gb" => "gb",
        "us" => "us",
        "uy" => "uy",
        "uz" => "uz",
        "vu" => "vu",
        "va" => "va",
        "ve" => "ve",
        "vn" => "vn",
        "wf" => "wf",
        "eh" => "eh",
        "ye" => "ye",
        "zm" => "zm",
        "zw" => "zw",
    ];

    const SE_LAYOUT_1 = [
        'id',
        'cover',
        'username',
        'thumb',
        'show',
        'view_oct',
        'view_count',
        'real_view_count'
    ];
    const SE_LAYOUT_2 = [
        'id',
        'cover',
        'username',
        'thumb',
        'view_oct',
        'view_count',
        'real_view_count',
        'favorite_oct',
        'favorite_count',
        'real_favorite_count',
        'like_count',
        'real_like_count',
        'comment_ct',
        'hls',
        'show',
        'type',
        'coins',
        'intro'
    ];

    const CK_LIST_LIVE = 'ck:list:live:%s:%s:%s';
    const GP_LIST_LIVE = 'gp:list:live';
    const CN_LIST_LIVE = '直播-列表';

    const CK_LIST_LIVE_RECOMMEND = 'ck:list:live:recommend:%s:%s:%s';
    const GP_LIST_LIVE_RECOMMEND = 'gp:list:live:recommend';
    const CN_LIST_LIVE_RECOMMEND = '直播-推荐列表';

    const CK_LIST_LIVE_SEARCH = 'ck:list:live:search:%s:%s:%s';
    const GP_LIST_LIVE_SEARCH = 'gp:list:live:search';
    const CN_LIST_LIVE_SEARCH = '直播-搜索列表';

    const CK_LIVE_DETAIL = 'ck:live:detail:%s';
    const GP_LIVE_DETAIL = 'gp:live:detail';
    const CN_LIVE_DETAIL = '直播-详情';

    //单场购买直播的集合
    const LIVE_PAY_SET = 'live:pay:set:%d';

    protected $appends = [
        'is_like',
        'is_favorite',
        'is_pay',
    ];

    public static function decrByLike($id){
        self::where('id', $id)->where('real_like_count', '>', 0)->decrement('real_like_count');
    }

    public static function incrByLike($id){
        self::where('id', $id)->increment('real_like_count', 1, ['like_count' => DB::raw('like_count + 3')]);
    }

    public static function decrByFavorite($id){
        self::where('id', $id)->where('real_favorite_count', '>', 0)->decrement('real_favorite_count');
    }

    public static function incrByFavorite($id){
        self::where('id', $id)->increment('real_favorite_count', 1, ['favorite_count' => DB::raw('favorite_count + 3')]);
    }

    public static function incrViewCt($id)
    {
        $key = "live:view:key:%d";
        $key = sprintf($key, $id);
        redis()->incrBy($key, 1);
        $val = redis()->get($key);
        $val = intval($val);
        if ($val >= 50){
            self::where('id', $id)->increment('real_view_count', $val, ['view_count' => DB::raw('view_count + ' . $val * 3)]);
            redis()->set($key, 0);
        }
    }

    public static function incrPayCount($id, $coins)
    {
        self::where('id', $id)->increment('pay_coins', $coins, ['pay_ct' => DB::raw('pay_ct + 1')]);
    }

    public static function incrRewardCount($id, $coins)
    {
        self::where('id', $id)->increment('reward_coins', $coins, ['reward_ct' => DB::raw('reward_ct + 1')]);
    }

    public function getViewCountAttribute()
    {
        $view_fct = $this->attributes['view_count'] ?? 0;
        $view_oct = $this->attributes['view_oct'] ?? 0;
        if ($view_fct < 300000){
            $id = $this->attributes['id'];
            $rating = rand(300000, 1000000);
            jobs([LiveModel::class, 'genFakeData'], [$id, $rating]);
        }
        return MODULE_NAME == 'api' ? $view_fct + $view_oct : $view_fct;
    }

    public static function genFakeData($id, $rating){
        $like = intval($rating / rand(6, 10));
        $favorite = intval($like /  rand(6, 10));
        $data = [
            'view_count' => $rating,
            'like_count' => $like,
            'favorite_count' => $favorite
        ];
        LiveModel::where('id', $id)->update($data);
    }

    public function getFavoriteCountAttribute(): int
    {
        $favorite_fct = $this->attributes['favorite_count'] ?? 0;
        $favorite_ct = $this->attributes['real_favorite_count'] ?? 0;
        $favorite_oct = $this->attributes['favorite_oct'] ?? 0;
        return MODULE_NAME == 'api' ? $favorite_fct + $favorite_ct + $favorite_oct : $favorite_fct;
    }

    //是否点赞
    public function getIsLikeAttribute()
    {
        $watchUser = self::$watchUser;
        if (empty($watchUser) || !isset($this->attributes['id'])) {
            return 0;
        }
        static $ids = null;
        if (null === $ids) {
            $ids = redis()->sMembers(sprintf(LiveLikeModel::MEMBER_LIVE_LIKE_SET, $watchUser->uid));
        }
        if (in_array($this->attributes['id'], $ids)) {
            return 1;
        }
        return 0;
    }

    //是否收藏
    public function getIsFavoriteAttribute()
    {
        $watchUser = self::$watchUser;
        if (empty($watchUser) || !isset($this->attributes['id'])) {
            return 0;
        }
        static $ids = null;
        if (null === $ids) {
            $ids = redis()->sMembers(sprintf(LiveFavoritesModel::MEMBER_FAVORITE_LIVE_SET, $watchUser->uid));
        }
        if (in_array($this->attributes['id'], $ids)) {
            return 1;
        }
        return 0;
    }

    public function getIsPayAttribute()
    {
        $watchUser = self::$watchUser;
        if (empty($watchUser) || !isset($this->attributes['id'])) {
            return 0;
        }
        $aff = $watchUser->getAttribute('aff') ?? 0;

        $type = $this->attributes['type'];
        if ($type == self::TYPE_FREE){
            return 1;
        }

        if ($type == self::TYPE_VIP){
            $hasPrivilege = UsersProductPrivilegeModel::hasPrivilege(USER_PRIVILEGE, PrivilegeModel::RESOURCE_TYPE_LIVE_VIP, PrivilegeModel::PRIVILEGE_TYPE_VIEW);
            if ($hasPrivilege){
                return 1;
            }
        }else{
            $hasPrivilege = UsersProductPrivilegeModel::hasPrivilege(USER_PRIVILEGE, PrivilegeModel::RESOURCE_TYPE_LIVE_COINS, PrivilegeModel::PRIVILEGE_TYPE_VIEW);
            if ($hasPrivilege){
                return 1;
            }

            $audioPay = LivePayModel::hasBuy($aff, $this->attributes['id']);
            //$audioPay = redis()->sIsMember(sprintf(LiveModel::LIVE_PAY_SET, $this->attributes['id']), $aff);
            if ($audioPay){
                return 1;
            }
        }

        return 0;
    }

    public function getThumbAttribute(): string
    {
        $uri = $this->attributes['thumb'] ?? '';
        return $uri ? url_cover($uri) : '';
    }

    public function setThumbAttribute($value)
    {
        $this->resetSetPathAttribute('thumb', $value);
    }

    public function getCoverAttribute(): string
    {
        $uri = $this->attributes['cover'] ?? '';
        return $uri ? url_cover($uri) : '';
    }

    public function setCoverAttribute($value)
    {
        $this->resetSetPathAttribute('cover', $value);
    }

    public static function process_hls($hls)
    {
        $m3u8s = $hls ? json_decode($hls, true) : [];
        foreach ($m3u8s as &$m3u8) {
            $host = parse_url($m3u8['url'], PHP_URL_HOST);
            $prefix = explode(".", $host)[0];
            $domain = setting('live_stream_domain', '');
            $m3u8['url'] = str_replace($host, $prefix . '.' . $domain, $m3u8['url']);
        }
        return $m3u8s;
    }

    public static function defend_related($id, $type, $symbol, $value)
    {
        LiveRelatedModel::where('theme_id', $id)->delete();
        LiveModel::select(['id'])
            ->when(1, function ($query) use ($type, $symbol, $value) {
                $values = explode(',', $value);
                $values = array_filter(array_unique($values));
                $fn = function ($query, $symbol, $values, $field) {
                    $raws = [];
                    foreach ($values as $value) {
                        $raws[] = sprintf('FIND_IN_SET("%s",`%s`)', $value, $field);
                    }
                    if (!$raws) {
                        return;
                    }
                    if ($symbol == LiveThemeModel::SYMBOL_OK) {
                        $query->whereRaw('(' . implode(" or ", $raws) . ')');
                        return;
                    }
                    $query->whereRaw('(NOT ' . implode(" and NOT ", $raws) . ')');
                };
                if ($type == LiveThemeModel::TYPE_COUNTRY) {
                    $fn($query, $symbol, $values, 'country');
                }
                if ($type == LiveThemeModel::TYPE_LANGUAGE) {
                    $fn($query, $symbol, $values, 'language');
                }
                if ($type == LiveThemeModel::TYPE_GENDER) {
                    $fn($query, $symbol, $values, 'gender');
                }
                if ($type == LiveThemeModel::TYPE_TAG) {
                    $fn($query, $symbol, $values, 'tag');
                }
            })
            ->get()
            ->map(function ($item) use ($id) {
                $data = [
                    'theme_id' => $id,
                    'live_id'  => $item->id,
                ];
                $isOk = LiveRelatedModel::create($data);
                test_assert($isOk, '出现异常');
            });
    }

    public static function list_live($id, $page, $limit)
    {
        $key = sprintf(self::CK_LIST_LIVE, $id, $page, $limit);
        return cached($key)
            ->group(self::GP_LIST_LIVE)
            ->chinese(self::CN_LIST_LIVE)
            ->fetchPhp(function () use ($id, $page, $limit) {
                $id_ary = LiveRelatedModel::select(['live.id'])
                    ->leftJoin('live', 'live.id', '=', 'live_related.live_id')
                    ->where('theme_id', $id)
                    ->where('live.status', self::STATUS_ON)
                    ->where('live.show', self::SHOW_PUBLIC)
                    //->orderByRaw('(ks_live.view_oct+ks_live.real_view_count) desc')
                    ->orderByRaw('ks_live.sort desc')
                    ->orderByDesc('live.id')
                    ->forPage($page, $limit)
                    ->get()
                    ->pluck('id');

                $list = self::select(self::SE_LAYOUT_1)
                    ->whereIn('id', $id_ary)
                    ->get();

                return array_keep_idx($list, $id_ary);
            }, mt_rand(10, 30));
    }

    public static function list_recommend($id, $theme_ids, $page, $limit)
    {
        $key = sprintf(self::CK_LIST_LIVE_RECOMMEND, $id, $page, $limit);
        return cached($key)
            ->group(self::GP_LIST_LIVE_RECOMMEND)
            ->chinese(self::CN_LIST_LIVE_RECOMMEND)
            ->fetchPhp(function () use ($id, $theme_ids, $page, $limit) {
                //DB::enableQueryLog();
                $id_ary = LiveRelatedModel::select(['live.id'])
                    ->leftJoin('live', 'live.id', '=', 'live_related.live_id')
                    ->whereIn('theme_id', $theme_ids)
                    ->where('live.status', self::STATUS_ON)
                    ->where('live.show', self::SHOW_PUBLIC)
                    ->where('live.id', '!=', $id)
                    ->orderByRaw('(ks_live.view_oct+ks_live.real_view_count) desc')
                    ->orderByDesc('live.id')
                    ->limit(1000)
                    ->get()
                    ->pluck('id');

                if ($id_ary->count() <= $limit / 2) {
                    $id_ary = self::select(['id'])
                        ->where('status', self::STATUS_ON)
                        ->where('show', self::SHOW_PUBLIC)
                        ->where('id', '!=', $id)
                        //->orderByRaw('(view_oct+view_fct) desc')
                        ->orderByDesc('sort')
                        ->orderByDesc('id')
                        ->limit(1000)
                        ->get()
                        ->pluck('id');
                }
                $id_ary = $id_ary->shuffle()->forPage($page, $limit);

                $list = self::select(self::SE_LAYOUT_1)
                    ->whereIn('id', $id_ary)
                    ->get();

                return array_keep_idx($list, $id_ary);
            }, mt_rand(10, 30));
    }

    public static function detail($id)
    {
        $cache_key = sprintf(self::CK_LIVE_DETAIL, $id);
        return cached($cache_key)
            ->group(self::GP_LIVE_DETAIL)
            ->chinese(self::CN_LIVE_DETAIL)
            ->fetchPhp(function () use ($id) {
                return self::select(self::SE_LAYOUT_2)
                    ->where('id', $id)
                    ->first();
            }, mt_rand(10, 30));
    }

    public static function search($word, $page, $limit)
    {
        $key = sprintf(self::CK_LIST_LIVE_SEARCH, $word, $page, $limit);
        return cached($key)
            ->group(self::GP_LIST_LIVE_SEARCH)
            ->chinese(self::CN_LIST_LIVE_SEARCH)
            ->fetchPhp(function () use ($word, $page, $limit) {
                $id_ary = self::select(['id'])
                    ->where('username', 'like', '%' . $word . '%')
                    ->where('status', self::STATUS_ON)
                    ->where('show', self::SHOW_PUBLIC)
                    //->orderByRaw('(view_oct+view_fct) desc')
                    ->orderByDesc('sort')
                    ->orderByDesc('id')
                    ->forPage($page, $limit)
                    ->get()
                    ->pluck('id');

                $list = self::select(self::SE_LAYOUT_1)
                    ->whereIn('id', $id_ary)
                    ->get();

                return array_keep_idx($list, $id_ary);
            }, mt_rand(10, 30));
    }
}