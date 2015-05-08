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
        if (empty($params)) {
            $result = array();
        } else {
            /** @var \Wms\Domain\Entity\Expedicao\EtiquetaSeparacaoRepository $etiquetaSeparacaoRepo */
            $etiquetaSeparacaoRepo = $this->getEntityManager()->getRepository('wms:Expedicao\EtiquetaSeparacao');
            $result = $etiquetaSeparacaoRepo->buscarEtiqueta($params);
        }

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
                'index' => 'produto',
            ))
            ->addColumn(array(
                'label' => 'Grade',
                'index' => 'dscGrade',
            ))
            ->addColumn(array(
                'label' => 'Produto',
                'index' => 'descricao',
            ))
            ->addColumn(array(
                'label' => 'Embalagem',
                'index' => 'embalagem',
            ))
            ->addColumn(array(
                'label' => 'Situação',
                'index' => 'sigla',
            ))
            ->addAction(array(
                'label' => 'Dados da Etiqueta',
                'target' => '_blank',
                'modelName' => 'expedicao',
                'controllerName' => 'etiqueta',
                'actionName' => 'dados-etiqueta',
                'cssClass' => 'dialogAjax',
                'pkIndex' => 'id'
            ))
            ->setShowExport(true)
            ->setShowMassActions($params);;

        return $this;
    }

}

