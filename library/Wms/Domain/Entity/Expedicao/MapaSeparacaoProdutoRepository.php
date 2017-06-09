<?php
namespace Wms\Domain\Entity\Expedicao;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Symfony\Component\Console\Output\NullOutput;
use Wms\Domain\Entity\Expedicao;
use Wms\Math;

class MapaSeparacaoProdutoRepository extends EntityRepository
{

    public function efetivaCorteMapasERP($pedidosCortar, $produtosCortar) {

        $math = new Math();

        //* SE NÃO TIVER NENHUM PRODUTO PARA CORTAR, ENTÂO NAO PRECISO FAZER NENHUM CORTE EM NENHUM MAPA, RETORNO TRUE
        if (count($produtosCortar) == 0) return true;

        // VERIFICO TODOS OS PEDIDOS_PRODUTOS QUE TEM QUE SER CORTADOS
        $SQLWherePP = "(";
        foreach ($produtosCortar as $pp) {
            if ($SQLWherePP != "(") {
                $SQLWherePP .= " OR ";
            }
            $SQLWherePP .= "(PP.COD_PRODUTO = '" . $pp['codProduto'] . "' AND PP.DSC_GRADE = '" . $pp['grade'] ."')";
        }
        $SQLWherePP .= ")";
        $SQL = " SELECT PP.COD_PEDIDO_PRODUTO, PP.QTD_CORTADA, PP.COD_PRODUTO, PP.DSC_GRADE, PP.COD_PEDIDO, P.DSC_PRODUTO
                   FROM PEDIDO_PRODUTO PP
                   LEFT JOIN PRODUTO P ON P.COD_PRODUTO = PP.COD_PRODUTO AND P.DSC_GRADE = PP.DSC_GRADE
                  WHERE PP.COD_PEDIDO IN($pedidosCortar)
                    AND $SQLWherePP
                    AND QTD_CORTADA > 0";
        $ppCortar = $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($ppCortar as $pp) {
            $qtdCortar = $pp['QTD_CORTADA'];
            $codPP = $pp['COD_PEDIDO_PRODUTO'];
            $codProduto = $pp['COD_PRODUTO'];
            $dscGrade = $pp['DSC_GRADE'];
            $dscProduto = $pp['DSC_PRODUTO'];
            $codPedido = $pp['COD_PEDIDO'];

            $SQL = "SELECT MSP.COD_MAPA_SEPARACAO_PRODUTO,
                           (MSP.QTD_SEPARAR * MSP.QTD_EMBALAGEM) as QTD_SEPARAR,
                           P.COD_PESSOA,
                           MSP.COD_MAPA_SEPARACAO
                      FROM MAPA_SEPARACAO_PRODUTO MSP
                     INNER JOIN MAPA_SEPARACAO_QUEBRA MSQ ON MSQ.COD_MAPA_SEPARACAO = MSP.COD_MAPA_SEPARACAO AND MSQ.IND_TIPO_QUEBRA = 'T'
                     INNER JOIN PEDIDO_PRODUTO PP ON PP.COD_PEDIDO_PRODUTO = MSP.COD_PEDIDO_PRODUTO
                     INNER JOIN PEDIDO P ON P.COD_PEDIDO = PP.COD_PEDIDO
                     WHERE MSP.COD_PEDIDO_PRODUTO = " . $codPP . " ORDER BY COD_MAPA_SEPARACAO";
            $mspEmbalados = $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);

            $SQL = " SELECT MSP.COD_MAPA_SEPARACAO, MSP.COD_MAPA_SEPARACAO_PRODUTO, (MSP.QTD_SEPARAR * MSP.QTD_EMBALAGEM) as QTD_PRODUTO
                       FROM MAPA_SEPARACAO_PRODUTO MSP
                      INNER JOIN MAPA_SEPARACAO_QUEBRA MSQ ON MSQ.COD_MAPA_SEPARACAO = MSP.COD_MAPA_SEPARACAO
                      INNER JOIN (SELECT *
                                    FROM MAPA_SEPARACAO_PEDIDO MSP
                                    LEFT JOIN PEDIDO_PRODUTO PP ON PP.COD_PEDIDO_PRODUTO = MSP.COD_PEDIDO_PRODUTO
                                   WHERE MSP.COD_PEDIDO_PRODUTO = $codPP) MSPED
                              ON MSPED.COD_MAPA_SEPARACAO = MSP.COD_MAPA_SEPARACAO
                             AND MSPED.COD_PRODUTO = MSP.COD_PRODUTO
                             AND MSPED.DSC_GRADE = MSP.DSC_GRADE
                      WHERE MSQ.IND_TIPO_QUEBRA <> 'T'";
            $mspNaoEmbalados = $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);

