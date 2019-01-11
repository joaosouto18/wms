<?php

namespace Wms\Module\InventarioNovo\Grid;

use Wms\Module\Web\Grid;

class DetalheContagem extends Grid
{

    public function init($params)
    {
        /** @var \Wms\Domain\Entity\InventarioNovo\InventarioContEndRepository $inventarioContEndRepo */
        $inventarioContEndRepo = $this->getEntityManager()->getRepository("wms:InventarioNovo\InventarioContEnd");
        $params['codInvEndereco'] = $params['CODINVENDERECO'];
        $detalheEndereco = $inventarioContEndRepo->getDetalhesByInventarioEndereco($params['codInvEndereco']);

        $this->setAttrib('title','Detalhe Contagem');
        $this->setSource(new \Core\Grid\Source\ArraySource($detalheEndereco));
        $this->setShowExport(false);
        $this->addColumn(array(
            'label' => 'N.Contagem',
            'index' => 'numContagem'
        ))
            ->addColumn(array(
                'label' => 'Usuario',
                'index' => 'nome'
            ))
            ->addColumn(array(
                'label' => 'Codigo',
                'index' => 'id'
            ))
            ->addColumn(array(
                'label' => 'Grade',
                'index' => 'grade'
            ))
            ->addColumn(array(
                'label' => 'Produto',
                'index' => 'descricao'
            ))
            ->addColumn(array(
                'label' => 'Volume',
                'index' => 'volume'
            ))
            ->addColumn(array(
                'label' => 'Quantidade Contada',
                'index' => 'qtdContada'
            ))
            ->addColumn(array(
                'label' => 'Quantidade Divergência',
                'index' => 'qtdDivergencia'
            ));

        return $this;
    }

}
