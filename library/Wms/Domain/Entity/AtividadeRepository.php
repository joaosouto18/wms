<?php
namespace Wms\Domain\Entity;

use Doctrine\ORM\EntityRepository,
    Wms\Domain\Entity\Atividade as AtividadeEntity;


class AtividadeRepository extends EntityRepository
{
    /**
     * Salva o registro no banco
     * @param Atividade $atividade
     * @param array $values valores vindo de um formulÃƒÂ¡rio
     */
    public function save(AtividadeEntity $atividade, array $values)
    {
	extract($values['identificacao']); 
	$em = $this->getEntityManager();
	
	$setor = $em->getReference('wms:Atividade\SetorOperacional', $setorOperacional);
	
	$atividade->setSetorOperacional($setor);
	$atividade->setDescricao($descricao);
	
	$em->persist($atividade);
	
    }
    
    /**
     * Remove o registro no banco atravÃƒÂ©s do seu id
     * @param integer $id 
     */
    public function remove($id)
    {
	$em = $this->getEntityManager();
	$proxy = $em->getReference('wms:Atividade', $id);

	// remove
	$em->remove($proxy);
    }
}
