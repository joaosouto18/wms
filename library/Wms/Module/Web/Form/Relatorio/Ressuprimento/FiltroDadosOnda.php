<?php

namespace Wms\Module\Web\Form\Relatorio\Ressuprimento;

use Wms\Module\Web\Form;

/**
 * Description of FiltroDadosOnda
 *
 * @author Michel Castro <mlaguardia@gmail.com>
 */
class FiltroDadosOnda extends Form
{

    public function init()
    {

        $em = $this->getEm();
        $repoSigla = $em->getRepository('wms:Util\Sigla');
        $perfilParam = $em->getRepository('wms:Sistema\Parametro')->findOneBy(array('constante' => 'COD_PERFIL_OPERADOR_EMPILHADEIRA'));
        $usuarioRepo = $em->getRepository('wms:Usuario');

        $this
            ->addElement('text', 'codOs', array(
                'size' => 7,
                'label' => 'Ordem de Serviço',
                'class' => 'focus',
            ))
            ->addElement('text', 'codOnda', array(
                'size' => 7,
                'label' => 'Onda'
            ))
            ->addElement('text', 'idProduto', array(
                'size' => 12,
                'label' => 'Cod. produto',
            ))
            ->addElement('text', 'grade', array(
                'size' => 12,
                'label' => 'Grade',
            ))
            ->addElement('numeric', 'expedicao', array(
                'label' => 'Expedição',
                'size' => 10,
                'maxlength' => 10,
            ))
            ->addElement('date', 'dataInicial', array(
                'size' => 20,
                'label' => 'Data Início'
            ))
            ->addElement('date', 'dataFinal', array(
                'label' => 'Data Fim',
                'size' => 10
            ))
            ->addElement('select', 'operador', array(
                'label' => 'Operador de Empilhadeira',
                'multiOptions' => array('firstOpt' => 'Todos', 'options' => $usuarioRepo->getIdValueByPerfil($perfilParam->getValor())),
            ))
            ->addElement('select', 'status', array(
                'label' => 'Status',
                'multiOptions' => array('firstOpt' => 'Todos', 'options' => $repoSigla->getIdValue(74))
            ))
            ->addElement('submit', 'submit', array(
                'label' => 'Buscar',
                'class' => 'btn',
                'decorators' => array('ViewHelper'),
            ))
            ->addElement('hidden', 'actionParams', array(
                'values'=>false
            ))
            ->addDisplayGroup(array('codOs', 'codOnda', 'expedicao', 'idProduto', 'grade', 'operador', 'dataInicial','dataFinal', 'status', 'submit','actionParams'),'filtro', array('legend' => 'Busca')
            );
    }

}