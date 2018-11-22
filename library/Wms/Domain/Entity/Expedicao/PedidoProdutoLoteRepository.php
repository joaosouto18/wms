<?php
/**
 * Created by PhpStorm.
 * User: Luis Fernando
 * Date: 02/05/2018
 * Time: 14:17
 */
namespace Wms\Domain\Entity\Expedicao;

use Doctrine\ORM\EntityRepository;
use Wms\Domain\Entity\Expedicao;
use Wms\Domain\Configurator;

class PedidoProdutoLoteRepository extends EntityRepository
{
    public function save($data) {

        $entity = new PedidoProdutoLote();
        Configurator::configure($entity, $data);
        $this->_em->persist($entity);

        return $entity;
    }

}