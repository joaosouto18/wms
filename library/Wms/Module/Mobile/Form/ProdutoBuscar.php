<?php

namespace Wms\Module\Mobile\Form;

/**
 * Description of Form
 *
 * @author medina
 */
class ProdutoBuscar extends \Core\Form
{

    protected $_controllerUrl = 'recebimento';

    protected $_actionUrl = 'produto-quantidade';

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
                            'controller' => 'recebimento',
                            'action' => 'produto-quantidade'
                        ))
                )
                ->addElement('hidden', 'idRecebimento')
                ->addElement('hidden', 'idExpedicao')
                ->addElement('hidden', 'idEtiqueta')
                ->addElement('hidden', 'codBarraProdutoEtiqueta')
                ->addElement('hidden', 'produto')
                ->addElement('text', 'codigoBarras', array(
                    'required' => true,
                    'label' => 'Código de Barras',
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
                array('idRecebimento', 'codigoBarras', 'submit'), 'identification', array('legend' => 'Busca do Produto')
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