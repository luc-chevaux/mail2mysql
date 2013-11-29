<?php
class ControllerPluginNoRoute extends Zend_Controller_Plugin_Abstract {
    function preDispatch(Zend_Controller_Request_Abstract $request) {
        $frontController = Zend_Controller_Front::getInstance();
        $dispatcher = $frontController->getDispatcher();

        if (!$dispatcher->isDispatchable($request)) {
            $request->setControllerName('index');
            $request->setActionName('noroute');
        }
    }
}
?>