            if (count($mspEmbalados) > 0) {
                $codPessoa = $mspEmbalados[0]['COD_PESSOA'];
                $codMapa = $mspEmbalados[0]['COD_MAPA_SEPARACAO'];
                $qtdConferida = 0;

                $SQL = "SELECT COD_PRODUTO, DSC_GRADE, SUM(QTD_CONFERIDA * QTD_EMBALAGEM) as QTD_CONF, COD_PESSOA, COD_MAPA_SEPARACAO
                         FROM MAPA_SEPARACAO_CONFERENCIA
                        WHERE COD_PRODUTO = '$codProduto'
                          AND DSC_GRADE = '$dscGrade'
                          AND COD_PESSOA = $codPessoa
                          AND COD_MAPA_SEPARACAO = $codMapa
                        GROUP BY COD_PRODUTO, DSC_GRADE, COD_PESSOA, COD_MAPA_SEPARACAO";
                $confEmbalados =  $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);
                if (count($confEmbalados)>0) {
                    $qtdConferida = $confEmbalados[0]['QTD_CONF'];
                }

                foreach ($mspEmbalados as $mapa) {
                    $mspId = $mapa['COD_MAPA_SEPARACAO_PRODUTO'];
                    $qtdSepararMapa = $mapa['QTD_SEPARAR'];
                    if ($qtdConferida >= $qtdSepararMapa) {
                        $qtdConfMSP = $qtdSepararMapa;
                    } else {
                        $qtdConfMSP = $qtdConferida;
                    }
                    $qtdPendente = $math->totalSubtracao($qtdSepararMapa,$qtdConfMSP );// $qtdSepararMapa - $qtdConfMSP;
                    if ($qtdPendente >0) {
                        $mspEn = $this->find($mspId);
                        if ($mspEn != null) {
                            $mspEn->setQtdCortado($qtdPendente);
                            $this->getEntityManager()->persist($mspEn);
                            $qtdCortar = $math->totalSubtracao($qtdCortar,$qtdPendente);// $qtdCortar - $qtdPendente;
                        }
                    }
                    $qtdConferida = $math->totalSubtracao($qtdConferida,$qtdConfMSP); //$qtdConferida - $qtdConfMSP;
                }
            }

            if (count($mspNaoEmbalados) > 0) {
                $codMapa = $mspNaoEmbalados[0]['COD_MAPA_SEPARACAO'];
                $qtdConferida = 0;

                $SQL = "SELECT COD_PRODUTO, DSC_GRADE, SUM(QTD_CONFERIDA * QTD_EMBALAGEM) as QTD_CONF, COD_MAPA_SEPARACAO
                         FROM MAPA_SEPARACAO_CONFERENCIA
                        WHERE COD_PRODUTO = '$codProduto'
                          AND DSC_GRADE = '$dscGrade'
                          AND COD_MAPA_SEPARACAO = $codMapa
                        GROUP BY COD_PRODUTO, DSC_GRADE, COD_PESSOA, COD_MAPA_SEPARACAO";
                $confNEmbalados =  $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);
                if (count($confNEmbalados)>0) {
                    $qtdConferida = $confNEmbalados[0]['QTD_CONF'];
                }

                foreach ($mspNaoEmbalados as $mapa) {
                    $mspId = $mapa['COD_MAPA_SEPARACAO_PRODUTO'];
                    $qtdSepararMapa = $mapa['QTD_PRODUTO'];
                    if ($qtdConferida >= $qtdSepararMapa) {
                        $qtdConfMSP = $qtdSepararMapa;
                    } else {
                        $qtdConfMSP = $qtdConferida;
                    }
                    $qtdPendente = $math->totalSubtracao($qtdSepararMapa,$qtdConfMSP);// $qtdSepararMapa - $qtdConfMSP;
                    if ($qtdPendente >0) {
                        $mspEn = $this->find($mspId);
                        if ($mspEn != null) {
                            $mspEn->setQtdCortado($qtdPendente);
                            $this->getEntityManager()->persist($mspEn);

                            $qtdCortar = $math->totalSubtracao($qtdCortar,$qtdPendente);// $qtdCortar - $qtdPendente;
                        }
                    }
                    $qtdConferida = $math->totalSubtracao($qtdConferida,$qtdConfMSP);// $qtdConferida - $qtdConfMSP;
                }
            }

            if ($qtdCortar >0) {
                throw new \Exception("Quantidade Cortada + Quantidade Conferida do Produto excede a quantidade solicitada no pedido $codPedido para o produto $codProduto/$dscGrade - $dscProduto");
            }

        }

        $this->getEntityManager()->flush();
        return true;
    }

    public function validaCorteMapasERP($produtosWMS) {

        $arrayPedidos = array();
        foreach ($produtosWMS as $pp) {
            $pedido = $pp['pedido'];
            if (!in_array($pedido,$arrayPedidos)) {
                $arrayPedidos[] = $pedido;
            }
        }
        $pedidos = implode(",",$arrayPedidos);

        $SQL = " SELECT DISTINCT MSP.COD_MAPA_SEPARACAO
                   FROM MAPA_SEPARACAO_PEDIDO MSP
                   LEFT JOIN PEDIDO_PRODUTO PP ON PP.COD_PEDIDO_PRODUTO = MSP.COD_PEDIDO_PRODUTO
                  WHERE PP.COD_PEDIDO IN ($pedidos)";
        $mapas =  $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);

        $mapaArray = array();
        foreach ($mapas as $mapa) {
            $mapaArray[] = $mapa['COD_MAPA_SEPARACAO'];
        }
        $mapas = implode(",",$mapaArray);

        $SQL = "SELECT MSP.COD_PRODUTO,
                       MSP.DSC_GRADE,
                       SUM(MSP.QTD_SEPARAR) as QTD_TOTAL,
                       SUM(NVL(MSC.QTD_CONF,0)) as QTD_CONF,
                       NVL(C.CORTE,0) as QTD_CORTE,
                       PROD.DSC_PRODUTO
                  FROM MAPA_SEPARACAO MS
                  LEFT JOIN (SELECT MSP.COD_MAPA_SEPARACAO,
                                    MSP.COD_PRODUTO,
                                    MSP.DSC_GRADE,
                                    SUM(MSP.QTD_SEPARAR * MSP.QTD_EMBALAGEM) as QTD_SEPARAR
                               FROM MAPA_SEPARACAO MS
                              INNER JOIN MAPA_SEPARACAO_PRODUTO MSP ON MSP.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO
                              GROUP BY MSP.COD_MAPA_SEPARACAO, MSP.COD_PRODUTO, MSP.DSC_GRADE) MSP
                    ON MSP.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO
                  LEFT JOIN (SELECT COD_MAPA_SEPARACAO,
                                    COD_PRODUTO,
                                    DSC_GRADE,
                                    SUM(QTD_CONFERIDA * QTD_EMBALAGEM) AS QTD_CONF
                               FROM MAPA_SEPARACAO_CONFERENCIA GROUP BY COD_MAPA_SEPARACAO, COD_PRODUTO, DSC_GRADE) MSC
                    ON MSC.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO AND MSC.COD_PRODUTO = MSP.COD_PRODUTO
                  LEFT JOIN (SELECT PP.COD_PRODUTO, PP.DSC_GRADE, SUM(PP.QTD_CORTADA) as CORTE
                               FROM PEDIDO_PRODUTO PP
                              WHERE PP.COD_PEDIDO IN ($pedidos)
                               GROUP BY PP.COD_PRODUTO, PP.DSC_GRADE) C ON C.COD_PRODUTO = MSP.COD_PRODUTO AND C.DSC_GRADE = MSP.DSC_GRADE
                  LEFT JOIN PRODUTO PROD ON PROD.COD_PRODUTO = MSP.COD_PRODUTO AND PROD.DSC_GRADE = MSP.DSC_GRADE
                 WHERE MS.COD_MAPA_SEPARACAO IN ($mapas)
                   AND C.CORTE >0
                 GROUP BY MSP.COD_PRODUTO, MSP.DSC_GRADE, C.CORTE, PROD.DSC_PRODUTO";
        $produtos =  $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);
        $produtosCortar = array();

        foreach ($produtos as $produto) {
            $codProduto = $produto['COD_PRODUTO'];
            $grade = $produto['DSC_GRADE'];
            $qtdMapa = $produto['QTD_TOTAL'];
            $qtdConferido = $produto['QTD_CONF'];
            $qtdCorte = $produto['QTD_CORTE'];
            $dscProduto = $produto['DSC_PRODUTO'];
            if ($qtdCorte + $qtdConferido > $qtdMapa) {
                throw new \Exception("Quantidade conferida ($qtdConferido) + Quantidade Cortada no ERP ($qtdCorte), excede a quantidade solicitada na separação para o produto $codProduto/$grade - $dscProduto");
            }
            if ($qtdCorte + $qtdConferido == $qtdMapa) {
                $produtosCortar[] = array('codProduto' =>$codProduto,
                                          'grade'=>$grade);
            }
        }

        return $this->efetivaCorteMapasERP($pedidos,$produtosCortar);
    }

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
            ->orderBy('de.rua, de.predio, de.nivel, de.apartamento, msp.numCaixaInicio, msp.numCaixaFim');

        return $sql->getQuery()->getResult();
    }

    public function getMapaProdutoByExpedicao($idExpedicao)
    {
//        $sql = $this->getEntityManager()->createQueryBuilder()
//            ->select('p.id, p.descricao, pe.codigoBarras codigoBarras, e.descricao endereco')
//            ->from('wms:Produto\Embalagem', 'pe')
//            ->innerJoin('pe.endereco', 'e')
//            ->innerJoin('pe.produto', 'p')
//            ->where("pe.imprimirCB = 'S'");



        $sql = $this->getEntityManager()->createQueryBuilder()
            ->select('p.id, p.grade, p.descricao, NVL(pe.codigoBarras, pv.codigoBarras) codigoBarras, NVL(pe.descricao, pv.descricao) unidadeMedida')
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
        $sql = "SELECT *
                    FROM (SELECT SUM(PP.QUANTIDADE - NVL(PP.QTD_CORTADA,0)) AS QTD_PEDIDO, PP.COD_PRODUTO, PP.DSC_GRADE
                      FROM PEDIDO P
                      INNER JOIN PEDIDO_PRODUTO PP ON PP.COD_PEDIDO = P.COD_PEDIDO
                      INNER JOIN CARGA C ON P.COD_CARGA = C.COD_CARGA
                      WHERE C.COD_EXPEDICAO = $idExpedicao AND P.IND_ETIQUETA_MAPA_GERADO = 'S'
                      GROUP BY PP.COD_PRODUTO, PP.DSC_GRADE) PP
                    LEFT JOIN (
                      SELECT SUM((MSP.QTD_SEPARAR * MSP.QTD_EMBALAGEM) - NVL(QTD_CORTADO,0)) AS QTD_MAPA, MSP.COD_PRODUTO, MSP.DSC_GRADE
                      FROM MAPA_SEPARACAO MS
                      INNER JOIN MAPA_SEPARACAO_PRODUTO MSP ON MSP.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO
                      WHERE MS.COD_EXPEDICAO = $idExpedicao
                      GROUP BY MSP.COD_PRODUTO, MSP.DSC_GRADE) MSP ON MSP.COD_PRODUTO = PP.COD_PRODUTO AND MSP.DSC_GRADE = PP.DSC_GRADE
                    WHERE QTD_PEDIDO <> QTD_MAPA";

        return $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);

    }

}