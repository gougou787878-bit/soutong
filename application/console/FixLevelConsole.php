<?php


namespace App\console;


use DB;

class FixLevelConsole extends AbstractConsole
{

    public $name = 'fix-vip-level';

    public $description = '修复用户购买永久会员的会员等级';


    public function process($argc, $argv)
    {
        $orders = \OrdersModel::where([
            ['id','<',2575500],
            ['id','>=',2571330],
            ['status','=',\OrdersModel::STATUS_SUCCESS],
            ['order_type','=',\ProductModel::TYPE_VIP]
        ])->get()->map(function (\OrdersModel $order){
            if(is_null($order)){
                return ;
            }
            $vip_level = 0;
            if($order->product_id == 2){//yun
                $vip_level = \MemberModel::VIP_LEVEL_MOON;
            }elseif($order->product_id == 99){//year
                $vip_level = \MemberModel::VIP_LEVEL_YEAR;
            }elseif($order->product_id == 100){//long
                $vip_level = \MemberModel::VIP_LEVEL_LONG;
            }
            if($vip_level){
                $flag = \MemberModel::where('uuid',$order->uuid)->where('oauth_type',$order->oauth_type)->update([
                    'vip_level'=>$vip_level
                ]);
                $msg = "falg:{$flag} | order:{$order->order_id} | uuid:{$order->uuid} | pid:{$order->product_id} | vip:{$vip_level} ".PHP_EOL;
                echo $msg;
                file_put_contents('vip.log',$msg,FILE_APPEND);
            }
        });

        echo "#################  over ############## \r\n ";
    }

    protected function abc(){
        for($i=1;$i<=13500;$i++)
        {
            usleep(800);

            /** @var \MvModel $model */
            $model = \MvModel::where([
                ['id','=',$i],
                ['duration','>=',1500],
            ])->first();
            if(is_null($model)){
                continue;
            }

            try{
                $data = $model->getAttributes();
                $curl = new \tools\CurlService();
                $return = $curl->request(SYNC_GTV_URL, $data);
                //errLog("sync req:".var_export([$data,$return],true));
                $returnArr = json_decode($return,true);
                if($returnArr['status'] == 0){
                    $model->increment('music_id',1);// music_id 已经弃用  作为同步标识

                }
                //{"status":0,"msg":"ok","data":[]}
                echo $i."# remoted ". var_export([$returnArr,1]).PHP_EOL.PHP_EOL;
            }catch (\Yaf\Exception $e){
                echo $e->getMessage();
            }
        }
    }

}