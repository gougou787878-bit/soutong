<?php

class TestingController extends BaseController
{

    public function testAction()
    {
        $all = MvModel::limit(1000)->where('tags','!=' , '')->get(['id', 'tags']);
        $all->map(function ($model) {
            /** @var MvModel $model */
            MvTagModel::createByAll($model->id, $model->tags);
        });
    }


}