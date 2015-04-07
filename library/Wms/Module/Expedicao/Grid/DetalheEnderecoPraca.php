<?php

namespace Wms\Module\Expedicao\Grid;

use Wms\Domain\Entity\Ressuprimento\OndaRessuprimentoOs;
use Wms\Module\Web\Grid,
    Wms\Domain\Entity\Expedicao as ExpedicaoEntity;

class DetalheEnderecoPraca extends Grid
{
    /**
     *
     * @param array $params 
     */
    public function init(array $gridValues = array(), array $formParamas = array())
    {
        $this->setSource(new \Core\Grid\Source\ArraySource($gridValues))
                ->setId('detalhe-endereco-praca')
                ->setAttrib('class', 'grid-expedicao')
                ->addColumn(array(
                    'label' => 'Código',
                    'index' => 'codCliente',
                ))
                ->addColumn(array(
                    'label' => 'Nome',
                    'index' => 'nome',
                ))
                ->addColumn(array(
                    'label' => 'Cidade',
                    'index' => 'cidade',
                ))
                ->addColumn(array(
                    'label' => 'Bairro',
                    'index' => 'bairro',
                ))
                ->addColumn(array(
                    'label' => 'Praça',
                    'index' => 'praca',
                ))
                ->addColumn(array(
                    'label' => 'UF',
                    'index' => 'estado',
                ))
                ->addAction(array(
                    'label' => 'Detalhamento Endereço',
                    'target' => '_blank',
                    'modelName' => 'expedicao',
                    'controllerName' => 'cliente',
                    'actionName' => 'detalhe-endereco-praca',
                    'pkIndex' => 'codCliente',
                    'cssClass' => 'dialogAjax',
                ))

                ->setShowExport(true);

        return $this;
    }


}

