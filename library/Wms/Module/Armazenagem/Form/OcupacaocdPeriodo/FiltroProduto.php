<?php
namespace Wms\Module\Armazenagem\Form\OcupacaocdPeriodo;

use Wms\Module\Web\Form;

class FiltroProduto extends Form
{

    public function init()
    {

        $this->addElement('text', 'ruaInicial', array(
            'size' => 20,
            'label' => 'Rua Inicial',
        ))
        ->addElement('text', 'ruaFinal', array(
            'size' => 20,
            'label' => 'Rua Final',
        ))
        ->addElement('select', 'tipoRelatorio', array(
            'mostrarSelecione' => false,
            'label' => 'Tipo Relatorio',
            'multiOptions' => array(
                    'C' => 'Classe',
                    'P' => 'Produto'
            ),
        ))
        ->addElement('submit', 'buscar', array(
            'label' => 'Buscar',
            'class' => 'btn',
            'decorators' => array('ViewHelper'),
        ));
        $this->addDisplayGroup(array('ruaInicial', 'ruaFinal', 'tipoRelatorio', 'buscar'), 'identificacao', array('legend' => 'Busca'));
    }

}