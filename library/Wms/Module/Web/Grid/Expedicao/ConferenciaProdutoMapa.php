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
class ConferenciaProdutoMapa extends Grid
{
    /**
     * @param $idExpedicao
     * @return $this|void
     */
    public function init($idMapa)
    {
        /** @var \Wms\Domain\Entity\Expedicao\MapaSeparacaoRepository $mapaRepo */
        $mapaRepo = $this->getEntityManager()->getRepository('wms:Expedicao\MapaSeparacao');
        $array = $mapaRepo->getResumoConferenciaMapaProduto($idMapa);
        $this->showPager = false;
        $this->showExport = false;
        $this->setSource(new \Core\Grid\Source\ArraySource($array))
                ->setId('expedicao-mapas-grid')
                ->setAttrib('class', 'grid-expedicao-pendencias')
                ->setAttrib('caption', 'Mapas')
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
                    'label' => 'Conferido',
                    'index' => 'CONFERIDO',
                ))
                ->addAction(array(
                    'label' => 'Visualizar CONFERENCIA',
                    'moduleName' => 'expedicao',
                    'controllerName' => 'os',
                    'actionName' => 'conferencia-transbordo',
                    'cssClass' => 'inside-modal',
                    'pkIndex' => 'COD_PRODUTO'
                ))
                ;

        return $this;
    }

}

