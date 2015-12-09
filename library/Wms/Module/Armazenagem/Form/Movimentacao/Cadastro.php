<?php

namespace Wms\Module\Armazenagem\Form\Movimentacao;

use Wms\Module\Web\Form;

class Cadastro extends Form
{

    public function init($utilizaGrade = "S")
    {

        $normasPaletizacao = $this->getEm()->getRepository('wms:Armazenagem\Unitizador')->getIdValue(true);

        $this
            ->setAttribs(array(
                'method' => 'post',
                'class' => 'filtro',
                'id' => 'cadastro-movimentacao',
            ))
            ->addElement('text', 'idProduto', array(
                'size' => 10,
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
        $this->addElement('date', 'validade', array(
            'label' => 'Data Validade',
        ));
        $this->addElement('select', 'volumes', array(
            'label' => 'Volumes',
        ))
            ->addElement('text', 'rua', array(
                'size' => 3,
                'label' => 'Rua',
                'maxlength' => '2',
                'class' => 'ctrSize',
            ))
            ->addElement('text', 'predio', array(
                'size' => 3,
                'maxlength' => '3',
                'label' => 'Predio',
                'class' => 'ctrSize',
            ))
            ->addElement('text', 'nivel', array(
                'size' => 3,
                'maxlength' => '2',
                'label' => 'Nivel',
                'class' => 'ctrSize',
            ))
            ->addElement('text', 'apto', array(
                'size' => 3,
                'maxlength' => '2',
                'label' => 'Apto',
                'class' => 'ctrSize',
            ))
            ->addElement('text', 'quantidade', array(
                'size' => 8,
                'label' => 'Qtd',
                'class' => 'ctrSize',
            ))
            ->addElement('button', 'buscarestoque', array(
                'class' => 'btn',
                'label' => 'Buscar Estoque',
                'decorators' => array('ViewHelper')
            ))
            ->addElement('select', 'idNormaPaletizacao', array(
                'label' => 'Unitizador',
                'mostrarSelecione' => true,
                'multiOptions' => $normasPaletizacao,
            ))
            ->addElement('text', 'uma', array(
                'size' => 4,
                'label' => 'UMA',
            ))
            ->addElement('submit', 'submit', array(
                'label' => 'Movimentar',
                'class' => 'btn',
                'decorators' => array('ViewHelper'),
            ))

            ->addElement('text', 'endereco_origem', array(
                'label' => 'Endereço Origem',
                'alt' => 'endereco',
                'size' => 20,
                'disabled' => 'disabled',
                'placeholder' => '00.000.00.00',
            ))

            ->addElement('text', 'ruaDestino', array(
                'size' => 3,
                'label' => 'Rua Destino',
                'maxlength' => '2',
                'class' => 'ctrSize',
            ))
            ->addElement('text', 'predioDestino', array(
                'size' => 3,
                'maxlength' => '3',
                'label' => 'Predio Destino',
                'class' => 'ctrSize',
            ))
            ->addElement('text', 'nivelDestino', array(
                'size' => 3,
                'maxlength' => '2',
                'label' => 'Nivel Destino',
                'class' => 'ctrSize',
            ))
            ->addElement('text', 'aptoDestino', array(
                'size' => 3,
                'maxlength' => '2',
                'label' => 'Apto Destino',
                'class' => 'ctrSize',
            ))

            ->addElement('submit', 'transferir', array(
                'label' => 'Transferir',
                'class' => 'btn',
                'decorators' => array('ViewHelper')
            ))
            ->addDisplayGroup(array('idProduto', 'grade', 'volumes','validade', 'uma', 'rua', 'predio', 'nivel', 'apto', 'quantidade','idNormaPaletizacao', 'submit', 'buscarestoque'), 'identificacao', array('legend' => 'Movimentar'))
            ->addDisplayGroup(array('endereco_origem','ruaDestino', 'predioDestino', 'nivelDestino', 'aptoDestino', 'transferir'), 'tranferencia', array('legend' => 'Transferir'));

    }

}
