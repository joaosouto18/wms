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

        $this->_em->beginTransaction();
        try {

            $enInventarioContEndProd = new InventarioContEndProd();

            $codInventarioContEnd = $this->_em->getReference('wms:InventarioNovo\InventarioContEnd',$params['idInventarioContEnd']);
            $codProduto           = $this->_em->getReference('wms:Produto',$params['idProduto']);
            $codGrade             = $this->_em->getReference('wms:Produto',$params['idGrade']);
            $codProdutoEmbalagem  = $this->_em->getReference('wms:Produto\Embalagem',$params['idProdutoEmbalagem']);
            $codProdutoVolume     = $this->_em->getReference('wms:Produto\Volume',$params['idProdutoVolume']);

            $enInventarioContEndProd->setInventarioContEnd($codInventarioContEnd);
            $enInventarioContEndProd->setProduto($codProduto);
            $enInventarioContEndProd->setGrade($codGrade);
            $enInventarioContEndProd->setGrade($codProdutoEmbalagem);
            $enInventarioContEndProd->setProdutoVolume($codProdutoVolume);


            $this->_em->persist($enInventarioContEndProd);
            $this->_em->flush();
            $this->_em->commit();
        } catch (\Exception $e) {
            $this->_em->rollback();
            throw new \Exception($e->getMessage());
        }

        return $enInventarioEndereco;
    }
}