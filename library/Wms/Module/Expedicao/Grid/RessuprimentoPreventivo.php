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
class RessuprimentoPreventivo extends Grid {

    /**
     *
     * @param array $params
     */
    public function init(array $gridValues = array()) {
        $this->setAttrib('title', 'Ressuprimento Preventivo');
        $this->setSource(new \Core\Grid\Source\ArraySource($gridValues))
                ->setId('ressuprimento-preventivo-grid')
                ->setAttrib('class', 'grid-expedicao')
                ->addColumn(array(
                    'label' => '<input class="checkBoxClass" type="checkbox" name="check-all" id="check-all">',
                    'index' => 'COD_DEPOSITO_ENDERECO',
                    'render' => 'Checkbox'
                ))
                ->addColumn(array(
                    'label' => 'Produto',
                    'index' => 'COD_PRODUTO'
                ))
                ->addColumn(array(
                    'label' => 'Embalagens',
                    'index' => 'EMBALAGENS',
                    'render' => 'Hidden'
                ))
                ->addColumn(array(
                    'label' => 'Volumes',
                    'index' => 'VOLUMES',
                    'render' => 'Hidden'
                ))
                ->addColumn(array(
                    'label' => 'Grade',
                    'index' => 'DSC_GRADE',
                ))
                ->addColumn(array(
                    'label' => 'Volume',
                    'index' => 'PRODUTO_VOLUME',
                ))
                ->addColumn(array(
                    'label' => 'Capacidade',
                    'index' => 'CAPACIDADE_PICKING',
                ))
                ->addColumn(array(
                    'label' => 'Saldo',
                    'index' => 'SALDO_PICKING',
                ))
                ->addColumn(array(
                    'label' => 'Ocupação',
                    'index' => 'OCUPACAO',
                    'width' => 10
                ))
                ->addColumn(array(
                    'label' => 'Picking',
                    'index' => 'DSC_DEPOSITO_ENDERECO',
                ))
                ->setShowExport(true);

        return $this;
    }

}
