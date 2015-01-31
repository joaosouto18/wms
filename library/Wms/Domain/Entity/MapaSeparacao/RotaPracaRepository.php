<?php

namespace Wms\Domain\Entity\MapaSeparacao;

use Doctrine\ORM\EntityRepository;


class RotaPracaRepository extends EntityRepository
{

    public function findRotaPracaById($idRotaPraca) {


        $query = "SELECT pf
                FROM wms:MapaSeparacao\RotaPraca rp
                WHERE rp.id = $idRotaPraca
            ";

        $result = $this->getEntityManager()->createQuery($query)->getResult();
        return $result;

    }

    public function salvar($idRota,$valores) {

        $numValores=(int)$valores['identificacao']['num_rotas'];

        for ($i=1; $i<=$numValores; $i++){
            $praca=$valores['identificacao']['praca_'.$i];

            if ( $praca !=''){
                $entity= new \Wms\Domain\Entity\MapaSeparacao\RotaPraca();

                $entity->setCodPraca($praca);
                $entity->setCodRota($idRota);
                $this->getEntityManager()->persist($entity);

                $this->getEntityManager()->flush();
            }
        }
    }

}