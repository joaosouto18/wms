<?php

namespace Wms\Module\Web\Form\Acesso;

use Wms\Module\Web\Form,
    Core\Form\SubForm;

/**
 * Description of Perfil
 *
 * @author Renato Medina <medinadato@gmail.com>
 */
class Perfil extends Form {

    public function init()
    {
        //form's attr
        $this->setAttribs(array('id' => 'acesso-perfil-form', 'class' => 'saveForm'));
        
        $formIdenficacao = new SubForm;
        $formIdenficacao->addElement('hidden', 'id')
                ->addElement('text', 'nome', array(
                    'label' => 'Perfil',
                    'class' => 'caixa-alta medio',
                    'size' => 30,
                    'maxlength' => 20,
                    'required' => true,
                    'description' => 'Para uso interno do sistema'
                ))
                ->addElement('text', 'descricao', array(
                    'label' => 'Descrição',
                    'class' => 'grande',
                    'size' => 60,
                    'maxlength' => 60,
                    'required' => true
                ))
                ->addDisplayGroup(
                        array(
                    'nome', 'descricao'
                        ), 'identificacao', array('legend' => 'Identificação')
        );

        $this->addSubFormTab('Identificação', $formIdenficacao, 'identificacao');

        $formRecursos = new SubForm;
        $formRecursos->addElement('hidden', 'id')
                ->addDisplayGroup(array('id'), 'recursos', array('legend' => 'Identificação'));

        $this->addSubFormTab('Recursos', $formRecursos, 'recursos', 'forms/recurso-perfil.phtml');
    }

    public function setDefaultsFromEntity(\Wms\Domain\Entity\Acesso\Perfil $perfil)
    {
        $values = array(
            'id' => $perfil->getId(),
            'nome' => $perfil->getNome(),
            'descricao' => $perfil->getDescricao(),
        );

        $this->setDefaults($values);
    }

}

