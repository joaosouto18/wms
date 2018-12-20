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
        /** @var \Wms\Domain\Entity\FabricanteRepository $fabricanteRepository */
        $fabricanteRepository = $this->getEm()->getRepository('wms:Fabricante');
        $fabricantes = $fabricanteRepository->findBy(array(), array('nome' => 'ASC'));
        $fabricanteArray = array('' => 'Todos');
        /** @var \Wms\Domain\Entity\Fabricante $fabricante */
        foreach ($fabricantes as $fabricante) {
            $fabricanteArray[$fabricante->getId()] = $fabricante->getNome();
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
        $deduzirAvaria = array(
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
            ->addElement('select', 'deduzirAvaria', array(
                'label' => 'Deduzir Avaria no Estoque ERP',
                'mostrarSelecione' => false,
                'multiOptions' => $deduzirAvaria
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
            ->addElement('select', 'fabricante', array(
                'label' => 'Fabricante',
                'multiOptions' => $fabricanteArray
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
            ->addDisplayGroup(array('inventario', 'divergencia', 'tipoDivergencia', 'linhaSeparacao', 'estoqueWms', 'estoqueErp', 'deduzirAvaria', 'fabricante', 'submit', 'gerarPdf'), 'apontamento', array('legend' => 'Relatório de comparativo de estoque ERP x WMS')
        );
    }
}