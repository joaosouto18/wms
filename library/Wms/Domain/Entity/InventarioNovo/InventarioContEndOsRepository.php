<?php
/**
 * Created by PhpStorm.
 * User: Joaby
 * Date: 26/11/2018
 * Time: 17:23
 */

namespace Wms\Domain\Entity\InventarioNovo;

use Doctrine\ORM\EntityRepository;
use Wms\Domain\Configurator;
use Wms\Domain\Entity\InventarioNovo;

class InventarioContEndOsRepository extends EntityRepository
{
    /**
     * @param $params
     * @param bool $executeFlush
     * @return InventarioContEndOs
     * @throws \Exception
     */
    public function save($params, $executeFlush = true)
    {
        try {
            /** @var InventarioContEndOs $entity */
            $entity = Configurator::configure(new $this->_entityName, $params);

            $this->_em->persist($entity);
            if ($executeFlush) $this->_em->flush();

            return $entity;

        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * @param $idUsuario
     * @param $idContEnd
     * @return InventarioContEndOs[]
     */
    public function getOsContUsuario($idUsuario, $idContEnd)
    {
        $dql = $this->_em->createQueryBuilder()
            ->select("iceos")
            ->from("wms:InventarioNovo\InventarioContEndOs", "iceos")
            ->innerJoin("iceos.ordemServico", "os")
            ->innerJoin("iceos.invContEnd", "ice")
            ->innerJoin("os.pessoa", "p")
            ->where("ice.id = :idContEnd")
            ->andWhere("p.id = :idPessoa")
            ->setParameters(["idContEnd" => $idContEnd, "idPessoa" => $idUsuario]);

        return $dql->getQuery()->getResult();
    }

    /**
     * @param $idUsuario
     * @param $idInventraio
     * @return InventarioContEndOs[]
     */
    public function getContagensUsuario($idUsuario, $idInventraio)
    {
        $dql = $this->_em->createQueryBuilder()
            ->select("iceos")
            ->from("wms:InventarioNovo\InventarioContEndOs", "iceos")
            ->innerJoin("iceos.ordemServico", "os")
            ->innerJoin("iceos.invContEnd", "ice")
            ->innerJoin("ice.inventarioEndereco", "ien")
            ->innerJoin("ien.inventario", "invn")
            ->innerJoin("os.pessoa", "p")
            ->where("invn.id = :idInventario")
            ->andWhere("p.id = :idPessoa")
            ->setParameters(["idInventario" => $idInventraio, "idPessoa" => $idUsuario]);

        return $dql->getQuery()->getResult();
    }
}