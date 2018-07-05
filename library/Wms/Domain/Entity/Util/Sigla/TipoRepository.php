<?php

namespace Wms\Domain\Entity\Util\Sigla;

use Doctrine\ORM\EntityRepository,
    Wms\Domain\Entity\Util\Sigla\Tipo as TipoEntity,
    Wms\Domain\Entity\Sistema\Recurso\AuditoriaRepository;

/**
 * Tipo
 *
 */
class TipoRepository extends EntityRepository
{
    /**
     * Salva o registro no banco
     * @param TipoEntity $tipoEntity
     * @param array $values valores vindo de um formulário
     */
    public function save(TipoEntity $tipoEntity, array $values)
    {
	$tipoEntity->setDescricao($values['identificacao']['descricao']);
	$tipoEntity->setIsSistema($values['identificacao']['isSistema']);	
	$this->getEntityManager()->persist($tipoEntity);
        
        //gravo auditoria
        $this->gravarAuditoria($values);
    }
    
    /**
     * Delete an record from database
     * @param int $id 
     */
    public function remove($id)
    {
	$em = $this->getEntityManager();
	$proxy = $em->getReference('wms:Util\Sigla\Tipo', $id);

	// check whether I have any user within the role
	$dql = $em->createQueryBuilder()
		->select('count(s) qtty')
		->from('wms:Util\Sigla', 's')
		->where('s.tipo = ?1')
		->setParameter(1, $id);
	$resultSet = $dql->getQuery()->execute();
	$count = (integer) $resultSet[0]['qtty'];

	// case perfilUsuario has any user 
	if ($count > 0)
	    throw new \Exception("Não é possivel remove o Tipo 
				    {$proxy->getDescricao()}, há {$count} 
				    sigla(s) vinculada(s)");
	// remove
	$em->remove($proxy);
    }
    
    /**
     * Returns all contexts stored as array (only id and nome)
     * @return array
     */
    public function getIdValue()
    {
	$tipos = array();

	foreach ($this->findAll() as $tipo)
	    $tipos[$tipo->getId()] = $tipo->getDescricao();

	return $tipos;
    }
    
    /**
     * Grava auditoria
     * 
     * @param array $dados
     */
    public function gravarAuditoria(array $values)
    {
        $descricao = $values['identificacao']['descricao'] . '|' . $values['identificacao']['isSistema'];
        AuditoriaRepository::save(457, $descricao);
    }

}
