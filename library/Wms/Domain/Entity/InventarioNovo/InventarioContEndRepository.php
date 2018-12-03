<?php
/**
 * Created by PhpStorm.
 * User: Joaby
 * Date: 27/11/2018
 * Time: 09:15
 */

namespace Wms\Domain\Entity\InventarioNovo;

use Doctrine\ORM\EntityRepository;
use Wms\Domain\Entity\InventarioNovo;

class InventarioContEndRepository extends EntityRepository
{
    /**
     * @return InventarioContEnd
     * @throws \Exception
     */
    public function save() {

        $this->_em->beginTransaction();
        try {

            $enInventarioContEnd = new InventarioContEnd();

            $codEndereco = $this->_em->getReference('wms:InventarioNovo\InventarioEndereco',$params['idInventarioEndereco']);

            $enInventarioContEnd->setInventarioEndereco($codEndereco);
            $enInventarioContEnd->setContagem(1);

            $this->_em->persist($enInventarioContEnd);
            $this->_em->flush();
            $this->_em->commit();
        } catch (\Exception $e) {
            $this->_em->rollback();
            throw new \Exception($e->getMessage());
        }

        return $enInventarioEndereco;
    }
}