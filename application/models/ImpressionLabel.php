<?php

class ImpressionLabelModel
{
    protected $redisKey = 'impression_label';

    private $config = [
        ['id'=>'1', 'name'=>'制服', 'orderno'=>'12', 'colour'=>'4edc90'],
        ['id'=>'2', 'name'=>'女神', 'orderno'=>'2', 'colour'=>'33caf9'],
        ['id'=>'3', 'name'=>'性感', 'orderno'=>'0', 'colour'=>'e5007f'],
        ['id'=>'4', 'name'=>'可爱萌妹', 'orderno'=>'4', 'colour'=>'f9b552' ],
        ['id'=>'5', 'name'=>'萝莉', 'orderno'=>'9', 'colour'=>'fff649'],
        ['id'=>'7', 'name'=>'美腿', 'orderno'=>'7', 'colour'=>'8fc41e'],
        ['id'=>'8', 'name'=>'少妇', 'orderno'=>'8', 'colour'=>'ea2893'],
        ['id'=>'9', 'name'=>'冷艳', 'orderno'=>'5', 'colour'=>'4cd1f8'],
        ['id'=>'10', 'name'=>'学生', 'orderno'=>'10', 'colour'=>'8fc41e'],
        ['id'=>'11', 'name'=>'高颜值', 'orderno'=>'11', 'colour'=>'01d8d0' ],
        ['id'=>'16', 'name'=>'熟女', 'orderno'=>'6', 'colour'=>'e01976'],
        ['id'=>'17', 'name'=>'火辣', 'orderno'=>'1', 'colour'=>'ed1c1c'],
        ['id'=>'18', 'name'=>'妖娆', 'orderno'=>'3', 'colour'=>'bf19a6']
    ];

    /**
     * @return
     */
    public function getConfig($key = false)
    {
        if ($key) {
            return $this->config[$key];
        }
        return $this->config;
    }

    public function getImpressionLabel() {
        return $this->getConfig();
    }

}