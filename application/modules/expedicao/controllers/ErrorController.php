<?php

/**
 * Class Expedicao_ErrorController
 */
class Expedicao_ErrorController extends \Wms\Module\Web\Controller\Action
{

    public function forbiddenAction()
    {
        $this->view->message = 'Você não está autorizado a ver esta página.';
        $this->render('message');
    }

    public function errorAction()
    {
        $errors = $this->_getParam('error_handler');

        if (!$errors) {
            $this->view->message = 'You have reached the error page';
            return;
        }

        switch ($errors->type) {
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ROUTE:
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_CONTROLLER:
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ACTION:
                // 404 error -- controller or action not found
                $this->getResponse()->setHttpResponseCode(404);
                $priority = Zend_Log::NOTICE;
                $this->view->message = 'Page not found';
                break;
            default:
                // application error
                $this->getResponse()->setHttpResponseCode(500);
                $priority = Zend_Log::CRIT;
                $this->view->message = 'Application error';
                break;
        }

        // Log exception, if logger available
        if ($log = $this->getLog()) {
            $log->log($this->view->message, $priority, $errors->exception);
            $log->log('Request Parameters', $priority, $request->getParams());
        }

        // conditionally display exceptions
        if ($this->getInvokeArg('displayExceptions') == true) {
            $this->view->exception = $errors->exception;
        }

        $this->view->request = $errors->request;

        $export = var_export($this->view->request->getParams(), true);
    }

    public function getLog()
    {
        $bootstrap = $this->getInvokeArg('bootstrap');
        if (!$bootstrap->hasResource('Log')) {
            return false;
        }
        $log = $bootstrap->getResource('Log');
        return $log;
    }

    public function semDepositoLogadoAction()
    {
        $this->view->message = 'Selecione um depósito para efetuar operações';
        $this->render('message');
    }

    public function semPermissaoDepositosAction()
    {
        $this->view->message = 'Você não tem permissão para acessar depósitos.
				Contacte o administrador do sistema para solicitar 
				acesso a um depósito.';
        $this->render('message');
    }

}

