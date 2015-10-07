<?php

namespace Wms\Module\Expedicao\Grid;

use Wms\Module\Web\Grid;

class MapasPendentes extends Grid
{
    public function init($idExpedicao)
    {
        /** @var \Wms\Domain\Entity\Expedicao\MapaSeparacaoConferenciaRepository $mapaSeparacaoConferenciaRepo */
        $mapaSeparacaoConferenciaRepo = $this->getEntityManager()->getRepository('wms:Expedicao\MapaSeparacaoConferencia');
        $result = $mapaSeparacaoConferenciaRepo->getProdutosConferir($idExpedicao);

        $this->setAttrib('title','Mapas Separação Conferir');
        $this->setSource(new \Core\Grid\Source\ArraySource($result))
                ->addColumn(array(
                    'label' => utf8_encode('Código'),
                    'index' => 'COD_PRODUTO',
                ))
                ->addColumn(array(
                    'label' => 'Produto',
                    'index' => 'DSC_PRODUTO',
                ))
                ->addColumn(array(
                    'label' => 'Quantidade',
                    'index' => 'QTD_CONFERIR',
                ));

        $this->setShowExport(false);

        return $this;
    }

}

