<?php

namespace Wms\Module\Web\Grid\Expedicao;
          

use Wms\Domain\Entity\Expedicao\EtiquetaSeparacao;
use Wms\Module\Web\Grid,
    Wms\Domain\Entity\Recebimento;

/**
 * Grid da Página Inicial da Expedição
 *
 * @author Lucas Chinelate <lucaschinelate@hotmail.com>
 */
class CorteAntecipado extends Grid
{
    /**
     * @param $idExpedicao
     * @return $this|void
     */
    public function init($produtos,$idPedido,$idExpedicao)
    {
        $this->showPager = false;
        $this->showExport = false;
        $this->setSource(new \Core\Grid\Source\ArraySource($produtos))
                ->setId('expedicao-mapas-grid')
                ->setAttrib('class', 'grid-expedicao-pendencias')
                ->setAttrib('caption', 'Produtos para Corte')
                ->addColumn(array(
                    'label' => 'Cod.',
                    'index' => 'COD_PRODUTO',
                ))
                ->addColumn(array(
                    'label' => 'Grade',
                    'index' => 'DSC_GRADE',
                ))
                ->addColumn(array(
                    'label' => 'Produto',
                    'index' => 'DSC_PRODUTO',
                ))                
                ->addColumn(array(
                    'label' => 'Qtd. Pedido',
                    'index' => 'QTD',
                ))
                ->addColumn(array(
                    'label' => 'Qtd. Corte',
                    'index' => 'QTD_CORTADA',
                ))
                ->addAction(array(
                    'label' => 'Cortar Item',
                    'moduleName' => 'expedicao',
                    'controllerName' => 'corte',
                    'actionName' => 'cortar-item',
                    'cssClass' => 'inside-modal',
                    'params' => array('expedicao' => $idExpedicao),
                    'pkIndex' => array('idProduto'=>'COD_PRODUTO','DSC_GRADE','COD_PEDIDO')
                ));
        $this->setShowPager(true);
        $pager = new \Core\Grid\Pager(count($produtos),1,2000);
        $this->setpager($pager);
        $this->setShowPager(false);

        return $this;
    }

}

