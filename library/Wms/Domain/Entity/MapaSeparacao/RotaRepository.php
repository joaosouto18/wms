<?php

namespace Wms\Domain\Entity\MapaSeparacao;

use Doctrine\ORM\EntityRepository;


class RotaRepository extends EntityRepository
{

    public function findRotaById($idRota) {


        $query = "SELECT r
                FROM wms:MapaSeparacao\Rota r
                WHERE r.id = $idRota
            ";

        $result = $this->getEntityManager()->createQuery($query)->getResult();
        return $result;

    }

    public function salvar($valores) {

        $entity= new \Wms\Domain\Entity\MapaSeparacao\Rota();

        $entity->setNomeRota($valores['identificacao']['nomeRota']);
        $this->getEntityManager()->persist($entity);

        $this->getEntityManager()->flush();
        $idRota=$entity->getId();
        $rotaPracaRepo = $this->getEntityManager()->getRepository("wms:MapaSeparacao\RotaPraca");

        $rotaPracaRepo->salvar($idRota,$valores);
    }

    public function getPracas($idRota) {

        $query = "SELECT rp.id,p.nomePraca
                FROM wms:MapaSeparacao\Praca p
                INNER JOIN wms:MapaSeparacao\RotaPraca rp WITH (p.id=rp.codPraca)
                WHERE rp.codRota = ".$idRota."
                group by rp.id,p.nomePraca
                order by p.nomePraca
            ";

        $result = $this->getEntityManager()->createQuery($query)->getResult();
        return $result;

    }


}