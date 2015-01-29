<?php

namespace Wms\Module\Web\Form\Sistema;

/**
 * Description of Acao
 *
 * @author Renato Medina <medinadato@gmail.com>
 */
class Acao extends \Wms\Module\Web\Form {

    public function init()
    {
        // form's attr
        $this->setAttribs(array('id' => 'sistema-acao-form', 'class' => 'saveForm'));

        $formIdentificacao = new \Core\Form\SubForm();
        $formIdentificacao->addElement('text', 'nome', array(
                    'label' => 'Nome da ação',
                    'class' => 'caixa-alta medio focus',
                    'maxlength' => 20,
                    'required' => true
                ))
                ->addElement('text', 'descricao', array(
                    'label' => 'Chave',
                    'class' => 'medio',
                    'maxlength' => 20,
                    'required' => true,
                    'description' => 'Deve ser única. Para uso interno do sistema'
                ))
                ->addDisplayGroup(array('nome', 'descricao'), 'identificacao');

        $this->addSubFormTab('Identificação', $formIdentificacao, 'identificacao');
    }

    /**
     * 
     * @param \Wms\Domain\Entity\Sistema\Acao $acao 
     */
    public function setDefaultsFromEntity(\Wms\Domain\Entity\Sistema\Acao $acao)
    {
        $values = array(
            'identificacao' => array(
                'nome' => $acao->getNome(),
                'descricao' => $acao->getDescricao(),
            )
        );

        $this->setDefaults($values);
    }

}