<?php
namespace Wms\Domain\Entity\Expedicao;

use Doctrine\ORM\EntityRepository,
    Wms\Domain\Entity\Expedicao\PedidoProduto;

class PedidoProdutoRepository extends EntityRepository
{

    public function save($pedido) {

        $em = $this->getEntityManager();

        $em->beginTransaction();
        try {
            $enPedidoProduto = new PedidoProduto;

            \Zend\Stdlib\Configurator::configure($enPedidoProduto, $pedido);

            $em->persist($enPedidoProduto);
            $em->flush();
            $em->commit();

        } catch(\Exception $e) {
            $em->rollback();
            throw new \Exception($e->getMessage());
        }

        return $enPedidoProduto;
    }

}