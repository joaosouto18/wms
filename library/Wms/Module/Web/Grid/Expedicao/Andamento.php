<?php

namespace Wms\Module\Web\Grid\Expedicao;
          

use Core\Grid\Pager;
use Wms\Module\Web\Grid,
    Wms\Domain\Entity\Recebimento;

/**
 * Grid da Página Inicial da Expedição
 *
 * @author Lucas Chinelate <lucaschinelate@hotmail.com>
 */
class Andamento extends Grid
{
    /**
     *
     * @param array $params 
     */
    public function init ($idExpedicao)
    {
 
        /** @var \Wms\Domain\Entity\ExpedicaoRepository $ExpedicaoRepo */
        $ExpedicaoRepo = $this->getEntityManager()->getRepository('wms:Expedicao');
        $result = $ExpedicaoRepo->getAndamentoByExpedicao($idExpedicao);
        $this->showPager = true;
        $this->setAttrib('title','Andamento Expedição');
        $this->setSource(new \Core\Grid\Source\ArraySource($result))
                ->setId('expedicao-andamento-grid')
                ->setAttrib('caption', 'Andamento da expedição')
                ->setAttrib('class', 'grid-andamento')
                ->addColumn(array(
                    'label'  => 'Data',
                    'index'  => 'dataAndamento',
                    'render' => 'DataTime'
                ))
                ->addColumn(array(
                    'label' => 'Usuário',
                    'index' => 'nome',
                ))
                ->addColumn(array(
                    'label' => 'Mapa',
                    'index' => 'codMapa',
                ))
                ->addColumn(array(
                    'label' => 'Andamento',
                    'index' => 'dscObservacao',
                ))
                ->setShowExport(false);
        $this->setPager(new Pager(count($result), 0, count($result)));

        return $this;
    }

}

