<?php

namespace Wms\Module\Mobile\Form;

/**
 * Description of Form
 *
 * @author medina
 */
class OrdemServico extends \Core\Form
{

    public function init()
    {
        $this->setAction($this->getView()->url(array(
                            'controller' => 'index',
                            'action' => 'produto-buscar'
                        ))
                )
                ->addElement('text', 'id', array(
                    'required' => true,
                    'label' => 'Código da Ordem de Serviço',
                    'size' => 15,
                    'class' => 'focus',
                    'maxlength' => 15
                ))
                ->addElement('submit', 'submit', array(
                    'label' => 'Pesquisar',
                    'class' => 'btn',
                    'decorators' => array('ViewHelper'),
                ))
                ->addDisplayGroup(
                        array('id', 'submit'), 'identification', array('legend' => 'Buscar Recebimento')
        );
    }

}