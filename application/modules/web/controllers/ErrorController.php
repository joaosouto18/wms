<?php

/**
 * 
 */
class Web_ErrorController extends \Wms\Module\Web\Controller\Action
{
    
    public function init()
    {
	/*// reset layout
	$layout = Zend_Layout::getMvcInstance();
	$layout->setLayout('error');*/
    }
    
    public function forbiddenAction()
    {
	//TODO: fazer rotina para cadastrar tentativa de acesso

	$message = "Você não está autorizado a ver esta página.";
	$message .= "<br /> <br />";
	$message .= "Module: " . $this->getRequest()->getParam('forbiddenModule') . "<br />";
	$message .= "Controller: " . $this->getRequest()->getParam('forbiddenController') . "<br />";
	$message .= "Action: " . $this->getRequest()->getParam('forbiddenAction') . "<br />";

	$this->view->message = $message;
	$this->render('message');

    }

    public function errorAction()
    {
	//TODO: fazer rotina para cadastrar erros no banco

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
	
	/*if (APPLICATION_ENV != 'development') {
	    //Envia e-mail para o administrador do sistema
	    $config = array('auth' => 'login',
			    'username' => 'sender@cilens.info',
			    'password' => 'sender123');

	    $transport = new \Zend_Mail_Transport_Smtp('mail.cilens.info', $config);
	    $html = "<h1>An error occurred</h1>";
	    $html.= "<h2>{$this->view->message}</h2>";
	    $html.= "<h3>Exception information:</h3><p><b>Message:</b>{$this->view->exception->getMessage()}</p>";
	    $html.= "<h3>Stack trace:</h3>
		    <pre>{$this->view->exception->getTraceAsString()}</pre>
		    <h3>Request Parameters:</h3>
		    <pre>{$export}</pre>";

	    $mail = new \Zend_Mail('utf-8');
	    $mail->setBodyHtml($html);
	    $mail->setFrom('sender@cilens.info', 'Wms-');
	    $mail->addTo('yourwebmaker@gmail.com', 'Daniel Lima');
	    $mail->addTo('fcaram@rovereti.com.br', 'Fábrio Caram');
	    $mail->setSubject("Wms-ERRO-" . APPLICATION_ENV);
	    $mail->send($transport);
	}*/
	
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

