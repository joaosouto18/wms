<?php

namespace Wms\Module\Expedicao\Grid;

use Doctrine\ORM\EntityManager;
use Wms\Module\Web\Grid,
    Wms\Domain\Entity\Expedicao as ExpedicaoEntity;

/**
 * Grid da Página Inicial da Expedição
 *
 * @author Lucas Chinelate <lucaschinelate@hotmail.com>
 */
class AcompanhamentoSeparacao extends Grid
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

        $result = $expedicaoRepo->getAcompanhamentoSeparacao($params, $sessao->codFilialExterno);

        $this->setAttrib('title','Expedição');
        $source = $this->setSource(new \Core\Grid\Source\ArraySource($result));
        $source->setId('expedicao-acompanhamento-grid')
            ->setAttrib('class', 'grid-acompanhamento-separacao')
            ->addColumn(array(
                'label' => 'Expedição',
                'index' => 'COD_EXPEDICAO',
            ))->addColumn(array(
                'label' => 'Mapa',
                'index' => 'COD_MAPA_SEPARACAO',
            ))->addColumn(array(
                'label' => 'Quebra',
                'index' => 'QUEBRA',
            ))->addColumn(array(
                'label' => 'Qtd.Prod.',
                'index' => 'QTD_PRODUTOS',
            ))->addColumn(array(
                'label' => 'Cubagem',
                'index' => 'QTD_CUBAGEM',
                'render' => 'N3'
            ))->addColumn(array(
                'label' => 'Peso',
                'index' => 'QTD_PESO',
                'render' => 'N3'
            ))->addColumn(array(
                'label' => '% Conf.',
                'index' => 'PERCENTUAL_CONFERENCIA',
            ))->addColumn(array(
                'label' => '% Sep.',
                'index' => 'PERCENTUAL_SEPARACAO',
            ))->addColumn(array(
                'label' => 'Separador',
                'index' => 'SEPARADOR',
            ))->addColumn(array(
                'label' => 'Produtividade',
                'index' => 'PRODUTIVIDADE_SEPARACAO',
            ))->addColumn(array(
                'label' => 'Status',
                'index' => 'STATUS_EXPEDICAO',
            ))->addAction(array(
                'label' => 'Visualizar Separação',
                'modelName' => 'expedicao',
                'controllerName' => 'os',
                'actionName' => 'consultar-separacao-ajax',
                'cssClass' => 'dialogAjax',
                'pkIndex' => 'COD_MAPA_SEPARACAO'
            ))
        ;

        $source->setShowExport(true)
               ->setShowMassActions($params);

        $pager = new \Core\Grid\Pager(count($result), $this->getPage(), count($result));
        $this->setPager($pager);

        return $this;
    }

}

