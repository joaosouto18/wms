<?php


namespace Wms\Module\Web\Grid\Expedicao;


use Core\Grid\Source\Doctrine;
use Wms\Module\Web\Grid;

class CaixaEmbalado extends Grid
{

    public function init()
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select("
            ce.id, 
            ce.descricao, 
            ce.pesoMaximo, 
            ce.cubagemMaxima, 
            ce.mixMaximo, 
            ce.unidadesMaxima, 
            ce.isDefault, 
            CASE WHEN ce.isDefault > 0 THEN 'SIM' ELSE '' END isDefaultStr")
            ->from("wms:Expedicao\CaixaEmbalado", "ce")
            ->where("ce.isAtiva > 0");


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
            ->addColumn([
                'label' => 'Padrão',
                'index' => 'isDefaultStr'
            ])
            ->addAction([
                'label' => 'Editar',
                'moduleName' => 'web',
                'controllerName' => 'caixa-embalado',
                'actionName' => 'edit',
                'pkIndex' => 'id'
            ])
            ->addAction([
                'label' => 'Tornar Padrão',
                'moduleName' => 'web',
                'controllerName' => 'caixa-embalado',
                'actionName' => 'setPadrao',
                'pkIndex' => 'id',
                'condition' => function ($row) {
                    return empty($row['isDefault']);
                }
            ])
            ->addAction([
                'label' => 'Remover',
                'moduleName' => 'web',
                'controllerName' => 'caixa-embalado',
                'actionName' => 'delete',
                'pkIndex' => 'id'
            ]);

        $this->setShowExport(false);
        return $this;
    }

}