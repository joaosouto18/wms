<?php

namespace Wms\Module\Web\Grid;

use Wms\Module\Web\Grid,
    Wms\Domain\Entity\Expedicao as ExpedicaoEntity;

/**
 * Grid da Página Inicial da Expedição
 *
 * @author Fábio Henrique <fabiohf7@gmail.com>
 */
class ConsultaEtiqueta extends Grid
{
    /**
     *
     * @param array $params
     */

    public function init(array $params = array())
    {

        /** @var \Wms\Domain\Entity\Expedicao\EtiquetaSeparacaoRepository $etiquetaSeparacaoRepo */
        $etiquetaSeparacaoRepo = $this->getEntityManager()->getRepository('wms:Expedicao\EtiquetaSeparacao');
        $result = $etiquetaSeparacaoRepo->buscarEtiqueta($params);

        $this->setSource(new \Core\Grid\Source\ArraySource($result))
            ->setId('consulta-etiqueta-index-grid')
            ->setAttrib('class', 'grid-consulta-etiqueta')
            ->addColumn(array(
                'label' => 'Etiqueta',
                'index' => 'id',
            ))
            ->addColumn(array(
                'label' => 'Expedição',
                'index' => 'idExpedicao',
            ))
            ->addColumn(array(
                'label' => 'Carga',
                'index' => 'tipoCarga',
            ))
            ->addColumn(array(
                'label' => 'Cód. Produto',
                'index' => 'codProduto',
            ))
            ->addColumn(array(
                'label' => 'Grade',
                'index' => 'grade',
            ))
            ->addColumn(array(
                'label' => 'Produto',
                'index' => 'produto',
            ))
            ->addColumn(array(
                'label' => 'Embalagem',
                'index' => 'embalagem',
            ))
            ->addColumn(array(
                'label' => 'Situação',
                'index' => 'situacao',
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
            ));

        return $this;
    }

}

