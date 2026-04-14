<?php


namespace traits;


trait ActionAopTrait
{

    private function callAopBefore() {
        $actionName = $this->getRequest()->getActionName();
        if (method_exists($this, $method = "_{$actionName}ActionBefore")) {
            call_user_func([$this, $method]);
        }
    }

    private function callAopAfter() {
        $actionName = $this->getRequest()->getActionName();
        if (method_exists($this, $method = "_{$actionName}ActionAfter")) {
            call_user_func([$this, $method]);
        }
    }


}