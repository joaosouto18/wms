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
//            'title' => 'Tipo Relatório',
            'multiOptions' => array(
                'options' => array(
                    'C' => 'Classe',
                    'P' => 'Produto'

                )
            ),
        ))
        ->addElement('button', 'btnBuscar', array(
            'label' => 'Buscar',
        ));

        $this->addDisplayGroup(array('ruaInicial', 'ruaFinal', 'tipoRelatorio', 'btnBuscar'), 'identificacao', array('legend' => 'Busca'));
    }

}