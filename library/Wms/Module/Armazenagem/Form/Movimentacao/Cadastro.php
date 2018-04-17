<?php

namespace Wms\Module\Armazenagem\Form\Movimentacao;

use Wms\Module\Web\Form;
use Wms\Util\Endereco;

class Cadastro extends Form
{

    public function init($utilizaGrade = "S", $controleProprietario = "N")
    {

        $normasPaletizacao = $this->getEm()->getRepository('wms:Armazenagem\Unitizador')->getIdValue(true);
        $proprietario = $this->getEm()->getRepository('wms:Filial')->getIdValue(true);
        $placeholder = Endereco::mascara();

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
            ->addElement('text', 'embalagens', array(
                'size' => 3,
                'label' => 'Embalagens',
                'class' => 'ctrSize',
            ))
            ->addElement('text', 'rua', array(
                'size' => 3,
                'label' => 'Rua',
                'alt' => 'enderecoRua',
                'class' => 'ctrSize',
            ))
            ->addElement('text', 'predio', array(
                'size' => 3,
                'alt' => 'enderecoPredio',
                'label' => 'Predio',
                'class' => 'ctrSize',
            ))
            ->addElement('text', 'nivel', array(
                'size' => 3,
                'alt' => 'enderecoNivel',
                'label' => 'Nivel',
                'class' => 'ctrSize',
            ))
            ->addElement('text', 'apto', array(
                'size' => 3,
                'alt' => 'enderecoApartamento',
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
            ));
        if($controleProprietario == 'S') {
            $this->addElement('select', 'codProprietario', array(
                'label' => 'Proprietário',
                'mostrarSelecione' => true,
                'multiOptions' => $proprietario,
            ));
        }
        $this->addElement('submit', 'submit', array(
                'label' => 'Movimentar',
                'class' => 'btn',
                'decorators' => array('ViewHelper')
            ))

            ->addElement('text', 'endereco_origem', array(
                'label' => 'Endereço Origem',
                'alt' => 'endereco',
                'size' => 20,
                'disabled' => 'disabled',
                'placeholder' => $placeholder,
            ))

            ->addElement('text', 'ruaDestino', array(
                'size' => 3,
                'label' => 'Rua Destino',
                'alt' => 'enderecoRua',
                'class' => 'ctrSize',
            ))
            ->addElement('text', 'predioDestino', array(
                'size' => 3,
                'alt' => 'enderecoPredio',
                'label' => 'Predio Destino',
                'class' => 'ctrSize',
            ))
            ->addElement('text', 'nivelDestino', array(
                'size' => 3,
                'alt' => 'enderecoNivel',
                'label' => 'Nivel Destino',
                'class' => 'ctrSize',
            ))
            ->addElement('text', 'aptoDestino', array(
                'size' => 3,
                'alt' => 'enderecoApartamento',
                'label' => 'Apto Destino',
                'class' => 'ctrSize',
            ))

            ->addElement('submit', 'transferir', array(
                'label' => 'Transferir',
                'class' => 'btn',
                'decorators' => array('ViewHelper')
            ));
        if($controleProprietario == 'S') {
            $this->addDisplayGroup(array('idProduto', 'grade', 'volumes', 'embalagens', 'validade', 'rua', 'predio', 'nivel', 'apto', 'quantidade', 'idNormaPaletizacao', 'codPessoa', 'submit', 'buscarestoque'), 'identificacao', array('legend' => 'Movimentar'));
        }else{
            $this->addDisplayGroup(array('idProduto', 'grade', 'volumes', 'embalagens', 'validade', 'rua', 'predio', 'nivel', 'apto', 'quantidade', 'idNormaPaletizacao', 'submit', 'buscarestoque'), 'identificacao', array('legend' => 'Movimentar'));
        }
        $this->addDisplayGroup(array('endereco_origem','ruaDestino', 'predioDestino', 'nivelDestino', 'aptoDestino', 'transferir'), 'tranferencia', array('legend' => 'Transferir'));

    }

}
