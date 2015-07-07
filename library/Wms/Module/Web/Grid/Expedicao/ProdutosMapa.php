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
class ProdutosMapa extends Grid
{
    /**
     * @param $idExpedicao
     * @return $this|void
     */
    public function init($idMapa,$idProduto,$grade,$numConferencia)
    {
        /** @var \Wms\Domain\Entity\Expedicao\MapaSeparacaoRepository $mapaSeparacaoRepo */
        $mapaSeparacaoRepo = $this->getEntityManager()->getRepository('wms:Expedicao\MapaSeparacao');
        $array = $mapaSeparacaoRepo->getDetalhesConferenciaMapaProduto($idMapa,$idProduto,$grade,$numConferencia);
        $this->showPager = false;
        $this->showExport = false;
        $this->setSource(new \Core\Grid\Source\ArraySource($array))
                ->setId('expedicao-mapas-grid')
                ->setAttrib('class', 'grid-expedicao-pendencias')
                ->setAttrib('caption', 'Mapas')
                ->addColumn(array(
                    'label' => 'Mapa',
                    'index' => 'COD_OS',
                ))
                ->addColumn(array(
                    'label' => 'DTH. CRIAÇÃO',
                    'index' => 'NOM_PESSOA',
                ))
                ->addColumn(array(
                    'label' => 'QUEBRAS',
                    'index' => 'EMBALAGEM',
                ))                
                ->addColumn(array(
                    'label' => 'TOTAL PRODUTOS',
                    'index' => 'QTD_CONFERIDA',
                ))
                ->addColumn(array(
                    'label' => 'PROD. CONFERIDOS',
                    'index' => 'DTH_CONFERENCIA',
                ))
                ;

        return $this;
    }

}

