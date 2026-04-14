<?php
/**
 *
 * 搜同 神叟引擎处理中心
 */

namespace service;

use Elasticsearch\ClientBuilder;

/**
 * Class ElkService
 * @package service
 */
class ElkService
{
    const ELK_API_HOST = 'http://elk.peachav.club:8080';

    const ELK_INDEX = 'xblue';//表

    const ELK_TYPE = 'doc';//类型


    public $_client = null;

    function __construct()
    {  ini_set('arg_separator.output', '&');
        $_host = [self::ELK_API_HOST];
        $this->_client = ClientBuilder::create()->setHosts($_host)->build();
    }

    /**
     * @param $data MvModelArray
     * @return array|null
     */
    function batchInsert($data)
    {
        $params = [
            'body'  => [],
            'index' => self::ELK_INDEX,
            'type'  => self::ELK_TYPE,
        ];
        foreach ($data as $_mv){
            if(is_null($_mv)){
                continue;
            }
            $params['body'][] = [
                'create' => [
                   // '_index' => self::ELK_INDEX,
                    //'_type'  => self::ELK_TYPE,
                    '_id'    => $_mv->id
                ],
            ];
            $params['body'][]=[
                'title'    => $_mv->title,
                'tags'     => $_mv->tags,
                'is_coin'  => $_mv->coins ? 1 : 0,
                'uid'      => $_mv->uid,
                'nickname' => $_mv->user ? $_mv->user->nickname : '游客'
            ];
        }
        //print_r($params);die;
        if (!empty($params['body'])) {
            return $this->_client->bulk($params);
        }
        return null;

    }
    function searchData($searchData){
        $response = $this->_client->search($searchData);
        return $response;
    }

    function searchIDS($word='大吊',$page=1,$size= 20){
        $page <=0 && $page = 1;
        $_s = [
            'index' => ElkService::ELK_INDEX,
            'type'  => ElkService::ELK_TYPE,
            'from' => ($page-1)*$size,
            'size' => $size,
            'body'  => [
                'query' => [
                    'bool' => [
                        'should' => [
                            ['match'=>['tags'=>$word]],
                            ['match'=>['title'=>$word]],
                            ['match'=>['nickname'=>$word]]
                        ],
                    ]
                ]
            ]
        ];
        $response = $this->searchData($_s);
        //print_r($response);die;
        if(isset($response['hits']) && $response['hits']['total']['value']){
            return array_column($response['hits']['hits'],'_id');
        }
        return null;
    }


}