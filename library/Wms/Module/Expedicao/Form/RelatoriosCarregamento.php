<?php
namespace Wms\Module\Expedicao\Form;

use Wms\Module\Web\Form;
use Wms\Util\WMS_Exception;

class RelatoriosCarregamento extends Form
{

    public function start($linhasSeparacao)
    {
          $this
              ->setAttribs(array(
                  'method' => 'get',
              ));

          if (isset($linhasSeparacao) && !empty($linhasSeparacao)) {
              $this->addElement('select', 'idLinhaSeparacao', array(
                  'label' => 'Linha de Separação',
                  'style' => 'height:auto; width:100%',
                  'multiOptions' => $linhasSeparacao,
                  'value' => key($linhasSeparacao),
                  'attribs' => array(
                      'id' => 'linhaSeparacao'
                  ),
              ))
                  ->addElement('button', 'relatorioCliente', array(
                      'label' => 'Imprimir Relatório Clientes',
                      'attribs' => array(
                          'id' => 'btn-relatorio-cliente'
                      ),
                      'decorators' => array('ViewHelper')
                  ))
                  ->addElement('button', 'relatorioProduto', array(
                      'label' => 'Imprimir Relatório Produtos',
                      'attribs' => array(
                          'id' => 'btn-relatorio-produto'
                      ),
                      'decorators' => array('ViewHelper'),
                  ))
                  ->addDisplayGroup(array('idLinhaSeparacao','relatorioCliente', 'relatorioProduto'), 'identificacao', array('legend' => 'Relatórios'));
          }
    }

}