<?php
namespace Wms\Domain\Entity\Deposito\Endereco;

use Doctrine\ORM\EntityRepository,
    Wms\Domain\Entity\Deposito\Endereco\Tipo as TipoEntity;


class TipoRepository extends EntityRepository
{
    /**
     * Salva o registro no banco
     * @param TipoEndereco $tipo
     * @param array $values valores vindo de um formulário
     */
    public function save(TipoEntity $tipo, array $values)
    {
	$tipo->setDescricao($values['identificacao']['descricao']);
	$tipo->setAltura($values['identificacao']['altura']);
	$tipo->setLargura($values['identificacao']['largura']);
	$tipo->setProfundidade($values['identificacao']['profundidade']);
	$tipo->setCubagem($values['identificacao']['cubagem']);
	$tipo->setCapacidade($values['identificacao']['capacidade']);
	$this->getEntityManager()->persist($tipo);
    }
    
    /**
     * Remove o registro no banco através do seu id
     * @param integer $id 
     */
    public function remove($id)
    {
	$em = $this->getEntityManager();
	$proxy = $em->getReference('wms:Deposito\Endereco\Tipo', $id);

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
