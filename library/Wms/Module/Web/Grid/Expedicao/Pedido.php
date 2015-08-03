<?php

namespace Wms\Module\Web\Grid\Expedicao;

use Wms\Module\Web\Grid,
    Wms\Domain\Entity\Expedicao as ExpedicaoEntity;

/**
 * Grid da Página Inicial da Expedição
 *
 * @author Lucas Chinelate <lucaschinelate@hotmail.com>
 */
class Pedido extends Grid
{
    /**
     *
     * @param array $params
     */

    public function init(array $params = array())
    {

        /** @var \Wms\Domain\Entity\ExpedicaoRepository $expedicaoRepo */
        $expedicaoRepo = $this->getEntityManager()->getRepository('wms:Expedicao');
        $this->setAttrib('title','Pedidos Expedição');
        $sessao = new \Zend_Session_Namespace('deposito');
        $params['centrais'] = $sessao->centraisPermitidas;

        $result = $expedicaoRepo->getPedidosByParams ($params, $sessao->codFilialExterno);

        $this->setSource(new \Core\Grid\Source\ArraySource($result))
            ->setId('expedicao-index-grid')
            ->setAttrib('class', 'grid-expedicao')
            ->addColumn(array(
                'label' => 'Pedido',
                'index' => 'COD_PEDIDO',
            ))
            ->addColumn(array(
                'label' => 'Cód.Cliente',
                'index' => 'COD_CLIENTE',
            ))
            ->addColumn(array(
                'label' => 'Cliente',
                'index' => 'CLIENTE',
            ))
            ->addColumn(array(
                'label' => 'Qtd.Produtos',
                'index' => 'QTD_PRODUTOS',
            ))
            ->addColumn(array(
                'label' => 'Qtd.Etiquetas',
                'index' => 'ETIQUETAS_GERADAS',
            ))
            ->addColumn(array(
                'label' => 'Expedição',
                'index' => 'COD_EXPEDICAO',
            ))
            ->addColumn(array(
                'label' => 'Carga',
                'index' => 'COD_CARGA_EXTERNO',
            ))
            ->addColumn(array(
                'label' => 'Placa',
                'index' => 'DSC_PLACA_EXPEDICAO',
            ))
            ->addColumn(array(
                'label' => 'Situação',
                'index' => 'DSC_SIGLA',
            ))
            ->addAction(array(
                'label' => 'Detalhar',
                'moduleName' => 'expedicao',
                'controllerName' => 'pedido',
                'actionName' => 'consultar',
                'cssClass' => 'dialogAjax',
                'pkIndex' => 'COD_PEDIDO',
            ))
            ->setShowExport(true);

        return $this;
    }

}

