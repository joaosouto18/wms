<?php
namespace Wms\Module\Expedicao\Form;

use Wms\Module\Web\Form;

class SaidaProduto extends Form
{

    public function init()
    {
        $this->setAction($this->getView()->url(array('module' =>'expedicao', 'controller' => 'relatorio_saida', 'action' => 'index')))
                ->setAttribs(array(
                    'method' => 'get',
                    'class' => 'filtro',
                    'id' => 'filtro-saida-produtos',
                ))
                ->addElement('text', 'idProduto', array(
                    'size' => 12,
                    'label' => 'Cod. produto',
                    'class' => 'focus',
                ))
                ->addElement('text', 'grade', array(
                    'size' => 12,
                    'label' => 'Grade',
                ))
                ->addElement('submit', 'submit', array(
                    'label' => 'Buscar',
                    'class' => 'btn',
                    'decorators' => array('ViewHelper'),
                ))
                ->addDisplayGroup(array('idProduto', 'grade', 'submit'), 'identificacao', array('legend' => 'Busca')
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