<?php
namespace Wms\Module\Armazenagem\Form\MovimentacaoProduto;

use Wms\Module\Web\Form;

class Filtro extends Form
{

    public function init($utilizaGrade = 'S')
    {
                $this->setAttribs(array(
                   'method' => 'post',
                   'class' => 'filtro',
                   'id' => 'relatorio-movimentacao_produto',
                ));
                $this->addElement('text', 'idProduto', array(
                   'size' => 12,
                   'label' => 'Cod. produto',
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
                $this->addElement('date', 'dataInicial', array(
                    'size' => 20,
                    'label' => 'Data Inicio'
                ))
                ->addElement('date', 'dataFim', array(
                    'size' => 10,
                    'label' => 'Data Fim'
                ))
                ->addElement('text', 'rua', array(
                    'size' => 3,
                    'label' => 'Rua',
                    'class' => 'focus',
                ))
                ->addElement('text', 'predio', array(
                    'size' => 3,
                    'label' => 'Prédio',
                ))
                ->addElement('text', 'nivel', array(
                    'size' => 3,
                    'label' => 'Nível',
                ))
                ->addElement('text', 'apto', array(
                    'size' => 3,
                    'label' => 'Apto',
                ))
                ->addElement('submit', 'submit', array(
                    'label' => 'Buscar',
                    'class' => 'btn',
                    'decorators' => array('ViewHelper'),
                ))
                ->addDisplayGroup(array('idProduto',  'grade', 'dataInicial', 'dataFim', 'rua', 'predio', 'nivel', 'apto', 'submit'), 'identificacao', array('legend' => 'Filtro')
            );
    }
/**
     *
     * @param array $params
     * @return boolean
     */
    public function isValid($params)
    {
        extract($params);

        if (!parent::isValid($params))
            return false;

        if ($this->checkAllEmpty())
           return false;

        return true;
    }

}