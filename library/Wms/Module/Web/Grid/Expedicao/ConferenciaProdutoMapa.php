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
    public function init($idMapa, $idProduto, $grade, $numConferencia)
    {
        /** @var \Wms\Domain\Entity\Expedicao\MapaSeparacaoRepository $mapaSeparacaoRepo */
        $mapaSeparacaoRepo = $this->getEntityManager()->getRepository('wms:Expedicao\MapaSeparacao');
        $array = $mapaSeparacaoRepo->getDetalhesConferenciaMapaProduto($idMapa,$idProduto,$grade,$numConferencia);
        $this->showPager = false;
        $this->showExport = false;
        $this->setSource(new \Core\Grid\Source\ArraySource($array))
            ->setId('expedicao-mapas-grid')
            ->setAttrib('class', 'grid-expedicao-pendencias')
            ->setAttrib('caption', 'Conferencia')
            ->addColumn(array(
                'label' => 'OS',
                'index' => 'COD_OS',
            ))
            ->addColumn(array(
                'label' => 'Usuário',
                'index' => 'NOM_PESSOA',
            ))
            ->addColumn(array(
                'label' => 'Embalagem',
                'index' => 'EMBALAGEM',
            ))
            ->addColumn(array(
                'label' => 'Qtd Conferida',
                'index' => 'QTD_CONFERIDA',
            ))
            ->addColumn(array(
                'label' => 'Data e Hora',
                'index' => 'DTH_CONFERENCIA',
            ))
        ;

        return $this;
    }

}

