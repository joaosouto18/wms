<?php

namespace Wms\Module\Web\Form\Subform;

use Wms\Domain\Entity\Usuario;

/**
 * Description of Usuario
 *
 * @author medina
 */
class Acesso extends \Core\Form\SubForm {

    public function init() {
        $repoPerfil = $this->getEm()->getRepository('wms:Acesso\Perfil');

        $this->addElement('text', 'login', array(
            'label' => 'Login',
            'class' => 'medio',
            'maxlength' => 20,
            'required' => true
        ));
        $this->addElement('multiCheckbox', 'perfis', array(
            'label' => 'Perfil(s) de Acesso',
            'multiOptions' => $repoPerfil->getIdValue(),
            'required' => true,
        ));
        $this->addElement('radio', 'isAtivo', array(
            'label' => 'Usuário Ativo?',
            'multiOptions' => array('S' => 'Sim', 'N' => 'Não'),
            'required' => true,
            'value' => 'S',
            'separator' => ''
        ));

        $this->addElement('text', 'percentReceb', array(
            'label' => 'Percentual para liberação de recebimentos',
            'maxlength' => 3,
            'alt' => 'number',
            'size' => 5
        ));

        $this->addElement('text', 'codErp', array(
            'label' => 'Código no ERP',
            'alt' => 'number',
            'size' => 6
        ));
        
        $this->addDisplayGroup(array(
            'login',
            'percentReceb',
            'codErp',
            'perfis',
            'isAtivo',
                ), 'identificacao', array('legend' => 'Identificação'
        ));

        $repoDeposito = $this->getEm()->getRepository('wms:Deposito');

        $this->addElement('multiCheckbox', 'depositos', array(
            'required' => true,
            'label' => 'Depósitos',
            'multiOptions' => $repoDeposito->getIdValue()
        ));

        $this->addDisplayGroup(
                array('depositos'), 'deposito', array('legend' => 'Depósitos que este usuário terá acesso')
        );
    }

    public function setDefaultsFromEntity(Usuario $usuario) {
        $values = array(
            'id' => $usuario->getId(),
            'login' => $usuario->getLogin(),
            'percentReceb' => $usuario->getPercentReceb(),
            'codErp' => $usuario->getCodErp(),
            'depositos' => $usuario->getIdsDepositos(),
            'perfis' => $usuario->getIdsPerfis(),
            'isAtivo' => $usuario->getIsAtivo(),
        );

        $this->setDefaults($values);
    }

}