<?php

namespace Wms\Module\Expedicao\Grid;

use Wms\Module\Web\Grid;

class MapasPendentes extends Grid
{
    public function init($idMapa)
    {
        /** @var \Wms\Domain\Entity\Expedicao\MapaSeparacaoConferenciaRepository $mapaSeparacaoConferenciaRepo */
        $mapaSeparacaoConferenciaRepo = $this->getEntityManager()->getRepository('wms:Expedicao\MapaSeparacaoConferencia');
        $result = $mapaSeparacaoConferenciaRepo->getProdutosConferir($idMapa);

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
                ))
                ->addColumn(array(
                    'label' => utf8_encode('Endereço'),
                    'index' => 'DSC_DEPOSITO_ENDERECO'
                ));

        $this->setShowExport(false);

        return $this;
    }

}

