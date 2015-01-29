<?php

namespace Wms\Module\Web\Grid\Expedicao;
          

use Wms\Module\Web\Grid,
    Wms\Domain\Entity\Expedicao;

/**
 * Grid do Peso de Cargas da Expedição
 *
 * @author Michel Castro <mlaguardia@gmail.com>
 */
class PesoCargas extends Grid
{
    /**
     *
     * @param array $params 
     */
    public function init ($parametros)
    {

        /** @var \Wms\Domain\Entity\ExpedicaoRepository $ExpedicaoRepo */
        $ExpedicaoRepo = $this->getEntityManager()->getRepository('wms:Expedicao');
        $result = $ExpedicaoRepo->getPesos($parametros);

        $grid = new \Core\Grid(new \Core\Grid\Source\ArraySource($result));

        $this->setSource(new \Core\Grid\Source\ArraySource($result))
                ->setId('expedicao-peso-grid')
                ->setAttrib('caption', 'Peso das Cargas Expedição '.$parametros['id'])
                ->setAttrib('class', 'grid-expedicao-peso')
                ->addColumn(array(
                    'label' => 'Carga',
                    'index' => 'COD_CARGA_EXTERNO',
                ))
                ->addColumn(array(
                    'label' => 'Cubagem',
                    'index' => 'NUM_CUBAGEM',
                    'render' => 'N3'
                ))
                ->addColumn(array(
                    'label' => 'Peso Total',
                    'index' => 'PESO_TOTAL',
                    'render' => 'N3'
                ))
                ->addAction(array(
                    'label' => 'Desagrupar Carga',
                    'modelName' => 'expedicao',
                    'controllerName' => 'index',
                    'actionName' => 'desagruparcarga',
                    'pkIndex' => 'COD_CARGA'
                ))
                ->setShowExport(false)
                ;

        return $this;
    }

}

