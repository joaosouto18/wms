<?php
namespace Wms\Domain\Entity\Recebimento\Divergencia;

use Doctrine\ORM\EntityRepository,
    Wms\Domain\Entity\Recebimento\Divergencia\Motivo as MotivoDivergenciaRecebimento;


class MotivoRepository extends EntityRepository
{
    /**
     * Salva o registro no banco
     * @param MotivoDivergenciaRecebimento $motivo
     * @param array $values valores vindo de um formulario
     */
    public function save(MotivoDivergenciaRecebimento $motivo, array $values)
    {
	
	$motivo->setDescricao($values['identificacao']['descricao']);
	
	$this->getEntityManager()->persist($motivo);
    }
    
    /**
     * Remove o registro no banco atraves do seu id
     * @param integer $id 
     */
    public function remove($id)
    {
	$em = $this->getEntityManager();
	$proxy = $em->getReference('wms:Recebimento\Divergencia\Motivo', $id);

	// remove
	$em->remove($proxy);
    }
    
    /**
     * Returns all contexts stored as array (only id and nome)
     * @return array
     */
    public function getIdValue()
    {
	$motivos = array();

	foreach ($this->findAll() as $motivo)
	    $motivos[$motivo->getId()] = $motivo->getDescricao();

	return $motivos;
    }

}
