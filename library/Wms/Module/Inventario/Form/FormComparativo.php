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
        $arr = array('' => 'Selecione...');
        /** @var \Wms\Domain\Entity\Armazenagem\LinhaSeparacao $linha */
        foreach ($result as $linha){
            $arr[$linha->getId()] = $linha->getDescricao();
        }

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
                'multiOptions' => array(
                    'S' => 'Sim',
                    'N' => 'Não'
                )
            ))
            ->addElement('select', 'tipoDivergencia', array(
                'label' => 'Tipo Divergência',
                'multiOptions' => array(
                    'S' => 'Sobra',
                    'F' => 'Falta'
                )
            ))
            ->addElement('select', 'linhaSeparacao', array(
                'label' => 'Linha de separação',
                'multiOptions' =>  $arr,
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
            ->addDisplayGroup(array('inventario', 'divergencia', 'tipoDivergencia', 'linhaSeparacao', 'submit', 'gerarPdf'), 'apontamento', array('legend' => 'Relatório de comparativo de estoque ERP x WMS')
        );
    }
}