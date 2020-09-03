<?php

namespace Wms\Domain\Entity\Expedicao;

use Doctrine\ORM\EntityRepository;

class TipoPedidoRepository extends EntityRepository
{
    /**
     * Salva o registro no banco
     * @param TipoPedido $tipo
     * @param array $values valores vindo de um formulario
     */
    public function save(TipoPedido $tipo, array $values)
    {
        extract($values['identificacao']);
        $tipo->setDescricao($descricao)
            ->setCodExterno($codExterno);

        $this->_em->persist($tipo);
    }

    /**
     * Remove o registro no banco atravÃƒÂ©s do seu id
     * @param integer $id
     */
    public function remove($id)
    {
        $em = $this->getEntityManager();
        $proxy = $em->getReference(TipoPedido::class, $id);

        // remove
        $em->remove($proxy);
    }

}
