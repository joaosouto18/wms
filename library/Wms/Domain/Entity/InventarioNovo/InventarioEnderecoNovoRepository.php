<?php
/**
 * Created by PhpStorm.
 * User: Joaby
 * Date: 26/11/2018
 * Time: 11:41
 */

namespace Wms\Domain\Entity\InventarioNovo;

use Doctrine\ORM\EntityRepository;
use Wms\Domain\Entity\InventarioNovo;

class InventarioEnderecoNovoRepository extends EntityRepository
{
    /**
     * @return InventarioEnderecoNovo
     * @throws \Exception
     */
    public function save() {
        $em = $this->getEntityManager();

        $em->beginTransaction();
        try {

            $enInventarioEndereco = new InventarioEnderecoNovo();

            $codInventario = $em->getReference('wms:InventarioNovo\InventarioNovo',$params['idCodInventario']);
            $codDeposito   = $em->getReference('wms:Inventario',$params['idDepositoEndereco']);

            $enInventarioEndereco->setCodInventario($codInventario);
            $enInventarioEndereco->setDepositoEndereco($codDeposito);
            $enInventarioEndereco->setContagem(1);
            $enInventarioEndereco->setFinalizado('N');

            $em->persist($enInventarioEndereco);
            $em->flush();
            $em->commit();
        } catch (\Exception $e) {
            $em->rollback();
            throw new \Exception($e->getMessage());
        }

        return $enInventarioEndereco;
    }
}