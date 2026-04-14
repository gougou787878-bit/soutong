<?php

class ScriptMapPlugin extends Yaf\Plugin_Abstract
{

    public function routerShutdown(Yaf\Request_Abstract $request, Yaf\Response_Abstract $response)
    {
        // 映射到分发器
        $request->setModuleName('Script');

        $request->setControllerName('Forward');

        $request->setActionName('index');

    }

}