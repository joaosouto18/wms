<?php
/**
 * Created by PhpStorm.
 * User: Joaby
 * Date: 27/11/2018
 * Time: 09:15
 */

namespace Wms\Domain\Entity\InventarioNovo;

use Doctrine\ORM\EntityRepository;
use Wms\Domain\Configurator;
use Wms\Domain\Entity\InventarioNovo;

class InventarioContEndRepository extends EntityRepository
{
    /**
     * @param $params
     * @param bool $executeFlush
     * @return InventarioContEnd
     * @throws \Exception
     */
    public function save($params, $executeFlush = true)
    {
        try {
            /** @var InventarioContEnd $entity */
            $entity = Configurator::configure(new $this->_entityName, $params);

            $this->_em->persist($entity);
            if ($executeFlush) $this->_em->flush();

            return $entity;

        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    public function getContagens($idInventario)
    {
        $sql = "SELECT DISTINCT 
                        ICE.COD_INV_CONT_END \"id\",
                        ICE.NUM_SEQUENCIA \"sequencia\", 
                        ICE.NUM_CONTAGEM \"contagem\", 
                        ICE.IND_CONTAGEM_DIVERGENCIA \"divergencia\"
                FROM INVENTARIO_ENDERECO_NOVO IEN
                INNER JOIN INVENTARIO_CONT_END ICE on IEN.COD_INVENTARIO_ENDERECO = ICE.COD_INVENTARIO_ENDERECO
                INNER JOIN (
                    SELECT MAX(ICE2.NUM_SEQUENCIA) ULTIMA, ICE2.COD_INVENTARIO_ENDERECO
                    FROM INVENTARIO_ENDERECO_NOVO IEN2
                    INNER JOIN INVENTARIO_CONT_END ICE2 on IEN2.COD_INVENTARIO_ENDERECO = ICE2.COD_INVENTARIO_ENDERECO
                    WHERE IEN2.COD_INVENTARIO = $idInventario
                    GROUP BY ICE2.COD_INVENTARIO_ENDERECO
                  ) LC ON LC.COD_INVENTARIO_ENDERECO = ICE.COD_INVENTARIO_ENDERECO AND LC.ULTIMA = ICE.NUM_SEQUENCIA
                WHERE IEN.COD_INVENTARIO = $idInventario AND IEN.IND_FINALIZADO = 'N'";

        return $this->_em->getConnection()->query($sql)->fetchAll();
    }
}