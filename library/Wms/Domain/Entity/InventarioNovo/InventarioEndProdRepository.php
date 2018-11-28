<?php
/**
 * Created by PhpStorm.
 * User: Joaby
 * Date: 26/11/2018
 * Time: 13:30
 */

namespace Wms\Domain\Entity\InventarioEndProd;

use Doctrine\ORM\EntityRepository;
use Wms\Domain\Entity\InventarioNovo;

class InventarioEndProdRepository extends EntityRepository
{
    /**
     * @return InventarioEndProd
     * @throws \Exception
     */
    public function save() {
        $em = $this->getEntityManager();

        $em->beginTransaction();
        try {

            $enInventarioEndProd = new InventarioEndProd();

            $codEndereco = $em->getReference('wms:InventarioNovo\InventarioEndereco',$params['idInventarioEndereco']);
            $codProduto  = $em->getReference('wms:Produto',$params['idProduto']);
            $codGrade    = $em->getReference('wms:Produto',$params['idGrade']);

            $enInventarioEndProd->setInventarioEndereco($codEndereco);
            $enInventarioEndProd->setProduto($codProduto);
            $enInventarioEndProd->setGrade($codGrade);

            $em->persist($enInventarioEndProd);
            $em->flush();
            $em->commit();
        } catch (\Exception $e) {
            $em->rollback();
            throw new \Exception($e->getMessage());
        }

        return $enInventarioEndereco;
    }
}