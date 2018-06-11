<?php

namespace Wms\Module\Expedicao\Grid;

use Core\Grid\Pager;
use Wms\Module\Web\Grid;

class RelatorioCorte extends Grid
{
    public function init(array $result)
    {
        /** @var \Wms\Domain\Entity\ExpedicaoRepository $expRepo */
        $expRepo = $this->getEntityManager()->getRepository('wms:Expedicao');
        $this->setAttrib('title','Cortes por Pedido e Produto');
        $this->setSource(new \Core\Grid\Source\ArraySource($result))
                ->addColumn(array(
                    'label' => 'Expedição',
                    'index' => 'COD_EXPEDICAO',
                ))
                ->addColumn(array(
                    'label' => 'Carga',
                    'index' => 'COD_CARGA_EXTERNO',
                ))
                ->addColumn(array(
                    'label' => 'Pedido',
                    'index' => 'COD_PEDIDO',
                ))
                ->addColumn(array(
                    'label' => 'Cód.Cliente',
                    'index' => 'COD_CLIENTE',
                ))
                ->addColumn(array(
                    'label' => 'Cliente',
                    'index' => 'CLIENTE',
                ))
                ->addColumn(array(
                    'label' => 'Cód.Produto',
                    'index' => 'COD_PRODUTO',
                ))
                ->addColumn(array(
                    'label' => 'GRADE',
                    'index' => 'DSC_GRADE',
                ))
                ->addColumn(array(
                    'label' => 'Produto',
                    'index' => 'DSC_PRODUTO',
                ))
                ->addColumn(array(
                    'label' => 'Qtd.Pedido',
                    'index' => 'QUANTIDADE',
                    'render'=> 'TEXT'
                ))
                ->addColumn(array(
                    'label' => 'Qtd.Cortada',
                    'index' => 'QTD_CORTADA',
                    'render'=> 'TEXT'
                ))
                ->addColumn(array(
                    'label' => 'Qtd.Atendida',
                    'index' => 'QTD_ATENDIDA',
                    'render'=> 'TEXT'
                ))
                ->addColumn(array(
                    'label' => 'Tipo Corte',
                    'index' => 'TIPO_CORTE',
                ))
                /*
                ->addColumn(array(
                    'label' => 'Dth.Inicio',
                    'index' => 'DTH_INICIO_EXPEDICAO',
                ))
                ->addColumn(array(
                    'label' => 'Dth.Fim',
                    'index' => 'DTH_FIM_EXPEDICAO',
                ))
                ->addColumn(array(
                    'label' => 'Status',
                    'index' => 'STATUS_EXPEDICAO',
                ))
                */
                ;

        $pg = new Pager(count($result), 0, count($result));
        $this->setPager($pg);
        $this->setShowExport(true)
            ->setButtonForm('Sequenciar');

        return $this;
    }

}

