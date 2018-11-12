<?php
namespace Wms\Module\Mobile\Form;

class BloquearEtiqueta extends \Core\Form
{

    public function init()
    {
        $this->setAction($this->getView()->url(array(
                            'controller' => 'expedicao',
                            'action' => 'bloquear-etiqueta-inexistente-ajax'
                        ))
                )
                ->addElement('hidden', 'idExpedicao')
                ->addElement('password', 'senha', array(
                    'required' => true,
                    'label' => 'Senha',
                    'size' => 40,
                    'class' => 'focus',
                    'maxlength' => 100,
                    'style' => 'width: 99%',
                ))
                ->addElement('submit', 'submit', array(
                    'label' => 'Liberar Etiqueta',
                    'class' => 'btn',
                    'decorators' => array('ViewHelper'),
                ))
                ->addDisplayGroup(
                array('idExpedicao', 'idEtiqueta', 'senha', 'submit'), 'identification', array('legend' => 'Liberar Etiqueta de Separacao')
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