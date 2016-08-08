<?php

namespace Wms\Module\Importacao\Form;

use Wms\Module\Web\Form;

class EditarCamposImportacao extends Form
{

    public function init()
    {
        //form's attr
        $this->setAttribs(array('id' => 'editar-campos-form', 'class' => 'saveForm'));
        $this->addElement('text','posicao', array(
            'label' => 'Posição do dado no arquivo:',
            'size' => 10,
            'maxlenght' => 2
        ))
        ->addElement('text', 'tInicio', array(
            'label' => 'Tamanho no início (Campo usado apenas em importação de formato TXT)'
        ))
        ->addElement('text', 'tFim', array(
            'label' => 'Tamanho no fim (Campo usado apenas em importação de formato TXT)'
        ))
        ->addElement('submit', 'submit', array(
            'label' => 'Salvar',
            'attribs' => array(
                'id' => 'btnSalvar',
                'class' => 'btn',
            ),
            'decorators' => array('ViewHelper'),
        ));
    }
}
