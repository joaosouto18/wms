<?php
/**
 * Created by PhpStorm.
 * User: Joaby
 * Date: 27/11/2018
 * Time: 09:28
 */

namespace Wms\Domain\Entity\InventarioNovo;

use Doctrine\ORM\EntityRepository;
use Wms\Domain\Configurator;
use Wms\Domain\Entity\InventarioNovo;

class InventarioContEndProdRepository extends EntityRepository
{
    /**
     * @param $params
     * @param bool $executeFlush
     * @return InventarioContEndProd
     * @throws \Exception
     */
    public function save($params, $executeFlush = true)
    {
        try {
            /** @var InventarioContEndProd $entity */
            $entity = Configurator::configure(new $this->_entityName, $params);

            $this->_em->persist($entity);
            if ($executeFlush) $this->_em->flush();

            return $entity;

        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * @param int $contEnd
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getContagensProdutos($contEnd)
    {
        $sql = "
            SELECT 
              ICE.NUM_SEQUENCIA, 
              ICEP.COD_PRODUTO, 
              ICEP.DSC_GRADE, 
              ICEP.DSC_LOTE, 
              TO_CHAR(ICEP.DTH_VALIDADE, 'DD/MM/YYYY') VALIDADE, 
              ICEP.COD_PRODUTO_VOLUME,
              SUM(ICEP.QTD_CONTADA * ICEP.QTD_EMBALAGEM) QTD_CONTAGEM
            FROM INVENTARIO_CONT_END_PROD ICEP
            INNER JOIN INVENTARIO_CONT_END ICE on ICEP.COD_INV_CONT_END = ICE.COD_INV_CONT_END
            INNER JOIN INVENTARIO_ENDERECO_NOVO IEN on ICE.COD_INVENTARIO_ENDERECO = IEN.COD_INVENTARIO_ENDERECO AND IEN.IND_ATIVO = 'S'
            WHERE ICE.COD_INV_CONT_END = $contEnd
            GROUP BY ICE.NUM_SEQUENCIA, ICEP.COD_PRODUTO, ICEP.DSC_GRADE, ICEP.DSC_LOTE, ICEP.DTH_VALIDADE, ICEP.COD_PRODUTO_VOLUME
            ORDER BY ICE.NUM_SEQUENCIA
        ";

        return $this->_em->getConnection()->query($sql)->fetchAll();
    }

    /**
     * @param int $invEnd
     * @param int $seq
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getProdutosContagemFinalizada($invEnd, $seq)
    {
        $sql = "
            SELECT ICEP.COD_PRODUTO, ICEP.DSC_GRADE, ICEP.COD_PRODUTO_VOLUME, ICEP.DSC_LOTE
            FROM INVENTARIO_CONT_END_PROD ICEP
            INNER JOIN INVENTARIO_CONT_END ICE on ICEP.COD_INV_CONT_END = ICE.COD_INV_CONT_END
            INNER JOIN INVENTARIO_ENDERECO_NOVO IEN on ICE.COD_INVENTARIO_ENDERECO = IEN.COD_INVENTARIO_ENDERECO AND IEN.IND_ATIVO = 'S'
            WHERE IEN.COD_INVENTARIO_ENDERECO = $invEnd AND ICE.NUM_SEQUENCIA < $seq AND ICEP.IND_DIVERGENTE = 'N'";

        return $this->_em->getConnection()->query($sql)->fetchAll();
    }

    /**
     * @param int $invEnd
     * @param $seq
     * @param $idProd
     * @param $grade
     * @param $lote
     * @param null $idVol
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getContagensAnteriores($invEnd, $seq, $idProd, $grade, $lote, $idVol)
    {
        $whereVol = (!empty($idVol)) ? " = $idVol" : "IS NULL";
        $whereLote = (!empty($lote)) ? " = '$lote'" : "IS NULL";

        $sql = "
            SELECT 
                ICE.NUM_SEQUENCIA,
                TO_CHAR(ICEP.DTH_VALIDADE, 'DD/MM/YYYY') VALIDADE, 
                SUM(ICEP.QTD_CONTADA * ICEP.QTD_EMBALAGEM) QTD_CONTAGEM
            FROM INVENTARIO_CONT_END_PROD ICEP
            INNER JOIN INVENTARIO_CONT_END ICE on ICEP.COD_INV_CONT_END = ICE.COD_INV_CONT_END
            INNER JOIN INVENTARIO_ENDERECO_NOVO IEN on ICE.COD_INVENTARIO_ENDERECO = IEN.COD_INVENTARIO_ENDERECO AND IEN.IND_ATIVO = 'S'
            WHERE ICE.COD_INVENTARIO_ENDERECO = $invEnd AND ICE.NUM_SEQUENCIA < $seq
                  AND ICEP.COD_PRODUTO = '$idProd' AND ICEP.DSC_GRADE = '$grade'
                  AND ICEP.DSC_LOTE $whereLote AND ICEP.COD_PRODUTO_VOLUME $whereVol
            GROUP BY ICE.NUM_SEQUENCIA, ICEP.DTH_VALIDADE
            ORDER BY ICE.NUM_SEQUENCIA";

        return $this->_em->getConnection()->query($sql)->fetchAll();
    }

    /**
     * @param $invEnd
     * @param $seq
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getProdutosContagemAnterior($invEnd, $seq)
    {
        $sql = "
            SELECT 
                   ICEP.COD_PRODUTO,
                   ICEP.DSC_GRADE,
                   ICEP.COD_PRODUTO_VOLUME,
                   ICEP.DSC_LOTE,
                   ICE.NUM_SEQUENCIA,
                   TO_CHAR(ICEP.DTH_VALIDADE, 'DD/MM/YYYY') VALIDADE, 
                   SUM(ICEP.QTD_CONTADA * ICEP.QTD_EMBALAGEM) QTD_CONTAGEM
            FROM INVENTARIO_CONT_END_PROD ICEP
            INNER JOIN INVENTARIO_CONT_END ICE on ICEP.COD_INV_CONT_END = ICE.COD_INV_CONT_END
            INNER JOIN INVENTARIO_ENDERECO_NOVO IEN on ICE.COD_INVENTARIO_ENDERECO = IEN.COD_INVENTARIO_ENDERECO AND IEN.IND_ATIVO = 'S'
            WHERE ICE.COD_INVENTARIO_ENDERECO = $invEnd AND ICE.NUM_SEQUENCIA = ($seq - 1)
            GROUP BY ICEP.COD_PRODUTO, ICEP.DSC_GRADE, ICEP.COD_PRODUTO_VOLUME, ICEP.DSC_LOTE, ICE.NUM_SEQUENCIA, ICEP.DTH_VALIDADE
            ORDER BY ICE.NUM_SEQUENCIA";

        return $this->_em->getConnection()->query($sql)->fetchAll();
    }
}