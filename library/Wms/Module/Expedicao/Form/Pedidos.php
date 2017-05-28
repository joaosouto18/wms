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
                  'size' => 15,
                  'label' => 'Data Inicial',
                  'value' => $dataUltimaExecucao
              ))
              ->addElement('submit', 'submit', array(
                  'label' => 'Buscar',
                  'class' => 'btn',
                  'decorators' => array('ViewHelper'),
              ))
            ->addDisplayGroup(array('dataInicio', 'submit'), 'identificacao', array('legend' => 'Listar Pedidos ERP')
        );
    }

}