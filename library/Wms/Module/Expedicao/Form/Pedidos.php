<?php
namespace Wms\Module\Expedicao\Form;

use Wms\Module\Web\Form;

class Pedidos extends Form
{

    public function init()
    {
          $this
              ->setAttribs(array(
                  'method' => 'get',
              ))
              ->addElement('text', 'dataInicio', array(
                  'size' => 15,
                  'label' => 'Data Inicial',
              ))


//              ->addElement('text', '0', array(
//                  'size' => 10,
//                  'label' => 'Carga Inicial',
//              ))
//              ->addElement('text', '1', array(
//                  'size' => 10,
//                  'label' => 'Carga Final',
//              ))
              ->addElement('submit', 'submit', array(
                  'label' => 'Buscar',
                  'class' => 'btn',
                  'decorators' => array('ViewHelper'),
              ))
            ->addDisplayGroup(array('dataInicio', 'submit'), 'identificacao', array('legend' => 'Listar Pedidos ERP')
//            ->addDisplayGroup(array('0','1', 'submit'), 'identificacao', array('legend' => 'Listar Pedidos ERP')
        );
    }

}