<?php

namespace Wms\Module\Expedicao\Grid;

use Wms\Module\Web\Grid;

class MapasPendentes extends Grid
{
    public function init($idExpedicao)
    {
        /** @var \Wms\Domain\Entity\Expedicao\MapaSeparacaoConferenciaRepository $mapaSeparacaoConferenciaRepo */
        $mapaSeparacaoConferenciaRepo = $this->getEntityManager()->getRepository('wms:Expedicao\MapaSeparacaoConferencia');
        $result = $mapaSeparacaoConferenciaRepo->getProdutosConferirByExpedicao($idExpedicao);

        $this->setAttrib('title','Mapas Separação Conferir');
        $this->setSource(new \Core\Grid\Source\ArraySource($result))
            ->addColumn(array(
                'label' => 'Mapa',
                'index' => 'COD_MAPA_SEPARACAO',
            ))
            ->addColumn(array(
                'label' => 'Código',
                'index' => 'COD_PRODUTO',
            ))
            ->addColumn(array(
                'label' => 'Produto',
                'index' => 'DSC_PRODUTO',
            ))
            ->addColumn(array(
                'label' => 'Cod. Barras',
                'index' => 'COD_BARRAS'
            ))
            ->addColumn(array(
                'label' => 'Quantidade',
                'index' => 'QTD_SEPARAR'
            ))
            ->addColumn(array(
                'label' => 'Qtd. Faltante',
                'index' => 'QTD_CONFERIR',
            ))
            ->addColumn(array(
                'label' => 'Endereço',
                'index' => 'DSC_DEPOSITO_ENDERECO'
            ));

        $this->setShowExport(false);

        return $this;
    }

}

