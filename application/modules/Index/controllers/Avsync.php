<?php
/**
 *
 * @date 2020/3/31
 * @author
 * @copyright kuaishou by KS
 *
 */

class AvsyncController extends SiteController
{

    public $post = null;

    public function init()
    {
        if ($this->getRequest()->isPost()) {
            $data = $_POST['data'] ?? '';
            $json = LibCrypt::decrypt($data, '123456abc');
            $data = json_decode($json, true);
            if (json_last_error() != JSON_ERROR_NONE) {
                errLog("解密错误；" . json_last_error_msg() . '#' . var_export($_POST, true));
                exit(0);
            }
            if (isset($data['av']) && is_array($data['av'])) {
                $this->post = $data['av'];
            }
        }
    }

    /**
     * 鲁先生接口同步处理
     *
     * 结构如下
     *[
     * 'av' =>[]
     * 'mod' => '',
     * 'code' => '',
     * 'version' => '2.0.0',
     * 'oauth_id' => '85efbff2e5fb1cf91c131d5b9180add2',
     * 'oauth_type' => 'android',
     * 'type' => 'av'
     * ]
     *
     * //  https://examine.tiansex.tv/upload18files/e965e29361a76a31cf3e5775ea17d7a4/e965e29361a76a31cf3e5775ea17d7a4.m3u8
     *
     * av - item:
     *
     * array (
     * 'id' => '22476',
     * '_id' => 'HEYZO-2224',
     * '_id_hash' => '6cf171c39a3fbc3c2fe0d3eefdf96253',
     * 'title' => '华丽身体下的性爱交织，欲仙欲醉【无码】',
     * 'source_240' => '',
     * 'source_480' => '',
     * 'source_720' => '/upload18files/e965e29361a76a31cf3e5775ea17d7a4/e965e29361a76a31cf3e5775ea17d7a4.m3u8',
     * 'source_1080' => '',
     * 'v_ext' => 'm3u8',
     * 'duration' => '3600',
     * 'cover_thumb' => '/new/av/20200401/2020040112054191878.jpg',
     * 'cover_full' => '/new/av/20200401/2020040112054191878.jpg',
     * 'directors' => '',
     * 'publisher' => '',
     * 'actors' => '上山奈々',
     * 'category' => '',
     * 'tags' => '性爱摄影,女上位,指交,口交,痴女,美腿,体内射精',
     * 'via' => 'self',
     * 'is_deleted' => '0',
     * 'desc' => '',
     * 'onshelf_tm' => '0',
     * 'rating' => '847',
     * 'created_at' => '1585713940',
     * 'refresh_at' => '1585713940',
     * 'isfree' => '0',
     * 'dislike' => '0',
     * 'like' => '5',
     * 'price' => '0',
     * ),
     */
    public function indexAction()
    {
        return ;//暂时手动 lusir 手动搬运
        $msg = 'avsync-同步数据 #' . date('Y-m-d H:i:s', TIMESTAMP) . PHP_EOL;
        if (!$this->getRequest()->isPost()) {
            $msg .= '非法请求:' . PHP_EOL . var_export($_REQUEST, true);
            errLog($msg);
            return;
        }
        //return ;
        //test insertData
        /*$testData = array(
            'av'         =>
                array(
                    0 =>
                        array(
                            'id'          => '22457',
                            '_id'         => 'JDXA-57061',
                            '_id_hash'    => '454383097f36fb539c69077d5200dcb1',
                            'title'       => '雾谷伯爵家6姐妹，和我的性爱契约签订【动画】',
                            'source_240'  => '',
                            'source_480'  => '',
                            'source_720'  => '/upload18files/22cc5a4ed4bf943cf1de4d002513db8e/22cc5a4ed4bf943cf1de4d002513db8e.m3u8',
                            'source_1080' => '',
                            'v_ext'       => 'm3u8',
                            'duration'    => '1500',
                            'cover_thumb' => '/new/av/20200331/2020033111023017490.jpg',
                            'cover_full'  => '/new/av/20200331/2020033111023017490.jpg',
                            'directors'   => '',
                            'publisher'   => '',
                            'actors'      => 'H动画',
                            'category'    => '',
                            'tags'        => '动画,巨乳,美少女,动漫,群交,多P,淫语,学生妹,制服诱惑',
                            'via'         => 'self',
                            'is_deleted'  => '0',
                            'desc'        => '',
                            'onshelf_tm'  => '0',
                            'rating'      => '2642',
                            'created_at'  => '1585623749',
                            'refresh_at'  => '1585623749',
                            'isfree'      => '1',
                            'dislike'     => '0',
                            'like'        => '15',
                            'price'       => '0',
                        ),
                    1 =>
                        array(
                            'id'          => '22458',
                            '_id'         => 'JUL-167',
                            '_id_hash'    => 'eb3d324f9e6cea48f515d276482cb5fa',
                            'title'       => '素股摩擦,防御力降为零的高潮风俗店 希岛爱理',
                            'source_240'  => '',
                            'source_480'  => '',
                            'source_720'  => '/upload18files/1d5420cdeeb65f5b37406bf85258e71f/1d5420cdeeb65f5b37406bf85258e71f.m3u8',
                            'source_1080' => '',
                            'v_ext'       => 'm3u8',
                            'duration'    => '7200',
                            'cover_thumb' => '/new/av/20200331/2020033111191529584.jpg',
                            'cover_full'  => '/new/av/20200331/2020033111191529584.jpg',
                            'directors'   => '',
                            'publisher'   => '',
                            'actors'      => '希島あいり',
                            'category'    => '',
                            'tags'        => '熟女,人妻,乱伦,单体作品,不伦,苗条,贫乳,熟女',
                            'via'         => 'self',
                            'is_deleted'  => '0',
                            'desc'        => '',
                            'onshelf_tm'  => '0',
                            'rating'      => '4495',
                            'created_at'  => '1585624754',
                            'refresh_at'  => '1585624754',
                            'isfree'      => '0',
                            'dislike'     => '4',
                            'like'        => '16',
                            'price'       => '0',
                        ),
                    2 =>
                        array(
                            'id'          => '22459',
                            '_id'         => '369FCTD-048',
                            '_id_hash'    => 'c51c12a683ad4a360924a43cd58fccad',
                            'title'       => '地窖里的恶魔女孩，被关起来蹂躏【独家】',
                            'source_240'  => '',
                            'source_480'  => '',
                            'source_720'  => '/upload18files/5fc37dbbb13f89144742b74ffbba609e/5fc37dbbb13f89144742b74ffbba609e.m3u8',
                            'source_1080' => '',
                            'v_ext'       => 'm3u8',
                            'duration'    => '3600',
                            'cover_thumb' => '/new/av/20200331/2020033111220968679.jpg',
                            'cover_full'  => '/new/av/20200331/2020033111220968679.jpg',
                            'directors'   => '',
                            'publisher'   => '',
                            'actors'      => '素人',
                            'category'    => '',
                            'tags'        => '体内射精,业馀 苗条,美乳,美少女,独家推荐,制服',
                            'via'         => 'self',
                            'is_deleted'  => '0',
                            'desc'        => '',
                            'onshelf_tm'  => '0',
                            'rating'      => '6745',
                            'created_at'  => '1585624929',
                            'refresh_at'  => '1585624929',
                            'isfree'      => '1',
                            'dislike'     => '5',
                            'like'        => '8',
                            'price'       => '0',
                        ),
                    3 =>
                        array(
                            'id'          => '22460',
                            '_id'         => '032720-001-CARIB',
                            '_id_hash'    => '751e29bfe53d61e96b163979da2efe46',
                            'title'       => '舞蹈家的性爱！ 射精我的脸！【无码】',
                            'source_240'  => '',
                            'source_480'  => '',
                            'source_720'  => '/upload18files/4886ebd3142c48e2646e208b211b9f18/4886ebd3142c48e2646e208b211b9f18.m3u8',
                            'source_1080' => '',
                            'v_ext'       => 'm3u8',
                            'duration'    => '3600',
                            'cover_thumb' => '/new/av/20200331/2020033111262029123.jpg',
                            'cover_full'  => '/new/av/20200331/2020033111262029123.jpg',
                            'directors'   => '',
                            'publisher'   => '',
                            'actors'      => '日高千晶',
                            'category'    => '',
                            'tags'        => '颜射,巨乳,淫语,69,美乳,痴女,美腿,美臀,体内射精',
                            'via'         => 'self',
                            'is_deleted'  => '0',
                            'desc'        => '',
                            'onshelf_tm'  => '0',
                            'rating'      => '3030',
                            'created_at'  => '1585625180',
                            'refresh_at'  => '1585625180',
                            'isfree'      => '0',
                            'dislike'     => '2',
                            'like'        => '7',
                            'price'       => '0',
                        ),
                    4 =>
                        array(
                            'id'          => '22461',
                            '_id'         => 'You Sleep',
                            '_id_hash'    => '4be6ff530714451dbbaaa4749bc46c1b',
                            'title'       => '睡觉时性艺术，早操运动【无码】莉莉·帕克',
                            'source_240'  => '',
                            'source_480'  => '',
                            'source_720'  => '/upload18files/fb01a6306cbae3a20f180a173a3b0ea0/fb01a6306cbae3a20f180a173a3b0ea0.m3u8',
                            'source_1080' => '',
                            'v_ext'       => 'm3u8',
                            'duration'    => '1500',
                            'cover_thumb' => '/new/av/20200331/2020033111314147821.jpg',
                            'cover_full'  => '/new/av/20200331/2020033111314147821.jpg',
                            'directors'   => '',
                            'publisher'   => '',
                            'actors'      => '素人',
                            'category'    => '',
                            'tags'        => '欧美,无码,外国人,巨乳,淫语,内射,道具,中出',
                            'via'         => 'self',
                            'is_deleted'  => '0',
                            'desc'        => '',
                            'onshelf_tm'  => '0',
                            'rating'      => '6578',
                            'created_at'  => '1585625500',
                            'refresh_at'  => '1585625500',
                            'isfree'      => '1',
                            'dislike'     => '3',
                            'like'        => '34',
                            'price'       => '0',
                        ),
                    5 =>
                        array(
                            'id'          => '22462',
                            '_id'         => 'MIAA-240-CN',
                            '_id_hash'    => 'd66270f8f0fc6910fa2d1bcf1df5dd7e',
                            'title'       => '催棉洗脑NTR 对朋友女友催眠搞爱爱同居,搞她怀孕【中字】根尾朱里',
                            'source_240'  => '',
                            'source_480'  => '',
                            'source_720'  => '/upload18files/32330f8ca1d7aae30a409f5494cb5d9e/32330f8ca1d7aae30a409f5494cb5d9e.m3u8',
                            'source_1080' => '',
                            'v_ext'       => 'm3u8',
                            'duration'    => '7200',
                            'cover_thumb' => '/new/av/20200331/2020033111415336981.jpg',
                            'cover_full'  => '/new/av/20200331/2020033111415336981.jpg',
                            'directors'   => '',
                            'publisher'   => '',
                            'actors'      => '根尾あかり',
                            'category'    => '',
                            'tags'        => '中文字幕,NTR,体内射精,单体作品,口交,美少女',
                            'via'         => 'self',
                            'is_deleted'  => '0',
                            'desc'        => '',
                            'onshelf_tm'  => '0',
                            'rating'      => '2482',
                            'created_at'  => '1585626112',
                            'refresh_at'  => '1585626112',
                            'isfree'      => '0',
                            'dislike'     => '2',
                            'like'        => '5',
                            'price'       => '0',
                        ),
                    6 =>
                        array(
                            'id'          => '22463',
                            '_id'         => 'JUL-120-CN',
                            '_id_hash'    => '5cb7a2dafebad51c047473fc90ca5c99',
                            'title'       => '知名化妆品广告模特，拥有美丽的白皮肤少妇进军AV！ ！',
                            'source_240'  => '',
                            'source_480'  => '',
                            'source_720'  => '/upload18files/9b3b30de02bf6d1a91c6a84dd94018bf/9b3b30de02bf6d1a91c6a84dd94018bf.m3u8',
                            'source_1080' => '',
                            'v_ext'       => 'm3u8',
                            'duration'    => '9600',
                            'cover_thumb' => '/new/av/20200331/2020033111455939432.jpg',
                            'cover_full'  => '/new/av/20200331/2020033111455939432.jpg',
                            'directors'   => '',
                            'publisher'   => '',
                            'actors'      => '美森けい',
                            'category'    => '',
                            'tags'        => '熟女,人妻,巨乳,美乳,中文字幕,美腿,单体作品 ,高大',
                            'via'         => 'self',
                            'is_deleted'  => '0',
                            'desc'        => '',
                            'onshelf_tm'  => '0',
                            'rating'      => '9979',
                            'created_at'  => '1585626358',
                            'refresh_at'  => '1585626358',
                            'isfree'      => '1',
                            'dislike'     => '6',
                            'like'        => '67',
                            'price'       => '0',
                        ),
                    7 =>
                        array(
                            'id'          => '22464',
                            '_id'         => 'WANZ-942',
                            '_id_hash'    => '26f8122280960ecc84fa0e55c4ef82f8',
                            'title'       => '日本全国最佳模特JULIA被老头子偷窥，并捆绑强暴',
                            'source_240'  => '',
                            'source_480'  => '',
                            'source_720'  => '/upload18files/515c3617d3225599a0a7ca2b7820d2a3/515c3617d3225599a0a7ca2b7820d2a3.m3u8',
                            'source_1080' => '',
                            'v_ext'       => 'm3u8',
                            'duration'    => '7200',
                            'cover_thumb' => '/new/av/20200331/2020033111495634429.jpg',
                            'cover_full'  => '/new/av/20200331/2020033111495634429.jpg',
                            'directors'   => '',
                            'publisher'   => '',
                            'actors'      => 'JULIA',
                            'category'    => '',
                            'tags'        => '巨乳,美乳,体内射精,单体作品,姐姐,苗条,巨乳',
                            'via'         => 'self',
                            'is_deleted'  => '0',
                            'desc'        => '',
                            'onshelf_tm'  => '0',
                            'rating'      => '3792',
                            'created_at'  => '1585626595',
                            'refresh_at'  => '1585626595',
                            'isfree'      => '0',
                            'dislike'     => '0',
                            'like'        => '11',
                            'price'       => '0',
                        ),
                    8 =>
                        array(
                            'id'          => '22465',
                            '_id'         => 'MIDE-754',
                            '_id_hash'    => 'f0371f0873f0affafaccaeaa3e7806d5',
                            'title'       => '超豪华SEXY女用贴身内衣裤销售员 诱惑销售',
                            'source_240'  => '',
                            'source_480'  => '',
                            'source_720'  => '/upload18files/7dc68403c3dbbc4b6761a0424997d123/7dc68403c3dbbc4b6761a0424997d123.m3u8',
                            'source_1080' => '',
                            'v_ext'       => 'm3u8',
                            'duration'    => '12000',
                            'cover_thumb' => '/new/av/20200331/2020033111540917004.jpg',
                            'cover_full'  => '/new/av/20200331/2020033111540917004.jpg',
                            'directors'   => '',
                            'publisher'   => '',
                            'actors'      => '高橋しょう子',
                            'category'    => '',
                            'tags'        => '单体作品,女用内衣裤,痴女,手交,淫语,巨乳',
                            'via'         => 'self',
                            'is_deleted'  => '0',
                            'desc'        => '',
                            'onshelf_tm'  => '0',
                            'rating'      => '11408',
                            'created_at'  => '1585626848',
                            'refresh_at'  => '1585626848',
                            'isfree'      => '1',
                            'dislike'     => '8',
                            'like'        => '84',
                            'price'       => '0',
                        ),
                    9 =>
                        array(
                            'id'          => '22466',
                            '_id'         => 'EBOD-738-CN',
                            '_id_hash'    => 'ea3a80ad0f8c2e806866c9c3209caf14',
                            'title'       => '一天至少2~3次做爱的深田咏梅性爱禁欲一周，展示野兽般的性爱需求【中字】',
                            'source_240'  => '',
                            'source_480'  => '',
                            'source_720'  => '/upload18files/efaa1c18bbc32257957340787f1db645/efaa1c18bbc32257957340787f1db645.m3u8',
                            'source_1080' => '',
                            'v_ext'       => 'm3u8',
                            'duration'    => '7200',
                            'cover_thumb' => '/new/av/20200331/2020033112041329343.jpg',
                            'cover_full'  => '/new/av/20200331/2020033112041329343.jpg',
                            'directors'   => '',
                            'publisher'   => '',
                            'actors'      => '深田えいみ',
                            'category'    => '',
                            'tags'        => '中文字幕,多P,体内射精,单体作品,口交,女上位,美少女',
                            'via'         => 'self',
                            'is_deleted'  => '0',
                            'desc'        => '',
                            'onshelf_tm'  => '0',
                            'rating'      => '5385',
                            'created_at'  => '1585627452',
                            'refresh_at'  => '1585627452',
                            'isfree'      => '0',
                            'dislike'     => '3',
                            'like'        => '17',
                            'price'       => '0',
                        ),
                ),
            'mod'        => '',
            'code'       => '',
            'version'    => '2.0.0',
            'oauth_id'   => '85efbff2e5fb1cf91c131d5b9180add2',
            'oauth_type' => 'android',
            'type'       => 'av',
        );
        $this->post = $testData['av'];*/

        $msg .= var_export($this->post, true);
        errLog($msg);
        array_map(function ($mvData) {
            if(!isset($mvData['source_720']) || strlen($mvData['source_720'])<10){
                errLog(PHP_EOL."################# 小视屏 不做处理 #################".PHP_EOL);
                return ;
            }
            $rdDays = rand(2, 10);//随机天数
            $tags = trim($mvData['tags'], ',');
            $uid = getOfficialUID();
            $cacheKey = 'avsync';
            redis()->hLen($cacheKey) > 2000 && redis()->delete($cacheKey);//如果超过2000 就自动清理一下
            $has = cached($cacheKey)->hash($mvData['source_720'])->expired(10*24*3600)->fetch(function()use($mvData,$uid){
                return   MvModel::where(['uid'=>$uid,'via'=>MvModel::VIA_LUSIR,'full_m3u8'=>$mvData['source_720']])->exists();
            });
            if($has){
                //不重复插入
                return ;
            }

            $insertData = [
                'uid'              => $uid,
                'music_id'         => 0,
                'coins'            => $mvData['price'] == 0 ? MvModel::COIN_DEFAULT : $mvData['price'],
                'vip_coins'        => -1,
                'title'            => "[{$mvData['_id']}]".$mvData['title'],
                'm3u8'             => $mvData['source_720'],
                'full_m3u8'        => $mvData['source_720'],
                'v_ext'            => $mvData['v_ext'],
                'duration'         => $mvData['duration'],
                'cover_thumb'      => $mvData['cover_thumb'],
                'directors'        => $mvData['directors'] ?? '川介之',
                'actors'           => $mvData['actors'] ?? '素人',
                'category'         => $mvData['category'],
                'tags'             => $tags,
                'via'              => MvModel::VIA_LUSIR,
                'onshelf_tm'       => $mvData['onshelf_tm'] ?? strtotime("-{$rdDays} days", TIMESTAMP),
                'rating'           => 0,
                'refresh_at'       => $mvData['refresh_at'],
                'is_free'          => 0,//收费
                'like'             => $mvData['like'],
                'comment'          => ($mvData['like'] + 1) * (rand(50, 100)),
                'status'           => MvModel::STAT_CALLBACK_DONE,
                'thumb_start_time' => 40,
                'thumb_duration'   => 30,
                'is_hide'          => 1,
                'created_at'       => TIMESTAMP,
                'is_recommend'     => 1,
            ];
            try {
                $mv = MvModel::create($insertData);
                if ($mv) {
                    if ($tags && is_string($tags)) {
                        $tags = explode(',', $tags);
                        MvTagModel::createByAll($mv->id, $tags);
                    }
                    //MvWordsModel::createForTitle($mv->id, $mv->title);//注意本地环境要启用 开启scws扩展 或者 注释本行
                    //redis()->sAdd(\MvModel::REDIS_MV_LIST, $mv->id);
                    //redis()->del(\MvModel::REDIS_USER_VIDEOS_ITEM . $uid . '_1');
                }
                //errLog("91lu-avsync:" . var_export($mv->toArray(), true));
            } catch (Exception $e) {
                errLog("91lu-error:" . $e->getMessage());
            }
        }, $this->post);

    }
}