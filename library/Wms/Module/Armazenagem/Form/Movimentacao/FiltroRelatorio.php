<?php

namespace Wms\Module\Armazenagem\Form\Movimentacao;

use Wms\Module\Web\Form;
use Wms\Util\Endereco;

class FiltroRelatorio extends Form
{

    public function init($utilizaGrade = 'S')
    {

        $this
            ->setAttribs(array(
                'method' => 'post',
                'class' => 'filtro-m',
                'id' => 'filtro-movimentacao',
            ))
            ->addElement('text', 'idProduto', array(
                'size' => 12,
                'label' => 'Cod. produto',
                'class' => 'focus',
            ));
        if ($utilizaGrade == "S") {
            $this->addElement('text', 'grade', array(
                'size' => 12,
                'label' => 'Grade',
            ));
        } else {
            $this->addElement('hidden', 'grade', array(
                'label' => 'Grade',
                'value' => 'UNICA'
            ));
        }
        $this->addElement('text', 'rua', array(
            'size' => 3,
            'alt' => 'enderecoRua',
            'label' => 'Rua'
        ))
            ->addElement('text', 'predio', array(
                'size' => 3,
                'alt' => 'enderecoPredio',
                'label' => 'Prédio',
            ))
            ->addElement('text', 'nivel', array(
                'size' => 3,
                'alt' => 'enderecoNivel',
                'label' => 'Nível',
            ))
            ->addElement('text', 'apto', array(
                'size' => 3,
                'alt' => 'enderecoApartamento',
                'label' => 'Apto',
            ))
            ->addElement('submit', 'imprimir', array(
                'label' => 'Imprimir',
                'class' => 'btn',
                'decorators' => array('ViewHelper'),
            ))
            ->addElement('button', 'produtos-divergentes', array(
                'label' => 'Imprimir Produtos com Volumes Divergentes',
                'class' => 'btn',
                'decorators' => array('ViewHelper'),
            ))
            ->addDisplayGroup(array('idProduto', 'grade', 'rua', 'predio', 'nivel', 'apto', 'imprimir'), 'identificacao', array('legend' => 'Filtro')
            );

    }

}
