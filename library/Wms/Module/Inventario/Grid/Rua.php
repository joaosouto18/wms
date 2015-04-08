<?php

namespace Wms\Module\Inventario\Grid;

use Wms\Module\Web\Grid;

class Rua extends Grid
{

    public function init($params)
    {
        /** @var \Wms\Domain\Entity\Inventario\EnderecoRepository $invEnderecoRepo */
        $invEnderecoRepo = $this->getEntityManager()->getRepository("wms:Inventario\Endereco");
        $params['idInventario'] = $params['id'];
        $params['rua']          = $params['RUA'];
        $detalheByRua = $invEnderecoRepo->getByInventario($params);

        $this->setSource(new \Core\Grid\Source\ArraySource($detalheByRua));
        $this->setShowExport(false);
        $this->addColumn(array(
                'label' => 'Endereço',
                'index' => 'DSC_DEPOSITO_ENDERECO'
             ))
            ->addColumn(array(
                'label' => 'Números de Contagens',
                'index' => 'ULTIMACONTAGEM',
            ))
            ->addColumn(array(
                'label' => 'Situação',
                'index' => 'SITUACAO',
            ))
            ->addAction(array(
                'label' => 'Visualizar Detalhe Contagem',
                'actionName' => 'view-detalhe-contagem-ajax',
                'cssClass' => 'inside-modal',
                'pkIndex' => 'CODINVENDERECO'
            ));

        return $this;
    }

}
