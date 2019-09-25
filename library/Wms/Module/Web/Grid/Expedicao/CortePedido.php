<?php

namespace Wms\Module\Web\Grid\Expedicao;
          

use Wms\Module\Web\Grid,
    Wms\Domain\Entity\Recebimento;

/**
 * Grid da Página Inicial da Expedição
 *
 * @author Lucas Chinelate <lucaschinelate@hotmail.com>
 */
class CortePedido extends Grid
{
    /**
     * @param $idExpedicao
     * @return $this|void
     */
    public function init($pedidos,$idExpedicao)
    {

        $permissaoEn = $this->getEntityManager()->getRepository('wms:Sistema\Parametro')->findOneBy(array('constante' => 'PERMITE_REALIZAR_CORTES_WMS'));
        $permite = (!empty($permissaoEn) && $permissaoEn->getValor() == "N") ? false : true;

        $this->showPager = false;
        $this->showExport = false;
        $source = $this->setSource(new \Core\Grid\Source\ArraySource($pedidos))
                ->setId('expedicao-mapas-grid')
                ->setAttrib('class', 'grid-expedicao-pendencias')
                ->setAttrib('caption', 'Pedidos')
                ->addColumn(array(
                    'label' => 'Cod.',
                    'index' => 'id',
                ))
                ->addColumn(array(
                    'label' => 'Cliente.',
                    'index' => 'cliente',
                ))
                ->addColumn(array(
                    'label' => 'Itinerario.',
                    'index' => 'itinerario',
                ));
        if ($permite) {
            $source->addAction(array(
                    'label' => 'Cortar Pedido',
                    'moduleName' => 'expedicao',
                    'controllerName' => 'corte-pedido',
                    'actionName' => 'cortar-pedido',
                    'cssClass' => 'inside-modal',
                    'params' => array('expedicao' => $idExpedicao),
                    'pkIndex' => 'id'
                ));
        }
        $this->setShowPager(true);
        $pager = new \Core\Grid\Pager(count($pedidos),1,2000);
        $this->setpager($pager);
        $this->setShowPager(false);

        return $this;
    }

}

