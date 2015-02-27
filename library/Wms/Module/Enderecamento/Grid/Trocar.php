<?php

namespace Wms\Module\Enderecamento\Grid;

use Wms\Domain\Entity\Enderecamento\Palete;
use Wms\Module\Web\Grid;

class Trocar extends Grid
{
    public function init(array $params = array())
    {
        /** @var \Wms\Domain\Entity\Enderecamento\PaleteRepository $paleteRepo */
        $paleteRepo = $this->getEntityManager()->getRepository("wms:Enderecamento\Palete");
        $result = $paleteRepo->getPaletesByProdutoAndGrade($params);

        $this->setSource(new \Core\Grid\Source\ArraySource($result))
                ->addColumn(array(
                    'label' => 'U.M.A',
                    'index' => 'id',
                ))
                ->addColumn(array(
                    'label' => 'Unitizador',
                    'index' => 'unitizador',
                ))
                ->addColumn(array(
                    'label' => 'Qtd Produtos',
                    'index' => 'qtd',
                ))
                ->addColumn(array(
                    'label' => 'Status',
                    'index' => 'status',
                ))
                ->addColumn(array(
                    'label' => 'Endereço',
                    'index' => 'endereco',
                ))
                ->addColumn(array(
                    'label' => 'Impresso',
                    'index' => 'impresso',
                ));

        if (isset($params['id']) && isset($params['codigo']) && isset($params['grade'])) {
            $this->setShowExport(false)
                ->addMassAction(
                    'enderecamento/palete/index/id/' . $params['id'] . '/codigo/' . $params['codigo'] . '/grade/' . urlencode($params['grade']),
                    'Realizar troca');
        }

        return $this;
    }

}

