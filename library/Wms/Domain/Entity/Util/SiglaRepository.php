<?php
namespace Wms\Domain\Entity\Util;

use Doctrine\ORM\EntityRepository,
    \Wms\Domain\Entity\Util\Sigla as SiglaEntity;

/**
 * ContextoParametroRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class SiglaRepository extends EntityRepository
{
    /**
     *
     * @param int $idTipo
     * @return type 
     */
    public function getIdValue($idTipo = false)
    {
	$result = ($idTipo) ? $this->findBy(array('tipo' => (int) $idTipo), array('sigla' => 'ASC')) : $this->findAll();

	foreach ($result as $row)
	    $rows[$row->getId()] = $row->getSigla();

	return $rows;
    }
    
    /**
     * Salva a sigla no banco
     * @param SiglaEntity $sigla
     * @param array $values 
     */
    public function save(SiglaEntity $sigla, array $values)
    {
	$values['identificacao']['tipo'] = $this->getEntityManager()->getReference('wms:Util\Sigla\Tipo', $values['identificacao']['idTipo']);
	\Zend\Stdlib\Configurator::configure($sigla, $values['identificacao']);
	$this->getEntityManager()->persist($sigla);
    }
    
    /**
     * Delete an record from database
     * @param int $id 
     */
    public function remove($id)
    {
	$em = $this->getEntityManager();
	$proxy = $em->getReference('wms:Util\Sigla', $id);
	$em->remove($proxy);
    }
    
    /**
     *
     * @param array $criteria
     * @param array $orderBy
     * @return type 
     */
    public function getReferenciaValor(array $criteria = array(), array $orderBy = array())
    {
	$result = $this->findBy($criteria, $orderBy);

	foreach ($result as $row)
	    $rows[$row->getReferencia()] = $row->getSigla();

	return $rows;
    }
}
