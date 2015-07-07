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
class Mapas extends Grid
{
    /**
     * @param $idExpedicao
     * @return $this|void
     */
    public function init($mapas)
    {
        /** @var \Wms\Domain\Entity\Expedicao\EtiquetaSeparacaoRepository $etiquetaRepo */
        $etiquetaRepo = $this->getEntityManager()->getRepository('wms:Expedicao\EtiquetaSeparacao');
        $this->showPager = false;
        $this->showExport = false;
        $this->setSource(new \Core\Grid\Source\ArraySource($mapas))
                ->setId('expedicao-mapas-grid')
                ->setAttrib('class', 'grid-expedicao-pendencias')
                ->setAttrib('caption', 'Mapas')
                ->addColumn(array(
                    'label' => 'Mapa',
                    'index' => 'COD_MAPA_SEPARACAO',
                ))
                ->addColumn(array(
                    'label' => 'DTH. CRIAÇÃO',
                    'index' => 'DTH_CRIACAO',
                ))
                ->addColumn(array(
                    'label' => 'QUEBRAS',
                    'index' => 'QUEBRA',
                ))                
                ->addColumn(array(
                    'label' => 'TOTAL PRODUTOS',
                    'index' => 'QTD_TOTAL',
                ))
                ->addColumn(array(
                    'label' => 'PROD. CONFERIDOS',
                    'index' => 'QTD_CONF',
                ))
                ->addColumn(array(
                    'label' => 'CONFERENCIA',
                    'index' => 'PERCENTUAL',
                ))
                ->addAction(array(
                    'label' => 'Visualizar Produtos',
                    'moduleName' => 'expedicao',
                    'controllerName' => 'os',
                    'actionName' => 'conferencia-transbordo',
                    'cssClass' => 'dialogAjax',
                    'pkIndex' => 'COD_MAPA_SEPARACAO'
                ))
                ;

        return $this;
    }

}

