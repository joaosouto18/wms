<?php

namespace Wms\Module\Web\Form\Sistema;

use Wms\Module\Web\Form;

/**
 * Form do menu
 * @author Augusto Vespermann
 */
class MenuItem extends Form
{

    public function init()
    {
        $itensMenu = $this->getEm()->getRepository('wms:Sistema\MenuItem');
        $recursoAcao = $this->getEm()->getRepository('wms:Sistema\Recurso');

        // form's attr
        $this->setAttribs(array('id' => 'sistema-menu-form', 'class' => 'saveForm'));
        $this->addElement('text', 'dscMenuItem', array(
                    'required' => true,
                    'class' => 'upper',
                    'label' => 'Menu',
                    'size' => '70px',
                ))
                ->addElement('numeric', 'peso', array(
                    'size' => '10px',
                    'maxlength' => 2,
                    'required' => true,
                    'label' => 'Peso'
                ))
                ->addElement('text', 'url', array(
                    'required' => true,
                    'class' => 'lower',
                    'label' => 'URL',
                    'size' => '60px',
                ))
                ->addElement('text', 'target', array(
                    'required' => true,
                    'label' => 'Target',
                    'size' => '10px',
                ))
                ->addElement('select', 'idPai', array(
                    'label' => 'Menu Pai',
                    'multiOptions' => $itensMenu->getIdValue(),
                    'class' => 'focus',
                ))
                ->addElement('select', 'idRecurso', array(
                    'label' => 'Recurso',
                    'multiOptions' => $recursoAcao->getIdValue(),
                ))
                ->addElement('hidden', 'idRecursoAcaoTemp')
                ->addElement('select', 'idRecursoAcao', array(
                    'label' => 'Ações do Recurso (Permissões)',
                    'multiOptions' => array(),
                    'registerInArrayValidator' => false,
                ))
                ->addDisplayGroup(
                        array('idPai', 'dscMenuItem', 'peso', 'url', 'target'), 'menu', array('legend' => 'Dados do Menu')
                )
                ->addDisplayGroup(
                        array('idRecurso', 'idRecursoAcao', 'idRecursoAcaoTemp'), 'permissao', array('legend' => 'Permissão à se vincular')
        );
    }

    /**
     * Sets the values from entity
     * @param \Wms\Entity\Sistema\MenuItem $menu
     */
    public function setDefaultsFromEntity(\Wms\Domain\Entity\Sistema\MenuItem $menu)
    {
        $em = $this->getEm();

        $values = array(
            'id' => $menu->getId(),
            'dscMenuItem' => $menu->getDscMenuItem(),
            'peso' => $menu->getPeso(),
            'target' => $menu->getTarget(),
            'url' => $menu->getUrl(),
            'idPai' => $menu->getPai()->getId(),
            'idRecurso' => $menu->getPermissao()->getRecurso()->getId(),
            'idRecursoAcaoTemp' => $menu->getPermissao()->getId(),
        );

        $this->setDefaults($values);
    }

}

