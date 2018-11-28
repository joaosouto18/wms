<?php
/**
 * Created by PhpStorm.
 * User: Joaby
 * Date: 27/11/2018
 * Time: 09:28
 */

namespace Wms\Domain\Entity\InventarioNovo;

use Doctrine\ORM\EntityRepository;
use Wms\Domain\Entity\InventarioNovo;

class InventarioContEndProdRepository extends EntityRepository
{
    /**
     * @return InventarioContEndProd
     * @throws \Exception
     */
    public function save() {
        $em = $this->getEntityManager();

        $em->beginTransaction();
        try {

            $enInventarioContEndProd = new InventarioContEndProd();

            $codInventarioContEnd = $em->getReference('wms:InventarioNovo\InventarioContEnd',$params['idInventarioContEnd']);
            $codProduto           = $em->getReference('wms:Produto',$params['idProduto']);
            $codGrade             = $em->getReference('wms:Produto',$params['idGrade']);
            $codProdutoEmbalagem  = $em->getReference('wms:Produto\Embalagem',$params['idProdutoEmbalagem']);
            $codProdutoVolume     = $em->getReference('wms:Produto\Volume',$params['idProdutoVolume']);

            $enInventarioContEndProd->setInventarioContEnd($codInventarioContEnd);
            $enInventarioContEndProd->setProduto($codProduto);
            $enInventarioContEndProd->setGrade($codGrade);
            $enInventarioContEndProd->setGrade($codProdutoEmbalagem);
            $enInventarioContEndProd->setProdutoVolume($codProdutoVolume);


            $em->persist($enInventarioContEndProd);
            $em->flush();
            $em->commit();
        } catch (\Exception $e) {
            $em->rollback();
            throw new \Exception($e->getMessage());
        }

        return $enInventarioEndereco;
    }
}