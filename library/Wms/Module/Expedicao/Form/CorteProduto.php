<?php
namespace Wms\Module\Expedicao\Form;

use Wms\Module\Web\Form;

class CorteProduto extends Form
{
    public function init()
    {

        $em = $this->getEm();

        /** @var \Wms\Domain\Entity\Util\SiglaRepository $repoSigla */
        $repoSigla = $em->getRepository('wms:Util\Sigla');

        $this
            ->addElement('text', 'produto', array(
                'label' => 'Produto',
                'value' => 'testes',
                'size' => 50,
                'disabled' => true
            ))
            ->addElement('select', 'motivo', array(
                'label' => 'Motivo de Corte',
                'multiOptions' => $repoSigla->getEstados(),
            ))
            ->addElement('submit', 'cortar', array(
                'label' => 'Cortar',
                'onClick' => 'confirmaCorte()',
                'class' => 'btn',
                'decorators' => array('ViewHelper'),
            ))
            ->addDisplayGroup(array(
                'produto',
                'motivo',
                'cortar'),
                'corteProduto',
                array('legend' => 'Motivo de Corte'));
    }

    public function setProduto ($produtoEn) {
        $values = array(
            'produto' => $produtoEn->getDescricao(),
        );
        $this->setDefaults($values);
    }
}