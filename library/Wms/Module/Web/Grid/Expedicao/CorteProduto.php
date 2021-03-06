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
     * @return $this|void
     */
    public function init($pedidos,$codProduto,$grade, $corteEmbalagemVenda)
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
                ->setId('expedicao-corte-produtos-grid')
                ->setAttrib('class', 'expedicao-corte-produtos-grid')
                ->addColumn(array(
                    'label' => 'Expedicao',
                    'index' => 'idExpedicao',
                ))
                ->addColumn(array(
                    'label' => 'Carga',
                    'index' => 'carga',
                ))
                ->addColumn(array(
                    'label' => 'Pedido',
                    'index' => 'id',
                ))
                ->addColumn(array(
                    'label' => 'Mapa',
                    'index' => 'mapa',
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
                    'index' => 'quantidade'
                ));

                if ($embalagensEn == null) {
                    $this->addColumn(array(
                        'label' => 'Emb.Corte.',
                        'index' => 'idEmbalagem'
                    ));
                } else {
                    $this->addColumn(array(
                        'render' => 'Select',
                        'label' => 'Emb.Corte',
                        'index' => 'idEmbalagem',
                        'values' => $values,
                        'enabled' => $enabled));
                }

                $this->addColumn(array(
                    'label' => 'Qtd.Cortar',
                    'index' => 'permiteCorte',
                    'render' => 'inputText',
                ))->addColumn(array(
                    'label' => 'Qtd.Corte/Mapa',
                    'index' => 'qtdCortada',
                ))->addColumn(array(
                    'label' => 'Qtd.Cortada Total',
                    'index' => 'qtdCorteTotal',
                ));

        $this->setShowPager(true);
        $pager = new \Core\Grid\Pager(count($pedidos),1,2000);
        $this->setpager($pager);
        $this->setShowPager(false);

        return $this;
    }

}

