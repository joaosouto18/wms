<?php

namespace Wms\Domain\Entity\MapaSeparacao;

use Doctrine\ORM\EntityRepository,
    \Wms\Domain\Entity\MapaSeparacao\Praca as PracaEntity;


class PracaRepository extends EntityRepository
{

    public function findPracaById($idPraca) {


        $query = "SELECT p
                FROM wms:MapaSeparacao\Praca p
                WHERE p.id = $idPraca
            ";

        $result = $this->getEntityManager()->createQuery($query)->getResult();
        return $result;

    }

    public function salvar($valores, $idPraca = null) {

        if ($idPraca == null) {
            $entity= new \Wms\Domain\Entity\MapaSeparacao\Praca();
        } else {
            $entity = $this->find($idPraca);
        }

        $entity->setNomePraca($valores['identificacao']['nomePraca']);
        $this->getEntityManager()->persist($entity);

        $this->getEntityManager()->flush();
        $idPraca=$entity->getId();
        $pracaFaixaRepo = $this->getEntityManager()->getRepository("wms:MapaSeparacao\PracaFaixa");

        $pracaFaixaRepo->salvar($idPraca,$valores);
    }

    public function getPracas() {

        $query = "SELECT p.id,p.nomePraca
                FROM wms:MapaSeparacao\Praca p
                group by p.id,p.nomePraca
                order by p.nomePraca
            ";

        $result = $this->getEntityManager()->createQuery($query)->getResult();
        return $result;

    }


    public function getFaixas($idPraca) {

        $query = "SELECT pf.id,pf.faixaCep1,pf.faixaCep2
                FROM wms:MapaSeparacao\PracaFaixa pf
                WHERE pf.codPraca = ".$idPraca."
                group by pf.id,pf.faixaCep1,pf.faixaCep2
                order by pf.id
            ";

        $result = $this->getEntityManager()->createQuery($query)->getResult();
        return $result;

    }

    public function getRotas($idPraca) {

        $query = "SELECT rp.id
                FROM wms:MapaSeparacao\Praca p
                INNER JOIN wms:MapaSeparacao\RotaPraca rp WITH (p.id=rp.codPraca)
                WHERE rp.codPraca = ".$idPraca."
                group by rp.id
            ";

        $result = $this->getEntityManager()->createQuery($query)->getResult();
        return $result;

    }

    public function getIdValue()
    {
        $rows = array();
        $result = $this->findAll();

        $rows[''] = '- Selecione -';
        foreach ($result as $row) {
            $rows[$row->getId()] = $row->getNomePraca();
        }

        return $rows;
    }

}