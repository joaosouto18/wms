<?php

namespace Wms\Module\Web\Grid\Expedicao;
          

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
        $grid = new \Core\Grid(new \Core\Grid\Source\Doctrine($result));
        $this->setSource(new \Core\Grid\Source\Doctrine($result))
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
                    'label' => 'Andamento',
                    'index' => 'dscObservacao',
                ))
                ->setShowExport(false);

        return $this;
    }

}

