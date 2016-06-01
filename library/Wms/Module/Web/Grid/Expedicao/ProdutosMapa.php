<?php

namespace Wms\Module\Web\Grid\Expedicao;


use Core\Grid\Pager;
use Wms\Domain\Entity\Expedicao\EtiquetaSeparacao;
use Wms\Module\Web\Grid,
    Wms\Domain\Entity\Recebimento;

/**
 * Grid da Página Inicial da Expedição
 *
 * @author Lucas Chinelate <lucaschinelate@hotmail.com>
 */
class ProdutosMapa extends Grid
{
    /**
     * @param $idExpedicao
     * @return $this|void
     */
    public function init($idMapa, $idExpedicao)
    {
        /** @var \Wms\Domain\Entity\Expedicao\MapaSeparacaoRepository $mapaRepo */
        $mapaRepo = $this->getEntityManager()->getRepository('wms:Expedicao\MapaSeparacao');
        $array = $mapaRepo->getResumoConferenciaMapaProduto($idMapa);
        $this->setShowExport(false);
        $this->setShowPager(true);
        $pager = new Pager(count($array), 1, 100);
        $this->setpager($pager);
        $this->setShowPager(false);

        $this->setSource(new \Core\Grid\Source\ArraySource($array))
            ->setId('expedicao-mapas-grid')
            ->setAttrib('class', 'grid-expedicao-pendencias')
            ->setAttrib('caption', 'Produtos')
            ->addColumn(array(
                'label' => 'Cod.Produto',
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
                'label' => 'Qtd.Separar',
                'index' => 'QTD_SEPARAR',
            ))
            ->addColumn(array(
                'label' => 'Qtd. Conferido',
                'index' => 'QTD_CONFERIDA',
            ))
            ->addColumn(array(
                'label' => 'Qtd. Cortado',
                'index' => 'QTD_CORTADO',
            ))
            ->addColumn(array(
                'label' => 'Conferido',
                'index' => 'CONFERIDO',
            ))
            ->addAction(array(
                'label' => 'Visualizar Conferencia',
                'moduleName' => 'expedicao',
                'controllerName' => 'mapa',
                'actionName' => 'conferencia',
                'cssClass' => 'inside-modal',
                'pkIndex' => array('COD_PRODUTO','DSC_GRADE','NUM_CONFERENCIA')
            ))
            ->addAction(array(
                'label' => 'Cortar Item',
                'moduleName' => 'expedicao',
                'controllerName' => 'corte-pedido',
                'actionName' => 'list',
                'cssClass' => 'inside-modal',
                'params'=>array('pedidoCompleto'=>'N','COD_EXPEDICAO'=>$idExpedicao),
                'pkIndex' => array('idProduto'=>'COD_PRODUTO','DSC_GRADE')
            ))
            ->addAction(array(
                'label' => 'Cortar Pedido',
                'moduleName' => 'expedicao',
                'controllerName' => 'corte-pedido',
                'actionName' => 'list',
                'cssClass' => 'inside-modal',
                'params'=>array('pedidoCompleto'=>'S','COD_EXPEDICAO'=>$idExpedicao),
                'pkIndex' => array('idProduto'=>'COD_PRODUTO','DSC_GRADE')
            ))

        ;
        return $this;
    }

}

