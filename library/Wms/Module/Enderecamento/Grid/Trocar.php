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

        $recebimento = isset($params['recebimento']) ? $params['recebimento'] : null;

        $result = $paleteRepo->getByRecebimentoAndStatus($recebimento);

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

        $this->setShowExport(false)
            ->addMassAction('trocar', 'Realizar troca');

        return $this;
    }

}

