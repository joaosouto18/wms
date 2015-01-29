<?php

namespace Wms\Module\Web;

use Wms\Configuration\Acl;

/**
 * Description of View
 *
 * @author Renato Medina <medinadato@gmail.com>
 * @todo Documentar
 */
class Page extends \Wms\Page
{

    /**
     * title of the page
     * @var string
     */
    protected $title;
    /**
     * html id of the page container
     * @var string
     */
    protected $id;
    /**
     * buttons collection of the page
     * @var array
     */
    protected $buttons = array();
    /**
     *
     * @var \Zend_Acl
     */
    protected $acl;

    /**
     * @var \Wms\Configuration\Acl
     */
    protected $aclConfiguration;

    /**
     * @var string
     */
    protected $role;
    /**
     * @var array
     */
    protected $requestParams;

    /**
     *
     * @param array $options
     * @return \Wms\Module\Web\Page
     */
    public function __construct(array $options = array())
    {
        // acl and role
        $this->acl = \Zend_Registry::get('acl');
        $this->role = \Zend_Auth::getInstance()->getIdentity()->getRoleId();

        //request
        $this->requestParams = \Zend_Controller_Front::getInstance()->getRequest()->getParams();
        $this->aclConfiguration = new Acl();
        $this->aclConfiguration->setResourceByRequest(\Zend_Controller_Front::getInstance()->getRequest());

        \Zend\Stdlib\Configurator::configure($this, $options);
        return $this;
    }

    /**
     *
     * @param array $options
     */
    public static function configure(array $options)
    {
        $page = new Page($options);
        $viewRenderer = \Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer');
        $viewRenderer->view->page = $page;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    public function getButtons()
    {
        return $this->buttons;
    }

    /**
     *
     * @param array $buttons
     * @return \Wms\Module\Web\Page
     */
    public function setButtons(array $buttons)
    {
        foreach ($buttons as $button)
            $this->addButton($button);

        return $this;
    }

    /**
     *
     * @param \Wms\Page\Element\Button $button
     * @return boolean
     */
    protected function isAllowed(\Wms\Page\Element\Button $button) {
        //url do botao
        $btnParams = $button->getUrlParams();

        $module = (isset($btnParams['module '])) ? $btnParams['module '] : $this->requestParams['module'];
        $controller = (isset($btnParams['controller'])) ? $btnParams['controller'] : $this->requestParams['controller'];
        $action = (isset($btnParams['action'])) ? $btnParams['action'] : $this->requestParams['action'];

        if ($this->aclConfiguration->isDefaultModule($module)) {
            $resource = $controller;
        } else {
            $resource =  $this->aclConfiguration->getResource();
        }

        return ($this->acl->has($resource) || $this->acl->isAllowed($this->role, $resource, $action));
    }

    /**
     *
     * @param mixed $button
     * @throws \Core\Grid\Exception
     */
    public function addButton($button)
    {
        if (is_array($button))
            $button = new \Wms\Page\Element\Button($button);
        elseif ($button instanceof \Wms\Page\Element\Button)
            $button = $button;
        else
            throw new \Core\Grid\Exception ('Invalid button param');

        if($this->isAllowed($button))
            $this->buttons[] = $button;
    }

}
