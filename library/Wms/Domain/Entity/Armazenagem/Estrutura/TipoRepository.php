<?php
namespace Wms\Domain\Entity\Armazenagem\Estrutura;

use Doctrine\ORM\EntityRepository,
    Wms\Domain\Entity\Armazenagem\Estrutura\Tipo as TipoEntity;


class TipoRepository extends EntityRepository
{
    /**
     * Salva o registro no banco
     * @param TipoEstruturaArmazenagem $tipo
     * @param array $values valores vindo de um formulÃƒÂ¡rio
     */
    public function save(TipoEntity $tipo, array $values)
    {
	$tipo->setDescricao($values['identificacao']['descricao']);
	$this->getEntityManager()->persist($tipo);
    }
    
    /**
     * Remove o registro no banco através do seu id
     * @param integer $id 
     */
    public function remove($id)
    {
	$em = $this->getEntityManager();
	$proxy = $em->getReference('wms:Armazenagem\Estrutura\Tipo', $id);

	// remove
	$em->remove($proxy);
    }

    /**
     * Returns all contexts stored as array (only id and nome)
     * @return array
     */
    public function getIdValue()
    {
	$tipos = array();
	foreach ($this->findAll() as $tipo)
	    $tipos[$tipo->getId()] = $tipo->getDescricao();
	return $tipos;
    }
}

