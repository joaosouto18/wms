<?php

namespace Wms\Module\Web\Grid\Expedicao;
          

use Wms\Domain\Entity\Expedicao\EtiquetaSeparacao;
use Wms\Module\Web\Grid,
    Wms\Domain\Entity\Recebimento;

/**
 * Grid da Página Inicial da Expedição
 *
 * @author Lucas Chinelate <lucaschinelate@hotmail.com>
 */
class ReentregaPendente extends Grid
{
    /**
     * @param $idExpedicao
     * @return $this|void
     */
    public function init($pendencias)
    {
        $this->setAttrib('title','Pendencias Expedição');
        $this->setSource(new \Core\Grid\Source\ArraySource($pendencias))
                ->setId('expedicao-pendencias-grid')
                ->setAttrib('class', 'grid-expedicao-pendencias')
                ->setAttrib('caption', 'Reentregas pendentes de conferencia')
                ->addColumn(array(
                    'label' => 'Etiqueta',
                    'index' => 'ETIQUETA',
                ))
                ->addColumn(array(
                    'label' => 'Produto',
                    'index' => 'COD_PRODUTO',
                ))
                ->addColumn(array(
                    'label' => 'Grade',
                    'index' => 'DSC_GRADE',
                ))
                ->addColumn(array(
                    'label' => 'Descrição',
                    'index' => 'PRODUTO',
                ))                
                ->addColumn(array(
                    'label' => 'Volume',
                    'index' => 'VOLUME',
                ))
                ->addColumn(array(
                    'label' => 'Cliente',
                    'index' => 'CLIENTE',
                ))
                ->setShowExport(true)
                ;

        return $this;
    }

}

