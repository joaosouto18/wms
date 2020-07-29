<?php

namespace Wms\Module\Web\Grid\Expedicao;


use Core\Grid\Pager;
use Wms\Domain\Entity\Expedicao\ConfCarregVolume;
use \Core\Grid\Source\ArraySource;
use Wms\Module\Web\Grid;

class ConfCarreg extends Grid
{
    public function init($idExpedicao)
    {

        $confCarreg = $this->getEntityManager()->getRepository(ConfCarregVolume::class)->getDetalheConfCarreg($idExpedicao);
        $this->setShowPager(false);
        $this->setShowExport(true);
        $this->setSource(new ArraySource($confCarreg))
            ->setId('conf-carreg-grid')
            ->setAttrib('caption', 'Conferência Carregamento')
            ->addColumn(array(
                'label' => 'Cliente',
                'index' => 'NOM_PESSOA',
            ))
            ->addColumn(array(
                'label' => 'Tipo de Volume',
                'index' => 'TIPO_VOL',
            ))
            ->addColumn(array(
                'label' => 'Cod. Volume',
                'index' => 'ID_VOLUME',
            ))
            ->addColumn(array(
                'label' => 'Status',
                'index' => 'STATUS',
            ));

        $pager = new \Core\Grid\Pager($this->getNumResults(), $this->getPage(), count($confCarreg));
        $this->setPager($pager);

        return $this;
    }
}

