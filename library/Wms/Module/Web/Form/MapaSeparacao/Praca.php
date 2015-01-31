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
class Praca extends \Wms\Module\Web\Form
{

    public function init()
    {
        //form's attr
        $this->setAttribs(array('id' => 'modelo-separacao-form', 'class' => 'saveForm'));


        //formulário
        $formPraca= new SubForm;
        $formPracaBotao = new SubForm;


        $formPraca->addElement('text', 'nomePraca', array(
                    'label' => 'Nome da Praça',
                    'required' => true,
                    'style'=>'min-width:180px; background-color:#fff'
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
                ->addElement('text', 'faixa1_1', array(
                    'label' => 'Faixa de CEP',

                    'required' => false,
                    'style'=>'min-width:150px',
                ))
                ->addElement('text', 'faixa2_1', array(
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
        $element->setDecorators(array('ViewHelper',

            'ViewHelper',
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
            ))
            ->addElement('hidden', 'num_pracas', array(
                'label' => '',
                'value'=>'1',
                'required' => false,
                'style'=>'min-width:150px',
            ))
            ->addDisplayGroup($formPraca->getElements(), 'veiculo', array('legend' => 'Cadastro de Praças'));

        $this->addSubFormTab('Identificação', $formPraca, 'identificacao', null);
    }


}
