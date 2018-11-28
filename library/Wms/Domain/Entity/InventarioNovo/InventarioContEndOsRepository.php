<?php
/**
 * Created by PhpStorm.
 * User: Joaby
 * Date: 26/11/2018
 * Time: 17:23
 */

namespace Wms\Domain\Entity\InventarioNovo;

use Doctrine\ORM\EntityRepository;
use Wms\Domain\Entity\InventarioNovo;

class InventarioContEndOsRepository extends EntityRepository
{
    /**
     * @return InventarioContEndOs
     * @throws \Exception
     */
    public function save() {
        $em = $this->getEntityManager();

        $em->beginTransaction();
        try {

            $enInventarioContEndOs = new InventarioContEndOs();

            $codContEnd = $em->getReference('wms:InventarioNovo\InventarioContEnd',$params['idInvContEnd']);
            $codOs      = $em->getReference('wms:OrdemServico',$params['idOs']);

            $enInventarioContEndOs->setInvContEnd($codContEnd);
            $enInventarioContEndOs->setCodOs($codOs);


            $em->persist($enInventarioContEndOs);
            $em->flush();
            $em->commit();
        } catch (\Exception $e) {
            $em->rollback();
            throw new \Exception($e->getMessage());
        }

        return $enInventarioEndereco;
    }
}