<?php

namespace Wms\Module\Web\Form\Recebimento;

use Wms\Module\Web\Form,
    Core\Form\SubForm;

/**
 * Description of Recebimento - Observacao do Andamento
 *
 * @author Augusto Vespermann 
 */
class ObservacaoAndamento extends Form
{

    public function init()
    {

        $this->setAttribs(array('method' => 'post'))
                ->addElement('hidden', 'idRecebimento')
                ->addElement('textarea', 'descricao', array(
                    'cols' => 80,
                    'rows' => 4,
                    'maxlength' => 130,
                    'required' => true,
                ))
                ->addElement('submit', 'btnSubmit', array(
                    'class' => 'btn',
                    'style' => 'display:block',
                    'label' => 'Confirmar Cancelamento',
                ))
                ->addDisplayGroup(array('idRecebimento', 'descricao', 'btnSubmit'), 'identificacao', array('legend' => 'Observação'));

        $this->setElementDecorators(array('ViewHelper', 'Errors'))
                ->setDecorators(array('FormElements', 'Form'));
    }

}