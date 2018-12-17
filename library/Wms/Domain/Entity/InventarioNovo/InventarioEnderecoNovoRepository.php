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
            ->innerJoin("ice.inventarioEndereco", "ie")
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
        $dql->select("p.id codProduto, p.grade, p.descricao, tc.id tipo, NVL(e.codigoBarras, v.codigoBarras) codBarra")
            ->from("wms:InventarioNovo\InventarioContEnd", "ice")
            ->innerJoin("ice.inventarioEndereco", "ie")
            ->innerJoin("ie.inventario", "inv")
            ->innerJoin("ie.depositoEndereco", "de")
            ->innerJoin("wms:InventarioNovo\InventarioEndProd", "iep", "WITH", "iep.inventarioEndereco = ie")
            ->innerJoin("iep.produto", "p")
            ->innerJoin("p.tipoComercializacao", "tc")
            ->leftJoin("p.embalagens", "e")
            ->leftJoin("p.volumes", "v")
            ->where("inv.id = :id")
            ->andWhere("ice.sequencia = :sq")
            ->andWhere("de.id = :end")
            ->setParameters(["id" => $idInventario, "sq" => $sequencia, "end" => $endereco])
            ->distinct(true)
        ;

        return $dql->getQuery()->getResult();
    }
}