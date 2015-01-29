<?php

namespace Wms\Module\Web\Form;

use Wms\Module\Web\Form,
    Core\Form\SubForm;

/**
 * Description of Usuario
 *
 * @author Renato Medina <medinadato@gmail.com>
 */
class Recebimento extends Form
{

    public function init()
    {
        //form's attr
        $this->setAttribs(array('id' => 'recebimento-form', 'class' => 'saveForm'));

        $em = $this->getEm();
        $sessao = new \Zend_Session_Namespace('deposito');
        $repoBox = $em->getRepository('wms:Deposito\Box');

        $this->addElement('hidden', 'idDeposito', array(
                    'value' => $sessao->idDepositoLogado
                ))
                ->addElement('select', 'idBox', array(
                    'label' => 'Box',
                    'multiOptions' => $repoBox->getIdValue(),
                    'required' => true,
                    'style' => 'width:200px',
                    'decorators' => array('ViewHelper'),
                ))
                ->addElement('textarea','observacao', 
                        array(
                            'label'=>'Observação:','rows' => 4,
                            'decorators' => array('ViewHelper'),
                            'style' => 'width:300px',
                            'class' => 'caixa-alta',
                            'maxlength'=> 250
                    )
                )
                ->addElement('submit', 'submit', array(
                    'label' => 'Salvar e Prosseguir',
                    'class' => 'btn',
                    'decorators' => array('ViewHelper'),
                ))
                ->addDisplayGroup(
                        array('idDeposito', 'idBox', 'observacao','submit'), 'identificacao', array('legend' => 'Selecionar Box')
        );
    }

    /**
     * Sets the values from entity
     * @param \Wms\Domain\Entity\Recebimento
     */
    public function setDefaultsFromEntity(\Wms\Domain\Entity\Recebimento $recebimento)
    {
        $values = array(
            'id' => $recebimento->getId(),
            'dataInicial' => $recebimento->getDataInicial(),
            'status' => $recebimento->getStatus(),
            'idBox' => $recebimento->getIdBox()
        );

        $this->setDefaults($values);
    }

}