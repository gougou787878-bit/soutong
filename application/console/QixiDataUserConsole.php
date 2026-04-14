<?php


namespace App\console;

use Elasticsearch\ClientBuilder;
use service\AppCenterService;
use service\QingMingService;

class QixiDataUserConsole extends AbstractConsole
{

    /**
     * @var string 定义同步命令
     */
    public $name = 'qixi-data-user';
    /**
     * @var string 定义命令描述
     */
    public $description = '七夕数据';

    /**
     * @var callable[]
     */
    private $works = [];

    /**
     *  php yaf qixi-data-user 2020-07-01 2020-07-01
     *
     * @param $argc
     * @param $argv
     *
     */
    public function process($argc, $argv)
    {


        echo "start daemonize qixi-data-user \r\n";

        $client = ClientBuilder::create()->setHosts([
            'http://elk.peachav.club:8080'
        ])->build();

        //var_dump($client);

        $model = \MvModel::queryWithUser()->where('id',14115105)->first();
        //print_r($model->toArray());

        //create index

        $index = 'test_gay_index2';
        $type = 'test_gay_type2';
        /** Array
         * (
         * [_index] => test_gay_index
         * [_type] => test_gay_type
         * [_id] => 14115105
         * [_version] => 1
         * [result] => created
         * [_shards] => Array
         * (
         * [total] => 2
         * [successful] => 1
         * [failed] => 0
         * )
         *
         * [_seq_no] => 0
         * [_primary_term] => 1
         * )*/


        $response = $client->index([
            'index' => $index,
            'type'  => $type,
            'id'    => $model->id,
            'body'  => [
                'title'    => $model->title,
                'tags'     => $model->tags,
                'coins'    => $model->coins,
                'uid'      => $model->uid,
                'nickname' => $model->user->nickname
            ]
        ]);

        print_r($response);


        //get index
        /*$response = $client->get([
            'index' => $index,
            'type'  => $type,
            'id'    => $model->id
        ]);*/
        /**Array
         * (
         * [_index] => test_gay_index
         * [_type] => test_gay_type
         * [_id] => 14115105
         * [_version] => 1
         * [_seq_no] => 0
         * [_primary_term] => 1
         * [found] => 1
         * [_source] => Array
         * (
         * [title] => 这妹子不错
         * [tags] => 激情,高清,自拍作品
         * [coins] => 0
         * [uid] => 14115105
         * [user] => Array
         * (
         * [nickname] => 忧心哈密瓜，数据线
         * )
         *
         * )
         *
         * )*/


        //search
        /*$response = $client->search([
            'index' => $index,
            'type'  => $type,
            'body'    =>[
                'query'=>[
                    'match'=>[
                        //'tags'=>'高清',
                        'user.nickname'=>'哈密瓜'
                    ]
                ]
            ]
        ]);*/

        $response = $client->search([
            'index' => $index,
            'type'  => $type,
            'body'  => [
                'query' => [
                    'constant_score' => [
                        'filter' => [
                            'match' => [
                                //'uid' => '14115105',
                                'tags' => '激情',
                            ]
                        ]
                    ]
                ]
            ]
        ]);
        print_r($response);die;

        $response = $client->search([
            'index' => $index,
            'type'  => $type,
            'body'  => [
                'query' => [
                    'filtered' => [
                        'filter' => [
                            'bool' => [

                                'should' => [
                                    ['term'=>['uid'=>14115105]],
                                    ['term'=>['title'=>'妹子']]
                                ],
                            ]
                        ]
                    ]
                ]
            ]
        ]);

      /*  Array
        (
                        [took] => 1
                [timed_out] =>
                [_shards] => Array
                (
                    [total] => 1
                        [successful] => 1
                        [skipped] => 0
                        [failed] => 0
                    )

                [hits] => Array
                (
                    [total] => Array
                    (
                        [value] => 1
                                [relation] => eq
                            )

                        [max_score] => 0.8630463
                        [hits] => Array
                (
                    [0] => Array
                    (
                        [_index] => test_gay_index
                        [_type] => test_gay_type
                [_id] => 14115105
                                        [_score] => 0.8630463
                                        [_source] => Array
                (
                    [title] => 这妹子不错
                    [tags] => 激情,高清,自拍作品
                [coins] => 0
                                                [uid] => 14115105
                                                [user] => Array
                (
                    [nickname] => 忧心哈密瓜，数据线
                )

                                            )

                                    )

                            )

                    )

)*/


        print_r($response);



        echo "\r\n over \r\n";
    }


}