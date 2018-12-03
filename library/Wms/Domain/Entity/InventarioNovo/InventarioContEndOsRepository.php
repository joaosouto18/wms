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

        $this->_em->beginTransaction();
        try {

            $enInventarioContEndOs = new InventarioContEndOs();

            $codContEnd = $this->_em->getReference('wms:InventarioNovo\InventarioContEnd',$params['idInvContEnd']);
            $codOs      = $this->_em->getReference('wms:OrdemServico',$params['idOs']);

            $enInventarioContEndOs->setInvContEnd($codContEnd);
            $enInventarioContEndOs->setCodOs($codOs);

            $this->_em->persist($enInventarioContEndOs);
            $this->_em->flush();
            $this->_em->commit();
        } catch (\Exception $e) {
            $this->_em->rollback();
            throw new \Exception($e->getMessage());
        }

        return $enInventarioEndereco;
    }
}