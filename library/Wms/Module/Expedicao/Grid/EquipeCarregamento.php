<?php

namespace Wms\Module\Expedicao\Grid;

use Wms\Module\Web\Grid;

class EquipeCarregamento extends Grid
{
    public function init(array $params = array())
    {
        /** @var \Wms\Domain\Entity\Expedicao\EquipeCarregamentoRepository $equipeCarregamentoRepo */
        $equipeCarregamentoRepo = $this->getEntityManager()->getRepository('wms:Expedicao\EquipeCarregamento');

        $result = $equipeCarregamentoRepo->getEquipeCarregamento($params);
        $this->setAttrib('title','Equipe-Carregamento');
        $this->setSource(new \Core\Grid\Source\ArraySource($result))
                ->addColumn(array(
                    'label' => utf8_encode('Expedição'),
                    'index' => 'expedicao',
                ))
                ->addColumn(array(
                    'label' => 'Nome',
                    'index' => 'nome',
                ))
                ->addColumn(array(
                    'label' => 'Data Vinculo',
                    'index' => 'dataVinculo',
                    'render'=> 'DataTime'
                ));

        $this->setShowExport(false);

        return $this;
    }

}

