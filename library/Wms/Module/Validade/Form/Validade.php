<?php

namespace Wms\Module\Validade\Form;

use Wms\Module\Web\Form;

class Validade extends Form
{
    public function init()
    {
        /** @var \Wms\Domain\Entity\Armazenagem\LinhaSeparacaoRepository $linhaRepo */
        $linhaRepo = $this->getEm()->getRepository('wms:Armazenagem\LinhaSeparacao');
        $result = $linhaRepo->findAll();
        $arr = array('' => 'Selecione');
        /** @var \Wms\Domain\Entity\Armazenagem\LinhaSeparacao $linha */
        foreach ($result as $linha){
            $arr[$linha->getId()] = $linha->getDescricao();
        }

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
                'size' => 45,
            ))
            ->addElement('select', 'linhaSeparacao', array(
                'label' => 'Linha de separação',
                'multiOptions' =>  $arr,
            ))
            ->addElement('hidden','br', array(
                'required' => false,
                'ignore' => true,
                'autoInsertNotEmptyValidator' => false,
                'decorators' => array(
                    array(
                        'HtmlTag', array(
                            'tag'  => 'br',
                        )
                    )
                )
            ))
            ->addElement('text', 'fornecedor', array(
                'label' => 'Fornecedor',
                'size' => 45,
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
                'linhaSeparacao',
                'br',
                'fornecedor',
                'dataReferencia',
                'submit',
                'gerarPdf'),
                'formulario', array('legend' => 'Formulário'));
    }

}