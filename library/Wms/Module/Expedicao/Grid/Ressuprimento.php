<?php

namespace Wms\Module\Expedicao\Grid;

use Wms\Module\Web\Grid;

class Ressuprimento extends Grid
{
    public function init(array $params = array())
    {
        /** @var \Wms\Domain\Entity\ExpedicaoRepository $expRepo */
        $expRepo = $this->getEntityManager()->getRepository('wms:Expedicao');

        $result = $expRepo->getOn($params['codExpedicao']);

        $this->setSource(new \Core\Grid\Source\ArraySource($result))
                ->addColumn(array(
                    'label' => 'Seq.',
                    'index' => 'pedido',
                    'render' => 'Input'
                ))
                ->addColumn(array(
                    'label' => 'Carga',
                    'index' => 'carga',
                ))
                ->addColumn(array(
                    'label' => 'Itinerario',
                    'index' => 'itinerario',
                ))
                ->addColumn(array(
                    'label' => 'Cidade',
                    'index' => 'cidade',
                ))
                ->addColumn(array(
                    'label' => 'Bairro',
                    'index' => 'bairro',
                ))
                ->addColumn(array(
                    'label' => 'Rua',
                    'index' => 'rua',
                ))
                ->addColumn(array(
                    'label' => 'Cliente',
                    'index' => 'cliente',
                ));

        $this->setShowExport(false)
            ->setButtonForm('Sequenciar');

        return $this;
    }

}

