<?php
namespace Wms\Domain\Entity\Expedicao;

use Doctrine\ORM\EntityRepository,
    Wms\Domain\Entity\Expedicao\Andamento;

class MotivoCorteRepository extends EntityRepository
{
    public function getMotivos()
    {
        $result = $this->findAll();
        foreach ($result as $row)
            $rows[$row->getId()] = $row->getDscMotivo();

        return $rows;
    }

}