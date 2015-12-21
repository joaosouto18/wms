<?php

namespace Wms\Module\Web\Grid;

use Wms\Module\Web\Grid,
    Wms\Domain\Entity\Expedicao as ExpedicaoEntity;

/**
 * Grid da Página Inicial do Relatório de Pedidos da Expedição
 *
 * @author Michel Castro <mlaguardia@gmail.com>
 */
class RelatorioPedidosExpedicao extends Grid
{
    /**
     *
     * @param array $params 
     */

    public function init(array $params = array())
    {

        /** @var \Wms\Domain\Entity\RelatoriosSimplesRepository $relatorioSimplesRepo */
        $relatorioSimplesRepo = $this->getEntityManager()->getRepository('wms:RelatoriosSimples');


        $result = $relatorioSimplesRepo->getConsultaRelatorioPedidosExpedicao($params);
        $this->setAttrib('title','Pedidos Expedicao');
        $this->setSource(new \Core\Grid\Source\ArraySource($result))
                ->setId('expedicao-index-grid')
                ->setAttrib('class', 'grid-expedicao')
                ->addColumn(array(
                    'label' => '',
                    'render' => "Checkbox",
                    'index'=>'Expedicao',
                    'width'=>'25',
                ))
                ->addColumn(array(
                    'label' => 'Expedicao',
                    'index' => 'Expedicao',
                ))

                ->addColumn(array(
                    'label' => 'Data de Início',
                    'index' => 'DataInicio',
                    'render'=>'Data',
                ))
                ->addColumn(array(
                    'label' => 'Data de Finalização',
                    'index' => 'DataFim',
                    'render'=>'Data',
                ))
                ->addColumn(array(
                    'label' => 'Placa',
                    'index' => 'placaExpedicao',
                ))
                ->addColumn(array(
                    'label' => 'Status',
                    'index' => 'sigla',
                ))
                /*->addColumn(array(
                    'label' => 'Quantidade',
                    'index' => 'Quantidade',
                ))
                ->addColumn(array(
                    'label' => 'Endereco',
                    'index' => 'Endereco',
                ))*/
                ->setShowExport(false)
                ->setShowMassActions($params);

        return $this;
    }


}

