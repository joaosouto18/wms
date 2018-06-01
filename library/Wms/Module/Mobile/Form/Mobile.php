<?php

namespace Wms\Module\Mobile\Form;

class Mobile extends \Core\Form
{
    protected $_label = '';
    protected $_labelCampo = '';
    protected $_nomeCampo = 'codigoBarras';

    public function setLabel($value)
    {
        $this->_label = $value;
    }

    public function getLabel()
    {
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

    public function setUrlParams($params)
    {
        $this->setAction($this->getView()->url(array(
            'controller' => $params['controller'],
            'action' => $params['action']
        )));
    }

    public function setLabelCampo($label)
    {
       $this->_labelCampo = $label;
    }

    public function getLabelCampo()
    {
        return $this->_labelCampo;
    }

    public function init()
    {
        $this
            ->addElement('text', $this->_nomeCampo, array(
                'required' => true,
                'label' => $this->getLabelCampo(),
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
                array($this->_nomeCampo, 'submit'), 'identification', array('legend' => $this->getLabel())
            );
    }

}