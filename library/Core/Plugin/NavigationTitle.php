<?php
namespace Core\Plugin;

class NavigationTitle extends \Zend_Controller_Plugin_Abstract
{
    /**
     * @var array nome dos módulos em que o plugin será executado
     * @todo TIRAR ESSA PROP. PORCA
     */
    protected $useInModules = array('web');

    public function preDispatch(\Zend_Controller_Request_Abstract $request)
    {
	if (!in_array($request->getModuleName(), $this->useInModules)) {
	    return;
	}
	
	//get view
	$view = \Zend_Controller_Action_HelperBroker::getExistingHelper('ViewRenderer')->view;

	//get active page and its label
	if ($activePage = $view->navigation()->findOneBy('active', true)) {
	    $label = $activePage->get('label');
	    //set page label as html title
	    $view->headTitle($label);
	    $view->title = $label;
	}
    }

}