<?php

namespace Wms\Module\Web\Form\MapaSeparacao;

use Wms\Module\Web\Form,
    Core\Form\SubForm,
    Wms\Domain\Entity\MapaSeparacao\Rota as RotaEntity;

/**
 * Description of Rota
 *
 * @author Michel Castro <mlaguardia@gmail.com>
 */
class Rota extends \Wms\Module\Web\Form
{

    public function __construct($params){
        parent::__construct();

        $arrayRetorno=array();
        foreach ($params as $c=>$v)
            $arrayRetorno[$v['id']]=$v['nomePraca'];

        $this->formulario($arrayRetorno);
    }

    public function formulario($params)
    {
        //form's attr

        $formulario= new Form();
        $this->setAttribs(array('id' => 'modelo-separacao-form', 'class' => 'saveForm'));

        //formulário
        $formRota= new SubForm;
        $formRotaBotao = new SubForm;

        $formRota->addElement('text', 'nomeRota', array(
                    'label' => 'Nome da Rota',
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
                ->addElement('select', 'praca_1', array(
                    'label' => 'Praça',
                    'multiOptions'=>$params,
                    'required' => false,
                    'style'=>'min-width:150px',
                ))
                ;

        $formRotaBotao->addElement('button', 'botaoAddRota', array(
            'label'=>'Adicionar',
            'required' => false,
            'class'=>'btn header'
        ));

        $element = $formRotaBotao->getElement('botaoAddRota');
        $element->setDecorators(array('ViewHelper',

            'ViewHelper',
            array(array('data'=>'HtmlTag'), array('tag'=>'dd', 'class'=>'element')),
            array(array('row'=> 'HtmlTag'), array('tag'=>'dd','style'=>'height:15px'))

         ));



        $formRota
            ->addElement($element)
            ->addElement('text', 'dummy6', array(
                'required' => false,
                'ignore' => true,
                'autoInsertNotEmptyValidator' => false,
                'decorators' => array(
                    array(
                        'HtmlTag', array(
                        'tag'  => 'div',
                        'id'   => 'rotas',
                        'style' => 'width:100%; clear:both;'
                    )
                    )
                )
            ))
            ->addElement('hidden', 'num_rotas', array(
                'label' => '',
                'value'=>'1',
                'required' => false,
                'style'=>'min-width:150px',
            ))
            ->addDisplayGroup($formRota->getElements(), 'veiculo', array('legend' => 'Cadastro de Rotas'));

        $this->addSubFormTab('Identificação', $formRota, 'identificacao', null);

    }


}
