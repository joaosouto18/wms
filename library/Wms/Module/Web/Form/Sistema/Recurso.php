<?php

namespace Wms\Module\Web\Form\Sistema;

/**
 * Description of Recurso
 *
 * @author Renato Medina <medinadato@gmail.com>
 */
class Recurso extends \Wms\Module\Web\Form {

    public function init()
    {
        // form's attr
        $this->setAttribs(array('id' => 'sistema-recurso-form', 'class' => 'saveForm'));
        
        //recurso
        $repo = $this->getEm()->getRepository('wms:Sistema\Recurso');
        $formIdentificacao = new \Core\Form\SubForm();
        $formIdentificacao->addElement('text', 'nome', array(
                    'label' => 'Nome do Recurso',
                    'class' => 'caixa-baixa focus',
                    'maxlength' => 40,
                    'required' => true,
                ))
                ->addElement('text', 'descricao', array(
                    'label' => 'Descrição',
                    'class' => 'grande',
                    'maxlength' => 60,
                    'required' => true,
                ))
                ->addElement('select', 'idPai', array(
                    'label' => 'Recurso Pai',
                    'multiOptions' => $repo->getIdValue()
                ))
                ->addDisplayGroup(array('nome', 'descricao', 'idPai'), 'identificacao', array('legend' => 'Identificação'));

        $this->addSubFormTab('Identificação', $formIdentificacao, 'identificacao');

        //acao
        $formAcoes = new \Core\Form\SubForm();
        $em = $this->getEm();
        $acoes = $em->getRepository('wms:Sistema\Acao')->findAll();
        
        foreach ($acoes as $acao) {

            $nomCheckbox = $acao->getId() . 'chk';
            $formAcoes->addElement('checkbox', $nomCheckbox, array(
                'label' => $acao->getNome(),
                'checkedValue' => $acao->getId()
            ));

            $nomText = $acao->getId() . 'txt';
            $formAcoes->addElement('text', $nomText, array(
                'size' => 30,
                'decorators' => array('ViewHelper')
            ));

            $nomeElementos[] = $nomCheckbox;
            $nomeElementos[] = $nomText;
        }
        $formAcoes->addDisplayGroup($nomeElementos, 'identificacao', array('legend' => 'Ações vinculadas a este recurso'));
        $formAcoes->setElementsBelongTo('acao');
        $this->addSubFormTab('Ações', $formAcoes, 'acoes', 'forms/recurso-acao.phtml');
    }

    /**
     * Sets the values from entity
     * @param \Wms\Entity\Recurso $recurso 
     */
    public function setDefaultsFromEntity(\Wms\Domain\Entity\Sistema\Recurso $recurso)
    {

        $em = $this->getEm();
        $repo = $em->getRepository('wms:Sistema\Recurso\Vinculo');
        $acoesViculadas = $repo->findBy(array('recurso' => $recurso->getId()));

        $acoes = array();
        foreach ($acoesViculadas as $acao) {
            $acoes[$acao->getAcao()->getId() . 'chk'] = true;
            $acoes[$acao->getAcao()->getId() . 'txt'] = $acao->getNome();
        }

        $values = array(
            'identificacao' => array(
                'id' => $recurso->getId(),
                'nome' => $recurso->getNome(),
                'descricao' => $recurso->getDescricao(),
                'idPai' => $recurso->getIdPai(),
            ),
            'acao' => $acoes
        );
        
        $this->setDefaults($values);
    }

}
