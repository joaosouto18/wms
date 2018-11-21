<?php
namespace Wms\Domain\Entity\Deposito\Nota\Entrada;

use Doctrine\ORM\EntityRepository,
    Wms\Domain\Entity\Deposito\Nota\Entrada\Tipo as TipoNotaEntrada;


class TipoRepository extends EntityRepository
{
    /**
     * Salva o registro no banco
     * @param TipoNotaEntrada $tipo
     * @param array $values valores vindo de um formulÃƒÂ¡rio
     */
    public function save(TipoNotaEntrada $tipo, array $values)
    {
	
	$tipo->setDescricao($values['identificacao']['descricao']);
	
	$this->getEntityManager()->persist($tipo);
    }
    
    /**
     * Remove o registro no banco atravÃƒÂ©s do seu id
     * @param integer $id 
     */
    public function remove($id)
    {
	$em = $this->getEntityManager();
	$proxy = $em->getReference('wms:Deposito\Nota\Entrada\Tipo', $id);

	// remove
	$em->remove($proxy);
    }

}
