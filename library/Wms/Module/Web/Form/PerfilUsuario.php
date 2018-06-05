<?php

namespace Wms\Module\Web\Form;

use Wms\Module\Web\Form,
    Core\Form\SubForm;

/**
 * Description of PerfilUsuario
 *
 * @author Renato Medina <medinadato@gmail.com>
 */
class PerfilUsuario extends Form {

    public function init()
    {
        //form's attr
        $this->setAttribs(array('id' => 'perfil-usuario-form', 'class' => 'saveForm'));
        
        $formIdenficacao = new SubForm;
        $formIdenficacao->addElement('hidden', 'id')
                ->addElement('text', 'nome', array(
                    'label' => 'Perfil',
                    'class' => 'caixa-alta',
                    'maxlength' => 20,
                    'required' => true,
                    'description' => 'Para uso interno do sistema'
                ))
                ->addElement('text', 'descricao', array(
                    'label' => 'Descrição',
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
        $formRecursos->addElement('hidden', 'id');

        $formRecursos->addDisplayGroup(
                array('id'), 'recursos', array('legend' => 'Identificação')
        );

        $this->addSubFormTab('Recursos', $formRecursos, 'recursos', 'forms/recurso-perfil.phtml');
    }

    public function setDefaultsFromEntity(\Wms\Entity\PerfilUsuario $perfil)
    {
        $values = array(
            'id' => $perfil->getId(),
            'nome' => $perfil->getNome(),
            'descricao' => $perfil->getDescricao(),
        );

        $this->setDefaults($values);
    }

}

