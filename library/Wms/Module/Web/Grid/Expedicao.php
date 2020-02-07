<?php

namespace Wms\Module\Web\Grid;

use Doctrine\ORM\EntityManager;
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
        $permissaoEn = $this->getEntityManager()->getRepository('wms:Sistema\Parametro')->findOneBy(array('constante' => 'PERMITE_REALIZAR_CORTES_WMS'));
        $permite = (!empty($permissaoEn) && $permissaoEn->getValor() == "N") ? false : true;

        $sessao = new \Zend_Session_Namespace('deposito');
        $params['centrais'] = $sessao->centraisPermitidas;

        $deposito = $this->getEntityManager()->getReference('wms:Deposito', $sessao->idDepositoLogado);
        $central = $deposito->getFilial()->getCodExterno();

        $result = $expedicaoRepo->buscar($params, $sessao->codFilialExterno);

        $this->setAttrib('title','Expedição');
        $source = $this->setSource(new \Core\Grid\Source\ArraySource($result));
        $source->setId('expedicao-index-grid')
            ->setAttrib('class', 'grid-expedicao')
            ->addColumn(array(
                'label' => 'Expedição',
                'index' => 'id',
            ))
            ->addColumn(array(
                'label' => 'Cargas',
                'index' => 'carga',
            ))
            ->addColumn(array(
                'label' => 'Tipo Pedido',
                'index' => 'tipopedido',
            ))
            ->addColumn(array(
                'label' => 'Cubagem',
                'index' => 'cubagem',
                'render' => 'N3'
            ))
            ->addColumn(array(
                'label' => 'Peso',
                'index' => 'peso',
                'render' => 'N3'
            ))
            ->addColumn(array(
                'label' => 'Qtd.Ped.',
                'index' => 'qtdPedidos',
                'render' => 'N0'
            ))
            ->addColumn(array(
                'label' => 'Placa',
                'index' => 'placaExpedicao'
            ))

            ->addColumn(array(
                'label' => 'Itinerarios',
                'index' => 'itinerario',
            ))
            ->addColumn(array(
                'label' => 'Motorista',
                'index' => 'motorista',
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
                'label' => 'Imprimir',
                'index' => 'imprimir',
            ))
            ->addColumn(array(
                'label' => '% Conferência',
                'index' => 'PercConferencia',
            ))
            ->addColumn(array(
                'label' => 'Status',
                'index' => 'status',
            ))
            ->addAction(array(
                'label' => 'Gerenciar Conferência',
                'moduleName' => 'expedicao',
                'controllerName' => 'os',
                'actionName' => 'index',
                'pkIndex' => 'id',
                'condition' => function ($row) {
                    return $row['status'] != "INTEGRADO";
                }
            ))
            ->addAction(array(
                'label' => 'Equipe de Carregamento',
                'moduleName' => 'produtividade',
                'controllerName' => 'carregamento',
                'actionName' => 'index',
                'pkIndex' => 'id',
                'condition' => function ($row) {
                        return $row['status'] != "INTEGRADO";
                    }
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
                'label' => 'Consultar Peso',
                'modelName' => 'expedicao',
                'controllerName' => 'index',
                'actionName' => 'consultarpeso',
                'cssClass' => 'dialogAjax',
                'pkIndex' => 'id'
            ))
            ->addAction(array(
                'label' => 'Agrupar Cargas',
                'modelName' => 'expedicao',
                'controllerName' => 'agrupar-cargas',
                'actionName' => 'index',
                'cssClass' => 'dialogAjax',
                'pkIndex' => 'id'
            ));
        if ($permite) {
            $source->addAction(array(
                'label' => 'Cortar Etiqueta',
                'moduleName' => 'expedicao',
                'controllerName' => 'corte',
                'actionName' => 'index',
                'pkIndex' => 'id',
                'cssClass' => 'dialogAjax',
                'condition' => function ($row) {
                    return $row['status'] != "FINALIZADO" AND $row['status'] != "INTEGRADO";
                }
            ))->addAction(array(
                'label' => 'Habilitar Corte ERP',
                'moduleName' => 'expedicao',
                'controllerName' => 'corte',
                'actionName' => 'habilita-corte-erp',
                'pkIndex' => 'id',
                'cssClass' => 'habilitar-corte',
                'params' => array('origin' => 'expedicao'),
                'condition' => function ($row) {
                    return $row['status'] != "FINALIZADO";
                }
            ))->addAction(array(
                'label' => 'Cortar Pedido',
                'moduleName' => 'expedicao',
                'controllerName' => 'corte',
                'actionName' => 'corte-pedido',
                'pkIndex' => 'id',
                'params' => array('origin' => 'expedicao'),
                'cssClass' => 'dialogAjax',
                'condition' => function ($row) {
                    return $row['status'] != "FINALIZADO";
                }
            ))->addAction(array(
                'label' => 'Cortar Produto',
                'moduleName' => 'expedicao',
                'controllerName' => 'corte',
                'actionName' => 'corte-produto',
                'pkIndex' => 'id',
                'params' => array('origin' => 'expedicao'),
                'cssClass' => 'dialogAjax',
                'condition' => function ($row) {
                    return $row['status'] != "FINALIZADO";
                }
            ));
        }
        $source->addAction(array(
                'label' => 'Imprimir',
                'modelName' => 'expedicao',
                'controllerName' => 'etiqueta',
                'actionName' => 'index',
                'params' => array('urlAction' => 'imprimir', 'urlController' => 'etiqueta', 'sc' => true),
                'cssClass' => 'dialogAjax pdf',
                'condition' => function ($row) {
                        return $row['imprimir'] == "SIM";
                    },
                'pkIndex' => 'id'
            ))
            ->addAction(array(
                'label' => 'Imprimir Produtos sem cod. barras',
                'controllerName' => 'etiqueta',
                'actionName' => 'gerar-etiqueta-produto-ajax',
                'cssClass' => 'pdf',
                'pkIndex' => 'id'
            ))
            ->addAction(array(
                'label' => 'Reimprimir Etiqueta',
                'modelName' => 'expedicao',
                'controllerName' => 'etiqueta',
                'actionName' => 'verificar-reimpressao-ajax',
                'cssClass' => 'dialogAjax pdf',
                'condition' => function ($row) {
                        return $row['status'] != "FINALIZADO" AND $row['status'] != "INTEGRADO" AND $row['status'] != "CANCELADO";
                    },
                'pkIndex' => 'id'
            ))
            ->addAction(array(
                'label' => 'Reimprimir Mapa de Separação',
                'modelName' => 'expedicao',
                'controllerName' => 'etiqueta',
                'actionName' => 'reimprimir-mapa',
                'condition' => function($row) {
                    return $row['status'] != "INTEGRADO" AND $row['status'] != "CANCELADO";
                },
                'pkIndex' => 'id'
            ))
            ->addAction(array(
                'label' => 'Reimprimir Volume Embalado',
                'ModelName' => 'expedicao',
                'controllerName' => 'etiqueta',
                'actionName' => 'reimprimir-embalados',
                'pkIndex' => 'id'
            ))
            ->addAction(array(
                'label' => 'Imprimir Volume Patrimonio',
                'modelName' => 'expedicao',
                'controllerName' => 'volume-patrimonio',
                'actionName' => 'imprimir-volume-patrimonio',
                'pkIndex' => 'id',
                'cssClass' => 'dialogAjax',
                'condition' => function($row) {
                    return $row['status'] != "EM SEPARACAO" AND $row['status'] != "INTEGRADO" AND $row['status'] != "CANCELADO";
                },
            ))
            ->addAction(array(
                'label' => 'Relatório de itens embalados',
                'modelName' => 'expedicao',
                'controllerName' => 'mapa',
                'actionName' => 'relatorio-itens-volume-embalado',
                'pkIndex' => 'id',
                'cssClass' => 'pdf',
                'condition' => function($row) {
                    return $row['status'] != "EM SEPARACAO" AND $row['status'] != "INTEGRADO" AND $row['status'] != "CANCELADO";
                },
            ))
            ->addAction(array(
                'label' => 'Relatório de Reentregas',
                'modelName' => 'expedicao',
                'controllerName' => 'etiqueta',
                'actionName' => 'gerar-pdf-ajax',
                'params' => array('tipo' => 'relatorio-reentrega','central'=>'','todas'=>'S'),
                'cssClass' => 'pdf',
                'condition' => function ($row) {
                    return ($row['reentrega'] > 0) and ($row['imprimir'] != "SIM") ;
                },
                'pkIndex' => 'id'
            ))
            ->addAction(array(
                'label' => 'Relatório de Produtos',
                'target' => '_blank',
                'modelName' => 'expedicao',
                'controllerName' => 'relatorio_produtos-expedicao',
                'actionName' => 'index',
                'params' => array('central' => $central),
                'cssClass' => 'pdf',
                'pkIndex' => 'id'
            ))
            ->addAction(array(
                'label' => 'Relatório Cod. Bar. Produtos',
                'target' => '_blank',
                'moduleName' => 'expedicao',
                'controllerName' => 'index',
                'actionName' => 'relatorio-codigo-barras-produtos',
                'cssClass' => 'pdf',
                'pkIndex' => 'id'
            ))
            ->addAction(array(
                'label' => 'Relatórios de Carregamento',
                'modelName' => 'expedicao',
                'controllerName' => 'index',
                'actionName' => 'relatorios-carregamento-ajax',
                'cssClass' => 'dialogAjax relatorio-carregamento',
                'pkIndex' => 'id'
            ))
            ->addAction(array(
                'label' => 'Relatório de Volumes Embalados',
                'modelName' => 'expedicao',
                'controllerName' => 'index',
                'actionName' => 'relatorio-volume-embalado-ajax',
                'condition' => function ($row) {
                    return $row['status'] == "FINALIZADO";
                },
                'pkIndex' => 'id',
                'cssClass' => 'pdf'
            ))
            ->addAction(array(
                'label' => 'Cancelar Expedição',
                'moduleName' => 'expedicao',
                'controllerName' => 'index',
                'actionName' => 'cancelar-expedicao-ajax',
                'cssClass' => 'cancelar',
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
                'label' => 'Relatório de Carregamento',
                'modelName' => 'expedicao',
                'controllerName' => 'relatorio_carregamento',
                'actionName' => 'imprimir',
                'cssClass' => 'pdf',
                'pkIndex' => 'id'
            ))
            ->addAction(array(
                'label' => 'Relatório de Volumes Patrimônio',
                'target' => '_blank',
                'moduleName' => 'expedicao',
                'controllerName' => 'index',
                'actionName' => 'imprimir',
                'cssClass' => 'pdf',
                'pkIndex' => 'id'
            ))
            ->addAction(array(
                'label' => 'Relatório de Corte Automático',
                'target' => '_blank',
                'moduleName' => 'expedicao',
                'controllerName' => 'corte',
                'actionName' => 'relatorio-corte-ajax',
                'cssClass' => 'pdf',
                'pkIndex' => 'id'
            ));

        if ($params['usaDeclaracaoVP'] === 'S'){
            $source->addAction(array(
                'label' => 'Declaração dos Volumes Patrimônio',
                'target' => '_blank',
                'moduleName' => 'expedicao',
                'controllerName' => 'index',
                'actionName' => 'declaracao-ajax',
                'cssClass' => 'pdf',
                'pkIndex' => 'id'
            ));
        }

        $source->setShowExport(true)
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

