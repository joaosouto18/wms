<?php

/**
 * 
 */
class Web_ErrorController extends \Wms\Module\Web\Controller\Action {

    public function init() {
        /* // reset layout
          $layout = Zend_Layout::getMvcInstance();
          $layout->setLayout('error'); */
    }

    public function forbiddenAction() {
        //TODO: fazer rotina para cadastrar tentativa de acesso

        $message = "Você não está autorizado a ver esta página.";
        $message .= "<br /> <br />";
        $message .= "Module: " . $this->getRequest()->getParam('forbiddenModule') . "<br />";
        $message .= "Controller: " . $this->getRequest()->getParam('forbiddenController') . "<br />";
        $message .= "Action: " . $this->getRequest()->getParam('forbiddenAction') . "<br />";

        $this->view->message = $message;
        $this->render('message');
    }

    public function errorAction() {
        //TODO: fazer rotina para cadastrar erros no banco

        $errors = $this->_getParam('error_handler');
        $this->view = new \Zend_View();
        if (!$errors) {
            $this->view->message = 'You have reached the error page';
            return;
        }
        $this->render('message');
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
		//$this->getResponse()->setHttpResponseCode(500);
                $priority = Zend_Log::CRIT;
                $this->view->message = 'Application error';
                break;
        }

	//if (APPLICATION_ENV != 'development') {
        $html = "<h1>An error occurred</h1>";
        $html .= "<h2>{$this->view->message}</h2>";
        $html .= "<h3>Exception information:</h3><p><b>Message:</b>{$errors->exception->getMessage()}</p>"
        . "<h3>Request Parameters:</h3><pre>{";
        foreach ($this->_getAllParams() as $key => $value) {
            if (!is_object($value)) {
                $html .= " <br /> &emsp; $key = $value";
            }
        }
        $html .= " <br />}</pre>";
        $html .= "<h3>Stack trace:</h3>
		    <pre>{$errors->exception->getTraceAsString()}</pre>";
        echo $html;
    }

    public function getLog() {
        $bootstrap = $this->getInvokeArg('bootstrap');
        if (!$bootstrap->hasResource('Log')) {
            return false;
        }
        $log = $bootstrap->getResource('Log');
        return $log;
    }

    public function semDepositoLogadoAction() {
        $this->view->message = 'Selecione um depósito para efetuar operações';
        $this->render('message');
    }

    public function semPermissaoDepositosAction() {
        $this->view->message = 'Você não tem permissão para acessar depósitos. 
				Contacte o administrador do sistema para solicitar 
				acesso a um depósito.';
        $this->render('message');
    }

}
