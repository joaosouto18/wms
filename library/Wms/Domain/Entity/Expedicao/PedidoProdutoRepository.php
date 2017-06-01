<?php
namespace Wms\Domain\Entity\Expedicao;

use Doctrine\ORM\EntityRepository,
    Wms\Domain\Entity\Expedicao\PedidoProduto;

class PedidoProdutoRepository extends EntityRepository
{

    public function save($pedido) {

        $em = $this->getEntityManager();

//        $em->beginTransaction();
        try {
            $enPedidoProduto = new PedidoProduto;
            \Zend\Stdlib\Configurator::configure($enPedidoProduto, $pedido);
            $em->persist($enPedidoProduto);
//            $em->flush();
//            $em->commit();

        } catch(\Exception $e) {
//            $em->rollback();
            throw new \Exception($e->getMessage() . ' - ' .$e->getTraceAsString());
        }

        return $enPedidoProduto;
    }

    public function getFilialByProduto($idPedido)
    {
        $dql = $this->getEntityManager()->createQueryBuilder()
            ->select('f.codExterno', 'f.indUtilizaRessuprimento', 'prod.id produto', 'prod.grade', 'ex.id expedicao', 'pp.quantidade')
            ->from('wms:Expedicao\PedidoProduto', 'pp')
            ->innerJoin('pp.pedido', 'p')
            ->innerJoin('wms:Filial', 'f', 'WITH', 'f.codExterno = p.centralEntrega')
            ->innerJoin('pp.produto', 'prod')
            ->innerJoin('p.carga', 'c')
            ->innerJoin('c.expedicao', 'ex')
            ->where("pp.codPedido = $idPedido");

        return $dql->getQuery()->getResult();
    }

    public function identificaExpedicaoPedido($dados)
    {
        $produto = $dados['produto'];
        $grade = $dados['grade'];
        $expedicao = $dados['expedicao'];
        $dql = $this->getEntityManager()->createQueryBuilder()
            ->select('re.id reservaEstoque')
            ->from('wms:Ressuprimento\ReservaEstoqueProduto', 'rep')
            ->innerJoin('rep.produto', 'p')
            ->innerJoin('rep.reservaEstoque', 're')
            ->innerJoin('wms:Ressuprimento\ReservaEstoqueExpedicao', 'ree', 'WITH', 'ree.reservaEstoque = re.id')
            ->innerJoin('ree.expedicao', 'ex')
            ->where("p.id = $produto AND p.grade = '$grade' AND ex.id = $expedicao")
            ->groupBy('re.id');

        return $dql->getQuery()->getResult();
    }

    public function compareMapaProdutoByPedido($produtoWms)
    {
        $sql = $this->getEntityManager()->createQueryBuilder()
            ->select('(msp.qtdSeparar * msp.qtdEmbalagem) AS corteMaximo, msp.id')
            ->from('wms:Expedicao\MapaSeparacaoProduto','msp')
            ->innerJoin('msp.mapaSeparacao','ms')
            ->innerJoin('wms:Expedicao\MapaSeparacaoPedido','msped','WITH','ms.id = msped.mapaSeparacao')
            ->innerJoin('wms:Expedicao\PedidoProduto','pp','WITH','pp.id = msped.pedidoProduto AND pp.codProduto = msp.codProduto AND pp.grade = msp.dscGrade')
            ->where("pp.id = $produtoWms[pedidoProduto]")
            ->andWhere("pp.codProduto = $produtoWms[codProduto]")
            ->andWhere("pp.grade = '$produtoWms[grade]'")
            ->orderBy('msp.qtdSeparar * msp.qtdEmbalagem','ASC');

        return $sql->getQuery()->getResult();
    }

}