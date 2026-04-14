<?php

class UserReportClassifyModel
{
    private $config = [
        ['id' => '1', 'order_no' => '0', 'name' => '骗取点击'],
        ['id' => '2', 'order_no' => '0', 'name' => '低俗色情'],
        ['id' => '3', 'order_no' => '0', 'name' => '侮辱谩骂'],
        ['id' => '4', 'order_no' => '0', 'name' => '盗用他人作品'],
        ['id' => '5', 'order_no' => '0', 'name' => '引人不适'],
        ['id' => '6', 'order_no' => '0', 'name' => '任性打抱不平，就爱举报'],
        ['id' => '7', 'order_no' => '0', 'name' => '其他']
    ];


    /* 举报类型 */
    public function getReportClass()
    {
        return $this->config;
    }
}