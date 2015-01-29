<?php
namespace Wms\Module\Armazenagem\Form\OcupacaocdPeriodo;

use Wms\Module\Web\Form;

class Filtro extends Form
{

    public function init($showPeriodo = true)
    {
        $arrayElements = $arrayElements = array( 'ruaInicial' , 'ruaFinal' , 'submit');;
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
            ->addElement('submit', 'submit', array(
                'label' => 'Buscar',
                'class' => 'btn',
                'decorators' => array('ViewHelper'),
            ));

            $arrayElements = array('dataInicial1', 'dataInicial2', 'dataFinal1', 'dataFinal2' , 'ruaInicial' , 'ruaFinal' , 'submit');
        }
        $this->addElement('text', 'ruaInicial', array(
            'size' => 20,
            'label' => 'Rua Inicial',
        ))
        ->addElement('text', 'ruaFinal', array(
            'size' => 20,
            'label' => 'Rua Final',
        ));

        $this->addDisplayGroup($arrayElements, 'identificacao', array('legend' => 'Busca'));
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

        //if ($this->checkAllEmpty())
        //    return false;

        return true;
    }

}