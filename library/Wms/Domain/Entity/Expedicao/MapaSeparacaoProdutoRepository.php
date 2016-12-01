<?php
namespace Wms\Domain\Entity\Expedicao;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Symfony\Component\Console\Output\NullOutput;
use Wms\Domain\Entity\Expedicao;

class MapaSeparacaoProdutoRepository extends EntityRepository
{
    public function getMapaProdutoByProdutoAndMapa($idMapa, $idProduto, $grade)
    {
        $sql = $this->getEntityManager()->createQueryBuilder()
            ->select('SUM(msp.qtdSeparar * pe.quantidade) qtdSeparar')
            ->from('wms:Expedicao\MapaSeparacao', 'ms')
            ->innerJoin('wms:Expedicao\MapaSeparacaoProduto', 'msp', 'WITH', 'msp.mapaSeparacao = ms.id')
            ->leftJoin('wms:Produto\Embalagem', 'pe', 'WITH', 'pe.id = msp.produtoEmbalagem')
            ->where("ms.id = $idMapa AND msp.codProduto = '$idProduto' AND msp.dscGrade = '$grade'");

        return $sql->getQuery()->getResult();
    }

    public function getMapaProdutoByMapa($idMapa)
    {
        $sql = $this->getEntityManager()->createQueryBuilder()
            ->select('ms.id, msp.codProduto, msp.dscGrade')
            ->from('wms:Expedicao\MapaSeparacao', 'ms')
            ->innerJoin('wms:Expedicao\MapaSeparacaoProduto', 'msp', 'WITH', 'msp.mapaSeparacao = ms.id')
            ->innerJoin('wms:Expedicao\PedidoProduto', 'pp', 'WITH', 'pp.id = msp.codPedidoProduto')
            ->innerJoin('wms:Expedicao\Pedido', 'p', 'WITH', 'p.id = pp.codPedido')
            ->where("ms.id = $idMapa")
            ->groupBy('ms.id, msp.codProduto, msp.dscGrade, p.id');

        return $sql->getQuery()->getResult();
    }

    public function getMapaProduto($idMapa)
    {
        $sql = $this->getEntityManager()->createQueryBuilder()
            ->select('msp')
            ->from('wms:Expedicao\MapaSeparacaoProduto', 'msp')
            ->leftJoin('msp.codDepositoEndereco', 'de')
            ->where("msp.mapaSeparacao = $idMapa")
            ->orderBy('msp.numCarrinho, de.rua, de.predio, de.nivel, de.apartamento, msp.numCaixaInicio, msp.numCaixaFim');

        return $sql->getQuery()->getResult();
    }

    public function getMapaProdutoByExpedicao($idExpedicao)
    {
        $sql = $this->getEntityManager()->createQueryBuilder()
            ->select('p.id, p.descricao, NVL(pe.codigoBarras, pv.codigoBarras) codigoBarras, NVL(pe.descricao, pv.descricao) unidadeMedida')
            ->from('wms:Expedicao\MapaSeparacao', 'ms')
            ->innerJoin('wms:Expedicao\MapaSeparacaoProduto', 'msp', 'WITH', 'msp.mapaSeparacao = ms.id')
            ->innerJoin('msp.produto', 'p')
            ->leftJoin('wms:Produto\Embalagem', 'pe', 'WITH', 'p.id = pe.codProduto AND p.grade = pe.grade AND msp.produtoEmbalagem = pe.id')
            ->leftJoin('wms:Produto\Volume', 'pv', 'WITH', 'p.id = pv.codProduto AND p.grade = pv.grade AND msp.produtoVolume = pv.id')
            ->where("ms.expedicao = $idExpedicao")
            ->andWhere("pe.imprimirCB = 'S'");

        return $sql->getQuery()->getResult();
    }

    public function getCaixasByExpedicao($expedicaoEntity,$pedidoEntity,$novoCliente)
    {
        $sql = $this->getEntityManager()->createQueryBuilder()
            ->select('MAX(msp.numCaixaInicio) AS numCaixaInicio, MAX(msp.numCaixaFim) AS numCaixaFim, SUM(msp.cubagem) AS cubagem')
            ->from('wms:Expedicao\MapaSeparacao', 'ms')
            ->innerJoin('wms:Expedicao\MapaSeparacaoProduto', 'msp', 'WITH', 'msp.mapaSeparacao = ms.id')
            ->innerJoin('wms:Expedicao\PedidoProduto', 'pp', 'WITH', 'msp.codPedidoProduto = pp.id')
            ->innerJoin('wms:Expedicao\Pedido', 'p', 'WITH', 'p.id = pp.codPedido')
            ->where("ms.expedicao = ".$expedicaoEntity->getId())
            ->andWhere("msp.numCaixaInicio is not null and msp.numCaixaFim is not null")
            ->orderBy('msp.id, msp.numCaixaInicio, msp.numCaixaFim', 'DESC');

        if ($novoCliente == false && isset($pedidoEntity) && !empty($pedidoEntity)) {
            $sql->andWhere("p.pessoa = ".$pedidoEntity->getPessoa()->getId());
        }

        return $sql->getQuery()->getResult();

    }

    public function verificaConsistenciaSeguranca($idExpedicao)
    {
        $sql = "SELECT PP.COD_PRODUTO, PP.DSC_GRADE, PP.QTD_PEDIDO, MSP.QTD_MAPA
                    FROM (SELECT SUM(PP.QUANTIDADE - PP.QTD_CORTADA) AS QTD_PEDIDO, PP.COD_PRODUTO, PP.DSC_GRADE
                      FROM PEDIDO P
                      INNER JOIN PEDIDO_PRODUTO PP ON PP.COD_PEDIDO = P.COD_PEDIDO
                      INNER JOIN CARGA C ON P.COD_CARGA = C.COD_CARGA
                      WHERE C.COD_EXPEDICAO = $idExpedicao AND P.IND_ETIQUETA_MAPA_GERADO = 'S'
                      GROUP BY PP.COD_PRODUTO, PP.DSC_GRADE) PP
                    LEFT JOIN (
                      SELECT SUM(MSP.QTD_SEPARAR * MSP.QTD_EMBALAGEM) AS QTD_MAPA, MSP.COD_PRODUTO, MSP.DSC_GRADE
                      FROM MAPA_SEPARACAO MS
                      INNER JOIN MAPA_SEPARACAO_PRODUTO MSP ON MSP.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO
                      WHERE MS.COD_EXPEDICAO = $idExpedicao
                      GROUP BY MSP.COD_PRODUTO, MSP.DSC_GRADE) MSP ON MSP.COD_PRODUTO = PP.COD_PRODUTO AND MSP.DSC_GRADE = PP.DSC_GRADE
                    WHERE QTD_PEDIDO <> QTD_MAPA";

        return $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);

    }

}