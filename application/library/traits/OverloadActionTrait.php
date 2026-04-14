<?php


namespace traits;


trait OverloadActionTrait
{
    protected function getModelClass(): string {
        // TODO: Implement getModelClass() method.
    }

    protected function getPkName(): string {
        // TODO: Implement getPkName() method.
    }

    public function saveAction() {
        return $this->ajaxError('禁止调用');
    }

    public function listAjaxAction() {
        return $this->ajaxError('禁止调用');
    }

    public function delAction()
    {
        return $this->ajaxError('禁止调用');
    }

}