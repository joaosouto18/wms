<?php

namespace Wms\Module\Web\Form\Subform\Pessoa\Papel;

/**
 * Description of SystemContextParam
 *
 * @author Renato Medina <medinadato@gmail.com>
 */
class FiltroUsuario extends \Wms\Module\Web\Form
{

    public function init()
    {
        $em = $this->getEm();
        $repoPerfil = $em->getRepository('wms:Acesso\Perfil');
        $repoDeposito = $em->getRepository('wms:Deposito');

        //$formIdentificacao = new \Core\Form\SubForm();

        $this->setAttrib('class', 'filtro');

        $this->addElement('select', 'idPerfil', array(
                    'label' => 'Perfil Usuário',
                    'mostrarSelecione' => false,
                    'multiOptions' => array('firstOpt' => 'Todos', 'options' => $repoPerfil->getIdValue()),
                    'class' => 'focus',
                ))
                ->addElement('select', 'idDeposito', array(
                    'label' => 'Depósito',
                    'multiOptions' => array('firstOpt' => 'Todos', 'options' => $repoDeposito->getIdValue())
                ))
                ->addElement('select', 'isAtivo', array(
                    'label' => 'Ativo',
                    'multiOptions' => array('S' => 'SIM', 'N' => 'NÃO'),
                    'value' => 'S',
                ))
                ->addElement('submit', 'submit', array(
                    'label' => 'Buscar',
                    'class' => 'btn',
                    'decorators' => array('ViewHelper'),
                ))
                ->addDisplayGroup(array('idPerfil', 'idDeposito', 'isAtivo', 'submit'), 'identificacao', array('legend' => 'Filtros de Busca'));

        //$this->addSubFormTab('Busca', $formIdentificacao, 'identificacao');
    }

}