<?php

namespace Wms\Module\Web\Grid\Enderecamento;

use Wms\Module\Web\Grid,
    Wms\Domain\Entity\Recebimento as RecebimentoEntity,
    Wms\Domain\Entity\OrdemServico as OrdemServicoEntity;

/**
 * Description of DadoLogistico
 *
 * @author Renato Medina <medinadato@gmail.com>
 */
class Listagem extends Grid
{

    /**
     *
     * @param array $params 
     */
    public function init(array $params = array())
    {

        /** @var \Wms\Domain\Entity\Enderecamento\PaleteRepository $paleteRepo */
        $paleteRepo    = $this->getEntityManager()->getRepository('wms:Enderecamento\Palete');
        $result = $paleteRepo->getQtdProdutosByRecebimento($params);
        $this->setAttrib('title','Listagem Enderecamento');
        $this->setSource(new \Core\Grid\Source\ArraySource($result))
                ->setId('enderecamento-index-grid')
                ->setAttrib('class', 'grid-enderecamento-listagem')
                ->addColumn(array(
                    'label' => 'Recebimento',
                    'index' => 'COD_RECEBIMENTO',
                ))
            ->addColumn(array(
                'label' => 'Fornecedor',
                'index' => 'FORNECEDORES',
            ))
                ->addColumn(array(
                    'label' => 'Data Inicial',
                    'index' => 'DTH_INICIO_RECEB',
                ))
                ->addColumn(array(
                    'label' => 'Data Final',
                    'index' => 'DTH_FINAL_RECEB',
                ))
                ->addColumn(array(
                    'label' => 'Status',
                    'index' => 'STATUS',
                ))
                ->addColumn(array(
                    'label' => 'Qtd.Total',
                    'index' => 'QTD_RECEBIDA',
                ))
                ->addColumn(array(
                    'label' => 'Qtd.Endereçada',
                    'index' => 'QTD_ENDERECADA',
                ))
                ->addColumn(array(
                    'label' => '% Endereçamento',
                    'index' => 'PERCENTUAL'
                ))

                ->addAction(array(
                    'label' => 'Endereçamento',
                    'moduleName' => 'enderecamento',
                    'actionName' => 'index',
                    'controllerName' => "produto",
                    'pkIndex' => 'COD_RECEBIMENTO'
                ))
                ->setShowExport(true)
                ->setShowMassActions($params);

        return $this;
    }

}

