<?php

namespace Wms\Module\Web\Grid;

use Wms\Module\Web\Grid,
    Wms\Domain\Entity\Expedicao as ExpedicaoEntity;

/**
 * Grid da Página Inicial da Expedição
 *
 * @author Lucas Chinelate <lucaschinelate@hotmail.com>
 */
class Expedicao extends Grid
{
    /**
     *
     * @param array $params 
     */

    public function init(array $params = array())
    {

        /** @var \Wms\Domain\Entity\ExpedicaoRepository $expedicaoRepo */
        $expedicaoRepo = $this->getEntityManager()->getRepository('wms:Expedicao');

        $sessao = new \Zend_Session_Namespace('deposito');
        $params['centrais'] = $sessao->centraisPermitidas;

        $result = $expedicaoRepo->buscar($params, $sessao->codFilialExterno);

        $this->setSource(new \Core\Grid\Source\ArraySource($result))
                ->setId('expedicao-index-grid')
                ->setAttrib('class', 'grid-expedicao')
                ->addColumn(array(
                    'label' => 'Código da Expedição',
                    'index' => 'id',
                ))
                ->addColumn(array(
                    'label' => 'Placa',
                    'index' => 'placaExpedicao',
                ))
                ->addColumn(array(
                    'label' => 'Cargas',
                    'index' => 'carga',
                ))
                ->addColumn(array(
                    'label' => 'Itinerarios',
                    'index' => 'itinerario',
                ))
                ->addColumn(array(
                    'label' => 'Data Inicial',
                    'index' => 'dataInicio',
                ))
                ->addColumn(array(
                    'label' => 'Data Final',
                    'index' => 'dataFinalizacao',
                ))

                ->addColumn(array(
                    'label' => 'Produtos Sem Etiquetas',
                    'index' => 'prodSemEtiqueta',
                ))
                ->addColumn(array(
                    'label' => 'Status',
                    'index' => 'status',
                ))

                ->addAction(array(
                    'label' => 'Imprimir Etiquetas',
                    'modelName' => 'expedicao',
                    'controllerName' => 'etiqueta',
                    'actionName' => 'index',
                    'params' => array('urlAction' => 'imprimir', 'urlController' => 'etiqueta', 'sc' => true),
                    'cssClass' => 'dialogAjax pdf',
                    'condition' => function ($row) {
                        return $row['status'] != "FINALIZADO";
                    },
                    'pkIndex' => 'id'
                ))

                ->addAction(array(
                    'label' => 'Reimprimir Etiqueta',
                    'modelName' => 'expedicao',
                    'controllerName' => 'etiqueta',
                    'actionName' => 'reimprimir',
                    'condition' => function ($row) {
                        return $row['status'] != "FINALIZADO" AND $row['status'] != "INTEGRADO" AND $row['status'] != "CANCELADO";
                    },
                    'pkIndex' => 'id'
                ))

                ->addAction(array(
                    'label' => 'Relatório de Produtos',
                    'target' => '_blank',
                    'modelName' => 'expedicao',
                    'controllerName' => 'etiqueta',
                    'actionName' => 'index',
                    'params' => array('urlAction' => 'index', 'urlController' => 'relatorio_produtos-expedicao', 'sc' => true),
                    'cssClass' => 'dialogAjax pdf',
                    'pkIndex' => 'id'
                ))
                ->addAction(array(
                    'label' => 'Relatório de Produtos sem Estoque',
                    'target' => '_blank',
                    'modelName' => 'expedicao',
                    'controllerName' => 'index',
                    'actionName' => 'sem-estoque-report',
                    'cssClass' => 'pdf',
                    'pkIndex' => 'id'
                ))
                ->addAction(array(
                    'label' => 'Relatório de Produtos sem Etiquetas',
                    'modelName' => 'expedicao',
                    'controllerName' => 'etiqueta',
                    'actionName' => 'index',
                    'params' => array('urlAction' => 'sem-dados', 'urlController' => 'etiqueta'),
                    'cssClass' => 'dialogAjax pdf',
                    'pkIndex' => 'id',
                    'condition' => function ($row) {
                        return $row['prodSemEtiqueta'] > 0;
                    }
                ))
                ->addAction(array(
                    'label' => 'Relatório de Carregamento',
                    'modelName' => 'expedicao',
                    'controllerName' => 'relatorio_carregamento',
                    'actionName' => 'imprimir',
                    'cssClass' => 'pdf',
                    'pkIndex' => 'id'
                ))
                ->addAction(array(
                    'label' => 'Finalizar Conferência Expedição',
                    'moduleName' => 'expedicao',
                    'controllerName' => 'conferencia',
                    'actionName' => 'index',
                    'cssClass' => 'dialogAjax',
                    'params' => array('origin' => 'expedicao'),
                    'condition' => function ($row) {
                        return $row['status'] != "FINALIZADO" AND  $row['status'] != "CANCELADO" AND $row['status'] != "INTEGRADO";
                    },
                    'pkIndex' => 'id'
                ))
                ->addAction(array(
                    'label' => 'Gerar Cortes',
                    'moduleName' => 'expedicao',
                    'controllerName' => 'corte',
                    'actionName' => 'index',
                    'pkIndex' => 'id',
                    'cssClass' => 'dialogAjax',
                    'condition' => function ($row) {
                        return $row['status'] != "FINALIZADO" AND $row['status'] != "INTEGRADO";
                    }
                ))
                ->addAction(array(
                    'label' => 'Gerenciar Conferencia',
                    'moduleName' => 'expedicao',
                    'controllerName' => 'os',
                    'actionName' => 'index',
                    'pkIndex' => 'id',
                    'condition' => function ($row) {
                        return $row['status'] != "INTEGRADO";
                    }
                ))
                ->setShowExport(true)
                ->setShowMassActions($params);

        return $this;
    }

    /**
     * @param $result
     * @param $expedicaoRepo
     * @return mixed
     */
    public function formatItinerarios($result, $expedicaoRepo)
    {
        $colItinerario = array();
        foreach ($result as $key => $expedicao) {
            $itinerarios = $expedicaoRepo->getItinerarios($result[$key]['id']);
            foreach ($itinerarios as $itinerario) {
                if (!is_numeric($itinerario['id'])) {
                    $colItinerario[] = '(' . $itinerario['id'] . ')' . $itinerario['descricao'];
                } else {
                    $colItinerario[] = $itinerario['descricao'];
                }
            }
            $result[$key]['itinerario'] = implode(', ', $colItinerario);
            unset($colItinerario);
        }
        return $result;
    }

    /**
     * @param $result
     * @param $expedicaoRepo
     * @return mixed
     */
    public function formataCargas($result, $expedicaoRepo)
    {
        $colCarga = array();
        foreach ($result as $key => $expedicao) {
            $cargas = $expedicaoRepo->getCargas($result[$key]['id']);
            foreach ($cargas as $carga) {
                $colCarga[] = $carga->getCodCargaExterno();
            }
            $result[$key]['carga'] = implode(', ', $colCarga);
            unset($colCarga);
        }
        return $result;
    }

}

