<?php
/**
 * Created by PhpStorm.
 * User: Joaby
 * Date: 26/11/2018
 * Time: 11:41
 */

namespace Wms\Domain\Entity\InventarioNovo;

use Doctrine\ORM\EntityRepository;
use Wms\Domain\Configurator;

class InventarioEnderecoNovoRepository extends EntityRepository
{
    /**
     * @return InventarioEnderecoNovo
     * @throws \Exception
     */
    public function save($params, $executeFlush = true)
    {
        try {
            $entity = Configurator::configure(new $this->_entityName, $params);

            $this->_em->persist($entity);
            if ($executeFlush) $this->_em->flush();

            return $entity;

        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    public function getArrEnderecos($idInventario, $sequencia)
    {
        $sql = "SELECT DISTINCT
                    DE.DSC_DEPOSITO_ENDERECO,
                    DE.COD_DEPOSITO_ENDERECO,
                    ICE.COD_INV_CONT_END,
                    ICE.IND_CONTAGEM_DIVERGENCIA,
                    ICE.NUM_CONTAGEM,
                    ICE.COD_INVENTARIO_ENDERECO,
                    EV.END_VAZIO
                FROM INVENTARIO_CONT_END ICE
                INNER JOIN INVENTARIO_ENDERECO_NOVO IEN ON ICE.COD_INVENTARIO_ENDERECO = IEN.COD_INVENTARIO_ENDERECO AND IEN.IND_ATIVO = 'S'
                INNER JOIN DEPOSITO_ENDERECO DE on IEN.COD_DEPOSITO_ENDERECO = DE.COD_DEPOSITO_ENDERECO
                LEFT JOIN (
                    SELECT 'N' END_VAZIO, ICE3.COD_INVENTARIO_ENDERECO
                    FROM INVENTARIO_ENDERECO_NOVO IEN3
                    INNER JOIN INVENTARIO_CONT_END ICE3 on IEN3.COD_INVENTARIO_ENDERECO = ICE3.COD_INVENTARIO_ENDERECO
                    INNER JOIN INVENTARIO_CONT_END_PROD ICEP ON ICE3.COD_INV_CONT_END = ICEP.COD_INV_CONT_END
                    WHERE IEN3.COD_INVENTARIO = $idInventario AND IEN3.IND_ATIVO = 'S' AND ICEP.QTD_CONTADA > 0
                          AND NOT EXISTS(
                            SELECT 'x' FROM INVENTARIO_END_PROD IEP
                            WHERE IEP.IND_ATIVO = 'N' AND IEP.COD_PRODUTO = ICEP.COD_PRODUTO AND IEP.DSC_GRADE = ICEP.DSC_GRADE 
                              AND IEP.COD_INVENTARIO_ENDERECO = IEN3.COD_INVENTARIO_ENDERECO
                          )
                    ) EV ON EV.COD_INVENTARIO_ENDERECO = IEN.COD_INVENTARIO_ENDERECO
                WHERE ICE.NUM_SEQUENCIA = $sequencia AND IEN.COD_INVENTARIO = $idInventario AND IEN.COD_STATUS != 3
                      AND NOT EXISTS(
                                SELECT 'x' FROM INVENTARIO_CONT_END ICE2
                                WHERE ICE2.NUM_SEQUENCIA > $sequencia AND IEN.COD_INVENTARIO_ENDERECO = ICE2.COD_INVENTARIO_ENDERECO
                              )
        ";

        $result = [];
        foreach ($this->_em->getConnection()->query($sql)->fetchAll() as $item) {
            $result[$item['DSC_DEPOSITO_ENDERECO']] = [
                "idEnd" => $item['COD_DEPOSITO_ENDERECO'],
                "idContEnd" => $item['COD_INV_CONT_END'],
                "idInvEnd" => $item['COD_INVENTARIO_ENDERECO'],
                "vazio" => empty($item["END_VAZIO"]),
                "indDivrg" => ($item["IND_CONTAGEM_DIVERGENCIA"] == "S"),
                "sequencia" => $sequencia,
                "contagem" => $item["NUM_CONTAGEM"],
            ];
        }
        return $result;
    }

    public function getInfoEndereco($idInventario, $sequencia, $endereco)
    {
        $dql = $this->_em->createQueryBuilder();
        $dql->select("p.id codProduto, p.grade, p.descricao, NVL(e.codigoBarras, v.codigoBarras) codBarras, '' idVol, '' lote")
            ->from("wms:InventarioNovo\InventarioContEnd", "ice")
            ->innerJoin("ice.inventarioEndereco", "ie", "WITH", "ie.ativo = 'S' and ie.inventario = $idInventario and ie.depositoEndereco = $endereco")
            ->innerJoin("ie.inventario", "inv")
            ->innerJoin("ie.depositoEndereco", "de")
            ->innerJoin("wms:InventarioNovo\InventarioEndProd", "iep", "WITH", "iep.inventarioEndereco = ie and iep.ativo = 'S'")
            ->innerJoin("iep.produto", "p")
            ->leftJoin("p.embalagens", "e", "WITH", "e.codigoBarras IS NOT NULL")
            ->leftJoin("p.volumes", "v", "WITH", "v.codigoBarras IS NOT NULL")
            ->where("ice.sequencia = $sequencia")
            ->distinct(true);

        return $dql->getQuery()->getResult();
    }

    public function getItensDiverg($idInventario, $sequencia, $endereco)
    {
        $dql = $this->_em->createQueryBuilder();
        $dql->select("p.id codProduto, p.grade, p.descricao, v.id idVol, v.descricao dscVol, NVL(e.codigoBarras, v.codigoBarras) codBarras, icep.qtdContada, icep.lote")
            ->from("wms:InventarioNovo\InventarioContEndProd", "icep")
            ->innerJoin("icep.inventarioContEnd", "ice", "WITH", "ice.sequencia = ($sequencia - 1)")
            ->innerJoin("ice.inventarioEndereco", "ie", "WITH", "ie.ativo = 'S' and ie.inventario = $idInventario and ie.depositoEndereco = $endereco")
            ->innerJoin("icep.produto", "p")
            ->leftJoin("icep.produtoEmbalagem", "e")
            ->leftJoin("icep.produtoVolume", "v")
            ->where("icep.divergente = 'S'")
            ->andWhere("NOT EXISTS(
                    SELECT 'x'
                    FROM wms:InventarioNovo\InventarioEndProd iep
                    INNER JOIN iep.inventarioEndereco ie2
                    WHERE iep.ativo = 'N' and ie2 = ie and iep.codProduto = icep.codProduto and iep.grade = icep.grade
                )")
            ->andWhere("NOT EXISTS(
                    SELECT 'x'
                    FROM wms:InventarioNovo\InventarioContEndProd icep2
                    INNER JOIN icep2.inventarioContEnd ice2 WITH ice2.sequencia = $sequencia
                    INNER JOIN ice2.inventarioEndereco ie3 WITH ie3.ativo = 'S'
                    WHERE ie = ie3 and icep2.codProduto = icep.codProduto and icep2.grade = icep.grade 
                         and NVL(icep2.lote,0) = NVL(icep.lote,0) and NVL(icep2.produtoVolume,0) = NVL(icep.produtoVolume,0) 
                         and icep2.qtdContada = 0
                )")
            ->distinct(true);

        return $dql->getQuery()->getResult();
    }
}