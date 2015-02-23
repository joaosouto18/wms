<?php

namespace Wms\Module\Web\Grid\Enderecamento;
          

use Wms\Module\Web\Grid;

/**
 * @author Lucas Chinelate <lucaschinelate@hotmail.com>
 */

class Produtos extends Grid
{
    /**
     * @param int $idRecebimento
     */
    public function init ($idRecebimento)
    {

        /** @var \Wms\Domain\Entity\RecebimentoRepository $recebimentoRepo */
        $recebimentoRepo    = $this->getEntityManager()->getRepository('wms:Recebimento');
        $result = $recebimentoRepo->getProdutosByRecebimento($idRecebimento);

        $this->setSource(new \Core\Grid\Source\ArraySource($result))
                ->setId('enderecamento-produtos-grid')
                ->setAttrib('caption', 'Produtos para Endereçar')
                ->setAttrib('class', 'grid-enderecamento')
                ->addColumn(array(
                    'label'  => 'Código',
                    'index'  => 'codigo',
                ))
                ->addColumn(array(
                    'label' => 'Produto',
                    'index' => 'produto',
                ))
                ->addColumn(array(
                    'label' => 'Grade',
                    'index' => 'grade',
                ))
                ->addColumn(array(
                    'label' => 'Qtd.Recebimento',
                    'index' => 'qtdRecebimento',
                ))
                ->addColumn(array(
                    'label' => 'Qtd.Recebida',
                    'index' => 'qtdRecebida',
                ))
                ->addColumn(array(
                    'label' => 'Qtd.Endereçamento',
                    'index' => 'qtdEnderecamento',
                ))
                ->addColumn(array(
                    'label' => 'Qtd.Endereçada',
                    'index' => 'qtdEnderecada',
                ))
                ->addColumn(array(
                    'label' => 'Qtd.Total',
                    'index' => 'qtdTotal',
                ))
                ->addAction(array(
                    'label' => 'Endereçar Paletes',
                    'controllerName' => 'palete',
                    'actionName' => 'index',
                    'pkIndex' => array('codigo','grade')
                ))
                ->addAction(array(
                    'label' => 'Andamento',
                    'controllerName' => 'produto',
                    'actionName' => 'list',
                    'cssClass' => 'dialogAjax',
                    'pkIndex' => array('codigo','grade')
                ))
                ->addAction(array(
                    'label' => 'Alterar Norma de Paletização',
                    'controllerName' => 'produto',
                    'actionName' => 'alterar-norma',
                    'cssClass' => 'dialogAjax',
                    'pkIndex' => array('codigo','grade')
                ))
            ->addAction(array(
                    'label' => 'Trocar U.M.As',
                    'controllerName' => 'produto',
                    'actionName' => 'trocar',
                    'cssClass' => 'dialogAjax',
                    'pkIndex' => array('codigo','grade')
                ))

            ->setShowExport(false);

        return $this;
    }

}

