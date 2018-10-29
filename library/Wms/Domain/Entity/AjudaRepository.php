<?php

namespace Wms\Domain\Entity;

use Doctrine\ORM\EntityRepository,
    Wms\Domain\Entity\Ajuda as AjudaEntity;


class AjudaRepository extends EntityRepository
{   
    /**
     * Retorna uma ajuda pelo nome do recurso e nome da ação
     * @param string $nomeRecurso
     * @param string $nomeAcao 
     */
    public function findOneByNomeRecursoAndAcao($nomeRecurso, $nomeAcao)
    {
        $dql = $this->createQueryBuilder('aj')
                ->innerJoin('aj.recursoAcao', 'ra')
                ->innerJoin('ra.recurso', 'r')
                ->innerJoin('ra.acao', 'a')
                ->where('r.nome = :nomeRecurso AND a.descricao = :nomeAcao')
                ->setParameters(array(
                    'nomeRecurso' => $nomeRecurso,
                    'nomeAcao'    => $nomeAcao,
                ));
        
        return $dql->getQuery()->getOneOrNullResult();
    }
    
    /**
     * Retorna um array id => valor do Ajuda
     * @return array
     */
    public function getIdValue()
    {
        $valores = array();

        foreach ($this->findBy(array(), array('dscAjuda' => 'ASC')) as $item) {
            $valores[$item->getId()] = $item->getDscAjuda();
        }

        return $valores;
    }
    
     /**
     *
     * @param AjudaEntity $ajuda
     * @param array $values
     * @throws \Exception 
     */
    public function save(AjudaEntity $ajuda, array $values)
    {
              
        extract($values);
        $em = $this->getEntityManager();
        $recursoAcao = $em->getReference('wms:Sistema\Recurso\Vinculo', $idRecursoAcao);
        
        // request
        $ajuda->setNumPeso($numPeso);
        $ajuda->setDscAjuda($dscAjuda);
        $ajuda->setRecursoAcao($recursoAcao);
        $ajuda->setIdAjudaPai($idAjudaPai);
        $ajuda->setDscConteudo($dscConteudo);
        
        $em->persist($ajuda);        
    }
    
    public function remove($id)
    {
	$em = $this->getEntityManager();
	$proxy = $em->getReference('wms:Ajuda', $id);
	$em->remove($proxy);
    }
}
