<?php
namespace Wms\Domain\Entity\Expedicao;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Symfony\Component\Console\Output\NullOutput;
use Wms\Domain\Entity\Expedicao;
use Wms\Domain\Entity\Produto\Lote;
use Wms\Math;

class MapaSeparacaoProdutoRepository extends EntityRepository
{

    public function efetivaCorteMapasERP($pedidosCortar, $produtosCortar) {

        /** @var \Wms\Domain\Entity\Expedicao\AndamentoRepository $andamentoRepo */
        $andamentoRepo  = $this->_em->getRepository('wms:Expedicao\Andamento');

        $idExpedicao = null;
        $cortarReservas = array();
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
        $SQL = " SELECT PP.COD_PEDIDO_PRODUTO, PP.QTD_CORTADA, PP.COD_PRODUTO, PP.DSC_GRADE, PP.COD_PEDIDO, P.DSC_PRODUTO, C.COD_EXPEDICAO
                   FROM PEDIDO_PRODUTO PP
                   LEFT JOIN PRODUTO P ON P.COD_PRODUTO = PP.COD_PRODUTO AND P.DSC_GRADE = PP.DSC_GRADE
                   LEFT JOIN PEDIDO PED ON PED.COD_PEDIDO = PP.COD_PEDIDO
                   LEFT JOIN CARGA C ON C.COD_CARGA = PED.COD_CARGA
                  WHERE PP.COD_PEDIDO IN($pedidosCortar)
                    AND $SQLWherePP
                    AND QTD_CORTADA > 0";
        $ppCortar = $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);
        if (count($ppCortar) >0) {
            $idExpedicao = $ppCortar[0]['COD_EXPEDICAO'];
        }

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
                    $qtdPendente = Math::subtrair($qtdSepararMapa,$qtdConfMSP );// $qtdSepararMapa - $qtdConfMSP;
                    if ($qtdPendente >0) {
                        $mspEn = $this->find($mspId);
                        if ($mspEn != null) {
                            $qtdCortarReserva = $qtdPendente - $mspEn->getQtdCortado();
                            if ($qtdCortarReserva >0) {
                                if (isset($cortarReservas[$codProduto][$dscGrade][$mspEn->getCodDepositoEndereco()][$codPedido])){
                                    $vlr = $cortarReservas[$codProduto][$dscGrade][$mspEn->getCodDepositoEndereco()][$codPedido];
                                    $cortarReservas[$codProduto][$dscGrade][$mspEn->getCodDepositoEndereco()][$codPedido] = $vlr + $qtdCortarReserva;
                                } else {
                                    $cortarReservas[$codProduto][$dscGrade][$mspEn->getCodDepositoEndereco()][$codPedido] = $qtdCortarReserva;
                                }
                            }

                            $mspEn->setQtdCortado($qtdPendente);
                            $this->getEntityManager()->persist($mspEn);
                            $qtdCortar = Math::subtrair($qtdCortar,$qtdPendente);// $qtdCortar - $qtdPendente;
                        }
                    }
                    $qtdConferida = Math::subtrair($qtdConferida,$qtdConfMSP); //$qtdConferida - $qtdConfMSP;
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
                    $qtdPendente = Math::subtrair($qtdSepararMapa,$qtdConfMSP);// $qtdSepararMapa - $qtdConfMSP;
                    if ($qtdPendente >0) {
                        $mspEn = $this->find($mspId);
                        if ($mspEn != null) {
                            $qtdCortarReserva = $qtdPendente - $mspEn->getQtdCortado();
                            if ($qtdCortarReserva >0) {
                                if (isset($cortarReservas[$codProduto][$dscGrade][$mspEn->getCodDepositoEndereco()][$codPedido])){
                                    $vlr = $cortarReservas[$codProduto][$dscGrade][$mspEn->getCodDepositoEndereco()][$codPedido];
                                    $cortarReservas[$codProduto][$dscGrade][$mspEn->getCodDepositoEndereco()][$codPedido] = $vlr + $qtdCortarReserva;
                                } else {
                                    $cortarReservas[$codProduto][$dscGrade][$mspEn->getCodDepositoEndereco()][$codPedido] = $qtdCortarReserva;
                                }
                            }
                            $mspEn->setQtdCortado($qtdPendente);
                            $this->getEntityManager()->persist($mspEn);

                            $qtdCortar = Math::subtrair($qtdCortar,$qtdPendente);// $qtdCortar - $qtdPendente;
                        }
                    }
                    $qtdConferida = Math::subtrair($qtdConferida,$qtdConfMSP);// $qtdConferida - $qtdConfMSP;
                }
            }

            if ($qtdCortar >0) {
                throw new \Exception("Quantidade Cortada + Quantidade Conferida do Produto excede a quantidade solicitada no pedido $codPedido para o produto $codProduto/$dscGrade - $dscProduto");
            }

        }

        $reservaEstoqueProdutoRepo = $this->getEntityManager()->getRepository('wms:Ressuprimento\ReservaEstoqueProduto');

        foreach ($cortarReservas as $idProduto => $arrGrade) {
            foreach ($arrGrade as $dscGrade => $arrEndereco) {
                foreach ($arrEndereco as $idEndereco => $arrPedido) {
                    foreach ($arrPedido as $idPedido => $qtdCortar) {

                        $observacao = 'Produto '. $idProduto.' Grade '.$dscGrade.' referente ao pedido '.$idPedido.' cortado com a quantidade '. $qtdCortar . '- motivo: '. 'Cortes importados via integração';
                        $andamentoRepo->save($observacao, $idExpedicao);

                        if ($idEndereco == null) $idEndereco = 0;

                        $SQL = "SELECT REE.COD_RESERVA_ESTOQUE
                                  FROM RESERVA_ESTOQUE_EXPEDICAO REE
                                  LEFT JOIN RESERVA_ESTOQUE_PRODUTO REP ON REE.COD_RESERVA_ESTOQUE = REP.COD_RESERVA_ESTOQUE
                                  LEFT JOIN RESERVA_ESTOQUE RE ON RE.COD_RESERVA_ESTOQUE = REE.COD_RESERVA_ESTOQUE
                                 WHERE REE.COD_PEDIDO = '$idPedido'
                                   AND REE.COD_EXPEDICAO = '$idExpedicao'
                                   AND RE.COD_DEPOSITO_ENDERECO = $idEndereco
                                   AND REP.COD_PRODUTO = '$idProduto'
                                   AND REP.DSC_GRADE = '$dscGrade'
                                   AND RE.IND_ATENDIDA = 'N'";

                        $result = $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);

                        if (count($result) >0) {
                            $idReservaEstoque = $result[0]['COD_RESERVA_ESTOQUE'];
                            $entityReservaEstoqueProduto = $reservaEstoqueProdutoRepo->findBy(array('reservaEstoque' => $idReservaEstoque));
                            foreach ($entityReservaEstoqueProduto as $reservaEstoqueProduto) {
                                $qtdReservada = $reservaEstoqueProduto->getQtd();
                                if ($qtdCortar + $qtdReservada == 0) {
                                    $reservaEstoqueEn = $reservaEstoqueProduto->getReservaEstoque();
                                    $reservaEstoqueEn->setAtendida("C");
                                    $this->getEntityManager()->remove($reservaEstoqueProduto);
                                } else {
                                    $reservaEstoqueProduto->setQtd($qtdReservada + $qtdCortar);
                                    $this->getEntityManager()->persist($reservaEstoqueProduto);
                                }
                            }
                        }
                    }
                }
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
        $pedidos = "'".implode("','",$arrayPedidos)."'";

        $SQL = " SELECT DISTINCT MSP.COD_MAPA_SEPARACAO
                   FROM MAPA_SEPARACAO_PEDIDO MSP
                   LEFT JOIN PEDIDO_PRODUTO PP ON PP.COD_PEDIDO_PRODUTO = MSP.COD_PEDIDO_PRODUTO
                  WHERE PP.COD_PEDIDO IN ($pedidos)";
        $mapas =  $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);

        if (empty($mapas)) return true;

        $mapaArray = array();
        foreach ($mapas as $mapa) {
            $mapaArray[] = $mapa['COD_MAPA_SEPARACAO'];
        }
        $mapas = implode(",",$mapaArray);

        $whereMapas = '';
        if (isset($mapas) && !empty($mapas)) {
            $whereMapas = " AND MS.COD_MAPA_SEPARACAO IN ($mapas) ";
        } else {
            $mapas = 0;
        }

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
                               FROM MAPA_SEPARACAO_CONFERENCIA
                              WHERE COD_MAPA_SEPARACAO IN ($mapas)
                               GROUP BY COD_MAPA_SEPARACAO, COD_PRODUTO, DSC_GRADE) MSC
                    ON MSC.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO AND MSC.COD_PRODUTO = MSP.COD_PRODUTO AND MSC.DSC_GRADE = MSP.DSC_GRADE 
                  LEFT JOIN (SELECT PP.COD_PRODUTO, PP.DSC_GRADE, SUM(PP.QTD_CORTADA) as CORTE
                               FROM PEDIDO_PRODUTO PP
                              WHERE PP.COD_PEDIDO IN ($pedidos)
                               GROUP BY PP.COD_PRODUTO, PP.DSC_GRADE) C ON C.COD_PRODUTO = MSP.COD_PRODUTO AND C.DSC_GRADE = MSP.DSC_GRADE
                  LEFT JOIN PRODUTO PROD ON PROD.COD_PRODUTO = MSP.COD_PRODUTO AND PROD.DSC_GRADE = MSP.DSC_GRADE
                 WHERE 1 = 1 $whereMapas
                      AND C.CORTE > 0
                 GROUP BY MSP.COD_PRODUTO, MSP.DSC_GRADE, C.CORTE, PROD.DSC_PRODUTO";
        $produtos =  $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);
        $produtosCortar = array();

        $falhas = array();
        $corteTotal = array();
        $corteParcial = array();
        foreach ($produtos as $produto) {
            $codProduto = $produto['COD_PRODUTO'];
            $grade = $produto['DSC_GRADE'];
            $qtdMapa = $produto['QTD_TOTAL'];
            $qtdConferido = $produto['QTD_CONF'];
            $qtdCorte = $produto['QTD_CORTE'];
            $dscProduto = $produto['DSC_PRODUTO'];
            if ($qtdCorte + $qtdConferido > $qtdMapa) {
                if ($qtdCorte == $qtdConferido) {
                    $corteTotal[] = $codProduto;
                } else {
                    $corteParcial[] = $codProduto;
                }
                $falhas[] = "Quantidade conferida ($qtdConferido) + Quantidade Cortada no ERP ($qtdCorte), excede a quantidade solicitada na separação para o produto $codProduto/$grade - $dscProduto";

            }
            if ($qtdCorte + $qtdConferido == $qtdMapa) {
                $produtosCortar[] = array('codProduto' =>$codProduto,
                                          'grade'=>$grade);
            }
        }

        if (count($falhas) >0) {
            $corteTotal = implode(",",$corteTotal);
            $corteParcial = implode(",",$corteParcial);

            throw new \Exception($falhas[0]);
        }

        return $this->efetivaCorteMapasERP($pedidos,$produtosCortar);
    }

    public function getMapaProdutoByProdutoAndMapa($idMapa, $idProduto, $grade)
    {
        $sql = $this->getEntityManager()->createQueryBuilder()
            ->select('msp.codDepositoEndereco, SUM(msp.qtdSeparar * pe.quantidade) qtdSeparar')
            ->from('wms:Expedicao\MapaSeparacao', 'ms')
            ->innerJoin('wms:Expedicao\MapaSeparacaoProduto', 'msp', 'WITH', 'msp.mapaSeparacao = ms.id')
            ->leftJoin('wms:Produto\Embalagem', 'pe', 'WITH', 'pe.id = msp.produtoEmbalagem')
            ->where("ms.id = $idMapa AND msp.codProduto = '$idProduto' AND msp.dscGrade = '$grade'")
            ->groupBy('msp.codDepositoEndereco');

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
            ->select("msp")
            ->addSelect("CASE WHEN sr.sentido = 'C' THEN de.predio ELSE 1 end as crescente")
            ->addSelect("CASE WHEN sr.sentido = 'D' THEN de.predio ELSE 1 end as decrescente")
            ->addSelect("CASE WHEN sr.sentido = 'C' THEN de.apartamento ELSE 1 end as crescenteApto")
            ->addSelect("CASE WHEN sr.sentido = 'D' THEN de.apartamento ELSE 1 end as decrescenteApto")
            ->from('wms:Expedicao\MapaSeparacaoProduto', 'msp')
            ->innerJoin('wms:Produto','p', 'WITH', 'p.id = msp.codProduto and p.grade = msp.dscGrade')
            ->leftJoin('msp.depositoEndereco', 'de')
            ->leftJoin('wms:Deposito\Endereco\SentidoRua', 'sr', 'WITH', 'sr.rua = de.rua AND sr.deposito = de.deposito')
            ->where("msp.mapaSeparacao = $idMapa")
            ->orderBy("de.rua",'ASC')
            ->addOrderBy("crescente",'ASC')
            ->addOrderBy("decrescente", 'DESC')
            ->addOrderBy('de.nivel','ASC')
            ->addOrderBy('crescenteApto','ASC')
            ->addOrderBy('decrescenteApto','DESC')
            ->addOrderBy('msp.numCaixaInicio, msp.numCaixaFim','ASC')
            ->addOrderBy('p.descricao', 'ASC');

        return $sql->getQuery()->getResult();
    }

    public function getMapaProdutoByExpedicao($idExpedicao)
    {

        $sql = $this->getEntityManager()->createQueryBuilder()
            ->select('ms.id codMapa, ms.dscQuebra, p.id, p.grade, p.descricao, NVL(pe.codigoBarras, pv.codigoBarras) codigoBarras, NVL(pe.descricao, pv.descricao) unidadeMedida')
            ->distinct(true)
            ->from('wms:Expedicao\MapaSeparacao', 'ms')
            ->innerJoin('wms:Expedicao\MapaSeparacaoProduto', 'msp', 'WITH', 'msp.mapaSeparacao = ms.id')
            ->innerJoin('msp.produto', 'p')
            ->leftJoin('wms:Produto\Embalagem', 'pe', 'WITH', 'p.id = pe.codProduto AND p.grade = pe.grade AND msp.produtoEmbalagem = pe.id')
            ->leftJoin('wms:Produto\Volume', 'pv', 'WITH', 'p.id = pv.codProduto AND p.grade = pv.grade AND msp.produtoVolume = pv.id')
            ->where("ms.expedicao = $idExpedicao")
            ->andWhere("pe.imprimirCB = 'S'")
            ->orderBy("ms.id, p.id, p.grade")
        ;

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
        $naoControlaLote = Lote::NCL;
        $sql = "SELECT *
                  FROM (SELECT (CASE WHEN PPL.DSC_LOTE IS NULL 
                                   THEN SUM(PP.QUANTIDADE - NVL(PP.QTD_CORTADA,0)) 
                                   ELSE SUM(PPL.QUANTIDADE - NVL(PPL.QTD_CORTE,0)) 
                               END) AS QTD_PEDIDO, 
                               PP.COD_PRODUTO, 
                               PP.DSC_GRADE, 
                               NVL(PPL.DSC_LOTE, '$naoControlaLote') DSC_LOTE
                          FROM PEDIDO P
                         INNER JOIN PEDIDO_PRODUTO PP ON PP.COD_PEDIDO = P.COD_PEDIDO
                          LEFT JOIN PEDIDO_PRODUTO_LOTE PPL ON PPL.COD_PEDIDO_PRODUTO = PP.COD_PEDIDO_PRODUTO
                         INNER JOIN CARGA C ON P.COD_CARGA = C.COD_CARGA
                         WHERE C.COD_EXPEDICAO = $idExpedicao AND P.IND_ETIQUETA_MAPA_GERADO = 'S'
                         GROUP BY PP.COD_PRODUTO, PP.DSC_GRADE,PPL.DSC_LOTE, NVL(PPL.DSC_LOTE, '$naoControlaLote')) PP
             LEFT JOIN (SELECT (SUM((MSP.QTD_SEPARAR * MSP.QTD_EMBALAGEM) - NVL(QTD_CORTADO,0)) / CASE WHEN COUNT(DISTINCT MSP.COD_PRODUTO_VOLUME) = 0 THEN 1 ELSE COUNT(MSP.COD_PRODUTO_VOLUME) END) AS QTD_MAPA, 
                               MSP.COD_PRODUTO, 
                               MSP.DSC_GRADE, 
                               NVL(MSP.DSC_LOTE, '$naoControlaLote') DSC_LOTE
                          FROM MAPA_SEPARACAO MS
                         INNER JOIN MAPA_SEPARACAO_PRODUTO MSP ON MSP.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO
                         WHERE MS.COD_EXPEDICAO = $idExpedicao
                           AND MS.COD_MAPA_SEPARACAO NOT IN (SELECT COD_MAPA_SEPARACAO FROM MAPA_SEPARACAO_QUEBRA WHERE IND_TIPO_QUEBRA = 'RE')
                         GROUP BY MSP.COD_PRODUTO, MSP.DSC_GRADE, NVL(MSP.DSC_LOTE, '$naoControlaLote')) MSP 
                    ON MSP.COD_PRODUTO = PP.COD_PRODUTO 
                   AND MSP.DSC_GRADE = PP.DSC_GRADE 
                   AND MSP.DSC_LOTE = PP.DSC_LOTE
             LEFT JOIN (SELECT SUM(NVL(ES.QTD_EMBALAGEM,1)) / (CASE WHEN (P.COD_TIPO_COMERCIALIZACAO = 1) THEN 1 ELSE P.NUM_VOLUMES END) QTD_ETIQUETA,  
                               ES.COD_PRODUTO, 
                               ES.DSC_GRADE, 
                               NVL(ES.DSC_LOTE, '$naoControlaLote') DSC_LOTE 
                          FROM ETIQUETA_SEPARACAO ES
                         INNER JOIN PRODUTO P ON P.COD_PRODUTO = ES.COD_PRODUTO AND P.DSC_GRADE = ES.DSC_GRADE
                         INNER JOIN ETIQUETA_MAE EM on ES.COD_ETIQUETA_MAE = EM.COD_ETIQUETA_MAE
                         INNER JOIN EXPEDICAO E on EM.COD_EXPEDICAO = E.COD_EXPEDICAO
                         WHERE E.COD_EXPEDICAO = $idExpedicao
                           AND ES.COD_STATUS NOT IN (524,525)
                         GROUP BY ES.COD_PRODUTO, ES.DSC_GRADE, P.NUM_VOLUMES, P.COD_TIPO_COMERCIALIZACAO,  NVL(ES.DSC_LOTE, '$naoControlaLote')) ETS 
                    ON ETS.COD_PRODUTO = PP.COD_PRODUTO 
                   AND ETS.DSC_GRADE = PP.DSC_GRADE 
                   AND ETS.DSC_LOTE = PP.DSC_LOTE
                 WHERE NVL(QTD_PEDIDO,0) <> (NVL(QTD_MAPA,0) + NVL(QTD_ETIQUETA,0))";

        return $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);

    }

    public function getCodBarrasByLoteMapa($mapa) {

        $dql = $this->_em->createQueryBuilder()
            ->select("distinct msp.lote, NVL(e.codigoBarras, v.codigoBarras) codigoBarras")
            ->from("wms:Expedicao\MapaSeparacaoProduto", "msp")
            ->innerJoin("msp.produto", "p")
            ->leftJoin("p.embalagens", "e", "WITH", "e.dataInativacao IS NULL and e.codigoBarras IS NOT NULL")
            ->leftJoin("p.volumes", "v", "WITH", "v.dataInativacao IS NULL and v.codigoBarras IS NOT NULL")
            ->where("msp.mapaSeparacao = :mapa")
            ->setParameter("mapa", $mapa);

        $result = $dql->getQuery()->getResult();

        $temLote = false;
        $arr = [];
        foreach ($result as $item) {
            if (!empty($item['lote'])) {
                $temLote = true;
                $arr[$item['codigoBarras']][] = $item['lote'];
            }
            else
                $arr[$item['codigoBarras']] = null;
        }

        return [$temLote, $arr];
    }

    public function getMaximosConsolidadoByCliente($idExpedicao)
    {
        $sql = "SELECT 
                    SUM(MSP.QTD_SEPARAR * PE.NUM_PESO) AS PESO_MAX,
                    SUM(MSP.CUBAGEM_TOTAL) AS CUBAGEM_MAX,
                    COUNT(DISTINCT MSP.COD_PRODUTO || '-!-' || MSP.DSC_GRADE) AS MIX_MAX,
                    SUM(MSP.QTD_SEPARAR * MSP.QTD_EMBALAGEM) AS UNIDS_MAX,
                    P.COD_PESSOA
                FROM MAPA_SEPARACAO MS
                INNER JOIN MAPA_SEPARACAO_PRODUTO MSP ON MSP.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO
                INNER JOIN MAPA_SEPARACAO_QUEBRA MSQ ON MS.COD_MAPA_SEPARACAO = MSQ.COD_MAPA_SEPARACAO AND MSQ.IND_TIPO_QUEBRA = 'T'
                INNER JOIN PRODUTO_EMBALAGEM PE ON MSP.COD_PRODUTO_EMBALAGEM = PE.COD_PRODUTO_EMBALAGEM
                INNER JOIN PEDIDO_PRODUTO PP ON PP.COD_PEDIDO_PRODUTO = MSP.COD_PEDIDO_PRODUTO
                INNER JOIN PEDIDO P ON PP.COD_PEDIDO = P.COD_PEDIDO
                WHERE MS.COD_EXPEDICAO = $idExpedicao
                GROUP BY P.COD_PESSOA";

        $result = $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);

        $arr = [];
        foreach ($result as $item) {
            $arr[$item['COD_PESSOA']] = [
                'pesoMaximo' => $item['PESO_MAX'],
                'cubagemMaxima' => $item['PESO_MAX'],
                'mixMaximo' => $item['MIX_MAX'],
                'unidadesMaxima' => $item['UNIDS_MAX'],
            ];
        }

        return $arr;
    }

    public function getCodBarrasAtivosByMapa($idExpdicao, $idMapa, $quebraColetor, $codCliente = null)
    {
        $andWhere = "";
        $sqlAppend = "";
        if (!empty($codCliente)) {
            $sqlAppend = "INNER JOIN PEDIDO_PRODUTO PP ON PP.COD_PEDIDO_PRODUTO = MSP.COD_PEDIDO_PRODUTO
                          INNER JOIN PEDIDO P ON P.COD_PEDIDO = PP.COD_PEDIDO";

            $andWhere = "AND P.COD_PESSOA = $codCliente";
        }

        if ($quebraColetor)
            $andWhere .= " AND MSP.COD_MAPA_SEPARACAO = $idMapa";

        $sql = "SELECT DISTINCT NVL(PE.COD_BARRAS, PV.COD_BARRAS) COD_BARRAS 
                FROM MAPA_SEPARACAO_PRODUTO MSP
                INNER JOIN MAPA_SEPARACAO MS ON MS.COD_MAPA_SEPARACAO = MSP.COD_MAPA_SEPARACAO
                $sqlAppend
                LEFT JOIN PRODUTO_EMBALAGEM PE ON PE.COD_PRODUTO = MSP.COD_PRODUTO AND PE.DSC_GRADE = MSP.DSC_GRADE AND PE.DTH_INATIVACAO IS NULL AND PE.COD_BARRAS IS NOT NULL
                LEFT JOIN PRODUTO_VOLUME PV ON PV.COD_PRODUTO_VOLUME = MSP.COD_PRODUTO_VOLUME AND PV.DTH_INATIVACAO IS NULL AND PV.COD_BARRAS IS NOT NULL
                WHERE MS.COD_EXPEDICAO = $idExpdicao $andWhere";

        $result = [];
        foreach ($this->getEntityManager()->getConnection()->query($sql)->fetchAll() as $r)
        {
            $result[] = "'$r[COD_BARRAS]'";
        }
        return $result;
    }
}