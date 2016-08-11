<?php
namespace Wms\Module\Expedicao\Form;

use Wms\Module\Web\Form,
    Core\Form\SubForm;


class ModeloSeparacao extends Form
{

    public function init()
    {
        //form's attr
        $this->setAttribs(array('id' => 'modelo-separacao-form', 'class' => 'saveForm'));

        $form = new SubForm;
        $form->addElement('text', 'descricao', array(
            'label' => 'Descrição',
            'size' => 50,
        ))->addElement('checkbox', 'utilizaCaixaMaster', array(
            'label' => 'Utiliza Conversão para Caixa Master',
            'checkedValue' => 'S'
        ))->addElement('checkbox', 'utilizaQuebraColetor', array(
            'label' => 'Utiliza Quebra na conferência do Coletor',
            'checkedValue' => 'S'
        ))->addElement('checkbox', 'utilizaEtiquetaMae', array(
            'label' => 'Utiliza Etiqueta Mãe',
            'checkedValue' => 'S'
        ))->addElement('checkbox', 'imprimeEtiquetaPatrimonio', array(
            'label' => 'Imprime Etiqueta Volume Patrimonio',
            'checkedValue' => 'S',
        ))->addElement('select', 'quebraPulmaDoca', array(
            'label' => 'Quebra no processo Pulmão-Doca',
            'multiOptions' => array('N' => 'Não utiliza', 'C' => 'Por Cliente', 'P' => 'Por Praça'),
        ))->addElement('select', 'tipoQuebraVolume', array(
            'label' => 'Tipo de Quebra no Volume',
            'multiOptions' => array('C' => 'Por Cliente', 'A' => 'Por Carga'),
        ))->addElement('select', 'separacaoPc', array(
            'label' => 'Separação com carrinho',
            'multiOptions' => array('S' => 'Sim', 'N' => 'Não'),
        ))->addElement('select', 'tipoDefaultEmbalado', array(
            'label' => 'Tipo Default de Embalados',
            'multiOptions' => array('P' => 'Por Produto', 'F' => 'Todos os fracionados'),
        ))->addElement('select', 'tipoConferenciaEmbalado', array(
            'label' => 'Tipo de Conferência para Embalados',
            'multiOptions' => array('I' => 'Item a Item', 'Q' => 'Informando a quantidade'),
        ))->addElement('select', 'tipoConferenciaNaoEmbalado', array(
            'label' => 'Tipo de Conferência para Não Embalados',
            'multiOptions' => array('I' => 'Item a Item', 'Q' => 'Informando a quantidade'),
        ))->addElement('select', 'tipoSeparacaoFracionado', array(
            'label' => 'Tipo de Separação',
            'multiOptions' => array('M' => 'Mapa de Separação', 'E' => 'Etiqueta de Separação'),
        ))->addElement('select', 'tipoSeparacaoNaoFracionado', array(
            'label' => 'Tipo de Separação',
            'multiOptions' => array('M' => 'Mapa de Separação', 'E' => 'Etiqueta de Separação'),


        ))->addElement('checkbox', 'ruaFracionados', array(
            'label' => 'Rua',
            'checkedValue' => 'R'
        ))->addElement('checkbox', 'linhaDeSeparacaoFracionados', array(
            'label' => 'Linha de Separação',
            'checkedValue' => 'L'
        ))->addElement('checkbox', 'pracaFracionados', array(
            'label' => 'Praça',
            'checkedValue' => 'P'
        ))->addElement('checkbox', 'clienteFracionados', array(
            'label' => 'Cliente',
            'checkedValue' => 'C'
        ))->addElement('checkbox', 'ruaNaoFracionados', array(
            'label' => 'Rua',
            'checkedValue' => 'R'
        ))->addElement('checkbox', 'linhaDeSeparacaoNaoFracionados', array(
            'label' => 'Linha de Separação',
            'checkedValue' => 'L'
        ))->addElement('checkbox', 'pracaNaoFracionados', array(
            'label' => 'Praça',
            'checkedValue' => 'P'
        ))->addElement('checkbox', 'clienteNaoFracionados', array(
            'label' => 'Cliente',
            'checkedValue' => 'C'
        ));

        $form->addDisplayGroup(array('descricao',
            'utilizaCaixaMaster',
            'utilizaQuebraColetor',
            'utilizaEtiquetaMae',
            'imprimeEtiquetaPatrimonio',
            'quebraPulmaDoca',
            'tipoQuebraVolume',
            'separacaoPc',
            'tipoDefaultEmbalado',
            'tipoConferenciaEmbalado',
            'tipoConferenciaNaoEmbalado',
            'tipoSeparacaoFracionado',
            'tipoSeparacaoNaoFracionado',
            'ruaFracionados',
            'linhaDeSeparacaoFracionados',
            'pracaFracionados',
            'clienteFracionados',
            'ruaNaoFracionados',
            'linhaDeSeparacaoNaoFracionados',
            'pracaNaoFracionados',
            'clienteNaoFracionados'), 'identificacao');
        $this->addSubFormTab("Identificação", $form, 'identificacao');
    }

}