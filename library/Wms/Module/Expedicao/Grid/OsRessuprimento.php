<?php

namespace Wms\Module\Expedicao\Grid;

use Wms\Domain\Entity\Ressuprimento\OndaRessuprimentoOs;
use Wms\Module\Web\Grid,
    Wms\Domain\Entity\Expedicao as ExpedicaoEntity;

/**
 * Grid da Página Inicial da Expedição
 *
 * @author Lucas Chinelate <lucaschinelate@hotmail.com>
 */
class OsRessuprimento extends Grid
{
    /**
     *
     * @param array $params
     */
    public function init(array $gridValues = array(), array $formParamas = array(), $utilizaGrade = 'N')
    {


        foreach ($gridValues as $row => $value) {
            $gridValues[$row]['id'] = $value['ID'];
        }
        $this->setAttrib('title','OS de Ressuprimento');
        $this->setSource(new \Core\Grid\Source\ArraySource($gridValues))
            ->setId('expedicao-os-grid')
            ->setAttrib('class', 'grid-expedicao')
            ->addColumn(array(
                'label' => 'Os',
                'index' => 'ID',
            ))
            ->addColumn(array(
                'label' => 'Onda',
                'index' => 'ONDA',
            ))
            ->addColumn(array(
                'label' => 'Dt.Criação',
                'index' => 'DT. CRIACAO',
            ))
            ->addColumn(array(
                'label' => 'Cod.',
                'index' => 'COD.',
            ));
        if ($utilizaGrade == 'S') {
            $this->addColumn(array(
                'label' => 'Grade',
                'index' => 'GRADE',
            ));
        }
            $this->addColumn(array(
                'label' => 'Produto',
                'index' => 'PRODUTO',
            ))
            ->addColumn(array(
                'label' => 'Volumes',
                'index' => 'VOLUMES',
//                'width' => 10
            ))
            ->addColumn(array(
                'label' => 'Qtd.',
                'index' => 'QTD',
            ))
            ->addColumn(array(
                'label' => 'Pulmão',
                'index' => 'PULMAO',
            ))
            ->addColumn(array(
                'label' => 'Picking',
                'index' => 'PICKING',
            ))
            ->addColumn(array(
                'label' => 'Status',
                'index' => 'STATUS',
            ))
            ->addColumn(array(
                'label' => 'Responsável',
                'index' => 'NOM_PESSOA'
            ))
            ->addColumn(array(
                'label' => 'Cod Barras',
                'index' => 'COD_BARRAS',
            ))
            ->addAction(array(
                'label' => 'Cancelar O.S.',
                'modelName' => 'expedicao',
                'controllerName' => 'onda-ressuprimento',
                'actionName' => 'cancelar',
                'params'=>$formParamas,
                'pkIndex' => 'ID',
                'cssClass' => 'edit confirm',
                'title' => 'Confirma cancelamento desta Ordem de Serviço?',
                'condition' => function ($row) {
                    return $row['COD_STATUS'] ==  OndaRessuprimentoOs::STATUS_DIVERGENTE;
                }
            ))
            ->addAction(array(
                'label' => 'Liberar Os',
                'modelName' => 'expedicao',
                'controllerName' => 'onda-ressuprimento',
                'actionName' => 'liberar',
                'pkIndex' => 'ID',
                'params'=>$formParamas,
                'condition' => function ($row) {
                    return $row['COD_STATUS'] == OndaRessuprimentoOs::STATUS_DIVERGENTE;
                }
            ))
            ->addAction(array(
                'label' => 'Efetivar Ressuprimento',
                'modelName' => 'expedicao',
                'controllerName' => 'onda-ressuprimento',
                'actionName' => 'finalizar',
                'pkIndex' => 'ID',
                'params'=>$formParamas,
                'cssClass' => 'edit confirm',
                'title' => 'Confirma efetivação desta Ordem de Serviço?',
                'condition' => function ($row) {
                    return $row['COD_STATUS'] == OndaRessuprimentoOs::STATUS_ONDA_GERADA;
                }
            ))
            ->addAction(array(
                'label' => 'Andamento Ressuprimento',
                'controllerName' => 'onda-ressuprimento',
                'actionName' => 'list',
                'pkIndex' => 'ID',
                'cssClass' => 'dialogAjax',
                'title' => 'Visualizar Andamento'
            ))
            ->addMassAction('finalizar', 'Efetivar Ressuprimento')
            ->setShowExport(true);

        return $this;
    }


}

