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
class Embalados extends Grid
{
    /**
     * @param $idExpedicao
     * @return $this|void
     */
    public function init($embalados)
    {
        /** @var \Wms\Domain\Entity\Expedicao\EtiquetaSeparacaoRepository $etiquetaRepo */
        $etiquetaRepo = $this->getEntityManager()->getRepository('wms:Expedicao\EtiquetaSeparacao');
        $this->showPager = false;
        $this->showExport = false;
        $this->setSource(new \Core\Grid\Source\ArraySource($embalados))
                ->setId('expedicao-mapas-grid')
                ->setAttrib('class', 'grid-expedicao-pendencias')
                ->setAttrib('caption', 'Embalados')
                ->addColumn(array(
                    'label' => 'Mapa',
                    'index' => 'COD_MAPA_SEPARACAO',
                ))
                ->addColumn(array(
                    'label' => 'Cod. Embalados',
                    'index' => 'COD_MAPA_SEPARACAO_EMB_CLIENTE',
                ))
                ->addColumn(array(
                    'label' => 'Cliente',
                    'index' => 'NOM_PESSOA',
                ))                
                ->addColumn(array(
                    'label' => 'Status',
                    'index' => 'DSC_SIGLA',
                ));

        return $this;
    }

}

