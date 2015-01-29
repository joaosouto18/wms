<?php
namespace Wms\Module\Expedicao\Form;

use Wms\Module\Web\Form;

class DadosMovimentacao extends Form
{

    public function init()
    {
        $this->setAction($this->getView()->url(array('module' =>'expedicao', 'controller' => 'relatorio_dados-movimentacao', 'action' => 'index')))
                ->setAttribs(array(
                    'method' => 'get',
                    'class' => 'filtro',
                    'id' => 'filtro-dados-movimentacao',
                ))
            ->addElement('date', 'dataInicial', array(
                'size' => 20,
                'label' => 'Data Inicio'
            ))
            ->addElement('date', 'dataFim', array(
                'size' => 10,
                'label' => 'Data Fim'
            ))
            ->addElement('submit', 'submit', array(
                'label' => 'Exportar Dados csv',
                'class' => 'btn',
                'decorators' => array('ViewHelper'),
            ))
            ->addDisplayGroup(array('dataInicial', 'dataFim', 'submit'), 'identificacao', array('legend' => 'Busca')
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