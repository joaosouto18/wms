<?php

namespace Wms\Domain\Entity\Deposito\Expedicao\Pedido;

use Doctrine\ORM\EntityRepository,
    Wms\Domain\Entity\Deposito\Expedicao\Pedido\Tipo as TipoPedidoExpedicao;

/**
 * 
 */
class TipoRepository extends EntityRepository
{
    /**
     * Salva o registro no banco
     * @param TipoPedidoExpedicao $tipo
     * @param array $values valores vindo de um formulario
     */
    public function save(TipoPedidoExpedicao $tipo, array $values)
    {
        extract($values['identificacao']);
	$tipo->setNome($nome)
                ->setDescricao($descricao)
                ->getEntityManager()
                ->persist($tipo);
    }
    
    /**
     * Remove o registro no banco atravÃƒÂ©s do seu id
     * @param integer $id 
     */
    public function remove($id)
    {
	$em = $this->getEntityManager();
	$proxy = $em->getReference('wms:Deposito\Expedicao\Pedido\Tipo', $id);

	// remove
	$em->remove($proxy);
    }

}
