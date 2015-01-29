<?php
namespace Wms\Domain\Entity\Atividade;

use Doctrine\ORM\EntityRepository,
    Wms\Domain\Entity\Atividade\SetorOperacional as SetorOperacionalEntity;


class SetorOperacionalRepository extends EntityRepository
{
    /**
     * Salva o registro no banco
     * @param Atividade $atividade
     * @param array $values valores vindo de um formulÃƒÂ¡rio
     */
    public function save(SetorOperacionalEntity $setorOperacional, array $values)
    {
	$setorOperacional->setDescricao($values['identificacao']['descricao']);
	$this->getEntityManager()->persist($setorOperacional);
    }
    
    /**
     * Remove o registro no banco atravÃƒÂ©s do seu id
     * @param integer $id 
     */
    public function remove($id)
    {
	$em = $this->getEntityManager();
	$proxy = $em->getReference('wms:Atividade\SetorOperacional', $id);
	$numErros = 0;
	
	$dqlAtividade = $em->createQueryBuilder()
		->select('count(a) qtty')
		->from('wms:Atividade', 'a')
		->where('a.setorOperacional = ?1')
		->setParameter(1, $id);
	$resultSetAtividade = $dqlAtividade->getQuery()->execute();
	$countAtividade = (integer) $resultSetAtividade[0]['qtty'];
	if ($countAtividade != 0) {
	    $msg = "{$countAtividade} atividade(s) ";
	    $numErros++;
	}
	
	if ($numErros != 0) {
	    throw new \Exception("Não é possivel remover o Setor Operacional {$proxy->getDescricao()}, 
				   há {$msg} atividades vinculada(s).");
	}
	    
	// remove
	$em->remove($proxy);
    }
    
            /**
     * Returns all contexts stored as array (only id and nome)
     * @return array
     */
    public function getIdValue()
    {
	$setores = array();

	foreach ($this->findAll() as $setor)
	    $setores[$setor->getId()] = $setor->getDescricao();

	return $setores;
    }
}
