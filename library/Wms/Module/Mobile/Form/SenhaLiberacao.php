<?php
namespace Wms\Module\Mobile\Form;

class SenhaLiberacao extends \Core\Form
{

    public function init()
    {
        $this->setAction($this->getView()->url(array(
                            'controller' => 'expedicao',
                            'action' => 'liberar-os'
                        ))
                )
                ->addElement('hidden', 'idExpedicao')
                ->addElement('hidden', 'idEtiqueta')
                ->addElement('password', 'senha', array(
                    'required' => true,
                    'label' => 'Senha',
                    'size' => 40,
                    'class' => 'focus',
                    'maxlength' => 100,
                    'style' => 'width: 99%',
                ))
                ->addElement('submit', 'submit', array(
                    'label' => 'Liberar Ordem de Serviço',
                    'class' => 'btn',
                    'decorators' => array('ViewHelper'),
                ))
                ->addDisplayGroup(
                array('idExpedicao', 'idEtiqueta', 'senha'), 'identification', array('legend' => 'Liberar Ordem de Serviço')
        );
    }

    public function newUrl($controller, $action)
    {
        $this->setAction($this->getView()->url(array(
            'controller' => $controller,
            'action' => $action
        )));
    }

}