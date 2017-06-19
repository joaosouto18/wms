<?php
namespace Wms\Module\Expedicao\Form;

use Wms\Module\Web\Form;

class Pedidos extends Form
{

    public function start($dataUltimaExecucao)
    {
          $this
              ->setAttribs(array(
                  'method' => 'get',
              ))
              ->addElement('text', 'dataInicio', array(
                  'size' => 25,
                  'label' => 'Data Inicial',
                  'value' => $dataUltimaExecucao,
                  'disable' => true
              ))
              ->addElement('submit', 'submit', array(
                  'label' => 'Importar Dados para WMS',
                  'class' => 'btn',
                  'decorators' => array('ViewHelper'),
              ))
            ->addDisplayGroup(array('dataInicio','submit'), 'identificacao', array('legend' => 'Listar Pedidos ERP')
        );
    }

}