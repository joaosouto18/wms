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
        $values = array(
            'identificacao' => array(
                'nomePraca' => 'TESTE',
                'faixa1_1' => '26100-001',
                'faixa2_1' => '26199-999',
                'faixa1_2' => '105',
                'faixa2_2' => '100',
                'num_pracas' => '2'
            )
        );
        $this->setDefaults($values);
    }

}
