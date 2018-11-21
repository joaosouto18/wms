<?php

namespace Wms\Domain\Entity\Importacao;

use Doctrine\ORM\EntityRepository;

class CamposRepository extends EntityRepository
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

}
