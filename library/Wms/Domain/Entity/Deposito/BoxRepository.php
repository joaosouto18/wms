<?php
namespace Wms\Domain\Entity\Deposito;

use Doctrine\ORM\EntityRepository,
    Wms\Domain\Entity\Deposito\Box as BoxEntity;

/**
 * Box
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class BoxRepository extends EntityRepository
{
    /**
     * Retorna se o código da entidade já existe existe
     * 
     * @return boolean
     */
    public function checkIdExiste($id, $idDeposito)
    {
	$box = $this->findOneBy(array('id' => $id, 'idDeposito' => $idDeposito));
	return ($box != null);
    }
    
    public function save(BoxEntity $boxEntity, array $values)
    {
	extract($values['identificacao']);
	$em = $this->getEntityManager();
	$deposito = $em->getReference('wms:Deposito', $idDeposito);
	//concatena o ID com o ID pai (caso exista)
	$id = (isset($idFilho)) ? $id.$idFilho : $id; 
	
	/**
	 * Lógica para verificar se ID existe
	 * 
	 * Se a entidade é nova
	 *    verifica se o código já existe
	 * se a entidade está sendo atualizada
	 *    se o código for diferente do código atual
	 *       verifica se o código já existe 	   
	 */
	//verificar se o ID já existe no banco
	if (($boxEntity->getId() == null) || ($boxEntity->getId() != $id)) {
	    if ($this->checkIdExiste($id, $idDeposito)) {
		throw new \Exception('Código já cadastrado');
	    }
	}
	
	$boxEntity->setDeposito($deposito);
	$boxEntity->setDescricao($descricao);
	//se o box possui um box pai
	if (isset($idPai) && $idPai != null) {
	    $pai = $em->getReference('wms:Deposito\Box', $idPai);
	    $boxEntity->setPai($pai);
	}
	$boxEntity->setId($id);
	$em->persist($boxEntity);
    }

    public function remove($id, $idDeposito)
    {
	$em = $this->getEntityManager();
	$proxy = $this->findOneBy(array('id' => $id, 'deposito' => $idDeposito));
	
	$dql = $em->createQueryBuilder()
		->select('count(b) qtty')
		->from('wms:Deposito\Box', 'b')
		->where('b.idPai = :idPai AND b.deposito = :idDeposito')
		->setParameter('idPai', $id)
                ->setParameter('idDeposito', $idDeposito);
        
	$resultSet = $dql->getQuery()->getOneOrNullResult();
	$count = (integer) $resultSet['qtty'];
        
	if ($count > 0) {
	    throw new \Exception("Não é possivel remover o Box 
				    {$proxy->getDescricao()}, há {$count} 
				    filho(s) vinculado(s).");
        }
        
        $recebimentos = $em->getRepository('wms:Recebimento')->findBy(array(
            'box' => $id, 'deposito' => $idDeposito
        ));
        
        $numRecebimentos = count($recebimentos);

        if ($numRecebimentos != 0) {
            throw new \Exception("Não é possivel remover o Box 
				    {$proxy->getDescricao()}, há {$numRecebimentos} 
				    recebimentos(s) vinculado(s).");
        }
        
        $em->remove($proxy);
	
    }

    /**
     * Returns all contexts stored as array (only id and nome)
     * @return array
     */
    public function getIdValue(array $criteria = null)
    {
	$boxes = array();
	$by['idPai'] = null;
	
	if ($criteria != null) {
	    foreach ($criteria as $key => $value) {
		$by[$key] = $value;
	    }
	}
	
	foreach ($this->findBy($by) as $box) {
	    $boxes[$box->getId()] = $box->getDescricao();
	}
	
	return $boxes;
    }

}
