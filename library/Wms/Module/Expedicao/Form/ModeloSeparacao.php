<?php
namespace Wms\Module\Expedicao\Form;

use Wms\Domain\Entity\Expedicao\MapaSeparacaoQuebra;
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
        ))->addElement('checkbox', 'utilizaVolumePatrimonio', array(
            'label' => 'Utiliza Volume Patrimônio',
            'checkedValue' => 'S'
        ))->addElement('checkbox', 'imprimeEtiquetaPatrimonio', array(
            'label' => 'Imprime Etiqueta Volume Patrimonio',
            'checkedValue' => 'S',
        ))->addElement('select', 'quebraPulmaDoca', array(
            'label' => 'Quebra no processo Pulmão-Doca',
            'multiOptions' => array(
                'N' => 'Não utiliza',
                \Wms\Domain\Entity\Expedicao\ModeloSeparacao::QUEBRA_PULMAO_DOCA_CLIENTE => 'Por Cliente',
                \Wms\Domain\Entity\Expedicao\ModeloSeparacao::QUEBRA_PULMAO_DOCA_PRACA => 'Por Praça'),
        ))->addElement('select', 'tipoQuebraVolume', array(
            'label' => 'Tipo de Quebra no Volume',
            'multiOptions' => array(
                \Wms\Domain\Entity\Expedicao\ModeloSeparacao::QUEBRA_VOLUME_CLIENTE => 'Por Cliente',
                \Wms\Domain\Entity\Expedicao\ModeloSeparacao::QUEBRA_VOLUME_CARGA => 'Por Carga'),
        ))->addElement('select', 'separacaoPc', array(
            'label' => 'Separação com carrinho',
            'multiOptions' => array('S' => 'Sim', 'N' => 'Não'),
        ))->addElement('select', 'tipoDefaultEmbalado', array(
            'label' => 'Tipo Default de Embalados',
            'multiOptions' => array(
                \Wms\Domain\Entity\Expedicao\ModeloSeparacao::DEFAULT_EMBALADO_PRODUTO => 'Por Produto',
                \Wms\Domain\Entity\Expedicao\ModeloSeparacao::DEFAULT_EMBALADO_FRACIONADOS => 'Todos os fracionados'),
        ))->addElement('select', 'tipoConferenciaEmbalado', array(
            'label' => 'Tipo de Conferência para Embalados',
            'multiOptions' => array(
                \Wms\Domain\Entity\Expedicao\ModeloSeparacao::CONFERENCIA_ITEM_A_ITEM => 'Item a Item',
                \Wms\Domain\Entity\Expedicao\ModeloSeparacao::CONFERENCIA_QUANTIDADE => 'Informando a quantidade'),
        ))->addElement('select', 'tipoConferenciaNaoEmbalado', array(
            'label' => 'Tipo de Conferência para Não Embalados',
            'multiOptions' => array(
                \Wms\Domain\Entity\Expedicao\ModeloSeparacao::CONFERENCIA_ITEM_A_ITEM => 'Item a Item',
                \Wms\Domain\Entity\Expedicao\ModeloSeparacao::CONFERENCIA_QUANTIDADE => 'Informando a quantidade'),
        ))->addElement('select', 'tipoSeparacaoFracionado', array(
            'label' => 'Tipo de Separação',
            'multiOptions' => array(
                \Wms\Domain\Entity\Expedicao\ModeloSeparacao::TIPO_SEPARACAO_MAPA => 'Mapa de Separação',
                \Wms\Domain\Entity\Expedicao\ModeloSeparacao::TIPO_SEPARACAO_ETIQUETA => 'Etiqueta de Separação'),
        ))->addElement('select', 'tipoSeparacaoNaoFracionado', array(
            'label' => 'Tipo de Separação',
            'multiOptions' => array(
                \Wms\Domain\Entity\Expedicao\ModeloSeparacao::TIPO_SEPARACAO_MAPA => 'Mapa de Separação',
                \Wms\Domain\Entity\Expedicao\ModeloSeparacao::TIPO_SEPARACAO_ETIQUETA => 'Etiqueta de Separação'),
        ))->addElement('checkbox', 'ruaFracionados', array(
            'label' => 'Rua',
            'checkedValue' => MapaSeparacaoQuebra::QUEBRA_RUA
        ))->addElement('checkbox', 'linhaDeSeparacaoFracionados', array(
            'label' => 'Linha de Separação',
            'checkedValue' => MapaSeparacaoQuebra::QUEBRA_LINHA_SEPARACAO
        ))->addElement('checkbox', 'pracaFracionados', array(
            'label' => 'Praça',
            'checkedValue' => MapaSeparacaoQuebra::QUEBRA_PRACA
        ))->addElement('checkbox', 'clienteFracionados', array(
            'label' => 'Cliente',
            'checkedValue' => MapaSeparacaoQuebra::QUEBRA_CLIENTE
        ))->addElement('checkbox', 'ruaNaoFracionados', array(
            'label' => 'Rua',
            'checkedValue' => MapaSeparacaoQuebra::QUEBRA_RUA
        ))->addElement('checkbox', 'linhaDeSeparacaoNaoFracionados', array(
            'label' => 'Linha de Separação',
            'checkedValue' => MapaSeparacaoQuebra::QUEBRA_LINHA_SEPARACAO
        ))->addElement('checkbox', 'pracaNaoFracionados', array(
            'label' => 'Praça',
            'checkedValue' => MapaSeparacaoQuebra::QUEBRA_PRACA
        ))->addElement('checkbox', 'clienteNaoFracionados', array(
            'label' => 'Cliente',
            'checkedValue' => MapaSeparacaoQuebra::QUEBRA_CLIENTE
        ));

        $form->addDisplayGroup(array('descricao',
            'utilizaCaixaMaster',
            'utilizaQuebraColetor',
            'utilizaEtiquetaMae',
            'utilizaVolumePatrimonio',
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