<?php
namespace Wms\Domain\Entity\Expedicao;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Symfony\Component\Console\Output\NullOutput;
use Wms\Domain\Entity\Expedicao;

class MapaSeparacaoPedidoRepository extends EntityRepository
{
    public function getPedidosByMapa($idMapa,$codProduto,$grade)
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
            ->groupBy('p.id, pe.nome, i.descricao')
            ->orderBy('pe.nome', 'asc');

        if (isset($codProduto) && !empty($codProduto)) {
            $sql->andWhere("pp.codProduto = '$codProduto' AND pp.grade = '$grade'");
        }

        return $sql->getQuery()->getResult();
    }

}