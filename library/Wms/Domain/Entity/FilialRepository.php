<?php

namespace Wms\Domain\Entity;

use Doctrine\ORM\EntityRepository,
    Wms\Domain\Entity\Filial as FilialEntity,
    Wms\Domain\Entity\AtorRepository;
use Doctrine\ORM\NonUniqueResultException;
use mysql_xdevapi\Exception;

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
        $filial->setIndUtilizaRessuprimento($values['pessoa']['juridica']['indRessuprimento']);
        $filial->setIndRecTransbObg($values['pessoa']['juridica']['indRecTransbObg']);
        $filial->setIsAtivo($values['pessoa']['juridica']['isAtivo']);
        $filial->setIsPrincipal(false);
        $em->persist($filial);
        $filial = $this->persistirAtor($filial, $values);

        if ($values['pessoa']['juridica']['isPrincipal'] == 'S')
            self::trocarPrincipal($filial->getId());
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

    public function trocarPrincipal($id)
    {
        $atual = self::getFilialPrincipal();
        if (!empty($atual) && $atual->getId() !== $id) {
            $atual->setIsPrincipal(false);
            $this->_em->persist($atual);
        }

        if (empty($atual) || (!empty($atual) && $atual->getId() !== $id)) {
            /** @var FilialEntity $nova */
            $nova = $this->find($id);
            $nova->setIsPrincipal(true);
            $this->_em->persist($nova);

            $this->_em->flush();
        }
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

    /**
     * @param $cnpj
     * @return Filial
     * @throws \Exception
     */
    public function getFilialByCnpj ($cnpj)
    {
        try {
            $dql = $this->_em->createQueryBuilder();
            $dql->select("f")
                ->from("wms:Filial", "f")
                ->innerJoin("f.juridica", "pj")
                ->where("pj.cnpj = '$cnpj' and f.isAtivo = 'S'");

            /** @var Filial $filial */
            $filial = $dql->getQuery()->getOneOrNullResult();
            if (empty($filial)) throw new \Exception("Nenhuma filial encontrada com esse CNPJ: $cnpj");

            return $filial;
        } catch (NonUniqueResultException $e) {
            throw new \Exception("Existe mais de uma filial vinculada à esse CNPJ: $cnpj");
        }
    }

    /**
     * @return FilialEntity
     */
    public function getFilialPrincipal()
    {
        return $this->findOneBy(['isPrincipal' => "S"]);
    }
}
