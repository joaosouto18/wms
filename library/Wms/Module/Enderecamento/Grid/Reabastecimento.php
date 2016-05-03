<?php

namespace Wms\Module\Enderecamento\Grid;

use Wms\Module\Web\Grid;

class Reabastecimento extends Grid
{

    public function init()
    {
        $this->setAttrib('title','Reabastecimento');
        $source = $this->getEntityManager()->createQueryBuilder()
            ->select("o.id, p.nome, o.dataInicial, o.dataFinal")
            ->from('wms:Enderecamento\ReabastecimentoManual', 'rm')
            ->innerJoin('rm.os', 'o')
            ->innerJoin('o.pessoa', 'p')
            ->groupBy('o.id, p.nome, o.dataInicial, o.dataFinal')
            ->orderBy('o.id', 'DESC');

        $this->setSource(new \Core\Grid\Source\Doctrine($source))
            ->setId('reabastecimento-manual');
        $this->setShowExport(false);
        $this->addColumn(array(
                'label' => 'Ordem de Serviço',
                'index' => 'id'
             ))
            ->addColumn(array(
                'label' => 'Conferente',
                'index' => 'nome'
            ))
            ->addColumn(array(
                'label' => 'Data inicio',
                'index' => 'dataInicial',
                'render'=> 'DataTime'
            ))
            ->addColumn(array(
                'label' => 'Data final',
                'index' => 'dataFinal',
                'render'=> 'DataTime'
            ))
            ->addAction(array(
                'label' => 'Imprimir relatório',
                'title' => 'Imprimir relatório',
                'actionName' => 'imprimir-ajax',
                'cssClass' => 'imprimir pdf',
                'pkIndex' => 'id'
            ))
            ->addAction(array(
                'label' => 'Imprimir relatório de ruptura',
                'title' => 'Imprimir relatório de ruptura',
                'actionName' => 'imprimir-ruptura-ajax',
                'cssClass' => 'imprimir pdf',
                'pkIndex' => 'id'
            ));
        return $this;
    }

}
