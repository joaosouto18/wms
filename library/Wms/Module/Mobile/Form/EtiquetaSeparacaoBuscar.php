<?php

namespace Wms\Module\Mobile\Form;

class EtiquetaSeparacaoBuscar extends \Core\Form
{

    protected $_controllerUrl = 'expedicao';

    protected $_actionUrl = 'buscar-etiqueta';

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
                            'controller' => 'expedicao',
                            'action' => 'buscar-etiqueta'
                        ))
                )
                ->addElement('hidden', 'idExpedicao')
                ->addElement('hidden', 'placa')
                ->addElement('hidden', 'central')
                ->addElement('text', 'codigoBarras', array(
                    'required' => true,
                    'label' => 'Código de Barras',
                    'size' => 40,
                    'class' => 'focus',
                    'maxlength' => 100,
                    'style' => 'width: 99%'
                ))
                ->addElement('submit', 'submit', array(
                    'label' => 'Buscar',
                    'class' => 'btn',
                    'decorators' => array('ViewHelper')
                ))
                ->addDisplayGroup(
                array('idRecebimento', 'codigoBarras', 'submit'), 'identification'
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