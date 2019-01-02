<?php
/**
 * Created by PhpStorm.
 * User: Joaby
 * Date: 26/11/2018
 * Time: 11:41
 */

namespace Wms\Domain\Entity\InventarioNovo;

use Doctrine\ORM\AbstractQuery;
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
        $dql = $this->_em->createQueryBuilder();
        $dql->select("de.descricao, de.id")
            ->from("wms:InventarioNovo\InventarioContEnd", "ice")
            ->innerJoin("ice.inventarioEndereco", "ie", "WITH", "ie.ativo = 'S'")
            ->innerJoin("ie.inventario", "inv")
            ->innerJoin("ie.depositoEndereco", "de")
            ->where("inv.id = :id")
            ->andWhere("ice.sequencia = :sq")
            ->andWhere("ie.finalizado = 'N'")
            ->setParameters(["id" => $idInventario, "sq" => $sequencia])
            ->distinct(true)
        ;

        $result = [];
        foreach ($dql->getQuery()->getResult() as $item) {
            $result[$item['descricao']] = $item['id'];
        }
        return $result;
    }

    public function getInfoEndereco($idInventario, $sequencia, $endereco)
    {
        $dql = $this->_em->createQueryBuilder();
        $dql->select("p.id codProduto, p.grade, p.descricao, NVL(e.codigoBarras, v.codigoBarras) codBarras")
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
            ->leftJoin("p.embalagens", "e")
            ->leftJoin("p.volumes", "v")
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