<?php
namespace Wms\Domain\Entity\Expedicao;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Symfony\Component\Console\Output\NullOutput;
use Wms\Domain\Entity\Expedicao;

class MapaSeparacaoPedidoRepository extends EntityRepository
{
    public function getPedidosByMapa($idMapa)
    {
        $sql = $this->getEntityManager()->createQueryBuilder()
            ->select('p.id, pe.nome cliente, NVL(i.descricao,\'PADRAO\') as itinerario')
            ->from('wms:Expedicao\MapaSeparacaoPedido', 'mps')
            ->innerJoin('mps.pedidoProduto','pp')
            ->innerJoin('wms:Expedicao\Pedido','p', 'WITH','p.id = pp.pedido')
            ->innerJoin('wms:Pessoa','pe', 'WITH', 'pe.id = p.pessoa')
            ->leftJoin('wms:Expedicao\Itinerario', 'i', 'WITH', 'i.id = p.itinerario')
            ->setParameter('mapa',$idMapa)
            ->where('mps.mapaSeparacao = :mapa')
            ->groupBy('p.id, pe.nome, i.descricao');
        
        return $sql->getQuery()->getResult();
    }

}