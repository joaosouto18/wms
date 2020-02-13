<?php

namespace Wms\Module\Validade\Form;

use Wms\Domain\Entity\Sistema\Parametro;
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

        $paramRepo = $this->getEm()->getRepository('wms:Sistema\Parametro');
        /** @var Parametro $param */
        $param = $paramRepo->findOneBy(array('constante' => "UTILIZA_GRADE"));

        $yDsc = 45;
        $yForn = 45;
        if ($param->getValor() === "S"){
            $yDsc = 37;
            $yForn = 30;
        }

        $this
            ->setAction($this->getView()->url(array('module' =>'validade', 'controller' => 'consulta', 'action' => 'index')))
            ->setAttribs(array(
                'method' => 'get',
                'class' => 'filtro',
                'id' => 'frm-index',
            ))
            ->addElement('text', 'codProduto', array(
                'label' => 'Cod. Produto',
                'size' => 10,
            ))
            ->addElement('text', 'descricao', array(
                'label' => 'Descrição',
                'size' => $yDsc,
            ))
            ->addElement('select', 'endereco', array(
                'label' => 'Tipo Endereço',
                'mostrarSelecione' => true,
                'class' => 'medio',
                'multiOptions' => array('firstOpt' => 'Todos', 'options' => array('37' => 'Pickinkg', '38' => 'Pulmão')),
            ))
            ->addElement('select', 'linhaSeparacao', array(
                'label' => 'Linha de separação',
                'multiOptions' =>  $arr,
            ))
            ->addElement('hidden', 'idFabricante')
            ->addElement('text', 'fabricante', array(
                'label' => 'Fabricante/Fornecedor',
                'size' => $yForn,
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
                'label' => 'Gerar relatório PDF',
                'class' => 'btn',
                'decorators' => array('ViewHelper')
            ))
            ->addElement('submit', 'gerarCsv', array(
                'label' => 'Gerar relatório CSV',
                'class' => 'btn',
                'decorators' => array('ViewHelper')
            ));

        $arr = array(
            'codProduto',
            'descricao',
            'endereco',
            'br',
            'linhaSeparacao',
            'br',
            'fabricante',
            'dataReferencia',
            'submit',
            'gerarPdf',
            'gerarCsv');

        if ($param->getValor() === 'S'){
            $this->addElement('text', 'grade', array(
                'label' => 'Grade',
                'size' => 8,
            ));
            $arr = array(
                'codProduto',
                'grade',
                'endereco',
                'descricao',
                'linhaSeparacao',
                'br',
                'fabricante',
                'dataReferencia',
                'submit',
                'gerarPdf',
                'gerarCsv');
        }

        $this->addDisplayGroup($arr, 'formulario', array('legend' => 'Formulário'));
    }

}