<?php
namespace Wms\Domain\Entity\Armazenagem;

use Doctrine\ORM\EntityRepository,
    Wms\Domain\Entity\Armazenagem\LinhaSeparacao as Linha;


class LinhaSeparacaoRepository extends EntityRepository
{
    /**
     * Salva o registro no banco
     * @param LinhaSeparacao $linha
     * @param array $values valores vindo de um formulÃƒÂ¡rio
     */
    public function save(Linha $linha, array $values)
    {
	$linha->setDescricao($values['identificacao']['descricao']);
	$this->getEntityManager()->persist($linha);
    }
    
    /**
     * Remove o registro no banco atravÃƒÂ©s do seu id
     * @param integer $id 
     */
    public function remove($id)
    {
	$em = $this->getEntityManager();
	$proxy = $em->getReference('wms:Armazenagem\LinhaSeparacao', $id);
	$numErros = 0;
	
	$dqlProduto = $em->createQueryBuilder()
		->select('count(p) qtty')
		->from('wms:Produto', 'p')
		->where('p.linhaSeparacao = ?1')
		->setParameter(1, $id);
	$resultSetProduto = $dqlProduto->getQuery()->execute();
	$countProduto = (integer) $resultSetProduto[0]['qtty'];
	if ($countProduto > 0) {
	    $msg .= "{$countProduto} produto(s) ";
	    $numErros++;
	}
	
	if($numErros > 0 ){
	    throw new \Exception("Não é possível remover a Linha de Separação {$proxy->getDescricao()}, 
				   há {$msg} vinculado(s).");
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
	$linhas = array();
	foreach ($this->findAll() as $linha)
	    $linhas[$linha->getId()] = $linha->getDescricao();
	return $linhas;
    }
}
