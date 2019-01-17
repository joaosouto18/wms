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
     * @param $idContEnd
     * @param $idUsuario
     * @return InventarioContEndOs[]
     */
    public function getOsContUsuario($idContEnd, $idUsuario)
    {
        $dql = $this->_em->createQueryBuilder()
            ->select("iceos")
            ->from("wms:InventarioNovo\InventarioContEndOs", "iceos")
            ->innerJoin("iceos.ordemServico", "os", "WITH", "os.pessoa = $idUsuario")
            ->innerJoin("iceos.invContEnd", "ice", "WITH", "ice.id = $idContEnd")
        ;

        return $dql->getQuery()->getOneOrNullResult();
    }

    /**
     * @param $idUsuario
     * @param $idInvEnd
     * @return InventarioContEndOs[]
     */
    public function getContagensUsuario($idUsuario, $idInvEnd)
    {
        $dql = $this->_em->createQueryBuilder()
            ->select("iceos")
            ->from("wms:InventarioNovo\InventarioContEndOs", "iceos")
            ->innerJoin("iceos.ordemServico", "os", "WITH", "os.pessoa = $idUsuario")
            ->innerJoin("iceos.invContEnd", "ice")
            ->innerJoin("ice.inventarioEndereco", "ien", "WITH", "ien.id = $idInvEnd")
            ->where("ien.ativo = 'S'")
        ;

        return $dql->getQuery()->getResult();
    }


    public function getOutrasOsAbertasContagem( $idUsuario, $idCondEnd)
    {
        $dql = $this->_em->createQueryBuilder()
            ->select("iceos")
            ->from("wms:InventarioNovo\InventarioContEndOs", "iceos")
            ->innerJoin("iceos.ordemServico", "os", "WITH", "os.pessoa != $idUsuario and os.dataFinal IS NULL")
            ->innerJoin("iceos.invContEnd", "ice", "WITH", "ice.id = $idCondEnd")
            ->innerJoin("ice.inventarioEndereco", "ien", "WITH", " ien.ativo = 'S'");

        return $dql->getQuery()->getResult();
    }
}