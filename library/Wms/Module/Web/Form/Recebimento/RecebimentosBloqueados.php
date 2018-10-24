<?php
namespace Wms\Module\Web\Form\Recebimento;

use Wms\Module\Web\Form,
    Core\Form\SubForm;


class RecebimentosBloqueados extends Form
{

    public function init()
    {
        $this->setAction($this->getView()->url(array('controller' => 'recebimento', 'action' => 'produtos-bloqueados-ajax')))

            ->addElement('text', 'codRecebimento', array(
            'size' => 10,
            'label' => 'Recebimento',
            'class' => 'focus',
            ))

            ->addElement('text', 'codProduto', array(
                'size' => 10,
                'label' => 'Cód. Produto',
            ))

            ->addElement('text', 'grade', array(
                'size' => 10,
                'label' => 'Dsc. Grade',
            ))


            ->addElement('date', 'dataInicial1', array(
                'size' => 20,
                'decorators' => array('ViewHelper'),
            ))

            ->addElement('date', 'dataInicial2', array(
                'size' => 20,
                'decorators' => array('ViewHelper'),
            ))

            ->addElement('submit', 'buscar', array(
                'class' => 'btn',
                'decorators' => array('ViewHelper'),
                'label' => 'Gerar Relatório',
            ))

            ->addDisplayGroup(array('codRecebimento', 'codProduto', 'grade', 'dataInicial1', 'dataInicial2', 'buscar'), 'recebimentosBloqueados');
    }

}