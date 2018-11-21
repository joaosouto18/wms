<?php
namespace Wms\Domain\Entity\Deposito\Endereco\Rua;

use Doctrine\ORM\EntityRepository,
    Wms\Domain\Entity\Deposito\Endereco\Rua\Sentido as SentidoEntity;

/**
 * Sentido
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */

class SentidoRepository extends EntityRepository
{
    public function save(SentidoEntity $sentidoEntity, array $values)
    {
	extract($values['identificacao']);
	$sentidoEntity->setDescricao($descricao);
	$sentidoEntity->setIdEnderecoRua($idEnderecoRua);
	$deposito = $this->getEntityManager()->getReference('wms:Deposito', $idDeposito);
	$sentidoEntity->setDeposito($deposito);
	$this->getEntityManager()->persist($sentidoEntity);
    }

    public function remove($id)
    {
	$em = $this->getEntityManager();
	$proxy = $em->getReference('wms:Deposito\Endereco\Rua\Sentido', $id);
	$em->remove($proxy);
    }

}
