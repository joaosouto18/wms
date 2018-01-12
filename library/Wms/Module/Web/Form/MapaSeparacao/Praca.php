<?php

namespace Wms\Module\Web\Form\MapaSeparacao;

use Wms\Domain\Entity\MapaSeparacao\PracaFaixa;
use Wms\Module\Web\Form,
    Core\Form\SubForm,
    Wms\Domain\Entity\Movimentacao\Veiculo as VeiculoEntity;

/**
 * Description of Veiculo
 *
 * @author Renato Medina <medinadato@gmail.com>
 */
class Praca extends \Wms\Module\Web\Form
{

    public function init($qtdFaixas = 1)
    {
        //form's attr
        $this->setAttribs(array('id' => 'modelo-separacao-form', 'class' => 'saveForm'));

        //formulário
        $formPraca= new SubForm;

        $formPraca->addElement('text', 'nomePraca', array(
                    'label' => 'Nome da Praça',
                    'required' => true,
                    'style'=>'min-width:180px; background-color:#fff'
                ));

        $formPraca->addElement('hidden', 'num_pracas', array(
                    'label' => '',
                    'value' => $qtdFaixas,
                    'required' => false,
                    'style'=>'min-width:150px',
                ));

        for ($i = 1; $i <= $qtdFaixas; $i++ ) {
            $formPracaBotao = new SubForm;
            $formPraca->addElement('text', 'dummy1', array(
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
                )))
                ->addElement('text', 'faixa1_' . $i, array(
                    'label' => 'Faixa de CEP',
                    'required' => false,
                    'style'=>'min-width:150px',
                ))
                ->addElement('text', 'faixa2_' . $i, array(
                    'label' => 'Até',
                    'required' => false,
                    'style'=>'min-width:150px',
                ));
            $formPracaBotao->addElement('button', 'botaoAdd', array(
                'label'=>'Adicionar',
                'required' => false,
                'class'=>'btn header'
            ));
            $element = $formPracaBotao->getElement('botaoAdd');
            $element->setDecorators(array('ViewHelper','ViewHelper',
                array(array('data'=>'HtmlTag'), array('tag'=>'dd', 'class'=>'element')),
                array(array('row'=> 'HtmlTag'), array('tag'=>'dd','style'=>'height:15px'))
            ));
            $formPraca
                ->addElement($element)
                ->addElement('text', 'dummy6', array(
                'required' => false,
                'ignore' => true,
                'autoInsertNotEmptyValidator' => false,
                'decorators' => array(
                    array(
                        'HtmlTag', array(
                        'tag'  => 'div',
                        'id'   => 'pracas',
                        'style' => 'width:100%; clear:both;'
                    )
                    )
                )
            ))            ->addDisplayGroup($formPraca->getElements(), 'veiculo', array('legend' => 'Cadastro de Praças'));
            ;

        }

        $this->addSubFormTab('Identificação', $formPraca, 'identificacao', null);
    }

    public function setDefaultsFromIdPraca ($idPraca){

        /** @var \Wms\Domain\Entity\MapaSeparacao\Praca $praca */
        $praca = $this->getEm()->find("wms:MapaSeparacao\Praca", $idPraca);
        $faixas = $this->getEm()->getRepository("wms:MapaSeparacao\PracaFaixa")->findBy(['codPraca' => $idPraca]);

        $result = [];
        $result['nomePraca']  = $praca->getNomePraca();
        /**
         * @var int $key
         * @var PracaFaixa $faixa
         */
        foreach ($faixas as $key => $faixa) {
            $result["faixa1_" . ($key + 1)] = $faixa->getFaixaCep1();
            $result["faixa2_" . ($key + 1)] = $faixa->getFaixaCep2();
        }

        $result['num_pracas'] = count($faixas);
        $values = [ 'identificacao' => $result ];
        $this->setDefaults($values);
    }

}
