<?php

namespace Core\Plugin;

use \Zend_Controller_Action_HelperBroker as HelperBroker,
    \Core\Controller\PluginAbstract;

/**
 * Description of Auth
 *
 * @link    www.moveissimonetti.com.br/wms
 * @since   1.0
 * @version $Revision$
 * @author Desenvolvimento
 * @todo CONSERTAR ESSA CLASSE PORCA!
 */
class SenhaProvisoria extends PluginAbstract
{
    /**
     * @var array nome dos módulos em que o plugin será executado
     * @todo TIRAR ESSA PROP. PORCA
     */
    protected $modulosProibidos = array('web');
    
    
    protected $rotasProibidas = array(
	array(
	    'controller' => 'usuario',
	    'action'     => 'mudar-senha-provisoria',
	    'module'     => 'web',
	)
    );
      
    public function preDispatch(\Zend_Controller_Request_Abstract $request) 
    {
        $controllerName = $request->getControllerName();
	$actionName = $request->getActionName();
        $auth = \Zend_Auth::getInstance();
        $usuario = $auth->getStorage()->read();
        
        if ($controllerName == 'auth' || $controllerName == 'error'|| $usuario == null) {
            return;
        } 
	
	//se usuario logado está com senha provisoria
	if ($usuario->getIsSenhaProvisoria() == 'S') {
            
            if ($controllerName != 'usuario') {
                //redireciona o usuário para a página de mudança de senha
                 HelperBroker::addHelper(
                    new \Zend_Controller_Action_Helper_Redirector()
                );

                $helper = HelperBroker::getExistingHelper('redirector');
                $helper->gotoSimple('mudar-senha-provisoria', 'usuario');
            }
	    
	}
    }
}