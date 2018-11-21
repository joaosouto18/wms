<?php
namespace Wms\Module\Expedicao\Form;

use Wms\Module\Web\Form;

class AssociarPraca extends Form
{
    public function init()
    {
        $em = $this->getEm();

        /** @var \Wms\Domain\Entity\MapaSeparacao\PracaRepository $repoPraca */
        $repoPraca = $em->getRepository('wms:MapaSeparacao\Praca');
        /** @var \Wms\Domain\Entity\Util\SiglaRepository $repoSigla */
        $repoSigla = $em->getRepository('wms:Util\Sigla');

        $this
            ->setAction($this->getView()->url(array('module' =>'expedicao', 'controller' => 'cliente', 'action' => 'associar-praca')))
            ->setAttribs(array(
                'method' => 'get',
                'class' => 'filtro',
                'id' => 'frm-associar-praca',
            ))
            ->addElement('text', 'codCliente', array(
                'label' => 'Cód. Cliente',
                'size' => 10,
            ))
            ->addElement('text', 'nomeCliente', array(
                'label' => 'Nome',
                'size' => 40,
            ))
            ->addElement('select', 'praca', array(
                'label' => 'Praça',
                'multiOptions' => $repoPraca->getIdValue(),
                'class' => 'pracas',
            ))
            ->addElement('text', 'cidade', array(
                'label' => 'Cidade',
                'size' => 30,
            ))
            ->addElement('text', 'bairro', array(
                'label' => 'Bairro',
                'size' => 30,
            ))
            ->addElement('select', 'estado', array(
                'label' => 'UF',
                'multiOptions' => $repoSigla->getEstados(),
            ))
            ->addElement('submit', 'submit', array(
                'label' => 'Buscar',
                'class' => 'btn',
                'decorators' => array('ViewHelper'),
            ))
            ->addDisplayGroup(array(
                'codCliente',
                'nomeCliente',
                'praca',
                'cidade',
                'bairro',
                'estado',
                'submit'),
                'consulta',
                array('legend' => 'Filtros'));
    }

}