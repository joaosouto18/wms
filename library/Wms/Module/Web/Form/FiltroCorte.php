<?php

namespace Wms\Module\Web\Form;

use Wms\Module\Web\Form;

/**
 * Descrição: Classe destinada para o form com os filtros
 * de busca do Corte por dia / produto
 *
 * @author Diogo Marcos <contato@diogomarcos.com>
 */
class FiltroCorte extends Form
{
    public function init($utilizaGrade = 'S')
    {

        $em = $this->getEm();
        /** @var SiglaRepository $repoSigla */
        $repoSigla = $em->getRepository('wms:Util\Sigla');


        $this->addElement('text', 'idExpedicao', array(
            'size' => 10,
            'class' => 'focus',
            'decorators' => array('ViewHelper'),
        ));
        if ($utilizaGrade == "S") {
            $this->addElement('text', 'grade', array(
                'size' => 12,
                'label' => 'Grade',
            ));
        } else {
            $this->addElement('hidden', 'grade', array(
                'label' => 'Grade',
                'value' => 'UNICA'
            ));
        }
        $this
            ->addElement('text', 'descricao', array(
                'size' => 45,
                'maxlength' => 40,
                'decorators' => array('ViewHelper'),
            ))
            ->addElement('date', 'dataInicial1', array(
                'size' => 10,
                'decorators' => array('ViewHelper'),
            ))
            ->addElement('date', 'dataInicial2', array(
                'size' => 10,
                'decorators' => array('ViewHelper'),
            ))
            ->addElement('date', 'dataFinal1', array(
                'size' => 10,
                'decorators' => array('ViewHelper'),
            ))
            ->addElement('date', 'dataFinal2', array(
                'size' => 10,
                'decorators' => array('ViewHelper'),
            ))
            ->addElement('submit', 'submit', array(
                'label' => 'Buscar',
                'class' => 'btn',
                'decorators' => array('ViewHelper'),
            ))
            ->addElement('select', 'status', array(
                'label' => 'Status da Expedição',
                'multiOptions' => array('firstOpt' => 'Todos', 'options' => $repoSigla->getIdValue(53)),
                'decorators' => array('ViewHelper'),
            ))
            ->addDisplayGroup($this->getElements(), 'identificacao', array('legend' => 'Filtros de Busca'));

        $this->setDecorators(array(array('ViewScript', array('viewScript' => 'forms/filtro-corte.phtml'))));
    }
}