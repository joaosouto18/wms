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
        ))->addElement('checkbox', 'utilizaConversaoParaCaixaMaster', array(
            'label' => 'Utiliza Conversão para Caixa Master',
            'checkedValue' => 'S'
        ))->addElement('checkbox', 'utilizaQuebraNaConferenciaDoColetor', array(
            'label' => 'Utiliza Quebra na conferência do Coletor',
            'checkedValue' => 'S'
        ))->addElement('checkbox', 'utilizaEtiquetaMae', array(
            'label' => 'Utiliza Etiqueta Mãe',
            'checkedValue' => 'S'
        ))->addElement('select', 'quebraNoProcessoPulmaoDoca', array(
            'label' => 'Quebra no processo Pulmão-Doca',
            'multiOptions' => array('N' => 'Não utiliza', 'C' => 'Por Cliente', 'P' => 'Por Praça'),
        ))->addElement('select', 'tipoDeQuebraNoVolume', array(
            'label' => 'Tipo de Quebra no Volume',
            'multiOptions' => array('C' => 'Por Cliente', 'A' => 'Por Carga'),
        ))->addElement('select', 'tipoDefaultDeEmbalados', array(
            'label' => 'Tipo Default de Embalados',
            'multiOptions' => array('P' => 'Por Produto', 'F' => 'Todos os fracionados'),
        ))->addElement('select', 'tipoDeConferenciaParaEmbalados', array(
            'label' => 'Tipo de Conferência para Embalados',
            'multiOptions' => array('I' => 'Item a Item', 'Q' => 'Informando a quantidade'),
        ))->addElement('select', 'tipoDeConferenciaParaNaoEmbalados', array(
            'label' => 'Tipo de Conferência para Não Embalados',
            'multiOptions' => array('I' => 'Item a Item', 'Q' => 'Informando a quantidade'),
        ))->addElement('select', 'tipoDeSeparacaoFracionados', array(
            'label' => 'Tipo de Separação',
            'multiOptions' => array('M' => 'Mapa de Separação', 'E' => 'Etiqueta de Separação'),
        ))->addElement('select', 'tipoDeSeparacaoNaoFracionados', array(
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
            'utilizaConversaoParaCaixaMaster',
            'utilizaQuebraNaConferenciaDoColetor',
            'utilizaEtiquetaMae',
            'quebraNoProcessoPulmaoDoca',
            'tipoDeQuebraNoVolume',
            'tipoDefaultDeEmbalados',
            'tipoDeConferenciaParaEmbalados',
            'tipoDeConferenciaParaNaoEmbalados',
            'tipoDeSeparacaoFracionados',
            'tipoDeSeparacaoNaoFracionados',
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