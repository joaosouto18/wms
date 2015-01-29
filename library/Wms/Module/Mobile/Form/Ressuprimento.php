<?php

namespace Wms\Module\Mobile\Form;

/**
 * Description of Form
 *
 * @author medina
 */
class Ressuprimento extends \Core\Form
{

    protected $_controllerUrl = 'ressuprimento';
    protected $_label = 'Busca de Endereços';
    protected $_actionUrl = 'listar-picking';

    public function setLabel($value) {
        $this->_label = $value;
    }

    public function getLabel() {
        return $this->_label;
    }

    public function setActionUrl($actionUrl)
    {
        $this->_actionUrl = $actionUrl;
    }

    public function getActionUrl()
    {
        return $this->_actionUrl;
    }

    public function setControllerUrl($controllerUrl)
    {
        $this->_controllerUrl = $controllerUrl;
    }

    public function getControllerUrl()
    {
        return $this->_controllerUrl;
    }

    public function init()
    {
        $this->setAction($this->getView()->url(array(
                            'controller' => $this->_controllerUrl,
                            'action' => $this->_actionUrl
                        ))
                )
                ->addElement('text', 'codigoBarras', array(
                    'required' => true,
                    'label' => 'Etiqueta de Endereço',
                    'size' => 40,
                    'class' => 'focus',
                    'maxlength' => 100,
                    'style' => 'width: 99%',
                ))
                ->addElement('submit', 'submit', array(
                    'label' => 'Buscar',
                    'class' => 'btn',
                    'decorators' => array('ViewHelper'),
                ))
                ->addDisplayGroup(
                array('codigoBarras', 'submit'), 'identification', array('legend' => $this->_label)
        );
    }

    public function newUrl()

    {
        $this->setAction($this->getView()->url(array(
            'controller' => $this->getControllerUrl(),
            'action' => $this->getActionUrl()
        )));
    }

}