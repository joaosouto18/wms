<?php


namespace Wms\Module\Web\Grid\Expedicao;


use Core\Grid\Source\Doctrine;
use Wms\Module\Web\Grid;

class CaixaEmbalado extends Grid
{

    public function init()
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select("ce")->from("wms:Expedicao\CaixaEmbalado", "ce");


        $this->setId("grid-caixas-embalado")
            ->setAttribs([
            "title" => "Caixas de Embalado Cadastradas"
            ])
            ->setSource(new Doctrine($qb));
        $this->addColumn([
                'label' => 'Id',
                'index' => 'id'
            ])
            ->addColumn([
                'label' => 'Descrição',
                'index' => 'descricao'
            ])
            ->addColumn([
                'label' => 'Peso',
                'index' => 'pesoMaximo'
            ])
            ->addColumn([
                'label' => 'Cubagem',
                'index' => 'cubagemMaxima'
            ])
            ->addColumn([
                'label' => 'Mix',
                'index' => 'mixMaximo'
            ])
            ->addColumn([
                'label' => 'Unidades',
                'index' => 'unidadesMaxima'
            ])
        ;
    }

}