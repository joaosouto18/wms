<?php
namespace Wms\Domain\Entity\Expedicao;

use Doctrine\ORM\EntityRepository;
use Wms\Domain\Configurator;

class ConfCarregOsRepository extends EntityRepository
{
    /**
     * @param $params
     * @param bool $executeFlush
     * @return ConfCarregOs
     * @throws \Exception
     */
    public function save($params, $executeFlush = true)
    {
        try {
            /** @var ConfCarregOs $entity */
            $entity = Configurator::configure(new $this->_entityName, $params);

            $this->_em->persist($entity);
            if ($executeFlush) $this->_em->flush();

            return $entity;

        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    public function getOsConf($confCarreg, $userId = null)
    {
        $dql = $this->_em->createQueryBuilder();
        $dql->select("cco")
            ->from(ConfCarregOs::class, 'cco')
            ->innerJoin("cco.conferenciaCarregamento", "cc")
            ->innerJoin("cco.ordemServico", "os")
            ->innerJoin("os.pessoa", "us")
            ->where("os.dataFinal IS NULL")
            ->andWhere("cc.id = :idConfCarreg")
            ->setParameter("idConfCarreg", $confCarreg);

        if (!empty($userId)) {
            $dql->andWhere("us.id = :idUser")
                ->setParameter("idUser", $userId);
        }

        return $dql->getQuery()->getResult();
    }
}