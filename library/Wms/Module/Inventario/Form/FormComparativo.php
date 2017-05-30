<?php
namespace Wms\Module\Inventario\Form;

use Wms\Module\Web\Form;

class FormComparativo extends Form
{
    public function init()
    {
        /** @var \Wms\Domain\Entity\Armazenagem\LinhaSeparacaoRepository $linhaRepo */
        $linhaRepo = $this->getEm()->getRepository('wms:Armazenagem\LinhaSeparacao');
        $result = $linhaRepo->findAll();
        $linhaSeparacaoArray = array('' => 'Todos');
        /** @var \Wms\Domain\Entity\Armazenagem\LinhaSeparacao $linha */
        foreach ($result as $linha){
            $linhaSeparacaoArray[$linha->getId()] = $linha->getDescricao();
        }
        $divergenciaArray = array(
            '' => 'Todos',
            'S' => 'SIM',
            'N' => 'NÃO'
        );
        $tipoDivergenciaArray = array(
            '' => 'Todos',
            'S' => 'SOBRA',
            'F' => 'FALTA'
        );
        $estoqueWMS = array(
            '' => 'Todos',
            'S' => 'SIM',
            'N' => 'NÃO'
        );
        $estoqueERP = array(
            '' => 'Todos',
            'S' => 'SIM',
            'N' => 'NÃO'
        );

        $this->setAction(
            $this->getView()->url(array(
                'module' =>'inventario',
                'controller' => 'comparativo',
                'action' => 'index'
                )
            ))
            ->setAttribs(array(
                'method' => 'get',
                'class' => 'filtro'
            ))
            ->addElement('text', 'inventario', array(
                'size' => 10,
                'label' => 'Num. Inventario',
            ))
            ->addElement('select', 'divergencia', array(
                'label' => 'Divergência',
                'multiOptions' => $divergenciaArray
            ))
            ->addElement('select', 'tipoDivergencia', array(
                'label' => 'Tipo Divergência',
                'multiOptions' => $tipoDivergenciaArray
            ))
            ->addElement('select', 'linhaSeparacao', array(
                'label' => 'Linha de separação',
                'multiOptions' =>  $linhaSeparacaoArray,
            ))
            ->addElement('select','estoqueWms', array(
                'label' => 'Estoque WMS',
                'multiOptions' => $estoqueWMS,
            ))
            ->addElement('select','estoqueErp', array(
                'label' => 'Estoque ERP',
                'multiOptions' => $estoqueERP
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
            ->addDisplayGroup(array('inventario', 'divergencia', 'tipoDivergencia', 'linhaSeparacao', 'estoqueWms', 'estoqueErp', 'submit', 'gerarPdf'), 'apontamento', array('legend' => 'Relatório de comparativo de estoque ERP x WMS')
        );
    }
}