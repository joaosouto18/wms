<?php

namespace Wms\Module\Armazenagem\Form\OcupacaocdPeriodo;

use Wms\Module\Web\Form;

class Filtro extends Form {

    public function init($showPeriodo = true) {

        $em = $this->getEm();
        $sessao = new \Zend_Session_Namespace('deposito');

        $arrayElements = $arrayElements = array('ruaInicial', 'ruaFinal', 'idAreaArmazenagem', 'idEstruturaArmazenagem', 'idTipoEndereco', 'submit');
        $repoEstrutura = $em->getRepository('wms:Armazenagem\Estrutura\Tipo');
        $repoTipo = $em->getRepository('wms:Deposito\Endereco\Tipo');
        $repoArea = $em->getRepository('wms:Deposito\AreaArmazenagem');
        $area = $repoArea->getIdValue(array('idDeposito' => $sessao->idDepositoLogado));

        if ($showPeriodo == true) {
            $this->addElement('date', 'dataInicial1', array(
                        'size' => 20,
                        'label' => 'Data Inicial',
                    ))
                    ->addElement('date', 'dataInicial2', array(
                        'size' => 20,
                        'label' => 'Data Final',
                    ))
                    ->addElement('date', 'dataInicial1', array(
                        'size' => 20,
                        'label' => 'Data Inicial',
                    ))
                    ->addElement('select', 'idAreaArmazenagem', array(
                        'mostrarSelecione' => false,
                        'multiOptions' => array('firstOpt' => 'Todos', 'options' => $area),
                        'label' => 'Área de Armazenagem',
                    ))
                    ->addElement('select', 'idEstruturaArmazenagem', array(
                        'mostrarSelecione' => false,
                        'multiOptions' => array('firstOpt' => 'Todos', 'options' => $repoEstrutura->getIdValue()),
                        'label' => 'Estrutura de Armazenagem',
                    ))
                    ->addElement('select', 'idTipoEndereco', array(
                        'mostrarSelecione' => false,
                        'multiOptions' => array('firstOpt' => 'Todos', 'options' => $repoTipo->getIdValue()),
                        'label' => 'Tipo do Endereço',
                    ))
                    ->addElement('submit', 'submit', array(
                        'label' => 'Buscar',
                        'class' => 'btn',
                        'decorators' => array('ViewHelper'),
            ));

            $arrayElements = array('dataInicial1', 'dataInicial2', 'dataFinal1', 'dataFinal2', 'ruaInicial', 'ruaFinal', 'idAreaArmazenagem', 'idEstruturaArmazenagem', 'idTipoEndereco', 'submit');
        }
        $this->addElement('text', 'ruaInicial', array(
                    'size' => 20,
                    'label' => 'Rua Inicial',
                ))
                ->addElement('text', 'ruaFinal', array(
                    'size' => 20,
                    'label' => 'Rua Final',
                ))
                ->addElement('select', 'idAreaArmazenagem', array(
                    'mostrarSelecione' => false,
                    'multiOptions' => array('firstOpt' => 'Todos', 'options' => $area),
                    'label' => 'Área de Armazenagem',
                ))
                ->addElement('select', 'idEstruturaArmazenagem', array(
                    'mostrarSelecione' => false,
                    'multiOptions' => array('firstOpt' => 'Todos', 'options' => $repoEstrutura->getIdValue()),
                    'label' => 'Estrutura de Armazenagem',
                ))
                ->addElement('select', 'idTipoEndereco', array(
                    'mostrarSelecione' => false,
                    'multiOptions' => array('firstOpt' => 'Todos', 'options' => $repoTipo->getIdValue()),
                    'label' => 'Tipo do Endereço',
        ));
        
        $this->addDisplayGroup($arrayElements, 'identificacao', array('legend' => 'Busca'));
    }

    /**
     *
     * @param array $params
     * @return boolean 
     */
    public function isValid($params) {
        extract($params);

        if (!parent::isValid($params))
            return false;

        //if ($this->checkAllEmpty())
        //    return false;

        return true;
    }

}
