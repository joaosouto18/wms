<?php

namespace Wms\Module\Validade\Form;

use Wms\Module\Web\Form;

class Index extends Form
{
    public function init()
    {

        $this
            ->setAction($this->getView()->url(array('module' =>'validade', 'controller' => 'consulta', 'action' => 'index')))
            ->setAttribs(array(
                'method' => 'post',
                'class' => 'filtro',
                'id' => 'frm-index',
            ))
            ->addElement('text', 'codProduto', array(
                'label' => utf8_encode('Cód. Produto'),
                'size' => 10,
            ))
            ->addElement('text', 'descricao', array(
                'label' => utf8_encode('Descrição'),
                'size' => 40,
            ))
            ->addElement('text', 'fornecedor', array(
                'label' => 'Fornecedor',
                'size' => 40,
            ))
            ->addElement('date', 'dataReferencia', array(
                'label' => utf8_encode('Data de Referência'),
                'size' => 10,
            ))

            ->addElement('submit', 'submit', array(
                'label' => 'Buscar',
                'class' => 'btn',
                'decorators' => array('ViewHelper'),
            ))
            ->addDisplayGroup(array(
                'codProduto',
                'descricao',
                'fornecedor',
                'dataReferencia',
                'submit'),
                'formulario', array('legend' => utf8_encode('Formulário')));
    }

}