<?php
namespace Wms\Module\Expedicao\Form;

use Wms\Module\Web\Form;
use Wms\Util\WMS_Exception;

class RelatorioReimpressaoEtiqueta extends Form
{

    public function init()
    {
        $this
            ->setAttribs(array(
                'method' => 'get',
            ));

            $this->addElement('text', 'idEtiqueta', array(
                    'size' => 10,
                    'class' => 'focus',
                    'decorators' => array('ViewHelper')
                ))
                ->addElement('text', 'expedicao', array(
                    'size' => 10,
                    'label' => 'Expedição',
                    'decorators' => array('ViewHelper'),
                ))
                ->addElement('text', 'codCargaExterno', array(
                    'size' => 10,
                    'label' => 'Carga',
                    'decorators' => array('ViewHelper'),
                ))
                ->addElement('text', 'codPedido', array(
                    'size' => 10,
                    'label' => 'Pedido',
                    'decorators' => array('ViewHelper'),
                ))
                ->addElement('date', 'dataInicial1', array(
                    'size' => 20,
                    'label' => 'Início da Expedição entre',
                    'decorators' => array('ViewHelper'),
                ))
                ->addElement('date', 'dataInicial2', array(
                    'size' => 20,
                    'decorators' => array('ViewHelper'),
                ))
                ->addElement('text', 'codProduto', array(
                    'size' => 10,
                    'label' => 'Produto',
                    'decorators' => array('ViewHelper'),
                ))
                ->addElement('text', 'grade', array(
                    'size' => 10,
                    'label' => 'Grade',
                    'decorators' => array('ViewHelper'),
                ))
                ->addElement('submit', 'submit', array(
                    'class' => 'btn',
                    'label' => 'Buscar',
                    'decorators' => array('ViewHelper'),
                ))
                ->addElement('submit', 'pdf', array(
                    'label' => 'Gerar PDF',
                    'class' => 'btn',
                    'decorators' => array('ViewHelper'),
                ))

            ->addDisplayGroup(array('idEtiqueta', 'expedicao', 'codCargaExterno', 'codPedido', 'dataInicial1', 'dataInicial2', 'codProduto', 'grade', 'submit', 'pdf'), 'identificacao', array('legend' => 'Filtro de Expedições'));
            $this->setDecorators(array(array('ViewScript', array('viewScript' => 'index/relatorio-filtro-reimpressao.phtml'))));
    }

}