<?php

namespace Wms\Module\Mobile\Form;

use Wms\Module\Web\Form;


class ConferirProdutosReentrega extends Form
{

    public function init()
    {
        $this->setAttribs(array('id' => 'conferir-produtos-form', 'class' => 'saveForm'));

        $this->setAction($this->getView()->url(array('controller' => 'reentrega', 'action' => 'reconferir-produtos')));

        $em = $this->getEm();

        $this->addElement('text', 'codBarras', array(
                    'label' => 'Código de Barras',
                    'class' => 'codBarras',
                    'id' => 'codBarras'
                ))
                ->addElement('text', 'qtd', array(
                    'label' => 'Quantidade',
                    'class' => 'qtd',
                    'id' => 'qtd'
                ))
                ->addElement('hidden', 'numeroNota', array(
                    'id' => 'numeroNota'
                ))
                ->addElement('submit', 'submit', array(
                    'label' => 'Reconferir',
                    'class' => 'btn',
                    'decorators' => array('ViewHelper'),
                ))
                ->addDisplayGroup(array('codBarras', 'qtd', 'submit'), 'reconferir');
    }

}
