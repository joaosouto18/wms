<?php

/**
 * Application Bootstrap class.
 *
 * @category MKX
 * @package Base
 * @subpackage Application
 */
class Bootstrap extends \Zend_Application_Bootstrap_Bootstrap //BaseBootstrap
{

    /**
     * Load namespaced libraries
     */
    protected function _initAutoloaderNamespaces()
    {
        require_once APPLICATION_PATH . '/../library/Doctrine/Common/ClassLoader.php';

        //thirdy party namespaced libraries
        $vendors = array(
            'Bisna' => null,
            'Core' => null,
            'DoctrineExtensions' => null,
            'Zend' => null,
            'ZendX' => null,
            'Symfony' => null,
            'Wms' => null,
            'Adl' => null,
            'ZFDebug' => null,
            'Mobile' => APPLICATION_PATH . '/modules/mobile/src/'
        );

        $autoloader = \Zend_Loader_Autoloader::getInstance();

        foreach ($vendors as $vendor => $path) {
            $fmmAutoloader = new \Doctrine\Common\ClassLoader($vendor, $path);
            $autoloader->pushAutoloader(array($fmmAutoloader, 'loadClass'), $vendor);
            $autoloader->registerNamespace($vendor);
        }
    }

    /**
     * Load acl
     * @todo 
     */
    protected function _initAclNav()
    {
        //@todo: remover esse bootstrap porco
        $this->bootstrap('doctrine');

        new \Core\Acl\Setup;

        // navigation
        new \Core\View\Navigation\Setup;
    }

    /**
     * Load Views' Configs
     */
    protected function _initViews()
    {
        $this->bootstrap('view');
        $view = $this->getResource('view');

        $view->addHelperPath("Core/View/Helper", "Core_View_Helper");
        $view->addHelperPath("ZendX/JQuery/View/Helper", "ZendX_JQuery_View_Helper");

        $view->doctype("XHTML1_STRICT");
        $view->headTitle("Titulo")->setSeparator(" | ");
        $view->headMeta()->appendHttpEquiv('Content-Type', 'text/html; charset=UTF-8');

        Zend_Registry::set('view', $view);
    }

    /**
     * 
     */
    protected function _initViewHelpers()
    {
        $this->bootstrap('frontController');

        $layout = $this->getResource('layout');
        $view = $this->getResource('view');
        $navConfig = \Zend_Registry::get('navConfig');
        $navContanier = new \Zend_Navigation($navConfig);
        $nav = $view->navigation($navContanier);
    }

    /**
     * 
     */
    protected function _initFlashMessenger()
    {
        /** @var $flashMessenger Zend_Controller_Action_Helper_FlashMessenger */
        $flashMessenger = \Zend_Controller_Action_HelperBroker::getStaticHelper('FlashMessenger');

        if ($flashMessenger->hasMessages()) {
            $view = $this->getResource('view');
            $view->messages = $flashMessenger->getMessages();
        }
    }

    /**
     * Sobrescrevo metodos do doctrine para meus tipos customizados 
     */
    protected function _initDoctrineTypes()
    {
        \Doctrine\DBAL\Types\Type::overrideType('date', '\DoctrineExtensions\DBAL\Types\DateType');
    }


    /**
     * Carrego tudo relativo ao modulo web 
     */
    public function webInitFunction()
    {
        $front = Zend_Controller_Front::getInstance();
        $front->registerPlugin(new \Core\Plugin\Auth)
                ->registerPlugin(new \Core\Plugin\SenhaProvisoria)
                ->registerPlugin(new \Core\Plugin\NavigationTitle)
                ->registerPlugin(new \Core\Plugin\Defaults)
                ->registerPlugin(new \Wms\Plugin\Deposito);
    }
    /**
     * Carrego tudo relativo ao modulo web 
     */
    public function mobileInitFunction()
    {
        $front = Zend_Controller_Front::getInstance();
        $front->registerPlugin(new \Core\Plugin\Coletor)
            ->registerPlugin(new \Wms\Plugin\Deposito);
    }

    public function importacaoInitFunction()
    {
        $front = Zend_Controller_Front::getInstance();
        $front->registerPlugin(new \Core\Plugin\Auth)
            ->registerPlugin(new \Core\Plugin\SenhaProvisoria)
            ->registerPlugin(new \Core\Plugin\NavigationTitle)
            ->registerPlugin(new \Core\Plugin\Defaults)
            ->registerPlugin(new \Wms\Plugin\Deposito);
    }

    public function notafiscalInitFunction()
    {
        $front = Zend_Controller_Front::getInstance();
        $front->registerPlugin(new \Core\Plugin\Auth)
            ->registerPlugin(new \Core\Plugin\SenhaProvisoria)
            ->registerPlugin(new \Core\Plugin\NavigationTitle)
            ->registerPlugin(new \Core\Plugin\Defaults)
            ->registerPlugin(new \Wms\Plugin\Deposito);
    }

    public function expedicaoInitFunction()
    {
        $front = Zend_Controller_Front::getInstance();
        $front->registerPlugin(new \Core\Plugin\Auth)
            ->registerPlugin(new \Core\Plugin\SenhaProvisoria)
            ->registerPlugin(new \Core\Plugin\NavigationTitle)
            ->registerPlugin(new \Core\Plugin\Defaults)
            ->registerPlugin(new \Wms\Plugin\Deposito);
    }

    public function enderecamentoInitFunction()
    {
        $front = Zend_Controller_Front::getInstance();
        $front->registerPlugin(new \Core\Plugin\Auth)
            ->registerPlugin(new \Core\Plugin\SenhaProvisoria)
            ->registerPlugin(new \Core\Plugin\NavigationTitle)
            ->registerPlugin(new \Core\Plugin\Defaults)
            ->registerPlugin(new \Wms\Plugin\Deposito);
    }

    public function produtividadeInitFunction()
    {
        $front = Zend_Controller_Front::getInstance();
        $front->registerPlugin(new \Core\Plugin\Auth)
            ->registerPlugin(new \Core\Plugin\SenhaProvisoria)
            ->registerPlugin(new \Core\Plugin\NavigationTitle)
            ->registerPlugin(new \Core\Plugin\Defaults)
            ->registerPlugin(new \Wms\Plugin\Deposito);
    }

    public function validadeInitFunction()
    {
        $front = Zend_Controller_Front::getInstance();
        $front->registerPlugin(new \Core\Plugin\Auth)
            ->registerPlugin(new \Core\Plugin\SenhaProvisoria)
            ->registerPlugin(new \Core\Plugin\NavigationTitle)
            ->registerPlugin(new \Core\Plugin\Defaults)
            ->registerPlugin(new \Wms\Plugin\Deposito);
    }

    public function inventarioInitFunction()
    {
        $front = Zend_Controller_Front::getInstance();
        $front->registerPlugin(new \Core\Plugin\Auth)
            ->registerPlugin(new \Core\Plugin\SenhaProvisoria)
            ->registerPlugin(new \Core\Plugin\NavigationTitle)
            ->registerPlugin(new \Core\Plugin\Defaults)
            ->registerPlugin(new \Wms\Plugin\Deposito);
    }

    protected function _initConfig()
    {
        Zend_Registry::set('config', new Zend_Config($this->getOptions()));
    }

}