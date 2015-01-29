<?php

namespace Wms\Module\Mobile\Form;

/**
 * Description of Form
 *
 * @author medina
 */
class PickingLeitura extends \Core\Form
{

    protected $_controllerUrl = 'enderecamento';
    protected $_label = 'Busca de Endereços de Picking';
    protected $_labelElement = 'Etiqueta de Endereço de Picking';
    protected $_actionUrl = 'leitura-picking';


    public function setLabelElement($value) {
        $this->_labelElement = $value;
    }

    public function getLabelElement() {
        return $this->_labelElement;
    }

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
                    'label' => $this->_labelElement,
                    'size' => 40,
                    'class' => 'focus',
                    'maxlength' => 100,
                    'style' => 'width: 99%',
                ))
                ->addElement('hidden', 'uma')
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