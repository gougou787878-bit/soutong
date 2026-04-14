<?php

use tools\RedisService;
use tools\TextSimilarity;

/**
 * \AdsampleModel
 */
class AdsampleModel extends EloquentModel
{
    const REDIS_SAMPLE_KEY = 'adsample';
    const SAMPLE_LIMITT = 200;
    protected $table = 'ads_sample';

    protected $fillable = [
        'content', 'created_at'
    ];

    /**
     *  比对匹配度
     *
     * @param $content
     * @return bool
     */
    static function checkTextSimilar($content){
        $content = self::getCommentTextSmilarity($content);
        if($content == false){
            return false;
        }
        $spam_sample = self::getListAdsample();
        if(!$spam_sample){
            return false;
        }
        $textSimilarity = new TextSimilarity();

        foreach($spam_sample as $sample){
            $textSimilarity->setText($content,$sample['content']);
            $percent = $textSimilarity->run();
            if($percent>0.5){
                return true;
            }
        }
        foreach($spam_sample as $sample){
            mb_similar_text($content, $sample['content'], $percent);
            if ($percent > 50) {
                return true;
            }
        }
        return false;
    }


    static function getCommentTextSmilarity($content){
        //中文标点
        $char = "，￥。、！？：；﹑•＂…‘’“”〝〞∕¦‖—　〈〉﹞﹝「」‹›〖〗】【»«』『〕〔》《﹐¸﹕︰﹔！¡？¿﹖﹌﹏﹋＇´ˊˋ―﹫︳︴¯＿￣﹢﹦﹤‐­˜﹟﹩﹠﹪﹡﹨﹍﹉﹎﹊ˇ︵︶︷︸︹︿﹀︺︽︾ˉ﹁﹂﹃﹄︻︼（）";
        $pattern = array(
            "/[[:punct:]]/i", //英文标点符号
            '/['.$char.']/u', //中文标点符号
            '/\s*/',				//空白字符
            '/\w*/'				//空白字符
        );
        $chinese = preg_replace($pattern, '', $content);
        if(mb_strlen($chinese)<2){
            return false;
        }
        $chinese = preg_replace ('/[^\p{Han}]/iu','', $chinese);//只留下汉字
        $len = mb_strlen($chinese);
        if($len){
            return $chinese;
        }
        return false;
    }


    static function addAdsample($data) {
        $has = self::where('content', trim($data['content']))->first();
        if ($has) {
            return false;
        }
        $data['created_at'] = time();
        $flag = self::insert($data);
        $flag && RedisService::del(self::REDIS_SAMPLE_KEY);
        return $flag;
    }

    static function updateAdsample($id, $data) {
        $flag = self::where('id', $id)->update($data);
        $flag && RedisService::del(self::REDIS_SAMPLE_KEY);
        return $flag;
    }
    static function delAdsample($id) {
        $flag = self::where('id', $id)->delete();
        $flag && RedisService::del(self::REDIS_SAMPLE_KEY);
        return $flag;
    }

    static function getListAdsample() {
        $data = RedisService::get(self::REDIS_SAMPLE_KEY);
        if ($data) {
            return $data;
        }
        $data = self::query()->limit(self::SAMPLE_LIMITT)->orderByDesc('id')->get()->toArray();
        if ($data) {
            RedisService::set(self::REDIS_SAMPLE_KEY, $data);
        }
        return $data;
    }

}