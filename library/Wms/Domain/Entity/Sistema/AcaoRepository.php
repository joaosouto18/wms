<?php
namespace Wms\Domain\Entity\Sistema;

use Doctrine\ORM\EntityRepository,
    Wms\Domain\Entity\Sistema\Acao as AcaoEntity;


class AcaoRepository extends EntityRepository implements \Doctrine\Common\Persistence\ObjectRepository
{
    public function checkAcaoExiste($nome, $descricao)
    {
	$em = $this->getEntityManager();
	$query = $em->createQuery('SELECT COUNT(a) nome FROM wms:Sistema\Acao a WHERE a.nome = :nome OR a.descricao = :descricao');
	
	$query->setParameter('nome', $nome);
	$query->setParameter('descricao', $descricao);
	$acao = $query->getOneOrNullResult();
        
	return ((string) $acao["nome"] == 0) ? false : true;
    }
    
    /**
     * Salva o registro no banco
     * @param Acao $acao
     * @param array $values valores vindo de um formulário
     */
    public function save(AcaoEntity $acao, array $values)
    {
	extract($values['identificacao']);
	
	if ($acao->getId() == null || $acao->getNome() != $nome || $acao->getDescricao() != $descricao){
	    if ($this->checkAcaoExiste($nome, $descricao)) {
		    throw new \Exception('Ação já cadastrada');
	    }
	}
	
	$acao->setNome($nome);
	$acao->setDescricao($descricao);
	$this->getEntityManager()->persist($acao);
    }
    
    /**
     * Remove o registro no banco através do seu id
     * @param integer $id 
     */
    public function remove($id)
    {
	$em = $this->getEntityManager();
	$proxy = $em->getReference('wms:Sistema\Acao', $id);

	// check whether I have any user within the role
	$dql = $em->createQueryBuilder()
		->select('count(v) qtty')
		->from('wms:Sistema\Recurso\Vinculo', 'v')
		->where('v.acao = ?1')
		->setParameter(1, $id);
	$resultSet = $dql->getQuery()->execute();
	$count = (integer) $resultSet[0]['qtty'];

	// case perfilUsuario has any user 
	if ($count > 0)
	    throw new \Exception("Não é possível remover a Ação 
				    {$proxy->getNome()}, há {$count} 
				    recursos(s) vinculados");

	// remove
	$em->remove($proxy);
    }

}
