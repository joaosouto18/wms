<?php


namespace Wms\Module\Web\Grid\Enderecamento;

use Core\Grid\Source\Doctrine;
use Wms\Module\Web\Grid;

class MotivoMovimentacao extends Grid
{
    public function init()
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select("
            mm.id, 
            mm.descricao, 
            p.nome,
            TO_CHAR(mm.dthCriacao, 'DD/MM/YYYY HH24:MI:SS') as dthCriacao
            ")
            ->from("wms:Enderecamento\MotivoMovimentacao", "mm")
            ->innerJoin("mm.usuarioCriacao", "u")
            ->innerJoin("u.pessoa", 'p')
            ->where("mm.isAtivo > 0");

        $this->setSource(new Doctrine($qb))
            ->setId('motivos-movimentacao-grid')
            ->setAttrib('caption', 'Motivos de Movimentação')
            ->addColumn([
                'label' => "Id",
                'index' => "id"
            ])
            ->addColumn([
                'label' => 'Descrição',
                'index' => 'descricao'
            ])
            ->addColumn([
                'label' => "Criado por",
                'index' => 'nome'
            ])
            ->addColumn([
                'label' => "Criado em",
                'index' => 'dthCriacao'
            ])
            ->addAction([
                'label' => 'Editar',
                'moduleName' => 'web',
                'controllerName' => 'motivo-movimentacao',
                'actionName' => 'edit',
                'pkIndex' => 'id'
            ])
            ->addAction([
                'label' => 'Remover',
                'moduleName' => 'web',
                'controllerName' => 'motivo-movimentacao',
                'actionName' => 'delete',
                'pkIndex' => 'id',
                'condition' => function ($row) {
                    return ($row['id'] > 1);
                }
            ]);

        $this->setShowExport(false);
        return $this;
    }
}