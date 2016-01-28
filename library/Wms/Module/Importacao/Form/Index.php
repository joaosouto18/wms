<?php

namespace Wms\Module\Importacao\Form;

use Wms\Module\Web\Form;

class Index extends Form
{
    public function init()
    {

        $this
            ->setAction($this->getView()->url(array('module' =>'importacao', 'controller' => 'index', 'action' => 'index')))
            ->setAttribs(array(
                'method' => 'post',
                'class' => 'filtro',
                'id' => 'frm-index',
            ))
            ->addElement('text', 'caracterQuebra', array(
                'label' => 'Caracter Quebra',
                'size' => 10,
            ))
            ->addElement('text', 'descricaoLeitura', array(
                'label' => 'Descrição de Leitura',
                'size' => 40,
            ))
            ->addElement('submit', 'submit', array(
                'label' => 'Buscar',
                'class' => 'btn',
                'decorators' => array('ViewHelper'),
            ))
            ->addDisplayGroup(array(
                'caracterQuebra',
                'descricaoLeitura',
                'submit'),
                'formulario');
    }

}