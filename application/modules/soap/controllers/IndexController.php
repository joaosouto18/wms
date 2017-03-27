<?php

/**
 * Description of Soap_IndexController
 *
 * @author Renato Medina <medinadato@gmail.com>
 */
class Soap_IndexController extends Core\Controller\Action\WebService
{
    /**
     * Configuração do webservice
     * @var Zend_Config_Ini
     */
    protected $conf;
    /**
     * Nome do serviço requisitado
     * @var string
     */
    protected $serviceName;


    public function init()
    {
        parent::init();

        $parametroRepo = $this->em->getRepository('wms:Sistema\Parametro');
        $parametro = $parametroRepo->findOneBy(array('constante' => 'ID_USER_ERP'));

        if (!empty($parametro)){
            $idUsuario = $parametro->getValor();
        } else {
            $idUsuario = APPLICATION_ENV == 'development' ? 142 : 1;
        }

        $usuario = $this->em->find('wms:Usuario', $idUsuario);

        if (empty($usuario)) {
            throw new \Exception("Usuário código: $idUsuario para webservice não cadastrado");
        }

        $perfis = array();
        foreach ($usuario->getPerfis() as $perfil) {
            $perfis[$perfil->getId()] = $perfil->getNome();
        }
        //ordeno crescentemente pelos codigos do perfil
        ksort($perfis);
        //gero o perfil
        $perfil = implode('-', $perfis);
        //seto o RoleId para o zf
        $usuario->setRoleId($perfil);

        $auth = \Zend_Auth::getInstance();
        $storage = $auth->getStorage();
        $storage->clear();
        $storage->write($usuario);

        $front = \Zend_Controller_Front::getInstance();
        $front->setParam('noErrorHandler', true);
        $front->setParam('noViewRenderer', true);

        $this->conf = $conf = new \Zend_Config_Ini(APPLICATION_PATH . '/configs/webservices.ini', APPLICATION_ENV);

        //desabilita layouts e views
        if (null != \Zend_Layout::getMvcInstance()) {
            \Zend_Layout::getMvcInstance()->disableLayout();
        }

        $this->getHelper('viewRenderer')->setNoRender(true);
        $service = $this->_getParam('service');

        if ($service == null) {
            throw new \Exception('Serviço inválido');
        }

        $this->serviceName = $service;
    }

    /**
     * Processa a requisição do serviço
     * @return void
     */
    public function indexAction()
    {
        $serviceName = ucwords($this->serviceName);
        // initialize server and set WSDL file location
        $server = new \Zend_Soap_Server($this->conf->soap->{$this->serviceName}->wsdl);

        $server->setEncoding('UTF-8');
        // set SOAP service class
        $server->setClass("Wms_WebService_{$serviceName}");
        // register exceptions that generate SOAP faults
        $server->registerFaultException('Exception');
        // handle request
        $server->handle();
    }

    /**
     * Retorna um XML contendo o WSDL do serviço.
     * @return void
     */
    public function wsdlAction()
    {
        // set up WSDL auto-discovery
        $wsdl = new \Zend_Soap_AutoDiscover('Zend_Soap_Wsdl_Strategy_ArrayOfTypeComplex');
        $serviceName = ucwords($this->serviceName);
        // attach SOAP service class
        $wsdl->setClass("Wms_WebService_{$serviceName}");
        // set SOAP action URI
        $wsdl->setUri($this->conf->soap->{$this->serviceName}->url);
        // handle request
        $wsdl->handle();
    }
}