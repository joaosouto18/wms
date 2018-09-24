<?php

namespace Wms\Module\Web\Grid\Expedicao;
          

use Wms\Module\Web\Grid,
    Wms\Domain\Entity\Recebimento;

/**
 * Grid da Página Inicial da Expedição
 *
 * @author Lucas Chinelate <lucaschinelate@hotmail.com>
 */
class CorteProduto extends Grid
{
    /**
     * @param $idExpedicao
     * @return $this|void
     */
    public function init($pedidos,$idExpedicao,$codProduto,$grade, $corteEmbalagemVenda)
    {

        $enabled = true;
        if ($corteEmbalagemVenda == "S") {
            $enabled = false;
        }

        $embalagemRepo = $this->getEntityManager()->getRepository("wms:Produto\Embalagem");
        $embalagensEn = $embalagemRepo->findBy(array('codProduto'=> $codProduto,'grade'=> $grade, 'dataInativacao' => null), array('quantidade' => 'ASC'));

        $values = array();
        foreach ($embalagensEn as $embalagemEn) {
            $values[$embalagemEn->getId()] = $embalagemEn->getDescricao() . "(" . $embalagemEn->getQuantidade(). ")" ;
        }

        $this->showHeaders = true;
        $this->showPager = false;
        $this->showExport = false;
        $this->setSource(new \Core\Grid\Source\ArraySource($pedidos))
                ->setId('expedicao-mapas-grid')
                ->setAttrib('class', 'grid-expedicao-pendencias')
                ->addColumn(array(
                    'label' => 'Pedido',
                    'index' => 'id',
                ))
                ->addColumn(array(
                    'label' => 'Cod.Cliente',
                    'index' => 'codcli',
                ))
                ->addColumn(array(
                    'label' => 'Cliente',
                    'index' => 'cliente',
                ))
                ->addColumn(array(
                    'label' => 'Itinerario.',
                    'index' => 'itinerario',
                ))->addColumn(array(
                    'label' => 'Qtd.Pedido',
                    'index' => 'quantidade',
                ))->addColumn(array(
                    'render' => 'Select',
                    'label' => 'Emb.Corte',
                    'index' => 'idEmbalagem',
                    'values' => $values,
                    'enabled' => $enabled
                ))->addColumn(array(
                    'label' => 'Qtd.Cortar',
                    'index' => 'qtdCortada',
                    'render' => 'inputText',
                ))->addColumn(array(
                    'label' => 'Qtd.Cortada',
                    'index' => 'qtdCortada',
                ));

        //$this->addMassAction('corte-total','Corte Total');

        $this->setShowPager(true);
        $pager = new \Core\Grid\Pager(count($pedidos),1,2000);
        $this->setpager($pager);
        $this->setShowPager(false);

        return $this;
    }

}

