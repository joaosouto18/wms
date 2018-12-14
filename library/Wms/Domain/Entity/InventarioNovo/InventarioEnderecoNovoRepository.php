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
        $dql->select("de.descricao")
            ->from("wms:InventarioNovo\InventarioContEnd", "ice")
            ->innerJoin("ice.inventarioEndereco", "ie")
            ->innerJoin("ie.inventario", "inv")
            ->innerJoin("ie.depositoEndereco", "de")
            ->where("inv.id = :id")
            ->andWhere("ice.sequencia = :sq")
            ->setParameters(["id" => $idInventario, "sq" => $sequencia])
            ->distinct(true)
        ;

        $result = [];
        foreach ($dql->getQuery()->getResult() as $item) {
            $result["formated"][] = $item['descricao'];
            $result["unformated"][] = str_replace(".", "", $item['descricao']);
        }
        return $result;
    }
}