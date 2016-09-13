<?php

namespace Wms\Module\Validade\Form;

use Wms\Module\Web\Form;

class Validade extends Form
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
                'label' => 'Cod. Produto',
                'size' => 10,
            ))
            ->addElement('text', 'descricao', array(
                'label' => 'Descrição',
                'size' => 40,
            ))
            ->addElement('text', 'fornecedor', array(
                'label' => 'Fornecedor',
                'size' => 40,
            ))
            ->addElement('date', 'dataReferencia', array(
                'label' => 'Data de Referência',
                'size' => 10
            ))

            ->addElement('submit', 'submit', array(
                'label' => 'Buscar',
                'class' => 'btn',
                'decorators' => array('ViewHelper'),
            ))
            ->addElement('submit', 'gerarPdf', array(
                'label' => 'Gerar relatório',
                'class' => 'btn',
                'decorators' => array('ViewHelper')
            ))
            ->addDisplayGroup(array(
                'codProduto',
                'descricao',
                'fornecedor',
                'dataReferencia',
                'submit',
                'gerarPdf'),
                'formulario', array('legend' => 'Formulário'));
    }

}