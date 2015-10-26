<?php

namespace Wms\Module\Web\Grid;

use Wms\Module\Web\Grid;

class Equipamento extends Grid
{

    /**
     *
     * @param array $params 
     */
    public function init(array $params = array())
    {
        /** @var \Wms\Domain\Entity\EquipamentoRepository $equipamentoRepo */
        $equipamentoRepo = $this->getEntityManager()->getRepository('wms:Equipamento');
        $resultSet = $equipamentoRepo->buscar($params);
        $this->setAttrib('title','Equipamento');
        $this->setSource(new \Core\Grid\Source\ArraySource($resultSet))
                ->setId('equipamento-index-grid')
                ->setAttrib('class', 'grid-equipamento')
                ->addColumn(array(
                    'label' => 'Código Equipamento',
                    'index' => 'id',
                ))
                ->addColumn(array(
                    'label' => 'Descrição',
                    'index' => 'descricao',
                ))
                ->addColumn(array(
                    'label' => 'Modelo',
                    'index' => 'modelo',
                ))
                ->addColumn(array(
                    'label' => 'Marca',
                    'index' => 'marca',
                ))
                ->addColumn(array(
                    'label' => 'Patrimonio',
                    'index' => 'patrimonio'
                ))
                ->addAction(array(
                    'label' => 'Editar',
                    'modelName' => 'web',
                    'controllerName' => 'equipamento',
                    'actionName' => 'edit',
                    'pkIndex' => 'id'
                ))
                ->addAction(array(
                    'label' => 'Excluir',
                    'modelName' => 'web',
                    'controllerName' => 'equipamento',
                    'actionName' => 'delete',
                    'pkIndex' => 'id'
                ))
                ->setShowExport(true)
                ->setShowMassActions($params);

        return $this;
    }

}

