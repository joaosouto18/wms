<?php
namespace Wms\Module\Expedicao\Form;

use Wms\Module\Web\Form;

class Pedidos extends Form
{

    public function start()
    {
          $this
              ->setAttribs(array(
                  'method' => 'get',
              ))
              ->addElement('submit', 'submit', array(
                  'label' => 'Importar Dados para WMS',
                  'class' => 'btn',
                  'decorators' => array('ViewHelper'),
              ))
            ->addDisplayGroup(array('submit'), 'identificacao', array('legend' => 'Listar Pedidos ERP')
        );
    }

}