<?php

namespace Wms\Domain\Entity\MapaSeparacao;

use Doctrine\ORM\EntityRepository;


class PracaFaixaRepository extends EntityRepository
{

    public function findPracaFaixaById($idPracaFaixa) {


        $query = "SELECT pf
                FROM wms:MapaSeparacao\PracaFaixa pf
                WHERE pf.id = $idPracaFaixa
            ";

        $result = $this->getEntityManager()->createQuery($query)->getResult();
        return $result;

    }

    public function salvar($idPraca,$valores) {

        $numValores=(int)$valores['identificacao']['num_pracas'];

       for ($i=1; $i<=$numValores; $i++){
           $cep1=$valores['identificacao']['faixa1_'.$i];
           $cep2=$valores['identificacao']['faixa2_'.$i];

           if ( $cep1!='' && $cep2 !=''){
               $entity= new \Wms\Domain\Entity\MapaSeparacao\PracaFaixa();

               $entity->setCodPraca($idPraca);
               $entity->setFaixaCep1($cep1);
               $entity->setFaixaCep2($cep2);
               $this->getEntityManager()->persist($entity);

               $this->getEntityManager()->flush();
           }
       }
    }

    public function findPracaFaixaByPraca($idPraca) {


        $query = "SELECT pf
                FROM wms:MapaSeparacao\PracaFaixa pf
                WHERE pf.codPraca= $idPraca
            ";

        $result = $this->getEntityManager()->createQuery($query)->getResult();
        return $result;

    }
}