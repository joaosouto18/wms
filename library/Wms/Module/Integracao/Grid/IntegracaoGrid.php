<?php


namespace Wms\Module\Integracao\Grid;


use Doctrine\ORM\EntityManager;
use Wms\Domain\Entity\Integracao\AcaoIntegracao;
use Wms\Module\Web\Grid;

class IntegracaoGrid extends Grid
{
    public function init()
    {

        $dql = $this->getEntityManager()->createQueryBuilder()
            ->select('i, c.id codConexao')
            ->from(AcaoIntegracao::class, 'i')
            ->innerJoin('i.conexao', 'c')
            ->orderBy('i.id');

        $this->setAttribs(['title' => 'Gerenciamento de Integrações'])
            ->setSource(new \Core\Grid\Source\Doctrine($dql))
            ->setShowExport(false)
            ->addColumn([
                'label' => 'nº',
                'index' => 'id'
            ])
            ->addColumn([
                'label' => 'Conexão',
                'index' => 'codConexao'
            ])
            ->addColumn([
                'label' => 'Descrição',
                'index' => 'dscAcaoIntegracao'
            ])
            ->addColumn([
                'label' => 'Ultima Execução',
                'index' => 'dthUltimaExecucao',
                'render' => 'DataTime'
            ])
            ->addColumn([
                'label' => 'Registra LOG',
                'index' => 'indUtilizaLog',
                'render' => 'SimOrNao'
            ])
            ->addColumn([
                'label' => 'Em Execução',
                'index' => 'indExecucao',
                'render' => 'SimOrNao'
            ])
            ->addAction([
                'label' => 'Visualizar',
                'moduleName' => 'integracao',
                'controllerName' => 'gerenciamento',
                'actionName' => 'view-detail-integracao-ajax',
                'cssClass' => 'dialogAjax',
                'pkIndex' => 'id'
            ])
            ->addAction([
                'label' => 'Editar',
                'moduleName' => 'integracao',
                'controllerName' => 'gerenciamento',
                'actionName' => 'acao-integracao-form',
                'pkIndex' => 'id'
            ])
            ->addAction([
                'label' => 'Desligar LOG',
                'moduleName' => 'integracao',
                'controllerName' => 'gerenciamento',
                'actionName' => 'toggle-log-integracao-ajax',
                'pkIndex' => 'id',
                'cssClass' => 'cross',
                'params' => ['status'=> 'N'],
                'condition' => function ($row) { return $row['indUtilizaLog'] == 'S';}
            ])
            ->addAction([
                'label' => 'Ativar LOG',
                'moduleName' => 'integracao',
                'controllerName' => 'gerenciamento',
                'actionName' => 'toggle-log-integracao-ajax',
                'pkIndex' => 'id',
                'cssClass' => 'registry',
                'params' => ['status'=> 'S'],
                'condition' => function ($row) { return $row['indUtilizaLog'] == 'N';}
            ])
            ->addAction([
                'label' => 'Resetar Execução',
                'moduleName' => 'integracao',
                'controllerName' => 'gerenciamento',
                'actionName' => 'refresh-exec-integracao-ajax',
                'pkIndex' => 'id',
                'cssClass' => 'refresh',
                'condition' => function ($row) { return $row['indExecucao'] == 'S';}
            ])
        ;

        return $this;
    }
}