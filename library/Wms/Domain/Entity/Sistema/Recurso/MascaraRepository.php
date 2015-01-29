<?php

namespace Wms\Domain\Entity\Sistema\Recurso;

use Doctrine\ORM\EntityRepository,
    Wms\Domain\Entity\Sistema\Recurso\Mascara as MascaraEntity;

class MascaraRepository extends EntityRepository {

    /**
     * Checo a data de inicio, caso invalida, sendo menor igual hoje lanco exception
     * Checo tambem se para o mesmo recurso há outro valido e cadastrado no sistema
     * Caso encontre registro traz o objeto relativo
     * 
     * @param int $recurso
     * @param string $datInicioVigencia Data Inicial de Vigencia (DD/MM/YYYY)
     * @param string $datFinalVigencia Data Inicial de Vigencia (DD/MM/YYYY)
     * @return boolena
     * 
     * @throws \Exception 
     */
    public function checkDatas($recurso, $datInicioVigencia, $datFinalVigencia)
    {
        if(new \Zend_Date($datInicioVigencia) <= \Zend_Date::now())
            throw new \Exception("A data de vigência inicial tem que ser maior que a data de hoje");
        
        $source = $this->getEntityManager()
                ->createQueryBuilder()
                ->select('m')
                ->from('wms:Sistema\Recurso\Mascara', 'm')
                ->where('m.recurso = ?1')
                ->andWhere('TRUNC(m.datFinalVigencia) = ?2')
                ->setParameter(1, $recurso)
                ->setParameter(2, \DateTime::createFromFormat('d/m/Y', $datFinalVigencia), 'date');
        
        $mascara = $source->getQuery()->getOneOrNullResult();
        
        if(!empty($mascara) && ($datInicioVigencia == $mascara->getDatInicioVigencia()))
            throw new \Exception("A data da nova mascara vigência inicial é identica a de outra mascara já existente para o mesmo recurso");
        
        return ($mascara) ? $mascara->getId() : false;
    }

    /**
     *
     * @param MascaraEntity $mascaraEntity
     * @param array $values
     * @throws \Exception 
     */
    public function save(MascaraEntity $mascaraEntity, array $values)
    {
        extract($values['identificacao']);
        $em = $this->getEntityManager();

        $recursoSave = $em->getReference('wms:Sistema\Recurso', $recurso);
        
        if ($mascaraEntity->getId() == null) {    
            if ($idMascara = $this->checkDatas($recurso, $datInicioVigencia, $datFinalVigencia)) {
                
                $dataFinal = \DateTime::createFromFormat('d/m/Y', $datInicioVigencia);
                //subtraio um dia da mascara existente
                $dataFinal->sub(new \DateInterval('P1D'));
                
                $mascaraAtual = $em->getReference('wms:Sistema\Recurso\Mascara', $idMascara);
                $mascaraAtual->setDatFinalVigencia($dataFinal);
                //persisto
                $em->persist($mascaraAtual);
            }
        }

        $dataInicial = \DateTime::createFromFormat('d/m/Y', $datInicioVigencia);
        $dataFinal = \DateTime::createFromFormat('d/m/Y', $datFinalVigencia);
        
        $mascaraEntity->setRecurso($recursoSave)
                ->setDatInicioVigencia($dataInicial)
                ->setDatFinalVigencia($dataFinal)
                ->setDscMascaraAuditoria($dscMascaraAuditoria);

        $em->persist($mascaraEntity);
    }

    /**
     * Delete an record from database
     * @param int $id 
     */
    public function remove($id)
    {
        $em = $this->getEntityManager();
        $proxy = $em->getReference('wms:Sistema\Recurso\Mascara', $id);
        // remove
        $em->remove($proxy);
    }

}