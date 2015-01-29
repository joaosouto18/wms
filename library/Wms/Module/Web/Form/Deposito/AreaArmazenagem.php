<?php

namespace Wms\Module\Web\Form\Deposito;

use Wms\Module\Web\Form,
    Core\Form\SubForm;

/**
 * Description of SystemContextParam
 *
 * @author Renato Medina <medinadato@gmail.com>
 */
class AreaArmazenagem extends Form {

    public function init()
    {
        //form's attr
        $this->setAttribs(array('id' => 'deposito-areaarmazenagem-form', 'class' => 'saveForm'));

        $em = $this->getEm();

        $sessao = new \Zend_Session_Namespace('deposito');
        $idDeposito = $sessao->idDepositoLogado;

        $formIdentificacao = new SubForm;

        $formIdentificacao->addElement('hidden', 'idDeposito', array(
                    'value' => $sessao->idDepositoLogado,
                ))
                ->addElement('text', 'id', array(
                    'label' => 'Código Interno',
                    'class' => 'codigo',
                    'readonly' => true,
                    'disable' => true
                ))
                ->addElement('text', 'descricao', array(
                    'label' => 'Descrição',
                    'class' => 'caixa-alta focus',
                    'size' => 60,
                    'maxlength' => 60,
                    'required' => true,

                ))
                ->addDisplayGroup(array('idDeposito', 'id', 'descricao'), 'identificacao');

        $this->addSubFormTab('Identificação', $formIdentificacao, 'identificacao');
    }

    /**
     * Sets the values from entity
     * @param \Wms\Domain\Entity\Deposito\AreaArmazenagem
     */
    public function setDefaultsFromEntity(\Wms\Domain\Entity\Deposito\AreaArmazenagem $areaArmazenagem)
    {
        $values = array(
            'id' => $areaArmazenagem->getId(),
            'idDeposito' => $areaArmazenagem->getIdDeposito(),
            'descricao' => $areaArmazenagem->getDescricao()
        );

        $this->setDefaults($values);
    }

}
