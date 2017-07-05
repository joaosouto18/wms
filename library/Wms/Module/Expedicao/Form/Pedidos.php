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
                  'disable' => true,
                  'decorators' => array('ViewHelper'),
              ))
              ->addElement('submit', 'submit', array(
                  'label' => 'Buscar Dados por DATA',
                  'class' => 'btn',
                  'decorators' => array('ViewHelper'),
              ))
              ->addElement('text','codigo',array(
                  'size' => 25,
                  'label' => 'Códigos',
                  'decorators' => array('ViewHelper'),
              ))
              ->addElement('submit','submitCodigos',array(
                  'label' => 'Buscar dados por CÓDIGOS',
                  'class' => 'btn',
                  'decorators' => array('ViewHelper')
              ))
            ->addDisplayGroup(array('dataInicio','submit','codigo','submitCodigos'), 'identificacao', array('legend' => 'Listar Pedidos ERP')
        );
    }

}