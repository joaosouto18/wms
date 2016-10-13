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
                    'label' => 'Dth. Criação',
                    'index' => 'DTH_CRIACAO',
                ))
                ->addColumn(array(
                    'label' => 'Quebras',
                    'index' => 'QUEBRA',
                ))                
                ->addColumn(array(
                    'label' => 'Total Produtos',
                    'index' => 'QTD_TOTAL',
                ))
                ->addColumn(array(
                    'label' => 'Prod. Conferidos',
                    'index' => 'QTD_CONF',
                ))
                ->addColumn(array(
                    'label' => '% Conferencia',
                    'index' => 'PERCENTUAL',
                ))
                ->addAction(array(
                    'label'=>'Visualizar produtos',
                    'moduleName'=>'expedicao',
                    'controllerName'=>'mapa',
                    'actionName'=>'consultar',
                    'cssClass'=>'dialogAjax',
                    'pkIndex'=>'COD_MAPA_SEPARACAO'
                ))
                ->addAction(array(
                    'label' => 'Visualizar pedidos',
                    'moduleName' => 'expedicao',
                    'controllerName' => 'corte',
                    'actionName' => 'corte-antecipado-ajax',
                    'cssClass' => 'dialogAjax',
                    'pkIndex' => 'COD_MAPA_SEPARACAO'
                ))
                ->addAction(array(
                    'label' => 'Visualizar Mapas sem Conferencia',
                    'moduleName' => 'expedicao',
                    'controllername' => 'mapa',
                    'actionName' => 'pendentes-conferencia',
                    'cssClass' => 'dialogAjax',
                    'pkIndex' => 'COD_MAPA_SEPARACAO'
                ));

        return $this;
    }

}

