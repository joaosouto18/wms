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
                        ICE.IND_CONTAGEM_DIVERGENCIA \"divergencia\",
                        NVL(EV.END_VAZIO, 'S') \"vazio\"
                FROM INVENTARIO_ENDERECO_NOVO IEN
                INNER JOIN INVENTARIO_CONT_END ICE on IEN.COD_INVENTARIO_ENDERECO = ICE.COD_INVENTARIO_ENDERECO
                INNER JOIN (
                    SELECT MAX(ICE2.NUM_SEQUENCIA) ULTIMA, ICE2.COD_INVENTARIO_ENDERECO
                    FROM INVENTARIO_ENDERECO_NOVO IEN2
                    INNER JOIN INVENTARIO_CONT_END ICE2 on IEN2.COD_INVENTARIO_ENDERECO = ICE2.COD_INVENTARIO_ENDERECO
                    WHERE IEN2.COD_INVENTARIO = $idInventario AND IEN2.IND_ATIVO = 'S'
                    GROUP BY ICE2.COD_INVENTARIO_ENDERECO
                  ) LC ON LC.COD_INVENTARIO_ENDERECO = ICE.COD_INVENTARIO_ENDERECO AND LC.ULTIMA = ICE.NUM_SEQUENCIA
                LEFT JOIN (
                    SELECT 'N' END_VAZIO, ICE3.COD_INVENTARIO_ENDERECO
                    FROM INVENTARIO_ENDERECO_NOVO IEN3
                    INNER JOIN INVENTARIO_CONT_END ICE3 on IEN3.COD_INVENTARIO_ENDERECO = ICE3.COD_INVENTARIO_ENDERECO
                    INNER JOIN INVENTARIO_CONT_END_PROD ICEP ON ICE3.COD_INV_CONT_END = ICEP.COD_INV_CONT_END
                    WHERE IEN3.COD_INVENTARIO = $idInventario AND IEN3.IND_ATIVO = 'S' AND ICEP.QTD_CONTADA > 0
                  ) EV ON EV.COD_INVENTARIO_ENDERECO = IEN.COD_INVENTARIO_ENDERECO
                WHERE IEN.COD_INVENTARIO = $idInventario AND IEN.IND_FINALIZADO = 'N' AND IEN.IND_ATIVO = 'S'";

        return $this->_em->getConnection()->query($sql)->fetchAll();
    }
}