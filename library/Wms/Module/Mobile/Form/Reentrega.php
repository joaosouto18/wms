<?php

namespace Wms\Module\Mobile\Form;

use Wms\Module\Web\Form;


class Reentrega extends Form
{

    public function init()
    {
        $this->setAttribs(array('id' => 'reentrega-form', 'class' => 'saveForm'));
        $this->setAttrib('onkeydown', 'nextInput(this)');

        $this->setAction($this->getView()->url(array('controller' => 'reentrega', 'action' => 'buscar')));

        $this->addElement('text', 'codEtiqueta', array(
            'label' => 'Código Barras Etiqueta',
            'id' => 'codEtiqueta',
        ))
            ->addElement('text', 'notaFiscal', array(
                'label' => 'Número Nota Fiscal',
                'class' => 'notaFiscal',
                'id' => 'notaFiscal',
            ))
            ->addElement('submit', 'submit', array(
                'label' => 'Buscar',
                'class' => 'btn',
                'decorators' => array('ViewHelper'),
            ))
            ->addDisplayGroup(array('carga', 'codEtiqueta', 'notaFiscal', 'submit'), 'recebimento',array('legend' => 'Gerar Recebimento'));
    }

}
