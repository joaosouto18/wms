<?php

namespace Wms\Domain\Entity\Sistema\Recurso;

use Doctrine\ORM\EntityRepository,
    Wms\Domain\Entity\RecursoAcao;

/**
 * RecursoAcaoRepository
 */
class VinculoRepository extends EntityRepository
{

    public function save(Recurso $recurso, array $values)
    {
	$recurso->setDscRecurso($values['dscRecurso']);
	$this->getEntityManager()->persist($recurso);
    }

    public function remove($id)
    {
	$em = $this->getEntityManager();
	$proxy = $em->getReference('wms:Recurso', $id);

	// check whether I have any user within the role
	$dql = $em->createQueryBuilder()
		->select('count(ra) qtty')
		->from('wms:RecursoAcao', 'ra')
		->where('ra.codRecurso = ?1')
		->setParameter(1, $id);
	$resultSet = $dql->getQuery()->execute();
	$count = (integer) $resultSet[0]['qtty'];

	// case perfilUsuario has any user 
	if ($count > 0)
	    throw new \Exception("Não foi possível possivel remover o Recurso 
				    {$proxy->getDscRecurso()}, há {$count} 
				    acao(es) vinculados ao recurso");

	// remove
	$em->remove($proxy);
    }
    
    /**
     * Retorna um array id => valor do
     * @return array
     */
    public function getIdValue()
    {
        $valores = array();

        foreach ($this->findBy(array(), array('nome' => 'ASC')) as $item) {
            $valores[$item->getId()] = $item->getNome();
        }

        return $valores;
    }

}
