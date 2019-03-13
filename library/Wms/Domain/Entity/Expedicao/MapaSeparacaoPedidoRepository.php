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
            ->select('p.codExterno as id, pe.nome cliente, NVL(i.descricao,\'PADRAO\') as itinerario, p.numSequencial')
            ->from('wms:Expedicao\MapaSeparacaoPedido', 'mps')
            ->innerJoin('mps.pedidoProduto','pp')
            ->innerJoin('wms:Expedicao\Pedido','p', 'WITH','p.id = pp.pedido')
            ->innerJoin('wms:Pessoa','pe', 'WITH', 'pe.id = p.pessoa')
            ->leftJoin('wms:Expedicao\Itinerario', 'i', 'WITH', 'i.id = p.itinerario')
            ->setParameter('mapa',$idMapa)
            ->where('mps.mapaSeparacao = :mapa')
            ->groupBy('p.codExterno, pe.nome, i.descricao, p.numSequencial')
            ->orderBy('pe.nome', 'asc');

        if (isset($codProduto) && !empty($codProduto)) {
            $sql->andWhere("pp.codProduto = '$codProduto' AND pp.grade = '$grade'");
        }

        $result = $sql->getQuery()->getResult();
        foreach ($result as $key => $value){
            if(!empty($value['numSequencial']) && $value['numSequencial'] > 1){
                $result[$key]['id'] = $value['id'].' - '.$value['numSequencial'];
            }
        }
        return $sql->getQuery()->getResult();
    }


    public function getMapaByPedidoProduto ($pedido, $codProduto, $grade)
    {
        $dql = $this->_em->createQueryBuilder();
        $dql->select("ms.id")
            ->from("wms:Expedicao\MapaSeparacaoPedido", "msp")
            ->innerJoin("msp.mapaSeparacao", "ms")
            ->innerJoin("msp.pedidoProduto", "pp")
            ->where("pp.codPedido = $pedido AND pp.codProduto = '$codProduto' AND pp.grade = '$grade'");

        return $dql->getQuery()->getResult();
    }
}