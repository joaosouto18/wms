<?php

namespace Wms\Domain\Entity\Movimentacao\Veiculo;

use Doctrine\ORM\EntityRepository,
    Wms\Domain\Entity\Movimentacao\Veiculo\Tipo as TipoVeiculo;


class TipoRepository extends EntityRepository
{
    /**
     * Salva o registro no banco
     * @param TipoVeiculo $tipo
     * @param array $values valores vindo de um formulario
     */
    public function save(TipoVeiculo $tipo, array $values)
    {
	
	$tipo->setDescricao($values['identificacao']['descricao']);
	
	$this->getEntityManager()->persist($tipo);
    }
    
    /**
     * Remove o registro no banco atraves do seu id
     * @param integer $id 
     */
    public function remove($id)
    {
	$em = $this->getEntityManager();
	$proxy = $em->getReference('wms:Movimentacao\Veiculo\Tipo', $id);
        
        $veiculo = $em->getRepository('wms:Movimentacao\Veiculo')->findBy(array('tipo' => $id));
        //caso haja veiculos
        if(count($veiculo) > 0)
            throw new \Exception('Não é possível remover. Há veículos cadastrados no tipo ' . $proxy->getDescricao());
        
	// remove
	$em->remove($proxy);
    }
    
    /**
     * Returns all contexts stored as array (only id and descricao)
     * @return array
     */
    public function getIdValue()
    {
	$tipos = array();
	foreach ($this->findby(array(), array('descricao' => 'ASC')) as $tipo)
	    $tipos[$tipo->getId()] = $tipo->getDescricao();
	return $tipos;
    }

}
