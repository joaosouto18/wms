<?php
namespace Wms\Module\Expedicao\Form;

use Wms\Module\Web\Form;

class RelatoriosCarregamento extends Form
{

    public function init()
    {
        $em = $this->getEm();
        $linhasSeparacao = $em->getRepository('wms:Armazenagem\LinhaSeparacao')->getIdValue();
          $this
              ->setAttribs(array(
                  'method' => 'get',
              ))
              ->addElement('multiselect', 'idLinhaSeparacao', array(
                  'label' => 'Linha de Separação',
                  'style' => 'height:auto; width:100%',
                  'multiOptions' => $linhasSeparacao,
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
            ->addDisplayGroup(array('idLinhaSeparacao','relatorioCliente', 'relatorioProduto'), 'identificacao', array('legend' => 'Relatórios')
        );
    }

}