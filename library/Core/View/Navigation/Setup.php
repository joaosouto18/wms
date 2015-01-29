<?php

namespace Core\View\Navigation;

/**
 * Description of Setup
 *
 * @link    www.moveissimonetti.com.br/wms
 * @since   1.0
 * @version $Revision$
 * @author Desenvolvimento
 */
class Setup
{    
    public function __construct()
    {
	$this->_initialize();
    }

    protected function _initialize()
    {

        $cache = new \Core\Cache();

	if (!$cache->load('navConfig'))
        throw new \Exception('Problema ao carregar o navConfig.');

	    $navConfig = $cache->load('navConfig');

	\Zend_Registry::getInstance()->set('navConfig', $navConfig);
    }

}
