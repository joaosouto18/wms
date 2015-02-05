<?php

namespace Wms\Domain\Entity;

use Doctrine\ORM\EntityRepository,
    Wms\Domain\Entity\Filial as FilialEntity,
    Wms\Domain\Entity\AtorRepository;

/**
 * Filial
 *
 */
class FilialRepository extends AtorRepository
{
     /**
     * Salva o registro no banco
     * @param FilialEntity $filial
     * @param array $values valores vindo de um formulário
     */
    public function save(FilialEntity $filial, array $values)
    {
	$em = $this->getEntityManager();
	$filial->setIdExterno($values['pessoa']['juridica']['idExterno']);
    $filial->setCodExterno($values['pessoa']['juridica']['codExterno']);
    $filial->setIndLeitEtqProdTransbObg($values['pessoa']['juridica']['indLeitEtqProdTransbObg']);
    $filial->setIndRecTransbObg($values['pessoa']['juridica']['indRecTransbObg']);
    $filial->setIsAtivo($values['pessoa']['juridica']['isAtivo']);
	$this->persistirAtor($filial, $values);
	$em->persist($filial);
    }

     /**
     * Remove o registro no banco através do seu id
     * @param integer $id 
     */
    public function remove($id)
    {
	$em = $this->getEntityManager();
	$proxy = $em->getReference('wms:Filial', $id);
	
	$dql = $em->createQueryBuilder()
		->select('count(d) qtty')
		->from('wms:Deposito', 'd')
		->where('d.idFilial = ?1')
		->setParameter(1, $id);
	$resultSet = $dql->getQuery()->execute();
	$countFilial = (integer) $resultSet[0]['qtty'];

	if ($countFilial > 0)
	    throw new \Exception("Não é possivel remover a Filial 
				    {$proxy->getPessoa()->getNome()}, há {$countFilial} 
				    depósito(s) vinculado(s).");
	// remove
	$em->remove($proxy);
    }
    
    public function getIdValue()
    {
	$filiais = array();

	foreach ($this->findAll() as $filial)
	    $filiais[$filial->getId()] = $filial->getPessoa()->getNomeFantasia();

	return $filiais;
    }

    public function getIdExternoValue()
    {
        $filiais = array();

        foreach ($this->findAll() as $filial)
            $filiais[$filial->getCodExterno()] = $filial->getPessoa()->getNomeFantasia();

        return $filiais;
    }

    public function getIdAndDescriptionExternoValue()
    {
        $filiais = array();

        foreach ($this->findAll() as $filial)
            $filiais[$filial->getCodExterno()] = $filial->getPessoa()->getNomeFantasia() . ' - ' . $filial->getCodExterno();

        return $filiais;
    }

}
