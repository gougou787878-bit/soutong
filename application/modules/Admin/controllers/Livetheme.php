<?php

/**
 * Class LiveController
 * @author xiongba
 * @date 2022-03-07 12:01:02
 */
class LivethemeController extends BackendBaseController
{
    use \traits\DefaultActionTrait;
    use \traits\DefaultActionTrait {
        doSave as fatherSave;
    }

    /**
     * 列表数据过滤
     * @return Closure
     */
    protected function listAjaxIteration()
    {
        return function (LiveThemeModel $item) {
            $item->setHidden([]);
            $item->country = $item->type == LiveThemeModel::TYPE_COUNTRY ? explode(",", $item->value) : [];
            $item->language = $item->type == LiveThemeModel::TYPE_LANGUAGE ? explode(",", $item->value) : [];
            $item->genger = $item->type == LiveThemeModel::TYPE_GENDER ? explode(",", $item->value) : [];
            $item->tags = $item->type == LiveThemeModel::TYPE_TAG ? explode(",", $item->value) : [];
            $item->status_str = LiveThemeModel::STATUS_TIPS[$item->status];
            $item->type_str = LiveThemeModel::TYPE_TIPS[$item->type];
            $item->symbol_str = LiveThemeModel::SYMBOL_TIPS[$item->symbol];
            return $item;
        };
    }

    /**
     * 试图渲染
     * @return void
     */
    public function indexAction()
    {
        $country = json_encode(LiveModel::COUNTRY, JSON_FORCE_OBJECT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        $language = json_encode(LiveModel::LANGUAGE, JSON_FORCE_OBJECT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        $gender = json_encode(LiveModel::GENDER_TIPS, JSON_FORCE_OBJECT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        $tag = json_encode(LiveModel::TAG, JSON_FORCE_OBJECT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        $this->assign('country_json', $country);
        $this->assign('language_json', $language);
        $this->assign('gender_json', $gender);
        $this->assign('tag_json', $tag);
        $this->display();
    }


    /**
     * 获取本控制器和哪个model绑定
     * @return string
     */
    protected function getModelClass(): string
    {
        return LiveThemeModel::class;
    }

    protected function doSave($data)
    {
        if (isset($data['value']) && is_array($data['value'])) {
            sort($data['value']);
            $data['value'] = implode(",", $data['value']);
        }
        return $this->fatherSave($data);
    }

    protected function saveAfterCallback($model, $oldModel = null)
    {
        if (!$oldModel) {
            jobs2([LiveModel::class, 'defend_related'], [$model->id, $model->type, $model->symbol, $model->value]);
        } else {
            if ($model->type != $oldModel->type || $model->symbol != $oldModel->symbol || $model->value != $oldModel->value || $model->status != $oldModel->status) {
                jobs2([LiveModel::class, 'defend_related'], [$model->id, $model->type, $model->symbol, $model->value]);
            }
        }
    }


    /**
     * 定义数据操作的表主键名称
     * @return string
     */
    protected function getPkName(): string
    {
        return 'id';
    }

    /**
     * 定义数据操作日志
     * @return string
     * @author xiongba
     */
    protected function getLogDesc(): string
    {
        return '';
    }
}