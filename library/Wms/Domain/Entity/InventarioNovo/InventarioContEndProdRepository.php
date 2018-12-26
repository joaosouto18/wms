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

    public function getContagensProdutos($idInvEnd)
    {
        $dql = $this->_em->createQueryBuilder();
        $dql->select("ice.sequencia, icep.codProduto, icep.grade, icep.lote, TO_CHAR(icep.validade, 'DD/MM/YYYY') validade, 
                        pv.id idVol, SUM(icep.qtdContada * icep.qtdEmbalagem) qtdContagem")
            ->from("wms:InventarioNovo\InventarioContEndProd", "icep")
            ->innerJoin("icep.inventarioContEnd", "ice")
            ->innerJoin("ice.inventarioEndereco", "ien", "WITH", "ien.ativo = 'S' AND ien.id = $idInvEnd")
            ->leftJoin("icep.produtoVolume", "pv")
            ->groupBy("ice.sequencia, icep.codProduto, icep.grade, icep.lote, icep.validade, pv.id")
            ->orderBy("ice.sequencia")
        ;

        return $dql->getQuery()->getResult();
    }
}