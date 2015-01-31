<?php

namespace Wms\Module\Web\Form\MapaSeparacao;

use Wms\Module\Web\Form,
    Core\Form\SubForm,
    Wms\Domain\Entity\Movimentacao\Veiculo as VeiculoEntity;

/**
 * Description of Veiculo
 *
 * @author Renato Medina <medinadato@gmail.com>
 */
class ModeloSeparacao extends \Wms\Module\Web\Form
{

    public function init()
    {
        //form's attr
        $this->setAttribs(array('id' => 'modelo-separacao-form', 'class' => 'saveForm'));

        $em = $this->getEm();
        $repoModeloSeparacao = $em->getRepository('wms:MapaSeparacao\ModeloSeparacao');

        //formulário
        $formModelo = new SubForm;

        $formModelo->addElement('select', 'tipoSFracionado', array(
                    'label' => 'Tipo de Separação Fracionado',
                    'multiOptions' => array('firstOpt' => 'Selecione...', 'options' => $repoModeloSeparacao->getTipoSeparacao()),
                    'required' => true,
                    'style'=>'min-width:180px'
                ))
                ->addElement('select', 'tipoQFracionado', array(
                    'label' => 'Tipo de Quebra Fracionado',
                    'multiOptions' => $repoModeloSeparacao->getTipoQuebra(),
                    'required' => true,
                    'style'=>'min-width:150px',
                ))
                ->addElement('text', 'dummy1', array(
                    'required' => false,
                    'ignore' => true,
                    'autoInsertNotEmptyValidator' => false,
                    'decorators' => array(
                            array(
                                'HtmlTag', array(
                                'tag'  => 'div',
                                'id'   => 'wmd-button-bar',
                                'style' => 'width:100%; clear:both; height:5px'
                            )
                        )
                    )
                ))
                ->addElement('select', 'tipoSNfracionado', array(
                    'label' => 'Tipo de Separação Não Fracionado',
                    'multiOptions' => array('firstOpt' => 'Selecione...', 'options' => $repoModeloSeparacao->getTipoSeparacao()),
                    'required' => true,
                    'style'=>'min-width:180px'
                ))
                ->addElement('select', 'tipoQNfracionado', array(
                    'label' => 'Tipo de Quebra Não Fracionado',
                    'multiOptions' => $repoModeloSeparacao->getTipoQuebra(),
                    'required' => true,
                    'style'=>'min-width:150px'
                ))
                ->addElement('text', 'dummy2', array(
                    'required' => false,
                    'ignore' => true,
                    'autoInsertNotEmptyValidator' => false,
                    'decorators' => array(
                        array(
                            'HtmlTag', array(
                            'tag'  => 'div',
                            'id'   => 'wmd-button-bar',
                            'style' => 'width:100%; clear:both; height:5px'
                        )
                        )
                    )
                ))
                ->addElement('select', 'quebraColetor', array(
                    'label' => 'Terá Quebra no Coletor?',
                    'multiOptions' => array('SIM'=>'SIM','NAO'=>'NÃO'),
                    'required' => true,
                    'style'=>'min-width:180px'
                ))
                ->addElement('select', 'emitirEtiquetaMae', array(
                    'label' => 'Emitir Etiqueta Mãe?',
                    'multiOptions' => array('SIM'=>'SIM','NAO'=>'NÃO'),
                    'required' => true,
                    'style'=>'min-width:180px'
                ))
                ->addElement('select', 'emitirEtiquetaMapa', array(
                    'label' => 'Emitir Etiqueta do Mapa?',
                    'multiOptions' => array('SIM'=>'SIM','NAO'=>'NÃO'),
                    'required' => true,
                    'style'=>'min-width:180px'
                ))
                ->addElement('select', 'conversaoFatorProduto', array(
                    'label' => 'Conversão por Fator do Produto?',
                    'multiOptions' => array('SIM'=>'SIM','NAO'=>'NÃO'),
                    'required' => true,
                    'style'=>'min-width:180px'
                ))
                ->addDisplayGroup($formModelo->getElements(), 'veiculo', array('legend' => 'Cadastro de Veículo'));

        $this->addSubFormTab('Identificação', $formModelo, 'identificacao', null);
    }

    /**
     * Sets the values from entity
     * @param \Wms\Domain\Entity\MapaSeparacao\ModeloSeparacao $modeloSeparacao
     */
    public function setDefaultsFromEntity(\Wms\Domain\Entity\MapaSeparacao\ModeloSeparacao $modeloSeparacao)
    {
        $values = array(
            'tipoSFracionado' => $modeloSeparacao->getTipoSeparacaoFracionado(),
            'tipoQFracionado' => $modeloSeparacao->getTipoQuebraFracionado(),
            'tipoSNfracionado' => $modeloSeparacao->getTipoSeparacaoNaofracionado(),
            'tipoQNfracionado' => $modeloSeparacao->getTipoQuebraNaofracionado(),
            'quebraColetor' => $modeloSeparacao->getQuebraColetor(),
            'emitirEtiquetaMae' => $modeloSeparacao->getEmitirEtiquetaMae(),
            'emitirEtiquetaMapa' => $modeloSeparacao->getEmitirEtiquetaMapa(),
            'conversaoFatorProduto' => $modeloSeparacao->getConversaoFatorProduto(),
        );

        $this->setDefaults($values);
    }

}
