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
        $this->addElement('text', 'idExpedicao', array(
            'size' => 10,
            'label' => 'Cód.Expedição',
            'class' => 'focus',
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
                'label' => 'Produto',
                'size' => 45,
                'maxlength' => 40,
            ))
            ->addElement('date', 'dataInicial', array(
                'required' => true,
                'label' => 'Data Início',
                'size' => 10,
            ))
            ->addElement('submit', 'submit', array(
                'label' => 'Buscar',
                'class' => 'btn',
                'decorators' => array('ViewHelper'),
            ))
            ->addDisplayGroup($this->getElements(), 'identificacao', array('legend' => 'Filtros de Busca'));
    }
}