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
        $params['divergencia']  = 'todos';
        $params['campos']       = "SELECT DISTINCT DE.DSC_DEPOSITO_ENDERECO, IE.COD_INVENTARIO_ENDERECO AS codInvEndereco,
          CASE WHEN IE.DIVERGENCIA = 1 THEN 'DIVERGENCIA' WHEN IE.INVENTARIADO = 1 THEN 'INVENTARIADO' ELSE 'PENDENTE' END SITUACAO";

        $detalheByRua = $invEnderecoRepo->getByInventario($params);

        $this->setAttrib('title','Rua');
        $this->setSource(new \Core\Grid\Source\ArraySource($detalheByRua));
        $this->setShowExport(false);
        $this->addColumn(array(
                'label' => 'Endereço',
                'index' => 'DSC_DEPOSITO_ENDERECO'
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
