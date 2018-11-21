<?php
namespace Wms\Module\Armazenagem\Form\EstoqueConsolidado;

use Wms\Module\Web\Form;

class Filtro extends Form
{

    public function init()
    {
        $repoLinhaSeparacao = $this->getEm()->getRepository('wms:Armazenagem\LinhaSeparacao');

        $this
                ->setAttribs(array(
                    'method' => 'get',
                    'class' => 'filtro',
                    'id' => 'filtro-inventario-por-rua',
                ))
               ->addElement('multiselect', 'grandeza', array(
                    'label' => 'Linha Separação',
                    'style' => 'height:auto; width:100%',
                     'multiOptions' => $repoLinhaSeparacao->getIdValue()
                ))
                ->addElement('submit', 'submit', array(
                    'label' => 'Imprimir CSV',
                    'class' => 'btn',
                    'decorators' => array('ViewHelper'),
                ))
            ->addElement('submit', 'exportPdf', array(
                'label' => 'Imprimir PDF',
                'class' => 'btn',
                'decorators' => array('ViewHelper'),
            ))
                ->addDisplayGroup(array('grandeza', 'submit', 'exportPdf'), 'identificacao', array('legend' => 'Busca'));
    }

}