<?php

namespace Wms\Domain\Entity\Inventario;

use Doctrine\ORM\EntityRepository;


class ContagemOsRepository extends EntityRepository
{

    /**
     * @return ContagemOS
     * @throws \Exception
     */
    public function save($params)
    {

        if (empty($params['codOs'])) {
            throw new \Exception("codOs não pode ser vazio");
        }

        if (empty($params['codInventario'])) {
            throw new \Exception("codInventario não pode ser vazio");
        }

        $em = $this->getEntityManager();
        $em->beginTransaction();
        try {

            $contagemOsEn = new ContagemOs();

            $inventarioEntity = $em->getReference('wms:Inventario',$params['codInventario']);
            $contagemOsEn->setInventario($inventarioEntity);
            $osEntity = $em->getReference('wms:OrdemServico',$params['codOs']);
            $contagemOsEn->setOs($osEntity);

            $em->persist($contagemOsEn);
            $em->commit();
            $em->flush();

        } catch(\Exception $e) {
            $em->rollback();
            throw new \Exception();
        }

        return $contagemOsEn;
    }

}