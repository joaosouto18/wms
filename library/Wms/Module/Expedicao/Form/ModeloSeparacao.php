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
        ))->addElement('checkbox', 'agrupContEtiquetas', array(
            'label' => 'Agrupar contagem de etiquetas de separação e embalados',
            'checkedValue' => 'S',
            'class' => 'condicionalCheckout'
        ))->addElement('checkbox', 'criarVolsFinalCheckout', array(
            'label' => 'Fechar e definir quantidade de volumes no final da conferência',
            'checkedValue' => 'S',
            'class' => 'condicionalCheckout'
        ))->addElement('checkbox', 'imprimeEtiquetaPatrimonio', array(
            'label' => 'Imprime Etiqueta Volume Patrimonio',
            'checkedValue' => 'S',
        ))->addElement('checkbox', 'produtoInventario', array(
            'label' => 'Expedir produtos sendo inventariados',
            'checkedValue' => 'S',
        ))->addElement('checkbox', 'forcarEmbVenda', array(
            'label' => 'Utilizar embalagem de venda por padrão',
            'checkedValue' => 'S',
        ))->addElement('checkbox', 'usaSequenciaRotaPraca', array(
            'label' => 'Exibir sequência de ROTA/PRAÇA no mapa de separação',
            'id' => 'usaSequenciaRotaPraca',
            'checkedValue' => 'S',
        ))->addElement('checkbox', 'quebraUnidFracionavel', array(
            'label' => 'Quebrar em mapa exclusivo e não agrupar unidades fracionáveis',
            'checkedValue' => 'S',
        ))->addElement('select', 'quebraPulmaDoca', array(
            'label' => 'Quebra no processo Pulmão-Doca',
            'class' => 'disableSequenciaPraca',
            'multiOptions' => array(
                'N' => 'Não utiliza',
                \Wms\Domain\Entity\Expedicao\ModeloSeparacao::QUEBRA_PULMAO_DOCA_EXPEDICAO => 'Por Expedição',
                \Wms\Domain\Entity\Expedicao\ModeloSeparacao::QUEBRA_PULMAO_DOCA_CARGA => 'Por Carga',
                \Wms\Domain\Entity\Expedicao\ModeloSeparacao::QUEBRA_PULMAO_DOCA_ROTA => 'Por Rota',
                \Wms\Domain\Entity\Expedicao\ModeloSeparacao::QUEBRA_PULMAO_DOCA_PRACA => 'Por Praça',
                \Wms\Domain\Entity\Expedicao\ModeloSeparacao::QUEBRA_PULMAO_DOCA_CLIENTE => 'Por Cliente'
            )
        ))->addElement('select', 'tipoQuebraVolume', array(
            'label' => 'Tipo de Quebra no Volume',
            'multiOptions' => array(
                \Wms\Domain\Entity\Expedicao\ModeloSeparacao::QUEBRA_VOLUME_CLIENTE => 'Por Cliente',
                \Wms\Domain\Entity\Expedicao\ModeloSeparacao::QUEBRA_VOLUME_CARGA => 'Por Carga'
            )
        ))->addElement('select', 'separacaoPc', array(
            'label' => 'Separação com carrinho',
            'id' => 'separacaoPc',
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
            'label' => 'Não Embalados',
            'multiOptions' => array(
                \Wms\Domain\Entity\Expedicao\ModeloSeparacao::TIPO_SEPARACAO_MAPA => 'Mapa',
                \Wms\Domain\Entity\Expedicao\ModeloSeparacao::TIPO_SEPARACAO_ETIQUETA => 'Etiqueta'),
        ))->addElement('select', 'tipoSeparacaoFracionadoEmbalado', array(
            'label' => 'Embalados',
            'multiOptions' => array(
                \Wms\Domain\Entity\Expedicao\ModeloSeparacao::TIPO_SEPARACAO_MAPA => 'Mapa',
                \Wms\Domain\Entity\Expedicao\ModeloSeparacao::TIPO_SEPARACAO_ETIQUETA => 'Etiqueta'),
        ))->addElement('select', 'tipoSeparacaoNaoFracionado', array(
            'label' => 'Não Embalados',
            'multiOptions' => array(
                \Wms\Domain\Entity\Expedicao\ModeloSeparacao::TIPO_SEPARACAO_MAPA => 'Mapa',
                \Wms\Domain\Entity\Expedicao\ModeloSeparacao::TIPO_SEPARACAO_ETIQUETA => 'Etiqueta'),
        ))->addElement('select', 'tipoSeparacaoNaoFracionadoEmbalado', array(
            'label' => 'Embalados',
            'multiOptions' => array(
                \Wms\Domain\Entity\Expedicao\ModeloSeparacao::TIPO_SEPARACAO_MAPA => 'Mapa',
                \Wms\Domain\Entity\Expedicao\ModeloSeparacao::TIPO_SEPARACAO_ETIQUETA => 'Etiqueta'),
        ))->addElement('multiCheckbox', 'quebraFracionados', array(
            'class' => 'disableSequenciaPraca',
            'multiOptions' => [
                MapaSeparacaoQuebra::QUEBRA_RUA => 'Rua',
                MapaSeparacaoQuebra::QUEBRA_LINHA_SEPARACAO => 'Linha de Separação',
                MapaSeparacaoQuebra::QUEBRA_PRACA => 'Praça',
                MapaSeparacaoQuebra::QUEBRA_ROTA => 'Rota',
                MapaSeparacaoQuebra::QUEBRA_CLIENTE => 'Cliente',
            ]
        ))->addElement('multiCheckbox', 'quebraNaoFracionados', array(
            'class' => 'disableSequenciaPraca',
            'multiOptions' => [
                MapaSeparacaoQuebra::QUEBRA_RUA => 'Rua',
                MapaSeparacaoQuebra::QUEBRA_LINHA_SEPARACAO => 'Linha de Separação',
                MapaSeparacaoQuebra::QUEBRA_PRACA => 'Praça',
                MapaSeparacaoQuebra::QUEBRA_ROTA => 'Rota',
                MapaSeparacaoQuebra::QUEBRA_CLIENTE => 'Cliente',
            ]
        ))->addElement('multiCheckbox', 'quebraEmbalados', array(
            'class' => 'disableSequenciaPraca',
            'multiOptions' => [
                MapaSeparacaoQuebra::QUEBRA_PRACA => 'Praça',
                MapaSeparacaoQuebra::QUEBRA_ROTA => 'Rota'
            ]
        ));

        $form->addDisplayGroup(array('descricao',
            'utilizaCaixaMaster',
            'utilizaQuebraColetor',
            'utilizaEtiquetaMae',
            'utilizaVolumePatrimonio',
            'agrupContEtiquetas',
            'criarVolsFinalCheckout',
            'forcarEmbVenda',
            'quebraUnidFracionavel',
            'produtoInventario',
            'imprimeEtiquetaPatrimonio',
            'quebraPulmaDoca',
            'tipoQuebraVolume',
            'separacaoPc',
            'tipoDefaultEmbalado',
            'tipoConferenciaEmbalado',
            'tipoConferenciaNaoEmbalado',
            'tipoSeparacaoFracionado',
            'tipoSeparacaoNaoFracionado',
            'tipoSeparacaoFracionadoEmbalado',
            'tipoSeparacaoNaoFracionadoEmbalado',
            'quebraFracionados',
            'quebraNaoFracionados',
            'usaSequenciaRotaPraca',
            'quebraEmbalados'), 'identificacao');
        $this->addSubFormTab("Identificação", $form, 'identificacao');
    }

}