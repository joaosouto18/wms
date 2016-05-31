<?php

namespace Wms\Domain\Entity;

use Core\Grid\Exception;
use Doctrine\ORM\EntityRepository,
    Wms\Domain\Entity\Expedicao as ExpedicaoEntity,
    Wms\Domain\Entity\Atividade as AtividadeEntity,
    Wms\Service\Coletor as LeituraColetor,
    Wms\Domain\Entity\Expedicao\EtiquetaSeparacao as EtiquetaSeparacao,
    Wms\Domain\Entity\OrdemServico as OrdemServicoEntity,
    Wms\Domain\Entity\Expedicao\Andamento;


class ExpedicaoRepository extends EntityRepository
{

    public function validaPedidosImpressos($idExpedicao) {
        $SQL = "SELECT C.COD_EXPEDICAO
                  FROM PEDIDO P
             LEFT JOIN CARGA C ON C.COD_CARGA = P.COD_CARGA
             LEFT JOIN ETIQUETA_SEPARACAO ES ON ES.COD_PEDIDO = P.COD_PEDIDO
             LEFT JOIN MAPA_SEPARACAO MS ON MS.COD_EXPEDICAO = C.COD_EXPEDICAO
                 WHERE (ES.COD_STATUS = 522 OR P.IND_ETIQUETA_MAPA_GERADO = 'N' OR MS.COD_STATUS = 522)
                   AND C.COD_EXPEDICAO = " . $idExpedicao;
        $result = $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);

        if (count($result) > 0) {
            return false;
        } else {
            return true;
        }

    }

    public function findProdutosSemEtiquetasById($idExpedicao, $central = null) {

        if ($central) {
            $andCentral = " AND rpe.centralEntrega = $central";
        } else {
            $andCentral = "";
        }

        $query = "SELECT rpe
                FROM wms:Expedicao\VRelProdutos rpe
                INNER JOIN wms:Expedicao\Carga c WITH c.id = rpe.codCarga
                INNER JOIN wms:Expedicao\Pedido p WITH c.id = p.carga
                INNER JOIN wms:Expedicao\PedidoProduto pp WITH p.id = pp.pedido
                  AND pp.codProduto = rpe.codProduto
                  AND pp.grade = rpe.grade
                WHERE rpe.codExpedicao = $idExpedicao
                $andCentral
                AND pp.id NOT IN (
                    SELECT pp2.id
                      FROM wms:Expedicao\EtiquetaSeparacao ep
                     INNER JOIN wms:Expedicao\PedidoProduto pp2 WITH ep.pedido = pp2.pedido
                      AND pp2.codProduto = ep.codProduto
                      AND pp2.grade = ep.grade
                      WHERE ep.codStatus <> 522
                )
            ";

        $result = $this->getEntityManager()->createQuery($query)->getResult();
        return $result;

    }

    public function getProdutosSemOnda($expedicoes,$filialExterno)
    {
        $Query = "SELECT PP.COD_PRODUTO,
                         PP.DSC_GRADE,
                         SUM (NVL(PP.QUANTIDADE,0)) - SUM(NVL(PP.QTD_CORTADA,0)) as QTD
                    FROM PEDIDO P
                    LEFT JOIN PEDIDO_PRODUTO PP ON PP.COD_PEDIDO = P.COD_PEDIDO
                    LEFT JOIN CARGA          C ON C.COD_CARGA = P.COD_CARGA
                    LEFT JOIN EXPEDICAO      E ON E.COD_EXPEDICAO = C.COD_EXPEDICAO
                    WHERE P.COD_PEDIDO NOT IN (SELECT COD_PEDIDO FROM ONDA_RESSUPRIMENTO_PEDIDO)
                          AND E.COD_EXPEDICAO IN (".$expedicoes.")
                          AND P.CENTRAL_ENTREGA = $filialExterno
                          AND (NVL(PP.QUANTIDADE,0) - NVL(PP.QTD_CORTADA,0))>0
                    GROUP BY PP.COD_PRODUTO, PP.DSC_GRADE";
        $result = $this->getEntityManager()->getConnection()->query($Query)-> fetchAll(\PDO::FETCH_ASSOC);
        return $result;
    }

    public function getProdutosSemOndaByExpedicao($expedicoes, $filialExterno)
    {
        $Query = "SELECT PP.COD_PRODUTO,
                         PP.DSC_GRADE,
                         SUM (NVL(PP.QUANTIDADE,0)) - SUM(NVL(PP.QTD_CORTADA,0)) as QTD,
                         E.COD_EXPEDICAO, PED.COD_PEDIDO
                    FROM PEDIDO PED
                    LEFT JOIN PEDIDO_PRODUTO PP ON PP.COD_PEDIDO = PED.COD_PEDIDO
                    LEFT JOIN CARGA          C ON C.COD_CARGA = PED.COD_CARGA
                    LEFT JOIN EXPEDICAO      E ON E.COD_EXPEDICAO = C.COD_EXPEDICAO
                    LEFT JOIN PRODUTO        P ON P.COD_PRODUTO = PP.COD_PRODUTO AND P.DSC_GRADE = PP.DSC_GRADE
                    WHERE PED.COD_PEDIDO NOT IN (SELECT COD_PEDIDO FROM ONDA_RESSUPRIMENTO_PEDIDO)
                          AND E.COD_EXPEDICAO IN (".$expedicoes.")
                          AND PED.CENTRAL_ENTREGA = $filialExterno
                          AND (NVL(PP.QUANTIDADE,0) - NVL(PP.QTD_CORTADA,0)) > 0
                    GROUP BY PP.COD_PRODUTO, PP.DSC_GRADE, E.COD_EXPEDICAO, PED.COD_PEDIDO";
        $result = $this->getEntityManager()->getConnection()->query($Query)-> fetchAll(\PDO::FETCH_ASSOC);
        return $result;
    }

    public function getPedidoProdutoSemOnda($expedicoes,$filialExterno)
    {
        $Query = "SELECT PP.COD_PRODUTO,
                         PP.DSC_GRADE,
                         (NVL(PP.QUANTIDADE,0) - NVL(PP.QTD_CORTADA,0)) as QTD,
                         P.COD_PEDIDO
                    FROM PEDIDO P
                    LEFT JOIN PEDIDO_PRODUTO PP ON PP.COD_PEDIDO = P.COD_PEDIDO
                    LEFT JOIN CARGA          C ON C.COD_CARGA = P.COD_CARGA
                    LEFT JOIN EXPEDICAO      E ON E.COD_EXPEDICAO = C.COD_EXPEDICAO
                    WHERE P.COD_PEDIDO NOT IN (SELECT COD_PEDIDO FROM ONDA_RESSUPRIMENTO_PEDIDO)
                          AND E.COD_EXPEDICAO IN (".$expedicoes.")
                          AND P.CENTRAL_ENTREGA = $filialExterno
                          ";

        $result = $this->getEntityManager()->getConnection()->query($Query)-> fetchAll(\PDO::FETCH_ASSOC);
        return $result;
    }

    private function validaPickingProdutosByExpedicao ( $produtosRessuprir) {
        /** @var \Wms\Domain\Entity\ProdutoRepository $produtoRepo */
        $produtoRepo = $this->getEntityManager()->getRepository("wms:Produto");

        foreach ($produtosRessuprir as $produto){
            $codProduto = $produto['COD_PRODUTO'];
            $grade = $produto['DSC_GRADE'];

            $produtoEn = $produtoRepo->findOneBy(array('id'=>$codProduto,'grade'=>$grade));
            $volumes = $produtoEn->getVolumes();
            $embalagens = $produtoEn->getEmbalagens();

            if ((count($volumes) == 0) && (count($embalagens)==0)) {
                $resultado = array();
                $resultado['observacao'] = "Existem produtos sem picking nesta(s) expedição(ões)";
                $resultado['resultado'] = false;
                return $resultado;
            }

            if ($produtoEn->getTipoComercializacao()->getId() == 1) {
                if (count($embalagens) == 0) {
                    throw new \Exception("O Produto cód. $codProduto - $grade não possui nenhuma embalagem cadastrada" );
                }
            } else {
                if (count($volumes) == 0) {
                    throw new \Exception("O Produto cód. $codProduto - $grade não possui nenhum volume cadastrado" );
                }
            }

            foreach($volumes as $volume) {
                if ($volume->getCapacidadePicking() == 0) {
                    throw new \Exception("O Produto cód. $codProduto - $grade possui volumes sem capacidade de picking definida" );
                }
                if ($volume->getPontoReposicao() >= $volume->getCapacidadePicking()) {
                    throw new \Exception("O Produto cód. $codProduto - $grade possui volumes com o ponto de reposição definido incorretamente" );
                }

                if ($volume->getEndereco() == null) {
                    $resultado = array();
                    $resultado['observacao'] = "Existem produtos sem picking nesta(s) expedição(ões)";
                    $resultado['resultado'] = false;
                    return $resultado;
                }
            }
            foreach($embalagens as $embalagem) {
                if ($embalagem->getCapacidadePicking() == 0) {
                    throw new \Exception("O Produto cód. $codProduto - $grade possui volumes sem capacidade de picking definida" );
                }
                if ($embalagem->getPontoReposicao() >= $embalagem->getCapacidadePicking()) {
                    throw new \Exception("O Produto cód. $codProduto - $grade possui volumes com o ponto de reposição definido incorretamente" );
                }

                if ($embalagem->getEndereco() == null) {
                    $resultado = array();
                    $resultado['observacao'] = "Existem produtos sem picking nesta(s) expedição(ões)";
                    $resultado['resultado'] = false;
                    return $resultado;
                }
            }
        }
        $resultado = array();
        $resultado['observacao'] = "";
        $resultado['resultado'] = true;
        return $resultado;
    }

    public function gerarOnda($expedicoes)
    {
        try {
            $this->getEntityManager()->beginTransaction();
            $strExpedicao = "";
            foreach ($expedicoes as $expedicao){
                $strExpedicao = $strExpedicao . $expedicao;
                if ($expedicao != end($expedicoes)) $strExpedicao = $strExpedicao . ",";
            }

            $sessao = new \Zend_Session_Namespace('deposito');
            $deposito = $this->_em->getReference('wms:Deposito', $sessao->idDepositoLogado);
            $central = $deposito->getFilial()->getCodExterno();
            if ($deposito->getFilial()->getIndUtilizaRessuprimento() == "N"){
                throw new \Exception("A Filial " . $deposito->getFilial()->getPessoa()->getNomeFantasia() . " não utiliza ressuprimento");
            }

            $produtosRessuprir = $this->getProdutosSemOnda($strExpedicao,$central);
            $pedidosProdutosRessuprir = $this->getPedidoProdutoSemOnda($strExpedicao, $central);
            $produtosReservaSaida = $this->getProdutosSemOndaByExpedicao($strExpedicao, $central);

            if (count($produtosRessuprir) <=0) {
                throw new \Exception("Nenhuma expedição Selecionada");
            }

            /** @var \Wms\Domain\Entity\Ressuprimento\ReservaEstoqueExpedicaoRepository $reservaEstoqueExpedicaoRepo */
            $reservaEstoqueExpedicaoRepo = $this->getEntityManager()->getRepository("wms:Ressuprimento\ReservaEstoqueExpedicao");
            /** @var \Wms\Domain\Entity\Ressuprimento\OndaRessuprimentoRepository $ondaRepo */
            $ondaRepo = $this->getEntityManager()->getRepository("wms:Ressuprimento\OndaRessuprimento");

            $ondaEn = $ondaRepo->geraNovaOnda();
            $ondaRepo->relacionaOndaPedidosExpedicao($pedidosProdutosRessuprir, $ondaEn);

            $produtosPorTipoSaida = $ondaRepo->getArrayProdutosPorTipoSaida($produtosReservaSaida);
                $reservaEstoqueExpedicaoRepo->gerarReservaSaidaPicking($produtosPorTipoSaida['picking']);
                $reservaEstoqueExpedicaoRepo->gerarReservaSaidaPulmao($produtosPorTipoSaida['pulmao']);
            $this->getEntityManager()->flush();

            $qtdOsGerada = $ondaRepo->geraOsRessuprimento($produtosRessuprir,$ondaEn);

            $this->getEntityManager()->flush();
            $ondaRepo->sequenciaOndasOs();
            $this->getEntityManager()->commit();

            $resultado = array();

            if ($qtdOsGerada == 0) {
                $resultado['observacao'] = "Nenhuma Os gerada";
                $resultado['resultado'] = true;

                return $resultado;
            }

            $resultado['observacao'] = "Ondas Geradas com sucesso";
            $resultado['resultado'] = true;

            return $resultado;
        } catch(\Exception $e) {
            $this->getEntityManager()->rollback();

            $resultado = array();
            $resultado['observacao'] = $e->getMessage();
            $resultado['resultado'] = false;

            return $resultado;
        }

    }

    public function verificaDisponibilidadeEstoquePedido($expedicoes)
    {

        $sessao = new \Zend_Session_Namespace('deposito');
        $deposito = $this->_em->getReference('wms:Deposito', $sessao->idDepositoLogado);
        $central = $deposito->getFilial()->getCodExterno();

        $expedicoes = implode(',', $expedicoes);

        $sql = "
         SELECT *
           FROM (SELECT DISTINCT
                        PEDIDO.COD_PRODUTO AS Codigo,
                        PEDIDO.DSC_GRADE AS Grade,
                        PROD.DSC_PRODUTO as Produto,
                        NVL(E.QTD,0) AS Estoque,
                        (NVL(E.QTD,0) + NVL(REP.QTD_RESERVADA,0)) - PEDIDO.quantidade_pedido saldo_Final
                   FROM (SELECT SUM(PP.QUANTIDADE - PP.QTD_CORTADA) quantidade_pedido , PP.COD_PRODUTO, PP.DSC_GRADE, C.COD_EXPEDICAO
                           FROM PEDIDO P
                          INNER JOIN PEDIDO_PRODUTO PP ON PP.COD_PEDIDO = P.COD_PEDIDO
                          INNER JOIN CARGA C ON P.COD_CARGA = C.COD_CARGA
                          WHERE P.CENTRAL_ENTREGA = $central
                          GROUP BY PP.COD_PRODUTO, PP.DSC_GRADE, C.COD_EXPEDICAO) PEDIDO
              LEFT JOIN (SELECT P.COD_PRODUTO, P.DSC_GRADE, MIN(NVL(E.QTD,0)) as QTD
                           FROM PRODUTO P
                           LEFT JOIN PRODUTO_VOLUME PV ON PV.COD_PRODUTO = P.COD_PRODUTO AND P.DSC_GRADE = PV.DSC_GRADE
                           LEFT JOIN (SELECT SUM(E.QTD) AS QTD, E.COD_PRODUTO, E.DSC_GRADE,
                                             NVL(E.COD_PRODUTO_VOLUME,0) AS VOLUME
                                        FROM ESTOQUE E
                                       GROUP BY E.COD_PRODUTO, E.DSC_GRADE, NVL(E.COD_PRODUTO_VOLUME,0)) E
                                  ON E.COD_PRODUTO = P.COD_PRODUTO
                                 AND E.DSC_GRADE = P.DSC_GRADE
                                 AND E.VOLUME = NVL(PV.COD_PRODUTO_VOLUME,0)
                          GROUP BY P.COD_PRODUTO, P.DSC_GRADE) E
                     ON PEDIDO.COD_PRODUTO = E.COD_PRODUTO AND PEDIDO.DSC_GRADE = E.DSC_GRADE
              LEFT JOIN (SELECT MAX(QTD_RESERVADA) QTD_RESERVADA, COD_PRODUTO, DSC_GRADE
                           FROM (SELECT SUM(REP.QTD_RESERVADA) AS QTD_RESERVADA, REP.COD_PRODUTO, REP.DSC_GRADE, NVL(REP.COD_PRODUTO_VOLUME,0)
                                   FROM RESERVA_ESTOQUE_EXPEDICAO REE
                                  INNER JOIN RESERVA_ESTOQUE RE ON REE.COD_RESERVA_ESTOQUE = RE.COD_RESERVA_ESTOQUE
                                  INNER JOIN RESERVA_ESTOQUE_PRODUTO REP ON REP.COD_RESERVA_ESTOQUE = RE.COD_RESERVA_ESTOQUE
                                  WHERE RE.TIPO_RESERVA = 'S' AND RE.IND_ATENDIDA = 'N'
                                  GROUP BY REP.COD_PRODUTO, REP.DSC_GRADE, NVL(REP.COD_PRODUTO_VOLUME,0)) MAX_RES
                          GROUP BY COD_PRODUTO, DSC_GRADE) REP
                     ON PEDIDO.COD_PRODUTO = REP.COD_PRODUTO AND PEDIDO.DSC_GRADE = REP.DSC_GRADE
              LEFT JOIN PRODUTO PROD
                     ON PROD.COD_PRODUTO = PEDIDO.COD_PRODUTO AND PROD.DSC_GRADE = PEDIDO.DSC_GRADE
                  WHERE PEDIDO.COD_EXPEDICAO IN ($expedicoes)
                    AND (NVL(E.QTD,0) + NVL(REP.QTD_RESERVADA,0)) - PEDIDO.quantidade_pedido <0) PROD
                  ORDER BY Codigo, Grade, Produto
        ";

        return $this->getEntityManager()->getConnection()->query($sql)-> fetchAll(\PDO::FETCH_ASSOC);
    }


    public function findPedidosProdutosSemEtiquetaById($idExpedicao, $central, $cargas = null) 
    {
        $sequencia = $this->getSystemParameterValue("SEQUENCIA_ETIQUETA_SEPARACAO");

        $whereCargas = null;
        if(!is_null($cargas) && is_array($cargas)) {
            $cargas = implode(',',$cargas);
            $whereCargas = " AND c.codCargaExterno in ($cargas) ";
        } else if (!is_null($cargas)) {
            $whereCargas = " AND c.codCargaExterno = $cargas ";
        }

        $query = "SELECT pp
                        FROM wms:Expedicao\PedidoProduto pp
                        INNER JOIN pp.produto p
                         LEFT JOIN p.linhaSeparacao ls
                        INNER JOIN pp.pedido ped
                        INNER JOIN wms:Expedicao\VProdutoEndereco e
                         WITH p.id = e.codProduto AND p.grade = e.grade
                        INNER JOIN ped.carga c
                        WHERE ped.indEtiquetaMapaGerado != 'S'
                          $whereCargas
                          AND ped.centralEntrega = '$central'
                        ";

        switch ($sequencia) {
            case 3:
                $order = " ORDER BY c.placaExpedicao,
                                    ls.descricao,
                                    e.rua,
                                    e.predio,
                                    e.nivel,
                                    e.apartamento,
                                    ped.id,
                                    p.descricao";
                break;
            case 2:
                $order = " ORDER BY ls.descricao,
                                    e.rua,
                                    e.predio,
                                    e.nivel,
                                    e.apartamento,
                                    p.descricao";
                break;
            default;
                $order = " ORDER BY c.placaExpedicao,
                                    e.rua,
                                    e.predio,
                                    e.nivel,
                                    e.apartamento,
                                    p.id";
        }

        return  $this->getEntityManager()->createQuery($query.$order)->getResult();
    }

    /**
     * @param $idExpedicao
     * @return mixed
     */
    public function countPedidosNaoCancelados($idExpedicao) 
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder()
            ->select('count(e.id)')
            ->from('wms:Expedicao\Pedido','p')
            ->innerJoin('p.carga', 'c')
            ->innerJoin('c.expedicao', 'e')
            ->where('e.id = :IdExpedicao')
            ->andWhere('p.dataCancelamento is null')
            ->setParameter('IdExpedicao',$idExpedicao);
        return $queryBuilder->getQuery()->getSingleScalarResult();
    }

    /**
     * @param $idExpedicao
     * @return array
     */
    public function getCargas($idExpedicao)
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder()
            ->select('c')
            ->from('wms:Expedicao\Carga', 'c')
            ->innerJoin('c.expedicao', 'e')
            ->where('c.expedicao = :IdExpedicao')
            ->setParameter('IdExpedicao', $idExpedicao);
        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param $idExpedicao
     * @return array
     */
    public function getProdutosSemDadosByExpedicao ($idExpedicao)
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder()
            ->select("prod.id, prod.grade, prod.descricao")
            ->from("wms:Expedicao\PedidoProduto", "pp")
            ->innerJoin("pp.pedido", "p")
            ->innerJoin("p.carga", "c")
            ->innerJoin("c.expedicao", "e")
            ->innerJoin("pp.produto", "prod")
            ->leftJoin("prod.volumes", "vol")
            ->leftJoin("prod.embalagens", "emb")
            ->where("e.id = :IdExpedicao")
            ->andWhere("vol.id IS NULL")
            ->andWhere("emb.id IS NULL")
            ->andWhere("(NVL(pp.quantidade,'0') - NVL(pp.qtdCortada,'0')) > 0")
            ->setParameter("IdExpedicao", $idExpedicao);

        $result = $queryBuilder->getQuery()->getResult();
        return $result;
    }

    /**
     * @param $idExpedicao
     * @return array
     */
    public function getCentralEntregaPedidos ($idExpedicao,$consideraParcialmenteFinalizado = true)
    {
        $expedicaoEntity = $this->find($idExpedicao);

        $source = $this->getEntityManager()->createQueryBuilder();

        if ($consideraParcialmenteFinalizado) {
            if ($expedicaoEntity->getStatus()->getId() == Expedicao::STATUS_PARCIALMENTE_FINALIZADO) {
                $source->select('pedido.pontoTransbordo as centralEntrega');
            } else {
                $source->select('pedido.centralEntrega');
            }
        } else {
            $source->select('pedido.centralEntrega');
        }

        $source
            ->from('wms:Expedicao', 'e')
            ->innerJoin('wms:Expedicao\Carga', 'c', 'WITH', 'e.id = c.expedicao')
            ->innerJoin('wms:Expedicao\Pedido', 'pedido', 'WITH', 'c.id = pedido.carga')
            ->where('e.id = :idExpedicao')
            ->distinct(true)
            ->setParameter('idExpedicao', $idExpedicao);

        return $source->getQuery()->getArrayResult();
    }

    /**
     * @param $idExpedicao
     * @return array
     */
    public function getCodCargasExterno ($idExpedicao)
    {
        $source = $this->getEntityManager()->createQueryBuilder()
            ->select('c.codCargaExterno, c.sequencia')
            ->from('wms:Expedicao', 'e')
            ->innerJoin('wms:Expedicao\Carga', 'c', 'WITH', 'e.id = c.expedicao')
            ->where('e.id = :idExpedicao')
            ->distinct(true)
            ->setParameter('idExpedicao', $idExpedicao);
        return $source->getQuery()->getArrayResult();
    }

    public function getExistsPendenciaCorte($expedicaoEn,$centralEstoque)
    {
        /** @var \Wms\Domain\Entity\Expedicao\EtiquetaSeparacaoRepository $EtiquetaRepo */
        $EtiquetaRepo = $this->_em->getRepository('wms:Expedicao\EtiquetaSeparacao');
        $qtdEtiquetasPendenteCorte = $EtiquetaRepo->countByStatus(\Wms\Domain\Entity\Expedicao\EtiquetaSeparacao::STATUS_PENDENTE_CORTE, $expedicaoEn, $centralEstoque);
        if ($qtdEtiquetasPendenteCorte > 0) {
            return true;
        } else {
            return false;
        }
    }

    private function validaStatusEtiquetas ($expedicaoEn, $central)
    {
        /** @var \Wms\Domain\Entity\Expedicao\EtiquetaSeparacaoRepository $EtiquetaRepo */
        $EtiquetaRepo = $this->_em->getRepository('wms:Expedicao\EtiquetaSeparacao');

        $qtdEtiquetasPendenteConferencia = $EtiquetaRepo->countByStatus(\Wms\Domain\Entity\Expedicao\EtiquetaSeparacao::STATUS_ETIQUETA_GERADA, $expedicaoEn, $central);
        $qtdEtiquetasPendenteImpressão = $EtiquetaRepo->countByStatus(\Wms\Domain\Entity\Expedicao\EtiquetaSeparacao::STATUS_PENDENTE_IMPRESSAO, $expedicaoEn, $central);

        if ($this->getSystemParameterValue('CONFERE_EXPEDICAO_REENTREGA') == 'S') {

            $qtdEtiquetasPendenteReentrega = $EtiquetaRepo->getEtiquetasReentrega($expedicaoEn->getId(), EtiquetaSeparacao::STATUS_PENDENTE_REENTREGA, $central);
            if (count($qtdEtiquetasPendenteReentrega) >0) {
                return 'Existem etiquetas de reentrega pendentes de conferência nesta expedição';
            }
        }

        if ($qtdEtiquetasPendenteConferencia > 0) {
            return 'Existem etiquetas pendentes de conferência nesta expedição';
        } else if ($qtdEtiquetasPendenteImpressão > 0) {
            return 'Existem etiquetas pendentes de impressão nesta expedição';
        }

        if ($expedicaoEn->getStatus()->getId() == \Wms\Domain\Entity\Expedicao::STATUS_PARCIALMENTE_FINALIZADO) {

            $qtdEtiquetasConferidas = $EtiquetaRepo->countByStatus(\Wms\Domain\Entity\Expedicao\EtiquetaSeparacao::STATUS_CONFERIDO, $expedicaoEn, $central);
            $qtdEtiquetasRecebidoTransbordo = $EtiquetaRepo->countByStatus(\Wms\Domain\Entity\Expedicao\EtiquetaSeparacao::STATUS_RECEBIDO_TRANSBORDO, $expedicaoEn, $central);

            if ($qtdEtiquetasConferidas > 0) {
                return 'Existem etiquetas de produtos de outra central que ainda não foram conferidas';
            } else if ($qtdEtiquetasRecebidoTransbordo > 0) {
                return 'Existem etiquetas de produtos de outra central que ainda não foram conferidas';
            }
        }

    }

    public function finalizarExpedicao ($idExpedicao, $central, $validaStatusEtiqueta = true, $tipoFinalizacao = false)
    {
        /** @var \Wms\Domain\Entity\Expedicao\EtiquetaSeparacaoRepository $EtiquetaRepo */
        $EtiquetaRepo = $this->_em->getRepository('wms:Expedicao\EtiquetaSeparacao');
        /** @var \Wms\Domain\Entity\Expedicao\MapaSeparacaoRepository $MapaSeparacaoRepo */
        $MapaSeparacaoRepo = $this->_em->getRepository('wms:Expedicao\MapaSeparacao');

        $expedicaoEn  = $this->findOneBy(array('id'=>$idExpedicao));
        $codCargaExterno = $this->validaCargaFechada($idExpedicao);
        if (isset($codCargaExterno) && !empty($codCargaExterno)) {
            return 'As cargas '.$codCargaExterno.' estão com pendencias de fechamento';
        }

        if ($this->validaPedidosImpressos($idExpedicao) == false) {
            return 'Existem produtos sem etiquetas impressas';
        }

        if ($this->getExistsPendenciaCorte($expedicaoEn,$central)) {
            return 'Existem etiquetas pendentes de corte nesta expedição';
        }
        ini_set('max_execution_time', 3000);
        Try {
            $this->getEntityManager()->beginTransaction();
            if ($validaStatusEtiqueta == true) {
                $result = $MapaSeparacaoRepo->verificaMapaSeparacao ($expedicaoEn->getId());
                if (is_string($result)) {
                    return $result;
                }
                $result = $this->validaStatusEtiquetas($expedicaoEn,$central);
                if (is_string($result)) {
                    return $result;
                }

                $result = $this->validaVolumesPatrimonio($idExpedicao);
                if (is_string($result)) {
                    return $result;
                }
            } else {
                $codCargaExterno = $this->validaCargaFechada($idExpedicao);
                if (isset($codCargaExterno) && !empty($codCargaExterno)) {
                    return 'As cargas '.$codCargaExterno.' estão com pendencias de fechamento';
                }
                $EtiquetaRepo->finalizaEtiquetasSemConferencia($idExpedicao, $central);
                $MapaSeparacaoRepo->forcaConferencia($idExpedicao);
            }

            $verificaReconferencia = $this->_em->getRepository('wms:Sistema\Parametro')->findOneBy(array('constante' => 'RECONFERENCIA_EXPEDICAO'))->getValor();

            if ($verificaReconferencia=='S'){
                $idStatus=$expedicaoEn->getStatus()->getId();

                /** @var \Wms\Domain\Entity\Expedicao\EtiquetaConferenciaRepository $EtiquetaConfRepo */
                $EtiquetaConfRepo = $this->_em->getRepository('wms:Expedicao\EtiquetaConferencia');

                if (($idStatus==Expedicao::STATUS_PRIMEIRA_CONFERENCIA) || ($idStatus==Expedicao::STATUS_EM_SEPARACAO)) {
                    $numEtiquetas=$EtiquetaConfRepo->getEtiquetasByStatus(EtiquetaSeparacao::STATUS_PENDENTE_IMPRESSAO, $idExpedicao, $central);

                    if (count($numEtiquetas) > 0) {
                        return 'Existem etiquetas pendentes de conferência nesta expedição';
                    } else {
                        /** @var \Wms\Domain\Entity\Expedicao $expedicaoEntity */
                        $expedicaoEntity = $this->find($idExpedicao);

                        $this->alteraStatus($expedicaoEntity,Expedicao::STATUS_SEGUNDA_CONFERENCIA);
                        $this->efetivaReservaEstoqueByExpedicao($idExpedicao);
                        $this->getEntityManager()->flush();
                        $this->getEntityManager()->commit();
                        return 0;
                    }
                } else {
                    $numEtiquetas=$EtiquetaConfRepo->getEtiquetasByStatus(EtiquetaSeparacao::STATUS_PRIMEIRA_CONFERENCIA, $idExpedicao, $central);
                    if (count($numEtiquetas) > 0) {
                        return 'Existem etiquetas pendentes de conferência nesta expedição';
                    }
                }
            }

            if ($this->getSystemParameterValue('CONFERE_EXPEDICAO_REENTREGA') == 'S') {
                $this->finalizarReentrega($idExpedicao);
            }

            $result = $this->finalizar($idExpedicao,$central,$tipoFinalizacao);
            $this->getEntityManager()->commit();
            return $result;
        } catch(\Exception $e) {
            $this->getEntityManager()->rollback();
            return $e->getMessage();
        }
    }

    public function finalizarReentrega($idExpedicao) {
        /** @var \Wms\Domain\Entity\Expedicao\NotaFiscalSaidaAndamentoRepository $andamentoNFRepo */
        $andamentoNFRepo = $this->_em->getRepository("wms:Expedicao\NotaFiscalSaidaAndamento");
        $reentregaRepo = $this->getEntityManager()->getRepository("wms:Expedicao\Reentrega");
        $nfSaidaRepo = $this->getEntityManager()->getRepository("wms:Expedicao\NotaFiscalSaida");
        $notasFiscais = $reentregaRepo->getReentregasByExpedicao($idExpedicao,false);
        $expedicaoRepo = $this->getEntityManager()->getRepository("wms:Expedicao");

        $expedicaoEn = $expedicaoRepo->findOneBy(array('id'=>$idExpedicao));
        $status = $this->getEntityManager()->getRepository('wms:Util\Sigla')->findOneBy(array('id'=>ExpedicaoEntity\NotaFiscalSaida::EXPEDIDO_REENTREGA));

        foreach ($notasFiscais as $notaFiscal) {
            $nfEn = $nfSaidaRepo->findOneBy(array('id'=>$notaFiscal['COD_NOTA_FISCAL_SAIDA']));
            $reentregaEn = $reentregaRepo->findOneBy(array('id'=>$notaFiscal['COD_REENTREGA']));
            $nfEn->setStatus($status);
            $this->getEntityManager()->persist($nfEn);

            $andamentoNFRepo->save($nfEn, ExpedicaoEntity\NotaFiscalSaida::EXPEDIDO_REENTREGA, false, $expedicaoEn, $reentregaEn);
        }

        $this->getEntityManager()->flush();
    }

    public function validaVolumesPatrimonio($idExpedicao){

        $volumesPatrimonioRepo = $this->getEntityManager()->getRepository("wms:Expedicao\ExpedicaoVolumePatrimonio");
        $volumesEn = $volumesPatrimonioRepo->findBy(array('expedicao'=> $idExpedicao));

        /** @var \Wms\Domain\Entity\Expedicao\ExpedicaoVolumePatrimonio $volumeEn */
        foreach ($volumesEn as $volumeEn) {
            $idVolume = $volumeEn->getVolumePatrimonio()->getId();

            if ($volumeEn->getDataFechamento() == NULL) {
                return "O Volume $idVolume ainda está em processo de conferencia";
            }
            if ($volumeEn->getDataConferencia() == NULL) {
                return "O Volume $idVolume ainda não foi conferido no box";
            }
        }
        return true;
    }

    private function validaCargaFechada($idExpedicao) {
        $cargas = $this->getCargas($idExpedicao);

        $codCargaExterno = array();
        foreach($cargas as $carga) {
            if ($carga->getDataFechamento() == null) {
                $codCargaExterno[] = $carga->getCodCargaExterno();
            }
        }
        return implode(', ', $codCargaExterno);
    }

        /**
     * @param array $cargas
     * @return bool
     */
    private function finalizar($idExpedicao, $centralEntrega, $tipoFinalizacao = false)
    {
        $codCargaExterno = $this->validaCargaFechada($idExpedicao);
        if (isset($codCargaExterno) && !empty($codCargaExterno)) {
            return 'As cargas '.$codCargaExterno.' estão com pendencias de fechamento';
        }

        /** @var \Wms\Domain\Entity\Expedicao\PedidoRepository $pedidoRepo */
        $pedidoRepo = $this->_em->getRepository('wms:Expedicao\Pedido');
        /** @var \Wms\Domain\Entity\Expedicao\AndamentoRepository $andamentoRepo */
        $andamentoRepo  = $this->_em->getRepository('wms:Expedicao\Andamento');
        /** @var \Wms\Domain\Entity\Ressuprimento\ReservaEstoqueRepository $reservaEstoqueRepo */
        $reservaEstoqueRepo = $this->getEntityManager()->getRepository("wms:Ressuprimento\ReservaEstoque");
        $estoqueRepo = $this->getEntityManager()->getRepository("wms:Enderecamento\Estoque");
        $usuarioRepo = $this->getEntityManager()->getRepository("wms:Usuario");

        /** @var \Wms\Domain\Entity\Expedicao $expedicaoEntity */
        $expedicaoEntity = $this->find($idExpedicao);
        $expedicaoEntity->setDataFinalizacao(new \DateTime());
        $expedicaoEntity->setTipoFechamento($tipoFinalizacao);

        $this->finalizeOSByExpedicao($expedicaoEntity->getId());
        $pedidoRepo->finalizaPedidosByCentral($centralEntrega,$expedicaoEntity->getId());

        $pedidosNaoConferidos = $pedidoRepo->findPedidosNaoConferidos($expedicaoEntity->getId());
        if ($pedidosNaoConferidos == null) {
            $novoStatus = Expedicao::STATUS_FINALIZADO;
            switch ($tipoFinalizacao) {
                case 'C':
                    $andamentoRepo->save("Conferencia finalizada com sucesso via coletor", $expedicaoEntity->getId());
                    break;
                case 'M':
                    $andamentoRepo->save("Conferencia finalizada com sucesso via desktop", $expedicaoEntity->getId());
                    break;
                case 'S':
                    $andamentoRepo->save("Conferencia finalizada com sucesso via desktop com senha de autorização", $expedicaoEntity->getId());
                    break;
                default:
                    $andamentoRepo->save("Expedição Finalizada com Sucesso", $expedicaoEntity->getId());
                    break;
            }
        } else {
            $novoStatus = Expedicao::STATUS_PARCIALMENTE_FINALIZADO;
            $andamentoRepo->save("Expedição Parcialmente Finalizada com Sucesso", $expedicaoEntity->getId());
        }

        $this->liberarVolumePatrimonioByExpedicao($expedicaoEntity->getId());
        $this->alteraStatus($expedicaoEntity,$novoStatus);
        $this->efetivaReservaEstoqueByExpedicao($idExpedicao);
        $this->getEntityManager()->flush();
        return true;
    }

    public function efetivaReservaEstoqueByExpedicao($idExpedicao)
    {
        $expedicaoEntity = $this->find($idExpedicao);

        /** @var \Wms\Domain\Entity\Ressuprimento\ReservaEstoqueRepository $reservaEstoqueRepo */
        $reservaEstoqueRepo = $this->getEntityManager()->getRepository("wms:Ressuprimento\ReservaEstoque");
        $estoqueRepo = $this->getEntityManager()->getRepository("wms:Enderecamento\Estoque");
        $usuarioRepo = $this->getEntityManager()->getRepository("wms:Usuario");

        $reservaEstoqueExpedicaoRepo = $this->getEntityManager()->getRepository("wms:Ressuprimento\ReservaEstoqueExpedicao");
        $reservaEstoqueArray = $reservaEstoqueExpedicaoRepo->findBy(array('expedicao'=> $expedicaoEntity->getId()));

        $idUsuario  = \Zend_Auth::getInstance()->getIdentity()->getId();
        $usuarioEn = $usuarioRepo->find($idUsuario);

        foreach ($reservaEstoqueArray as $re) {
            $reservaEstoqueEn = $re->getReservaEstoque();
            if ($reservaEstoqueEn->getAtendida() == 'N') {
                $reservaEstoqueRepo->efetivaReservaByReservaEntity($estoqueRepo, $reservaEstoqueEn,"E",$idExpedicao,$usuarioEn);
            }
        }

    }


    public function liberarVolumePatrimonioByExpedicao($idExpedicao)
    {
        $volumes = $this->getVolumesPatrimonioByExpedicao($idExpedicao);

        foreach ($volumes as $key => $volume){
            $volumeRepo = $this->getEntityManager()->getRepository('wms:Expedicao\VolumePatrimonio');
            $volumeEn = $volumeRepo->findOneBy(array('id'=> $key));
            if ($volumeEn) {
                $volumeEn->setOcupado('N');
                $this->getEntityManager()->persist($volumeEn);
            }
        }
    }

    public function finalizeOSByExpedicao($idExpedicao)
    {
        $osRepo = $this->getEntityManager()->getRepository('wms:OrdemServico');
        $result = $osRepo->getOsByExpedicao($idExpedicao);

        foreach ($result as $os) {
            $osEn = $osRepo->find($os['id']);
            $osEn->setDataFinal(new \DateTime());
            $this->_em->persist($osEn);
        }

        $this->_em->flush();
    }

    /**
     * @param $expedicaoEntity
     * @param $status
     * @return bool
     */
    public function alteraStatus($expedicaoEntity, $status)
    {
        $statusEntity = $this->_em->getReference('wms:Util\Sigla', $status);
        $expedicaoEntity->setStatus($statusEntity);
        $this->_em->persist($expedicaoEntity);
        $this->_em->flush();
        return true;
    }

    public function getVolumesPatrimonioByExpedicao($idExpedicao){

        $result = $this->getVolumesPatrimonio($idExpedicao);
        $arrayResult = array();

        foreach ($result as $line) {
            $arrayResult[$line['id']] = $line['descricao'] . ' ' . $line['id'];
        }

        return $arrayResult;
    }

    public function getVolumesPatrimonio($idExpedicao){
        $query = $this->getEntityManager()->createQueryBuilder()
            ->select("vp.id,vp.descricao, vp.ocupado,
                      (CASE WHEN ev.dataFechamento is NULL THEN 'EM CONFERENCIA'
                            else 'FECHADO' END) aberto,
                      (CASE WHEN ev.dataFechamento is NULL THEN 'EM CONFERENCIA'
                            WHEN ev.dataConferencia IS NULL THEN 'AGUARDANDO CONFERENCIA NO BOX'
                      else 'CONFERIDO' END) situacao")
            ->from("wms:Expedicao\ExpedicaoVolumePatrimonio","ev")
            ->leftJoin("ev.volumePatrimonio",'vp')
            ->where("ev.expedicao = $idExpedicao")
            ->distinct(true);

        return $query->getQuery()->getArrayResult();
    }

    public function getExpedicaoSemOndaByParams ($parametros)
    {
        $sessao = new \Zend_Session_Namespace('deposito');
        $deposito = $this->_em->getReference('wms:Deposito', $sessao->idDepositoLogado);
        $central = $deposito->getFilial()->getCodExterno();
        $statusFinalizado = Expedicao::STATUS_FINALIZADO;
        $SQLOrder = " ORDER BY E.COD_EXPEDICAO ";

        $Query = "SELECT DISTINCT E.COD_EXPEDICAO,
                                  TO_CHAR(E.DTH_INICIO,'DD/MM/YYYY') as DTH_INICIO,
                                  '' as ITINERARIO,
                                  '' as CARGA,
                                  S.DSC_SIGLA as STATUS,
                                  E.DSC_PLACA_EXPEDICAO as PLACA
                    FROM PEDIDO P
                    LEFT JOIN CARGA C ON C.COD_CARGA = P.COD_CARGA
                    LEFT JOIN EXPEDICAO E ON E.COD_EXPEDICAO = C.COD_EXPEDICAO
                    LEFT JOIN SIGLA S ON S.COD_SIGLA = E.COD_STATUS
                   WHERE P.COD_PEDIDO NOT IN (SELECT COD_PEDIDO FROM ONDA_RESSUPRIMENTO_PEDIDO)
                   AND E.COD_STATUS <> $statusFinalizado
                   AND P.CENTRAL_ENTREGA = $central
                   ";

        if (isset($parametros['idExpedicao']) && !empty($parametros['idExpedicao'])) {
            $Query = $Query . " AND E.COD_EXPEDICAO = " . $parametros['idExpedicao'];
        }

        if (isset($parametros['codCargaExterno']) && !empty($parametros['codCargaExterno'])) {
            $Query = $Query . " AND C.COD_CARGA_EXTERNO = " . $parametros['codCargaExterno'];
        }

        if (isset($parametros['placa']) && !empty($parametros['placa'])) {
            $Query = $Query . " AND E.DSC_PLACA_EXPEDICAO = " . $parametros['placa'];
        }

        if (isset($parametros['dataInicial1']) && (!empty($parametros['dataInicial1'])) && (!empty($parametros['dataInicial2']))) {
            $dataInicial = $parametros['dataInicial1'];
            $dataFinal = $parametros['dataInicial2'];
            $Query = $Query ." AND (E.DTH_INICIO BETWEEN TO_DATE('$dataInicial 00:00', 'DD-MM-YYYY HH24:MI') AND TO_DATE('$dataFinal 23:59', 'DD-MM-YYYY HH24:MI'))";
        }

        if (isset($parametros['dataFinal1']) && (!empty($parametros['dataFinal1'])) && (!empty($parametros['dataFinal2']))) {
            $dataInicial = $parametros['dataFinal1'];
            $dataFinal = $parametros['dataFinal2'];
            $Query = $Query ." AND (E.DTH_FINALIZACAO BETWEEN TO_DATE('$dataInicial 00:00', 'DD-MM-YYYY HH24:MI') AND TO_DATE('$dataFinal 23:59', 'DD-MM-YYYY HH24:MI'))";
        }

        if (isset($parametros['status']) && (!empty($parametros['status']))) {
            $Query = $Query . " AND E.COD_STATUS = " . $parametros['status'];
        }

        $result = $this->getEntityManager()->getConnection()->query($Query . $SQLOrder)-> fetchAll(\PDO::FETCH_ASSOC);

        $colItinerario = array();
        $colCarga = array();
        foreach ($result as $key => $expedicao) {
            $itinerarios = $this->getItinerarios($expedicao['COD_EXPEDICAO']);
            $cargas = $this->getCargas($expedicao['COD_EXPEDICAO']);
            foreach ($itinerarios as $itinerario) {
                if (!is_numeric($itinerario['id'])) {
                    $colItinerario[] = '(' . $itinerario['id'] . ')' . $itinerario['descricao'];
                } else {
                    $colItinerario[] = $itinerario['descricao'];
                }
            }
            foreach ($cargas as $carga) {
                $colCarga[] = $carga->getCodCargaExterno();
            }
            $result[$key]['CARGA'] = implode(', ', $colCarga);
            $result[$key]['ITINERARIO'] = implode(', ', $colItinerario);

            unset($colCarga);
            unset($colItinerario);
        }
        return $result;
    }


    public function alteraPrimeiraCentralFinalizada($expedicaoEntity,$centralEntrega) {
        $expedicaoEntity->setCentralEntregaParcFinalizada($centralEntrega);
        $this->_em->persist($expedicaoEntity);
        $this->_em->flush();
        return true;
    }

    /**
     * @param $placaExpedicao
     * @return Expedicao
     * @throws \Exception
     */
    public function save($placaExpedicao)
    {

        if (empty($placaExpedicao)) {
            throw new \Exception("placaExpedicao não pode ser vazio");
        }

        $em = $this->getEntityManager();

        $em->beginTransaction();
        try {

            $enExpedicao = new ExpedicaoEntity;

            $enExpedicao->setPlacaExpedicao($placaExpedicao);
            $statusEntity = $em->getReference('wms:Util\Sigla',ExpedicaoEntity::STATUS_INTEGRADO);
            $enExpedicao->setStatus($statusEntity);
            $enExpedicao->setDataInicio(new \DateTime);

            $em->persist($enExpedicao);
            $em->flush();
            $em->commit();

        } catch(\Exception $e) {
            $em->rollback();
            throw new \Exception($e->getMessage() . ' - ' .$e->getTraceAsString());
        }

        return $enExpedicao;
    }

    /**
     * @param $idExpedicao
     * @return array
     */
    public function getItinerarios($idExpedicao, $carga= null)
    {
        $source = $this->getEntityManager()->createQueryBuilder()
                ->select('i.id, i.descricao')
                ->from('wms:Expedicao', 'e')
                ->innerJoin('wms:Expedicao\Carga', 'c', 'WITH', 'e.id = c.expedicao')
                ->innerJoin('wms:Expedicao\Pedido', 'pedido', 'WITH', 'c.id = pedido.carga')
                ->innerJoin('wms:Expedicao\Itinerario', 'i', 'WITH', 'i.id = pedido.itinerario')
                ->where('e.id = :idExpedicao')
                ->distinct(true)
                ->setParameter('idExpedicao', $idExpedicao);

        if ($carga != null) {
            $source->andWhere("c.id = " . $carga);
        }

        return $source->getQuery()->getArrayResult();
    }

    public function getProdutos($idExpedicao, $central, $cargas = null, $linhaSeparacao = null)
    {
        $source = $this->getEntityManager()->createQueryBuilder()
            ->select('rp')
            ->from('wms:Expedicao\VRelProdutos', 'rp')
            ->leftJoin('wms:Produto','p','WITH','p.id = rp.codProduto AND p.grade = rp.grade')
            ->where('rp.codExpedicao in (' . $idExpedicao . ')')
            ->andWhere('rp.centralEntrega = :centralEntrega')
            ->setParameter('centralEntrega', $central);

        if (!is_null($linhaSeparacao)) {
            $source->andWhere("p.linhaSeparacao = $linhaSeparacao");
        }

        if(!is_null($cargas) && is_array($cargas)) {
           $cargas = implode(',',$cargas);
           $source->andWhere("rp.codCargaExterno in ($cargas)");
        } else if (!is_null($cargas)) {
            $source->andWhere('rp.codCargaExterno = :cargas')
                   ->setParameter('cargas', $cargas);
        }

        return $source->getQuery()->getResult();
    }

    public function getPlacasByExpedicaoCentral ($idExpedicao)
    {
        $dql = $this->getEntityManager()->createQueryBuilder()
            ->select('c.placaCarga, c.codCargaExterno')
            ->from('wms:Expedicao\Carga','c')
            ->innerJoin('wms:Expedicao\Pedido', 'p' , 'WITH', 'c.id = p.codCarga')
            ->where('c.codExpedicao = :idExpedicao')
            ->setParameter('idExpedicao', $idExpedicao)
            ->distinct(true);

        return $dql->getQuery()->getArrayResult();
    }

    /**
     * @param $parametros
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getPesos($parametros)
    {

        $where="";
        $and="";
        if (isset($parametros['id']) && (!empty($parametros['id']))) {
            $where.=$and."c.COD_EXPEDICAO = ".$parametros['id']."";
            $and=" and ";
        }


        if (isset($parametros['agrup']) && (!empty($parametros['agrup'])) && $parametros['agrup']=='carga'  ) {
            $agrupador="q.COD_CARGA, q.COD_CARGA_EXTERNO";
        } else if (isset($parametros['agrup']) && (!empty($parametros['agrup'])) && $parametros['agrup']=='expedicao'  ) {
            $agrupador="q.COD_EXPEDICAO";
        }

        $sql='
                SELECT
                  '.$agrupador.',
                  SUM(q.NUM_CUBAGEM) NUM_CUBAGEM,
                  SUM(q.PESO_TOTAL) PESO_TOTAL
                FROM
                (
                  SELECT
                    c.COD_CARGA,
                    c.COD_CARGA_EXTERNO,
                    c.COD_EXPEDICAO,
                    ped.COD_PEDIDO,
                    pedProd.COD_PEDIDO_PRODUTO,
                    prod.COD_PRODUTO,
                    prod.DSC_GRADE ,
                    prod.NUM_CUBAGEM,
                    SUM(prod.NUM_PESO*pedProd.QUANTIDADE) as PESO_TOTAL
                  FROM
                    CARGA c
                  INNER JOIN
                    PEDIDO ped on (c.COD_CARGA=ped.COD_CARGA)
                  INNER JOIN
                    PEDIDO_PRODUTO pedProd on (ped.COD_PEDIDO=pedProd.COD_PEDIDO)
                  INNER JOIN
                    (
                         SELECT
                      P.COD_PRODUTO,
                      P.DSC_GRADE,
                      PDL.NUM_PESO,
                      PDL.NUM_CUBAGEM
                      FROM(
                      SELECT PE.COD_PRODUTO,
                           PE.DSC_GRADE,
                           MIN(PDL.COD_PRODUTO_DADO_LOGISTICO) as COD_PRODUTO_DADO_LOGISTICO
                        FROM (SELECT MIN(COD_PRODUTO_EMBALAGEM) AS COD_PRODUTO_EMBALAGEM,
                               PE.COD_PRODUTO,
                               PE.DSC_GRADE
                            FROM PRODUTO_EMBALAGEM PE
                           INNER JOIN (SELECT MIN(QTD_EMBALAGEM) AS FATOR, COD_PRODUTO, DSC_GRADE
                                   FROM PRODUTO_EMBALAGEM PE
                                  GROUP BY COD_PRODUTO,DSC_GRADE) PEM
                            ON (PEM.COD_PRODUTO = PE.COD_PRODUTO) AND (PEM.DSC_GRADE = PE.DSC_GRADE) AND (PEM.FATOR = PE.QTD_EMBALAGEM)
                           GROUP BY PE.COD_PRODUTO, PE.DSC_GRADE) PE
                       INNER JOIN PRODUTO_DADO_LOGISTICO PDL ON PDL.COD_PRODUTO_EMBALAGEM = PE.COD_PRODUTO_EMBALAGEM
                       GROUP BY COD_PRODUTO, DSC_GRADE
                      ) P
                       INNER JOIN PRODUTO_DADO_LOGISTICO PDL ON PDL.COD_PRODUTO_DADO_LOGISTICO = P.COD_PRODUTO_DADO_LOGISTICO
                       UNION
                       SELECT PV.COD_PRODUTO,
                          PV.DSC_GRADE,
                          SUM(PV.NUM_PESO) as NUM_PESO,
                          SUM(PV.NUM_CUBAGEM) as NUM_CUBAGEM
                       FROM PRODUTO_VOLUME PV
                      GROUP BY PV.COD_PRODUTO,
                           PV.DSC_GRADE

                    ) prod on (pedProd.COD_PRODUTO=prod.COD_PRODUTO and pedProd.DSC_GRADE=prod.DSC_GRADE)
                  where
                    '.$where.'
                  group by
                    c.COD_CARGA,C.COD_CARGA_EXTERNO, ped.COD_PEDIDO,pedProd.COD_PEDIDO_PRODUTO,prod.COD_PRODUTO,prod.DSC_GRADE ,prod.NUM_PESO,
                   prod.NUM_CUBAGEM,
                   pedProd.QUANTIDADE,
                    c.COD_EXPEDICAO
                  order by
                    c.COD_CARGA,ped.COD_PEDIDO,pedProd.COD_PEDIDO_PRODUTO,prod.COD_PRODUTO,prod.DSC_GRADE,prod.NUM_PESO,
                   prod.NUM_CUBAGEM,
                   pedProd.QUANTIDADE,
                    c.COD_EXPEDICAO
                ) q
                GROUP BY
                  '.$agrupador
                  ;

        $result=$this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);

        return $result;

    }



    /**
     * @param $parametros
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function buscar($parametros, $idDepositoLogado = null)
    {

        $where="";
        $whereSubQuery="";
        $and="";
        $andSub="";
        $cond="";

        $WhereSigla = "";
        $WherePedido = "";
        $WhereCarga = "";
        $WhereExpedicao = "";

        if (isset($idDepositoLogado)) {
            $andWhere = "WHERE P.CENTRAL_ENTREGA = '$idDepositoLogado'";
        } else {
            $andWhere = '';
        }

        if (is_array($parametros['centrais'])) {
            $central = implode("','",$parametros['centrais']);
            $central = "'" . $central . "'";
            $where.=$and."( PED.CENTRAL_ENTREGA in(".$central.")";
            $where.=" OR PED.PONTO_TRANSBORDO in(".$central.") )";

            $WherePedido .= " AND ( PED.CENTRAL_ENTREGA in(".$central.") OR PED.PONTO_TRANSBORDO in(".$central."))";
            $and=" AND ";

        }

        if (isset($parametros['placa']) && !empty($parametros['placa'])) {
            $where.=$and." E.DSC_PLACA_EXPEDICAO = '".$parametros['placa']."'";
            $and=" AND ";
            $WhereExpedicao .= " AND (E.DSC_PLACA_EXPEDICAO = '".$parametros['placa']."')";
        }

        if (isset($parametros['dataInicial1']) && (!empty($parametros['dataInicial1']))){
            $where.=$and." E.DTH_INICIO >= TO_DATE('".$parametros['dataInicial1']." 00:00', 'DD-MM-YYYY HH24:MI')";
            $and=" AND ";
            $WhereExpedicao .= " AND (E.DTH_INICIO >= TO_DATE('".$parametros['dataInicial1']." 00:00', 'DD-MM-YYYY HH24:MI'))";
        }
        if (isset($parametros['dataInicial2']) && (!empty($parametros['dataInicial2']))){
            $where.=$and." E.DTH_INICIO <= TO_DATE('".$parametros['dataInicial2']." 23:59', 'DD-MM-YYYY HH24:MI')";
            $and=" AND ";
            $WhereExpedicao .= " AND (E.DTH_INICIO <= TO_DATE('".$parametros['dataInicial2']." 23:59', 'DD-MM-YYYY HH24:MI'))";
        }

        if (isset($parametros['dataFinal1']) && (!empty($parametros['dataFinal1']))) {
            $where.=$and."E.DTH_FINALIZACAO >= TO_DATE('".$parametros['dataFinal1']." 00:00', 'DD-MM-YYYY HH24:MI')";
            $and=" AND ";
            $WhereExpedicao .= " AND (E.DTH_FINALIZACAO >= TO_DATE('".$parametros['dataFinal1']." 00:00', 'DD-MM-YYYY HH24:MI'))";

        }
        if (isset($parametros['dataFinal2']) && (!empty($parametros['dataFinal2']))) {
            $where.=$and."E.DTH_FINALIZACAO <= TO_DATE('".$parametros['dataFinal2']." 23:59', 'DD-MM-YYYY HH24:MI')";
            $and=" AND ";
            $WhereExpedicao .= " AND (E.DTH_FINALIZACAO <= TO_DATE('".$parametros['dataFinal2']." 23:59', 'DD-MM-YYYY HH24:MI'))";;
        }

        if (isset($parametros['status']) && (!empty($parametros['status']))) {
            $where.=$and."S.COD_SIGLA = ".$parametros['status']."";
            $and=" and ";
            $WhereSigla .= "AND (S.COD_SIGLA = ".$parametros['status'].")";
        }

        if (isset($parametros['idExpedicao']) && !empty($parametros['idExpedicao'])) {
            $where=" E.COD_EXPEDICAO = ".$parametros['idExpedicao']."";
            $whereSubQuery=" C.COD_EXPEDICAO = ".$parametros['idExpedicao']."";
            $and=" and ";
            $andSub=" and ";
            $WhereExpedicao .= " AND (C.COD_EXPEDICAO = ".$parametros['idExpedicao'].")";
        }

        if (isset($parametros['codCargaExterno']) && !empty($parametros['codCargaExterno'])) {
            $where=" AND CA.COD_CARGA_EXTERNO = ".$parametros['codCargaExterno']."";
            $whereSubQuery=" C.COD_CARGA_EXTERNO = ".$parametros['codCargaExterno']."";
            $and=" and ";
            $andSub=" and ";
            $WhereCarga .= " AND  (COD_CARGA_EXTERNO = ".$parametros['codCargaExterno'].")";
        }

        $JoinExpedicao = "";
        $JoinSigla = "";
        $JoinCarga = "";
        if ($WhereExpedicao != "") {
            $JoinExpedicao = " LEFT JOIN EXPEDICAO E ON E.COD_EXPEDICAO = C.COD_EXPEDICAO ";
        }
        if ($WhereSigla != "") {
            $JoinSigla = " LEFT JOIN SIGLA S ON S.COD_SIGLA = E.COD_STATUS ";
            $JoinExpedicao = " LEFT JOIN EXPEDICAO E ON E.COD_EXPEDICAO = C.COD_EXPEDICAO ";
        }
        if ($WhereCarga != "") {
            $JoinCarga = " LEFT JOIN CARGA C ON C.COD_CARGA = P.COD_CARGA ";
        }

        $FullWhere = $WhereExpedicao . $WhereCarga . $WhereSigla;

        if ( $whereSubQuery!="" )
            $cond=" WHERE ";

        $sql='  SELECT E.COD_EXPEDICAO AS "id",
                       E.DSC_PLACA_EXPEDICAO AS "placaExpedicao",
                       to_char(E.DTH_INICIO,\'DD/MM/YYYY HH24:MI:SS\') AS "dataInicio",
                       to_char(E.DTH_FINALIZACAO,\'DD/MM/YYYY HH24:MI:SS\') AS "dataFinalizacao",
                       C.CARGAS AS "carga",
                       S.DSC_SIGLA AS "status",
                       P.IMPRIMIR AS "imprimir",
                       PESO.NUM_PESO as "peso",
                       PESO.NUM_CUBAGEM as "cubagem",
                       I.ITINERARIOS AS "itinerario",
                       (CASE WHEN ((NVL(MS.QTD_CONFERIDA,0) + NVL(C.CONFERIDA,0)) * 100) = 0 THEN 0
                          ELSE CAST(((NVL(MS.QTD_CONFERIDA,0) + NVL(C.CONFERIDA,0) + NVL(MSCONF.QTD_TOTAL_CONF_MANUAL,0) ) * 100) / (NVL(MSP.QTD_TOTAL,0) + NVL(C.QTDETIQUETA,0)) AS NUMBER(6,2))
                       END) AS "PercConferencia"
                  FROM EXPEDICAO E
                  LEFT JOIN SIGLA S ON S.COD_SIGLA = E.COD_STATUS
                  LEFT JOIN (SELECT C1.Etiqueta AS CONFERIDA,
                                    (COUNT(DISTINCT ESEP.COD_ETIQUETA_SEPARACAO)) AS QTDETIQUETA,
                                    C1.COD_EXPEDICAO
                               FROM ETIQUETA_SEPARACAO ESEP
                         INNER JOIN PEDIDO P ON P.COD_PEDIDO = ESEP.COD_PEDIDO
                         INNER JOIN CARGA C ON C.COD_CARGA = P.COD_CARGA  ' . $JoinExpedicao . $JoinSigla . '
                          LEFT JOIN (SELECT COUNT(DISTINCT ES.COD_ETIQUETA_SEPARACAO) AS Etiqueta,
                                            C.COD_EXPEDICAO
                                       FROM ETIQUETA_SEPARACAO ES
                                      INNER JOIN PEDIDO P ON P.COD_PEDIDO = ES.COD_PEDIDO
                                      INNER JOIN CARGA C ON C.COD_CARGA = P.COD_CARGA ' . $JoinExpedicao . $JoinSigla . '
                                      WHERE ES.COD_STATUS IN(526, 531, 532) '. $FullWhere .'
                                      GROUP BY C.COD_EXPEDICAO) C1 ON C1.COD_EXPEDICAO = C.COD_EXPEDICAO
                         WHERE ESEP.COD_STATUS NOT IN(524, 525) ' . $FullWhere . '
                         GROUP BY C1.COD_EXPEDICAO, C1.Etiqueta) C ON C.COD_EXPEDICAO = E.COD_EXPEDICAO
                  LEFT JOIN (SELECT SUM(MSC.QTD_CONFERIDA) QTD_CONFERIDA, MS.COD_EXPEDICAO
                               FROM MAPA_SEPARACAO MS
                         INNER JOIN MAPA_SEPARACAO_CONFERENCIA MSC ON MSC.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO
                              GROUP BY MS.COD_EXPEDICAO) MS ON MS.COD_EXPEDICAO = E.COD_EXPEDICAO
                  LEFT JOIN (SELECT SUM(MSP.QTD_SEPARAR) QTD_TOTAL, MS.COD_EXPEDICAO
                               FROM MAPA_SEPARACAO_PRODUTO MSP
                              INNER JOIN MAPA_SEPARACAO MS ON MSP.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO
                              GROUP BY MS.COD_EXPEDICAO) MSP ON MSP.COD_EXPEDICAO = E.COD_EXPEDICAO
                  LEFT JOIN (SELECT SUM(MSP.QTD_SEPARAR) QTD_TOTAL_CONF_MANUAL, MS.COD_EXPEDICAO
                               FROM MAPA_SEPARACAO_PRODUTO MSP
                              INNER JOIN MAPA_SEPARACAO MS ON MSP.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO
                               LEFT JOIN MAPA_SEPARACAO_CONFERENCIA MSCONF ON MSCONF.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO
                              WHERE MSP.IND_CONFERIDO = \'S\'
                                AND MSCONF.COD_MAPA_SEPARACAO_CONFERENCIA IS NULL
                              GROUP BY MS.COD_EXPEDICAO) MSCONF ON MSCONF.COD_EXPEDICAO = E.COD_EXPEDICAO
                  LEFT JOIN (SELECT C.COD_EXPEDICAO,
                                    LISTAGG (C.COD_CARGA_EXTERNO,\', \') WITHIN GROUP (ORDER BY C.COD_CARGA_EXTERNO) CARGAS
                               FROM CARGA C '. $JoinExpedicao . $JoinSigla . '
                               WHERE 1 = 1 '. $WhereExpedicao . $WhereSigla . $WhereCarga.'
                              GROUP BY C.COD_EXPEDICAO) C ON C.COD_EXPEDICAO = E.COD_EXPEDICAO
                  LEFT JOIN (SELECT COD_EXPEDICAO,
                                    LISTAGG (DSC_ITINERARIO,\', \') WITHIN GROUP (ORDER BY DSC_ITINERARIO) ITINERARIOS
                               FROM (SELECT DISTINCT C.COD_EXPEDICAO,
                                            I.DSC_ITINERARIO,
                                            COD_CARGA_EXTERNO
                                       FROM CARGA C
                                      INNER JOIN PEDIDO P ON P.COD_CARGA = C.COD_CARGA '. $JoinExpedicao . $JoinSigla .'
                                      INNER JOIN ITINERARIO I ON P.COD_ITINERARIO = I.COD_ITINERARIO
                                      WHERE 1 = 1 '. $FullWhere .')
                              GROUP BY COD_EXPEDICAO) I ON I.COD_EXPEDICAO = E.COD_EXPEDICAO
                  LEFT JOIN (SELECT C.COD_EXPEDICAO,
                                    CASE WHEN (SUM(CASE WHEN (P.IND_ETIQUETA_MAPA_GERADO = \'N\') OR ((R.IND_ETIQUETA_MAPA_GERADO = \'N\' AND PARAM.DSC_VALOR_PARAMETRO = \'S\')) THEN 1 ELSE 0 END)) + NVL(MAP.QTD,0) + NVL(PED.QTD,0) > 0 THEN \'SIM\'
                                         ELSE \'\' END AS IMPRIMIR
                               FROM (SELECT DSC_VALOR_PARAMETRO FROM PARAMETRO WHERE DSC_PARAMETRO = \'CONFERE_EXPEDICAO_REENTREGA\') PARAM,
                                    CARGA C
                               LEFT JOIN REENTREGA R ON R.COD_CARGA = C.COD_CARGA
                               LEFT JOIN PEDIDO P ON P.COD_CARGA = C.COD_CARGA ' . $JoinExpedicao . $JoinSigla .'
                               LEFT JOIN (SELECT C.COD_EXPEDICAO, COUNT(COD_ETIQUETA_SEPARACAO) as QTD
                                            FROM ETIQUETA_SEPARACAO ES
                                            LEFT JOIN PEDIDO P ON P.COD_PEDIDO = ES.COD_PEDIDO
                                            LEFT JOIN CARGA C ON C.COD_CARGA = P.COD_CARGA ' . $JoinExpedicao . $JoinSigla .'
                                           WHERE ES.COD_STATUS = 522 ' . $FullWhere . '
                                           GROUP BY C.COD_EXPEDICAO) PED ON PED.COD_EXPEDICAO = C.COD_EXPEDICAO
                               LEFT JOIN (SELECT COD_EXPEDICAO,
                                                 COUNT(COD_MAPA_SEPARACAO) as QTD
                                            FROM MAPA_SEPARACAO
                                           WHERE COD_STATUS = 522
                                           GROUP BY COD_EXPEDICAO ) MAP ON MAP.COD_EXPEDICAO = C.COD_EXPEDICAO
                                   WHERE 1 = 1 ' .  $FullWhere .'
                              GROUP BY C.COD_EXPEDICAO, MAP.QTD, PED.QTD) P ON P.COD_EXPEDICAO = E.COD_EXPEDICAO
                  LEFT JOIN CARGA CA ON CA.COD_EXPEDICAO=E.COD_EXPEDICAO
                  LEFT JOIN PEDIDO PED ON CA.COD_CARGA=PED.COD_CARGA
                  LEFT JOIN (SELECT C.COD_EXPEDICAO,
                                    SUM(PROD.NUM_PESO * PP.QUANTIDADE) as NUM_PESO,
                                    SUM(PROD.NUM_CUBAGEM * PP.QUANTIDADE) as NUM_CUBAGEM
                               FROM CARGA C
                               LEFT JOIN PEDIDO P ON P.COD_CARGA = C.COD_CARGA
                               LEFT JOIN PEDIDO_PRODUTO PP ON PP.COD_PEDIDO = P.COD_PEDIDO '. $JoinExpedicao . $JoinSigla . '
                               LEFT JOIN SUM_PESO_PRODUTO PROD ON PROD.COD_PRODUTO = PP.COD_PRODUTO AND PROD.DSC_GRADE = PP.DSC_GRADE
                               WHERE 1 = 1  '.$FullWhere.'
                              GROUP BY C.COD_EXPEDICAO) PESO ON PESO.COD_EXPEDICAO = E.COD_EXPEDICAO
                 WHERE 1 = 1'. $FullWhere . '
                  GROUP BY E.COD_EXPEDICAO,
                          E.DSC_PLACA_EXPEDICAO,
                          E.DTH_INICIO,
                          E.DTH_FINALIZACAO,
                          C.CARGAS,
                          S.DSC_SIGLA,
                          P.IMPRIMIR,
                          PESO.NUM_PESO,
                          C.CONFERIDA,
                          PESO.NUM_CUBAGEM,
                          I.ITINERARIOS,
                          MS.QTD_CONFERIDA,
                          MSP.QTD_TOTAL,
                          C.QTDETIQUETA,
                          MSCONF.QTD_TOTAL_CONF_MANUAL
                 ORDER BY E.COD_EXPEDICAO DESC
    ';
        return \Wms\Domain\EntityRepository::nativeQuery($sql);
        //return $result=$this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }


    /**
     * @param $parametros
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function buscarVelho($parametros, $idDepositoLogado = null)
    {
        $where="";
        $whereSubQuery="";
        $and="";
        $andSub="";
        $cond="";

        if (isset($idDepositoLogado)) {
            $andWhere = 'WHERE P.CENTRAL_ENTREGA = ' . $idDepositoLogado;
        } else {
            $andWhere = '';
        }

        if (is_array($parametros['centrais'])) {
            $central = implode(',',$parametros['centrais']);
            $where.=$and."( PED.CENTRAL_ENTREGA in(".$central.")";
            $where.=" OR PED.PONTO_TRANSBORDO in(".$central.") )";
            $and=" AND ";
        }

        if (isset($parametros['placa']) && !empty($parametros['placa'])) {
            $where.=$and." E.DSC_PLACA_EXPEDICAO = '".$parametros['placa']."'";
            $and=" AND ";
        }

        if (isset($parametros['dataInicial1']) && (!empty($parametros['dataInicial1']))){
            $where.=$and." E.DTH_INICIO >= TO_DATE('".$parametros['dataInicial1']." 00:00', 'DD-MM-YYYY HH24:MI')";
            $and=" AND ";
        }
        if (isset($parametros['dataInicial2']) && (!empty($parametros['dataInicial2']))){
            $where.=$and." E.DTH_INICIO <= TO_DATE('".$parametros['dataInicial2']." 23:59', 'DD-MM-YYYY HH24:MI')";
            $and=" AND ";
        }

        if (isset($parametros['dataFinal1']) && (!empty($parametros['dataFinal1']))) {
            $where.=$and."E.DTH_FINALIZACAO >= TO_DATE('".$parametros['dataFinal1']." 00:00', 'DD-MM-YYYY HH24:MI')";
            $and=" AND ";
        }
        if (isset($parametros['dataFinal2']) && (!empty($parametros['dataFinal2']))) {
            $where.=$and."E.DTH_FINALIZACAO <= TO_DATE('".$parametros['dataFinal2']." 23:59', 'DD-MM-YYYY HH24:MI')";
            $and=" AND ";
        }

        if (isset($parametros['status']) && (!empty($parametros['status']))) {
            $where.=$and."S.COD_SIGLA = ".$parametros['status']."";
            $and=" and ";
        }
        if (isset($parametros['idExpedicao']) && !empty($parametros['idExpedicao'])) {
            $where=" E.COD_EXPEDICAO = ".$parametros['idExpedicao']."";
            $whereSubQuery=" C.COD_EXPEDICAO = ".$parametros['idExpedicao']."";
            $and=" and ";
            $andSub=" and ";
        }

        if (isset($parametros['codCargaExterno']) && !empty($parametros['codCargaExterno'])) {
            $where=" CA.COD_CARGA_EXTERNO = ".$parametros['codCargaExterno']."";
            $whereSubQuery=" C.COD_CARGA_EXTERNO = ".$parametros['codCargaExterno']."";
            $and=" and ";
            $andSub=" and ";
        }


        if ( $whereSubQuery!="" )
            $cond=" WHERE ";


        $sql='  SELECT E.COD_EXPEDICAO AS "id",
                       E.DSC_PLACA_EXPEDICAO AS "placaExpedicao",
                       to_char(E.DTH_INICIO,\'DD/MM/YYYY HH24:MI:SS\') AS "dataInicio",
                       to_char(E.DTH_FINALIZACAO,\'DD/MM/YYYY HH24:MI:SS\') AS "dataFinalizacao",
                       C.CARGAS AS "carga",
                       S.DSC_SIGLA AS "status",
                       P.IMPRIMIR AS "imprimir",
                       PESO.NUM_PESO as "peso",
                       PESO.NUM_CUBAGEM as "cubagem",
                       I.ITINERARIOS AS "itinerario",
                       (CASE WHEN ((NVL(MS.QTD_CONFERIDA,0) + NVL(C.CONFERIDA,0)) * 100) = 0 THEN 0
                          ELSE CAST(((NVL(MS.QTD_CONFERIDA,0) + NVL(C.CONFERIDA,0) + NVL(MSCONF.QTD_TOTAL_CONF_MANUAL,0) ) * 100) / (NVL(MSP.QTD_TOTAL,0) + NVL(C.QTDETIQUETA,0)) AS NUMBER(6,2))
                       END) AS "PercConferencia"
                  FROM EXPEDICAO E
                  LEFT JOIN SIGLA S ON S.COD_SIGLA = E.COD_STATUS
                  LEFT JOIN (SELECT C.Etiqueta AS CONFERIDA, (COUNT(DISTINCT ESEP.COD_ETIQUETA_SEPARACAO)) AS QTDETIQUETA, CARGA.COD_EXPEDICAO
                        FROM ETIQUETA_SEPARACAO ESEP
                        INNER JOIN PEDIDO P ON P.COD_PEDIDO = ESEP.COD_PEDIDO
                        INNER JOIN CARGA ON CARGA.COD_CARGA = P.COD_CARGA
                        LEFT JOIN (
                        SELECT COUNT(DISTINCT ES.COD_ETIQUETA_SEPARACAO) AS Etiqueta, C.COD_EXPEDICAO
                        FROM ETIQUETA_SEPARACAO ES
                        INNER JOIN PEDIDO P ON P.COD_PEDIDO = ES.COD_PEDIDO
                        INNER JOIN CARGA C ON C.COD_CARGA = P.COD_CARGA
                        WHERE ES.COD_STATUS IN(526, 531, 532) GROUP BY C.COD_EXPEDICAO) C ON C.COD_EXPEDICAO = CARGA.COD_EXPEDICAO
                        WHERE ESEP.COD_STATUS NOT IN(524, 525) GROUP BY CARGA.COD_EXPEDICAO, C.Etiqueta) C ON C.COD_EXPEDICAO = E.COD_EXPEDICAO
                  LEFT JOIN (SELECT
                        SUM(MSC.QTD_CONFERIDA) QTD_CONFERIDA, MS.COD_EXPEDICAO
                        FROM MAPA_SEPARACAO MS
                        INNER JOIN MAPA_SEPARACAO_CONFERENCIA MSC ON MSC.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO
                        GROUP BY MS.COD_EXPEDICAO) MS ON MS.COD_EXPEDICAO = E.COD_EXPEDICAO
                  LEFT JOIN (SELECT SUM(MSP.QTD_SEPARAR) QTD_TOTAL, MS.COD_EXPEDICAO
                        FROM MAPA_SEPARACAO_PRODUTO MSP
                        INNER JOIN MAPA_SEPARACAO MS ON MSP.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO
                        GROUP BY MS.COD_EXPEDICAO) MSP ON MSP.COD_EXPEDICAO = E.COD_EXPEDICAO
                  LEFT JOIN (SELECT
                        SUM(MSP.QTD_SEPARAR) QTD_TOTAL_CONF_MANUAL, MS.COD_EXPEDICAO
                        FROM MAPA_SEPARACAO_PRODUTO MSP
                        INNER JOIN MAPA_SEPARACAO MS ON MSP.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO
                        LEFT JOIN MAPA_SEPARACAO_CONFERENCIA MSCONF ON MSCONF.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO
                        WHERE MSP.IND_CONFERIDO = \'S\' AND MSCONF.COD_MAPA_SEPARACAO_CONFERENCIA IS NULL
                        GROUP BY MS.COD_EXPEDICAO) MSCONF ON MSCONF.COD_EXPEDICAO = E.COD_EXPEDICAO
                  LEFT JOIN (SELECT C.COD_EXPEDICAO,
                                    LISTAGG (C.COD_CARGA_EXTERNO,\', \') WITHIN GROUP (ORDER BY C.COD_CARGA_EXTERNO) CARGAS
                               FROM CARGA C '.$cond.' '.$whereSubQuery.'
                              GROUP BY COD_EXPEDICAO) C ON C.COD_EXPEDICAO = E.COD_EXPEDICAO
                  LEFT JOIN (SELECT COD_EXPEDICAO,
                                    LISTAGG (DSC_ITINERARIO,\', \') WITHIN GROUP (ORDER BY DSC_ITINERARIO) ITINERARIOS
                               FROM (SELECT DISTINCT C.COD_EXPEDICAO,
                                            I.DSC_ITINERARIO,
                                            COD_CARGA_EXTERNO
                                       FROM CARGA C
                                      INNER JOIN PEDIDO P ON P.COD_CARGA = C.COD_CARGA
                                      INNER JOIN ITINERARIO I ON P.COD_ITINERARIO = I.COD_ITINERARIO '.$cond.' '.$whereSubQuery.')
                              GROUP BY COD_EXPEDICAO) I ON I.COD_EXPEDICAO = E.COD_EXPEDICAO
                  LEFT JOIN (SELECT C.COD_EXPEDICAO,
                                    CASE WHEN (SUM(CASE WHEN (P.IND_ETIQUETA_MAPA_GERADO = \'N\') OR ((R.IND_ETIQUETA_MAPA_GERADO = \'N\' AND PARAM.DSC_VALOR_PARAMETRO = \'S\')) THEN 1 ELSE 0 END)) + NVL(MAP.QTD,0) + NVL(PED.QTD,0) > 0 THEN \'SIM\'
                                            ELSE \'\' END AS IMPRIMIR
                               FROM (SELECT DSC_VALOR_PARAMETRO FROM PARAMETRO WHERE DSC_PARAMETRO = \'CONFERE_EXPEDICAO_REENTREGA\') PARAM,
                                    CARGA C
                               LEFT JOIN REENTREGA R ON R.COD_CARGA = C.COD_CARGA
                               LEFT JOIN PEDIDO P ON P.COD_CARGA = C.COD_CARGA
                               LEFT JOIN (SELECT C.COD_EXPEDICAO, COUNT(COD_ETIQUETA_SEPARACAO) as QTD
                                            FROM ETIQUETA_SEPARACAO ES
                                            LEFT JOIN PEDIDO P ON P.COD_PEDIDO = ES.COD_PEDIDO
                                            LEFT JOIN CARGA C ON C.COD_CARGA = P.COD_CARGA
                                           WHERE COD_STATUS = 522 GROUP BY C.COD_EXPEDICAO) PED ON PED.COD_EXPEDICAO = C.COD_EXPEDICAO
                               LEFT JOIN (SELECT COD_EXPEDICAO, COUNT(COD_MAPA_SEPARACAO) as QTD FROM MAPA_SEPARACAO WHERE COD_STATUS = 522 GROUP BY COD_EXPEDICAO ) MAP ON MAP.COD_EXPEDICAO = C.COD_EXPEDICAO
                              GROUP BY C.COD_EXPEDICAO, MAP.QTD, PED.QTD) P ON P.COD_EXPEDICAO = E.COD_EXPEDICAO
                  LEFT JOIN CARGA CA ON CA.COD_EXPEDICAO=E.COD_EXPEDICAO
                  LEFT JOIN PEDIDO PED ON CA.COD_CARGA=PED.COD_CARGA
                  LEFT JOIN (SELECT C.COD_EXPEDICAO,
                                    SUM(PROD.NUM_PESO * PP.QUANTIDADE) as NUM_PESO,
                                    SUM(PROD.NUM_CUBAGEM * PP.QUANTIDADE) as NUM_CUBAGEM
                               FROM CARGA C
                               LEFT JOIN PEDIDO P ON P.COD_CARGA = C.COD_CARGA
                               LEFT JOIN PEDIDO_PRODUTO PP ON PP.COD_PEDIDO = P.COD_PEDIDO
                               LEFT JOIN (SELECT P.COD_PRODUTO,
                                                 P.DSC_GRADE,
                                                 PDL.NUM_PESO,
                                                 PDL.NUM_CUBAGEM
                                            FROM (SELECT PE.COD_PRODUTO, PE.DSC_GRADE, MIN(PDL.COD_PRODUTO_DADO_LOGISTICO) as COD_PRODUTO_DADO_LOGISTICO
                                                   FROM (SELECT MIN(COD_PRODUTO_EMBALAGEM) AS COD_PRODUTO_EMBALAGEM, PE.COD_PRODUTO,PE.DSC_GRADE
                                                           FROM PRODUTO_EMBALAGEM PE
                                                          INNER JOIN (SELECT MIN(QTD_EMBALAGEM) AS FATOR, COD_PRODUTO, DSC_GRADE
                                                                        FROM PRODUTO_EMBALAGEM PE
                                                                       GROUP BY COD_PRODUTO,DSC_GRADE) PEM
                                                             ON (PEM.COD_PRODUTO = PE.COD_PRODUTO) AND (PEM.DSC_GRADE = PE.DSC_GRADE) AND (PEM.FATOR = PE.QTD_EMBALAGEM)
                                                          GROUP BY PE.COD_PRODUTO, PE.DSC_GRADE) PE
                                                  INNER JOIN PRODUTO_DADO_LOGISTICO PDL ON PDL.COD_PRODUTO_EMBALAGEM = PE.COD_PRODUTO_EMBALAGEM
                                                  GROUP BY COD_PRODUTO, DSC_GRADE) P
                                           INNER JOIN PRODUTO_DADO_LOGISTICO PDL ON PDL.COD_PRODUTO_DADO_LOGISTICO = P.COD_PRODUTO_DADO_LOGISTICO
                                          UNION
                                          SELECT PV.COD_PRODUTO,
                                                 PV.DSC_GRADE,
                                                 SUM(PV.NUM_PESO) as NUM_PESO,
                                                 SUM(PV.NUM_CUBAGEM) as NUM_CUBAGEM
                                            FROM PRODUTO_VOLUME PV
                                           GROUP BY PV.COD_PRODUTO,
                                                    PV.DSC_GRADE) PROD
                                 ON PROD.COD_PRODUTO = PP.COD_PRODUTO AND PROD.DSC_GRADE = PP.DSC_GRADE
                                 '.$andWhere.'
                              GROUP BY C.COD_EXPEDICAO) PESO ON PESO.COD_EXPEDICAO = E.COD_EXPEDICAO
                 WHERE '.$where.'
                 GROUP BY E.COD_EXPEDICAO,
                          E.DSC_PLACA_EXPEDICAO,
                          E.DTH_INICIO,
                          E.DTH_FINALIZACAO,
                          C.CARGAS,
                          S.DSC_SIGLA,
                          P.IMPRIMIR,
                          PESO.NUM_PESO,
                          C.CONFERIDA,
                          PESO.NUM_CUBAGEM,
                          I.ITINERARIOS,
                          MS.QTD_CONFERIDA,
                          MSP.QTD_TOTAL,
                          C.QTDETIQUETA,
                          MSCONF.QTD_TOTAL_CONF_MANUAL
                 ORDER BY E.COD_EXPEDICAO DESC
                     ';

        return \Wms\Domain\EntityRepository::nativeQuery($sql);
        //$result=$this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        //return $result;
    }

    /**
     * @param null $status
     * @return array
     */
    public function getByStatusAndCentral($status = null, $central = null)
    {
        $source = $this->getEntityManager()->createQueryBuilder()
            ->select('e.id, e.dataInicio, e.codStatus, e.placaExpedicao')
            ->from('wms:Expedicao', 'e')
            ->innerJoin('wms:Expedicao\Carga', 'c', 'WITH', 'e.id = c.expedicao')
            ->innerJoin('wms:Expedicao\Pedido', 'pedido', 'WITH', 'c.id = pedido.carga')
            ->orderBy("e.id" , "DESC")
            ->distinct(true);

        $parcialmenteFinalizado = ExpedicaoEntity::STATUS_PARCIALMENTE_FINALIZADO;
        if (is_array($central)) {
            $central = implode(',',$central);
            $source->andWhere("pedido.centralEntrega in ($central) AND e.codStatus != $parcialmenteFinalizado")
                ->orWhere("pedido.pontoTransbordo in ($central) AND e.codStatus = $parcialmenteFinalizado");
        } else if ($central) {
            $source->andWhere("pedido.centralEntrega = :central AND e.codStatus != $parcialmenteFinalizado")
                ->orWhere("pedido.pontoTransbordo = :central AND e.codStatus = $parcialmenteFinalizado");
            $source->setParameter('central', $central);
        }
        $source->andWhere("pedido.conferido = 0 OR pedido.conferido IS NULL");

        if (is_array($status)) {
            $status = implode(',',$status);
            $source->andWhere("e.status in ($status)");
        }else if ($status) {
            $source->andWhere("e.status = :status")
                ->setParameter('status', $status);
        }

        return $source->getQuery()->getArrayResult();

    }


    /**
     * @param $idExpedicao
     * @return array
     */
    public function criarOrdemServico($idExpedicao)
    {
        /** @var \Wms\Domain\Entity\OrdemServicoRepository $ordemServicoRepo */
        $ordemServicoRepo = $this->_em->getRepository('wms:OrdemServico');

        $ordemServicoEntity = $this->verificaOSUsuario($idExpedicao);

        if ($ordemServicoEntity == null) {

            // cria ordem de servico
            $idOrdemServico = $ordemServicoRepo->save(new OrdemServicoEntity, array(
                'identificacao' => array(
                    'tipoOrdem' => 'expedicao',
                    'idExpedicao' => $idExpedicao,
                    'idAtividade' => AtividadeEntity::CONFERIR_EXPEDICAO,
                    'formaConferencia' => OrdemServicoEntity::COLETOR,
                ),
            ));
        } else {
            $idOrdemServico = $ordemServicoEntity[0]->getID();
        }

        return array(
            'criado' => true,
            'id' => $idOrdemServico,
            'mensagem' => 'Ordem de Serviço Nº ' . $idOrdemServico . ' criada com sucesso.',
        );
    }

    /**
     * @param $idExpedicao
     * @return array
     */
    public function verificaOSUsuario($idExpedicao)
    {
        $idPessoa = \Zend_Auth::getInstance()->getIdentity()->getId();
        $source = $this->_em->createQueryBuilder()
            ->select('os')
            ->from('wms:OrdemServico', 'os')
            ->where('os.expedicao = :idExpedicao')
            ->andWhere('os.pessoa = :pessoa')
            ->setParameter('idExpedicao', $idExpedicao)
            ->setParameter('pessoa', $idPessoa);

        return $source->getQuery()->getResult();
    }

    /**
     * @param $idExpedicao
     * @return mixed
     */
    public function getResumoConferenciaByID ($idExpedicao)
    {
        $source = $this->_em->createQueryBuilder()
            ->select('e.id,
                      e.dataInicio,
                      e.dataFinalizacao,
                      s.id as codSigla,
                      s.sigla')
            ->from('wms:Expedicao', 'e')
            ->leftJoin("e.status", "s")
            ->addSelect("(
                         SELECT COUNT(es1.id)
                           FROM wms:Expedicao\EtiquetaSeparacao es1
                          LEFT JOIN es1.pedido ped1
                          LEFT JOIN ped1.carga c1
                          WHERE c1.codExpedicao = e.id
                            AND es1.codStatus NOT IN(524,525)
                          GROUP BY c1.codExpedicao
                          ) as qtdEtiquetas")
            ->where('e.id = :idExpedicao')
            ->setParameter('idExpedicao', $idExpedicao);

        $expedicaoRepo   = $this->_em->getRepository('wms:Expedicao');
        $expedicaoEntity = $expedicaoRepo->find($idExpedicao);
        if ($expedicaoEntity->getStatus()->getId() == Expedicao::STATUS_SEGUNDA_CONFERENCIA) {
            $source->addSelect("(
             SELECT COUNT(es2.id)
               FROM wms:Expedicao\EtiquetaConferencia es2
               LEFT JOIN es2.pedido ped2
               LEFT JOIN ped2.carga c2
              INNER JOIN wms:Expedicao\EtiquetaSeparacao ess WITH es2.codEtiquetaSeparacao = ess.id
              WHERE c2.codExpedicao = e.id
                AND es2.codStatus in ( ". Expedicao::STATUS_SEGUNDA_CONFERENCIA . " )
              GROUP BY c2.codExpedicao
              ) as qtdConferidas");

        } else {
            $source->addSelect("(
             SELECT COUNT(es2.id)
               FROM wms:Expedicao\EtiquetaSeparacao es2
              LEFT JOIN es2.pedido ped2
              LEFT JOIN ped2.carga c2
              WHERE c2.codExpedicao = e.id
                AND es2.codStatus in ( 526, 531, 532 )
              GROUP BY c2.codExpedicao
              ) as qtdConferidas");
        }

        $result = $source->getQuery()->getResult();

        return $result[0];
    }

    public function getAndamentoByExpedicao ($idExpedicao) {
        $source = $this->_em->createQueryBuilder()
            ->select("a.dscObservacao,
                      a.dataAndamento,
                      p.nome")
            ->from("wms:Expedicao\Andamento", "a")
            ->innerJoin("a.usuario", "u")
            ->innerJoin("u.pessoa", "p")
            ->where('a.expedicao = :idExpedicao')
            ->setParameter('idExpedicao', $idExpedicao)
            ->orderBy("a.id" , "DESC");

        $result = $source->getQuery()->getResult();

        return $source;
    }

    public function getOSByUser () {

        $idPessoa = \Zend_Auth::getInstance()->getIdentity()->getId();

        $source = $this->_em->createQueryBuilder()
            ->select("exp.id")
            ->from("wms:OrdemServico","os")
            ->innerJoin("os.expedicao", "exp")
            ->where("os.pessoa = :idPessoa")
            ->andWhere("exp.status IN (464,463)")
            ->setParameter("idPessoa",$idPessoa);

        $result = $source->getQuery()->getResult();

        $arrayResult = array();
        foreach ($result as $item) {
            $arrayResult = $item;
        }

        return $arrayResult;
    }

    public function getRelatorioSaidaProdutos($codProduto, $grade, $dataInicial = null, $dataFinal = null)
    {
        $source = $this->_em->createQueryBuilder()
            ->select("es.dataConferencia, i.descricao as itinerario, i.id as idItinerario, c.codCargaExterno, e.id as idExpedicao, cliente.codClienteExterno, es.codProduto, es.dscGrade,
             e.dataInicio, e.dataFinalizacao, p.id as idPedido")
            ->from("wms:Expedicao\EtiquetaSeparacao","es")
            ->innerJoin('es.pedido', 'p')
            ->innerJoin('p.itinerario', 'i')
            ->innerJoin('p.carga', 'c')
            ->innerJoin('c.expedicao', 'e')
            ->innerJoin('p.pessoa', 'cliente')
            ->where('es.codProduto = :codProduto')
            ->orderBy('e.dataFinalizacao','DESC')
            ->setParameter("codProduto", $codProduto);

        if (isset($dataInicial) && (!empty($dataInicial))) {
            $dataInicial = str_replace('/','-',$dataInicial);
            $data1 = new \DateTime($dataInicial);
            $data1 = $data1->format('Y-m-d') . ' 00:00:00';
            $source->setParameter('dataInicio', $data1)
                ->andWhere("e.dataFinalizacao >= :dataInicio");

        }

        if (isset($dataFinal) && (!empty($dataFinal))) {
            $dataFinal = str_replace('/','-',$dataFinal);
            $data2 = new \DateTime($dataFinal);
            $data2 = $data2->format('Y-m-d') . ' 23:59:59';

            $source->setParameter('dataFinal', $data2)
                ->andWhere('e.dataFinalizacao <= :dataFinal');
        }

        if (isset($grade)) {
            $source->andWhere('es.dscGrade = :grade')
                    ->setParameter('grade', $grade);
        }

        return $source->getQuery()->getResult();
    }


    public function getEtiquetasConferidasByVolume($idExpedicao,$idVolumePatrimonio)
    {
        $dql = $this->getEntityManager()->createQueryBuilder()
            ->select("es.codBarras,
                      es.cliente,
                      es.codProduto,
                      es.produto,
                      es.codCargaExterno,
                      es.grade,
                      es.codEstoque,
                      CASE WHEN emb.descricao IS NULL THEN vol.descricao ELSE emb.descricao END as embalagem,
                      etq.dataConferencia,
                      p.nome as conferente,
                      CONCAT(CONCAT(vp.descricao ,' '), vp.id) as volumePatrimonio")
            ->from('wms:Expedicao\VEtiquetaSeparacao','es')
            ->innerJoin('wms:Expedicao\EtiquetaSeparacao', 'etq', 'WITH', 'es.codBarras = etq.id')
            ->leftJoin('wms:OrdemServico','os','WITH','etq.codOS = os.id')
            ->leftJoin('os.pessoa','p')
            ->leftJoin('etq.volumePatrimonio','vp')
            ->leftJoin('etq.produtoEmbalagem','emb')
            ->leftJoin('etq.produtoVolume','vol')
            ->where("es.codExpedicao = $idExpedicao")
            ->andWhere('es.codStatus IN (526,531,532)');

        if ($idVolumePatrimonio != NULL) {
            $dql->andWhere("etq.volumePatrimonio = $idVolumePatrimonio");
        }

        $result = $dql->getQuery()->getArrayResult();
        return $result;
    }

    public function getProdutosEmbalado($idExpedicao)
    {
        $source = $this->_em->createQueryBuilder()
            ->select("count(DISTINCT exp.id) as nEmbalados")
            ->from("wms:Expedicao\EtiquetaSeparacao","es")
            ->innerJoin('wms:Produto', 'p', 'WITH', 'es.codProduto = p.id')
            ->innerJoin('p.embalagens', 'pe')
            ->innerJoin("wms:Expedicao\PedidoProduto", 'pp', 'WITH', 'pp.codProduto = p.id')
            ->innerJoin('pp.pedido', 'ped')
            ->innerJoin('ped.carga', 'c')
            ->innerJoin('c.expedicao', 'exp')
            ->where('exp.id = :idExpedicao')
            ->andWhere("pe.embalado = 'S' ")
            ->setParameter("idExpedicao", $idExpedicao);

        $result = $source->getQuery()->getSingleResult();
        return $result['nEmbalados'];
    }

    public function getCargaExternoEmbalados($idExpedicao, $codStatus = EtiquetaSeparacao::STATUS_ETIQUETA_GERADA)
    {
        $source = $this->_em->createQueryBuilder()
            ->select("es.codCargaExterno")
            ->from("wms:Expedicao\VEtiquetaSeparacao","es")
            ->innerJoin('wms:Produto', 'p', 'WITH', 'es.codProduto = p.id')
            ->innerJoin('p.embalagens', 'pe')
            ->where('es.codExpedicao = :idExpedicao')
            ->andWhere("pe.embalado = 'S' ")
            ->groupBy('es.codCargaExterno')
            ->setParameter("idExpedicao", $idExpedicao);
        return $source->getQuery()->getResult();
    }

    public function getDadosExpedicao ($params) 
    {
        $dataInicial = $params['dataInicial'];
        $dataFim = $params['dataFim'];
        $statusCancelado = \Wms\Domain\Entity\Expedicao::STATUS_CANCELADO;

        $sql = "  SELECT E.COD_EXPEDICAO as \"COD.EXPEDICAO\",
                         E.DSC_PLACA_EXPEDICAO \"PLACA EXPEDICAO\",
                         TO_CHAR(E.DTH_INICIO,'DD/MM/YYYY HH24:MI:SS') \"DTH. INICIO EXPEDICAO\",
                         TO_CHAR(E.DTH_FINALIZACAO,'DD/MM/YYYY HH24:MI:SS') \"DTH. FINAL EXPEDICAO\",
                         S.DSC_SIGLA \"STATUS EXPEDICAO\",
                         C.COD_CARGA_EXTERNO as \"CARGA\",
                         C.CENTRAL_ENTREGA as \"CENTRAL ENTREGA CARGA\",
                         C.DSC_PLACA_CARGA \"PLACA CARGA\",
                         (SELECT COUNT (PP.COD_PEDIDO_PRODUTO) FROM PEDIDO PED
                             INNER JOIN ETIQUETA_SEPARACAO ETI ON PED.COD_PEDIDO = ETI.COD_PEDIDO WHERE PED.COD_CARGA = C.COD_CARGA) \"QTD. ETIQUETAS CARGA\",
                         P.COD_PEDIDO \"PEDIDO\",
                         S2.DSC_SIGLA AS \"TIPO PEDIDO\",
                         I.DSC_ITINERARIO \"ITINERARIO\",
                         P.DSC_LINHA_ENTREGA \"LINHA DE ENTREGA\",
                         P.CENTRAL_ENTREGA as \"CENTRAL ENTREGA PEDIDO\",
                         P.PONTO_TRANSBORDO as \"PONTO DE TRANSBORDO PEDIDO\",
                         PP.COD_PRODUTO \"COD. PRODUTO\",
                         PP.DSC_GRADE \"GRADE\",
                         PROD.DSC_PRODUTO \"PRODUTO\",
                         F.NOM_FABRICANTE \"FABRICANTE\",
                         LS.DSC_LINHA_SEPARACAO \"LINHA SEPARACAO\",
                         TO_CHAR(ES.DTH_CONFERENCIA,'DD/MM/YYYY HH24:MI:SS') \"DTH CONFERENCIA ETIQUETA\",
                         ES.COD_ETIQUETA_SEPARACAO \"ETIQUETA SEPARACAO\",
                         SES.DSC_SIGLA \"STATUS ETIQUETA\",
                         NVL(PDL.NUM_PESO, PV.NUM_PESO) \"PESO\",
                         NVL(PDL.NUM_LARGURA, PV.NUM_LARGURA) \"LARGURA\",
                         NVL(PDL.NUM_ALTURA, PV.NUM_ALTURA) \"ALTURA\",
                         NVL(PDL.NUM_PROFUNDIDADE, PV.NUM_PROFUNDIDADE) \"PROFUNDIDADE\",
                         NVL(PDL.NUM_CUBAGEM, PV.NUM_CUBAGEM) \"CUBAGEM\",
                         NVL(PE.DSC_EMBALAGEM, PV.DSC_VOLUME) \"EMBALAGEM/VOLUME\",
                                   NVL(DE1.DSC_DEPOSITO_ENDERECO, DE2.DSC_DEPOSITO_ENDERECO) \"END.PICKING\",
                               OS.COD_OS \"OS\",
                               CONFERENTE.NOM_PESSOA \"CONFERENTE\",
                               CASE WHEN OS.COD_FORMA_CONFERENCIA = 'C' THEN 'COLETOR'
                                    ELSE 'MANUAL'
                               END AS \"TIPO CONFERENCIA\",
                               ES.COD_OS_TRANSBORDO \"OS TRANSBORDO\",
                               CONFERENTE_TRANSBORDO.NOM_PESSOA \"CONFERENTE TRANSBORDO\",
                               CLIENTE.COD_CLIENTE_EXTERNO \"CODIGO CLIENTE\",
                               CLIENTE.NOM_PESSOA \"CLIENTE\",
                               ENDERECO.DSC_ENDERECO \"ENDERECO CLIENTE\",
                               ENDERECO.NOM_LOCALIDADE \"CIDADE CLIENTE\",
                               UF.DSC_SIGLA \"ESTADO CLIENTE\",
                               ENDERECO.NOM_BAIRRO \"NOME BAIRRO\"
                         FROM EXPEDICAO E
                        INNER JOIN CARGA C ON E.COD_EXPEDICAO = C.COD_EXPEDICAO
                        INNER JOIN SIGLA S ON E.COD_STATUS = S.COD_SIGLA
                        INNER JOIN PEDIDO P ON C.COD_CARGA = P.COD_CARGA
                        INNER JOIN SIGLA S2 ON S2.COD_SIGLA = P.COD_TIPO_PEDIDO
                        INNER JOIN ITINERARIO I ON P.COD_ITINERARIO = I.COD_ITINERARIO
                        INNER JOIN PEDIDO_PRODUTO PP ON P.COD_PEDIDO = PP.COD_PEDIDO
                         LEFT JOIN PRODUTO PROD ON PP.COD_PRODUTO = PROD.COD_PRODUTO AND PP.DSC_GRADE  = PROD.DSC_GRADE
                         LEFT JOIN FABRICANTE F ON F.COD_FABRICANTE = PROD.COD_FABRICANTE
                         LEFT JOIN LINHA_SEPARACAO LS ON PROD.COD_LINHA_SEPARACAO = LS.COD_LINHA_SEPARACAO
                         LEFT JOIN ETIQUETA_SEPARACAO ES ON PP.COD_PEDIDO = ES.COD_PEDIDO AND PP.COD_PRODUTO = ES.COD_PRODUTO
                         LEFT JOIN SIGLA SES ON SES.COD_SIGLA = ES.COD_STATUS
                         LEFT JOIN PRODUTO_VOLUME PV ON ES.COD_PRODUTO_VOLUME = PV.COD_PRODUTO_VOLUME
                         LEFT JOIN PRODUTO_EMBALAGEM PE ON PE.COD_PRODUTO_EMBALAGEM = ES.COD_PRODUTO_EMBALAGEM
                 LEFT JOIN DEPOSITO_ENDERECO DE1 ON DE1.COD_DEPOSITO_ENDERECO = PE.COD_DEPOSITO_ENDERECO
                 LEFT JOIN DEPOSITO_ENDERECO DE2 ON DE2.COD_DEPOSITO_ENDERECO = PV.COD_DEPOSITO_ENDERECO
                         LEFT JOIN PRODUTO_DADO_LOGISTICO PDL ON PDL.COD_PRODUTO_EMBALAGEM = PE.COD_PRODUTO_EMBALAGEM
                         LEFT JOIN ORDEM_SERVICO OS ON ES.COD_OS = OS.COD_OS
                         LEFT JOIN ORDEM_SERVICO OS2 ON ES.COD_OS_TRANSBORDO = OS2.COD_OS
                         LEFT JOIN (SELECT CONF.COD_CONFERENTE, PE.NOM_PESSOA
                                      FROM CONFERENTE CONF
                                     INNER JOIN PESSOA PE ON CONF.COD_CONFERENTE = PE.COD_PESSOA) CONFERENTE ON OS.COD_PESSOA = CONFERENTE.COD_CONFERENTE
                        LEFT JOIN (SELECT CONF2.COD_CONFERENTE, PE2.NOM_PESSOA
                                     FROM CONFERENTE CONF2
                                    INNER JOIN PESSOA PE2 ON CONF2.COD_CONFERENTE = PE2.COD_PESSOA) CONFERENTE_TRANSBORDO ON OS2.COD_PESSOA = CONFERENTE_TRANSBORDO.COD_CONFERENTE
                        LEFT JOIN PEDIDO_ENDERECO ENDERECO ON ENDERECO.COD_PEDIDO = P.COD_PEDIDO
                        LEFT JOIN SIGLA UF ON UF.COD_SIGLA = ENDERECO.COD_UF
                        LEFT JOIN (SELECT CL.COD_PESSOA,
                                          CL.COD_CLIENTE_EXTERNO,
                                          PE.NOM_PESSOA
                                     FROM CLIENTE CL
                                    INNER JOIN PESSOA PE ON CL.COD_PESSOA = PE.COD_PESSOA) CLIENTE
                          ON P.COD_PESSOA = CLIENTE.COD_PESSOA
               WHERE (E.COD_STATUS <> $statusCancelado)
                 AND ((E.DTH_INICIO >= TO_DATE('$dataInicial 00:00', 'DD-MM-YYYY HH24:MI'))
                 AND (E.DTH_INICIO <= TO_DATE('$dataFim 23:59', 'DD-MM-YYYY HH24:MI')))
                ORDER BY E.DTH_INICIO";

        $resultado = $this->getEntityManager()->getConnection()->query($sql)-> fetchAll(\PDO::FETCH_ASSOC);
        return $resultado;
    }

    public function getCarregamentoByExpedicao($codExpedicao, $codStatus = null, $codCargaExterno = null)
    {
        $source = $this->_em->createQueryBuilder()
            ->select("ped.id              as pedido,
                      it.descricao        as itinerario,
                      endere.localidade   as cidade,
                      endere.bairro       as bairro,
                      endere.descricao    as rua,
                      pessoa.nome         as cliente,
                      ped.sequencia,
                      car.codCargaExterno              as carga
             ")
            ->from("wms:Expedicao\PedidoProduto", "pp")
            ->leftJoin("pp.produto"         ,"prod")
            ->leftJoin("pp.pedido"          ,"ped")
            ->leftJoin("ped.carga"          ,"car")
            ->leftJoin("ped.itinerario"     ,"it")
            ->leftJoin("ped.pessoa"         ,"cli")
            ->leftJoin("cli.pessoa"         ,"pessoa")
            ->leftJoin("pessoa.enderecos"   ,"endere")
            ->distinct(true)
            ->where("prod.linhaSeparacao != 15")
            ->groupBy("ped.id, it.descricao, endere.localidade, endere.bairro, endere.descricao, pessoa.nome, ped.sequencia, car.codCargaExterno")
            ->orderBy('car.codCargaExterno, ped.sequencia,  it.descricao, endere.localidade, endere.bairro, endere.descricao, pessoa.nome ');

        if (!is_null($codExpedicao) && ($codExpedicao != "")) {
            $source->andWhere("car.codExpedicao = " . $codExpedicao);
        }

        if (!is_null($codCargaExterno) && ($codCargaExterno != "")) {
            $source->andWhere("car.codCargaExterno = " . $codCargaExterno);
        }

        if ($codStatus != NULL){
            $source->andWhere("es.codStatus = $codStatus ");
        }

        return $source->getQuery()->getResult();
    }

    public function getProdutosSemEstoqueByExpedicao($idExpedicao) 
    {

        $SQL = "
            SELECT * FROM (
            SELECT DE.DSC_DEPOSITO_ENDERECO as ENDERECO,
                   REP.COD_PRODUTO as CODIGO,
                   REP.DSC_GRADE as GRADE,
                   P.DSC_PRODUTO as PRODUTO,
                   NVL(PV.DSC_VOLUME,'PRODUTO UNITARIO') as VOLUME,
                   NVL(E.QTD,0) as ESTOQUE,
                   SUM(REP.QTD_RESERVADA) * -1 as QTD_RESERVADO,
                   NVL(E.QTD,0) + SUM(REP.QTD_RESERVADA) as SALDO
              FROM RESERVA_ESTOQUE_EXPEDICAO REE
              LEFT JOIN RESERVA_ESTOQUE RE ON RE.COD_RESERVA_ESTOQUE = REE.COD_RESERVA_ESTOQUE
              LEFT JOIN RESERVA_ESTOQUE_PRODUTO REP ON REP.COD_RESERVA_ESTOQUE = RE.COD_RESERVA_ESTOQUE
              LEFT JOIN PRODUTO_VOLUME PV ON PV.COD_PRODUTO_VOLUME = REP.COD_PRODUTO_VOLUME
              LEFT JOIN DEPOSITO_ENDERECO DE ON DE.COD_DEPOSITO_ENDERECO = RE.COD_DEPOSITO_ENDERECO
              LEFT JOIN PRODUTO P ON P.COD_PRODUTO = REP.COD_PRODUTO AND P.DSC_GRADE = REP.DSC_GRADE
              LEFT JOIN (SELECT COD_PRODUTO,DSC_GRADE, COD_DEPOSITO_ENDERECO, NVL(COD_PRODUTO_VOLUME,0) as VOLUME, SUM(QTD) as QTD
                           FROM ESTOQUE
                          GROUP BY COD_PRODUTO, DSC_GRADE, COD_DEPOSITO_ENDERECO, NVL(COD_PRODUTO_VOLUME,0)) E
                ON E.COD_DEPOSITO_ENDERECO = RE.COD_DEPOSITO_ENDERECO
               AND E.COD_PRODUTO = REP.COD_PRODUTO
               AND E.DSC_GRADE = REP.DSC_GRADE
               AND E.VOLUME = NVL(REP.COD_PRODUTO_VOLUME,0)
             WHERE 1 = 1
               AND REE.COD_EXPEDICAO = $idExpedicao
               AND RE.IND_ATENDIDA = 'N'
             GROUP BY REP.COD_PRODUTO, REP.DSC_GRADE, PV.DSC_VOLUME, P.DSC_PRODUTO, E.QTD, DE.DSC_DEPOSITO_ENDERECO)
             WHERE SALDO <0
             ORDER BY CODIGO";

        $result=$this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);
        return $result;

    }

    public function finalizacarga($codExpedicao)
    {

        $cargaRepo = $this->getEntityManager()->getRepository('wms:Expedicao\Carga');
        $getCargaByExpedicao = $cargaRepo->findBy(array('expedicao' => $codExpedicao));

        foreach ($getCargaByExpedicao as $cargas) {
            if ($cargas->getDataFechamento() == null || $cargas->getDataFechamento() == '') {
                $cargas->setDataFechamento(new \DateTime());
                $this->_em->persist($cargas);
            }
        }
        $this->getEntityManager()->flush();

    }

    public function getVolumesExpedicaoByExpedicao($idExpedicao)
    {
        $sql = "SELECT
                  DISTINCT
                    vp.COD_VOLUME_PATRIMONIO as VOLUME, vp.DSC_VOLUME_PATRIMONIO as DESCRICAO, i.DSC_ITINERARIO as ITINERARIO, pes.NOM_PESSOA as CLIENTE
                    FROM EXPEDICAO_VOLUME_PATRIMONIO evp
                INNER JOIN VOLUME_PATRIMONIO vp ON vp.COD_VOLUME_PATRIMONIO = evp.COD_VOLUME_PATRIMONIO
                INNER JOIN CARGA c ON c.COD_EXPEDICAO = evp.COD_EXPEDICAO
                INNER JOIN PEDIDO p ON p.COD_CARGA = C.COD_CARGA
                INNER JOIN ETIQUETA_SEPARACAO es ON p.COD_PEDIDO = es.COD_PEDIDO AND evp.COD_VOLUME_PATRIMONIO = es.COD_VOLUME_PATRIMONIO
                INNER JOIN PESSOA pes ON pes.COD_PESSOA = p.COD_PESSOA
                INNER JOIN ITINERARIO i ON i.COD_ITINERARIO = p.COD_ITINERARIO
                WHERE evp.COD_EXPEDICAO = $idExpedicao
                ORDER BY vp.COD_VOLUME_PATRIMONIO ASC";

        $result=$this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);

        return $result;

    }

    public function getEtiquetaMae($quebras,$modelos,$arrayEtiqueta,$idExpedicao){

        /** @var \Wms\Domain\Entity\Expedicao\EtiquetaMaeRepository $EtiquetaMaeRepo */
        $EtiquetaMaeRepo = $this->_em->getRepository('wms:Expedicao\EtiquetaMae');
        $tipoFracao=$this->getTipoFracao($arrayEtiqueta,$idExpedicao);

        if ( !empty($tipoFracao[0]["TIPO"]) ){
            $dscEtiqueta=$tipoFracao[0]["TIPO"].";";

            foreach ($quebras as $chv => $vlr){
                if ( !empty($tipoFracao[0]["TIPO"]) && $tipoFracao[0]["TIPO"]=="1" ) {
                    $fracionados=$vlr['frac'];

                    foreach ($fracionados as $chvFrac => $vlrFrac){
                        $verificaFrac=false;

                        $sql="select E.COD_ETIQUETA_MAE from
                                ETIQUETA_MAE E
                                INNER JOIN ETIQUETA_MAE_QUEBRA EQ ON (E.COD_ETIQUETA_MAE=EQ.COD_ETIQUETA_MAE)
                            WHERE E.COD_EXPEDICAO=".$idExpedicao;

                        $codQuebra=$this->getCodQuebra($tipoFracao,$vlrFrac['tipoQuebra']);
                        if ( empty($codQuebra) ){
                            $codQuebra=" is NULL";
                        } else if ($codQuebra=="NULL") {
                            $codQuebra=" is NULL";
                        } else {
                            $codQuebra="=".$codQuebra;
                        }

                        $where=" AND EQ.TIPO_FRACAO='FRACIONADOS' AND EQ.COD_QUEBRA".$codQuebra." AND EQ.IND_TIPO_QUEBRA='".$vlrFrac['tipoQuebra']."'";

                        $sql.=$where;
                        $result = $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);

                        $dscEtiqueta.=$vlrFrac['tipoQuebra']."|".$this->getCodQuebra($tipoFracao,$vlrFrac['tipoQuebra']).";";

                        if ( !empty($result[0]['COD_ETIQUETA_MAE']) )
                            $verificaFrac=true;
                        else
                            break;
                    }

                    if ($verificaFrac)
                        $codEtiquetaMae=$result[0]['COD_ETIQUETA_MAE'];
                    else
                        $codEtiquetaMae=$EtiquetaMaeRepo->gerarEtiquetaMae($quebras,$tipoFracao,$idExpedicao,$dscEtiqueta);
                } else {
                    $naofracionados=$vlr['frac'];

                    foreach ($naofracionados as $chvNFrac => $vlrNFrac){
                        $verificaNFrac=false;

                        $sql="select E.COD_ETIQUETA_MAE from
                                ETIQUETA_MAE E
                                INNER JOIN ETIQUETA_MAE_QUEBRA EQ ON (E.COD_ETIQUETA_MAE=EQ.COD_ETIQUETA_MAE)
                            WHERE E.COD_EXPEDICAO=".$idExpedicao;

                        $codQuebra=$this->getCodQuebra($tipoFracao,$vlrNFrac['tipoQuebra']);
                        if ( empty($codQuebra) ){
                            $codQuebra=" is NULL";
                        } else if ($codQuebra=="NULL") {
                            $codQuebra=" is NULL";
                        } else {
                            $codQuebra="=".$codQuebra;
                        }

                        $where=" AND EQ.TIPO_FRACAO='NAOFRACIONADOS' AND EQ.COD_QUEBRA".$codQuebra." AND EQ.IND_TIPO_QUEBRA='".$vlrNFrac['tipoQuebra']."'";

                        $sql.=$where;
                        $dscEtiqueta.=$vlrNFrac['tipoQuebra']."|".$this->getCodQuebra($tipoFracao,$vlrNFrac['tipoQuebra']).";";

                        $result = $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);

                        if ( !empty($result[0]['COD_ETIQUETA_MAE']) )
                            $verificaNFrac=true;
                        else
                            break;
                    }

                    if ($verificaNFrac)
                        $codEtiquetaMae=$result[0]['COD_ETIQUETA_MAE'];
                    else
                        $codEtiquetaMae=$EtiquetaMaeRepo->gerarEtiquetaMae($quebras,$tipoFracao,$idExpedicao,$dscEtiqueta);
                }


            }
        } else {
            $codEtiquetaMae=null;
        }

        return $codEtiquetaMae;
    }

    public function getPracaByCliente($idCliente)
    {
        $dql = "SELECT (CASE WHEN C.COD_PRACA IS NOT NULL THEN C.COD_PRACA
                              ELSE PF.COD_PRACA END) as praca
                  FROM CLIENTE C
                INNER JOIN PESSOA_ENDERECO PE ON C.COD_PESSOA = PE.COD_PESSOA
                LEFT JOIN PRACA_FAIXA PF ON PE.NUM_CEP BETWEEN PF.FAIXA_CEP1 AND PF.FAIXA_CEP2
                  WHERE C.COD_CLIENTE_EXTERNO = $idCliente
      ";

        $result = $this->getEntityManager()->getConnection()->query($dql)->fetch(\PDO::FETCH_ASSOC);

        return $result;
    }

    public function getQtdMapasPendentesImpressao($codExpedicao){
        $SQL = "SELECT COUNT(COD_MAPA_SEPARACAO) as QTD
                  FROM MAPA_SEPARACAO
                 WHERE COD_STATUS = 522
                   AND COD_EXPEDICAO = " . $codExpedicao;
        $result = $this->getEntityManager()->getConnection()->query($SQL)->fetch(\PDO::FETCH_ASSOC);
        if (count($result) >0) {
            return $result['QTD'];
        } else {
            return 0;
        }
    }

    public function getQtdEtiquetasPendentesImpressao($codExpedicao){
        $SQL = "SELECT COUNT(COD_ETIQUETA_SEPARACAO) as QTD
                  FROM ETIQUETA_SEPARACAO ES
                  LEFT JOIN PEDIDO P ON P.COD_PEDIDO = ES.COD_PEDIDO
                  LEFT JOIN CARGA C ON C.COD_CARGA = P.COD_CARGA
                 WHERE COD_STATUS = 522
                   AND C.COD_EXPEDICAO = " . $codExpedicao;
        $result = $this->getEntityManager()->getConnection()->query($SQL)->fetch(\PDO::FETCH_ASSOC);
        if (count($result) >0) {
            return $result['QTD'];
        } else {
            return 0;
        }
    }

    public function getUrlMobileByCodBarras($codBarras){
        $LeituraColetor = new LeituraColetor();
        $codBarras = (float) $codBarras;
        $tipoEtiqueta  = null;

        if (strlen($codBarras) >2){
            if ((substr($codBarras,0,2)) == "39") {
                $tipoEtiqueta = EtiquetaSeparacao::PREFIXO_ETIQUETA_SEPARACAO;
            }

            if ((substr($codBarras,0,2)) == "69") {
                $tipoEtiqueta = EtiquetaSeparacao::PREFIXO_ETIQUETA_SEPARACAO;
            }
            if ((substr($codBarras,0,2)) == "68") {
                $tipoEtiqueta = EtiquetaSeparacao::PREFIXO_ETIQUETA_SEPARACAO;
            }

            if ((substr($codBarras,0,2)) == "10") {
                $tipoEtiqueta = EtiquetaSeparacao::PREFIXO_ETIQUETA_SEPARACAO;
            }
            if ((substr($codBarras,0,2)) == "11") {
                $tipoEtiqueta = EtiquetaSeparacao::PREFIXO_ETIQUETA_MAE;
            }
            if ((substr($codBarras,0,2)) == "12") {
                $tipoEtiqueta = EtiquetaSeparacao::PREFIXO_MAPA_SEPARACAO;
            }
            if ((substr($codBarras,0,2)) == "13") {
                $tipoEtiqueta = EtiquetaSeparacao::PREFIXO_ETIQUETA_VOLUME;
            }
        }

        //ETIQUETA DE VOLUME
        $volumeRepo  = $this->getEntityManager()->getRepository("wms:Expedicao\VolumePatrimonio");
        $volumeEn = $volumeRepo->find($codBarras);
        if ($volumeEn != null) {
            $tipoEtiqueta = EtiquetaSeparacao::PREFIXO_ETIQUETA_VOLUME;
        }

        if ($tipoEtiqueta == EtiquetaSeparacao::PREFIXO_ETIQUETA_SEPARACAO) {
            //ETIQUETA DE SEPARAÇÃO
            $codBarras = $LeituraColetor->retiraDigitoIdentificador($codBarras);
            $etiquetaSeparacao = $this->getEntityManager()->getRepository('wms:Expedicao\EtiquetaSeparacao')->find($codBarras);
            if ($etiquetaSeparacao == null) {
                throw new \Exception("Nenhuma Etiqueta de Separação encontrada com o codigo de barras " . $codBarras);
            }
            $idExpedicao = 0;
            $placa = "";
            $carga = "";

            if ($etiquetaSeparacao->getReentrega() != null) {
                $idExpedicao = $etiquetaSeparacao->getReentrega()->getCarga()->getExpedicao()->getId();

                $operacao = "Conferencia de Etiqueta de Reentrega";
                $url = "/mobile/expedicao/ler-codigo-barras/idExpedicao/$idExpedicao/tipo-conferencia/naoembalado";
            } else {
                switch ($etiquetaSeparacao->getStatus()->getId()){
                    case EtiquetaSeparacao::STATUS_PENDENTE_IMPRESSAO:
                        throw new \Exception("Etiqueta pendente de impresão");
                        break;
                    case EtiquetaSeparacao::STATUS_CORTADO:
                        throw new \Exception("Etiqueta Cortada");
                        break;
                    case EtiquetaSeparacao::STATUS_PENDENTE_CORTE:
                        throw new \Exception("Etiqueta Pendente de Corte");
                        break;
                    case EtiquetaSeparacao::STATUS_CONFERIDO:
                        $expedicao = $etiquetaSeparacao->getPedido()->getCarga()->getExpedicao();
                        $idExpedicao = $expedicao->getId();
                        $placa    = $etiquetaSeparacao->getPedido()->getCarga()->getPlacaCarga();
                        $carga    = $etiquetaSeparacao->getPedido()->getCarga()->getCodCargaExterno();
                        $idStatus = $expedicao->getStatus()->getId();

                        if ($idStatus == Expedicao::STATUS_PARCIALMENTE_FINALIZADO){
                            $idFilialExterno = $etiquetaSeparacao->getPedido()->getPontoTransbordo();
                            $filialEn = $this->getEntityManager()->getRepository("wms:Filial")->findOneBy(array('codExterno'=>$idFilialExterno));
                            if ($filialEn == null) {
                                throw new \Exception("Nenhuma filial encontrada com o código " . $idFilialExterno);
                            }

                            if ($filialEn->getIndRecTransbObg() == "S") {
                                $operacao = "Recebimento de Transbordo";
                                $url      = "/mobile/recebimento-transbordo/ler-codigo-barras/idExpedicao/".$idExpedicao;
                            } else {
                                $operacao    = "Expedição de Transbordo";
                                $url =       "/mobile/expedicao/ler-codigo-barras/idExpedicao/$idExpedicao/placa/$placa";
                            }
                            return array('operacao'=>$operacao,'url'=>$url, 'expedicao'=>$idExpedicao ,'placa'=>$placa,'carga'=>$carga, 'parcialmenteFinalizado' => true);

                        }
                        if ($idStatus == Expedicao::STATUS_FINALIZADO) {
                            throw new \Exception("Expedição Finalizada");
                        }
                    case EtiquetaSeparacao::STATUS_ETIQUETA_GERADA:
                        $idExpedicao = $etiquetaSeparacao->getPedido()->getCarga()->getExpedicao()->getId();

                        $idModeloSeparacao = $this->getSystemParameterValue('MODELO_SEPARACAO_PADRAO');
                        $modeloSeparacao = $this->getEntityManager()->getRepository("wms:Expedicao\ModeloSeparacao")->find($idModeloSeparacao);
                        if ($modeloSeparacao == null) throw new \Exception("Modelo de Separação não encontrado");
                        $embalagem = $etiquetaSeparacao->getProdutoEmbalagem();

                        $embalado = false;
                        if ($embalagem != null) {
                            if ($modeloSeparacao->getTipoDefaultEmbalado() == "P") {
                                if ($embalagem->getEmbalado() == "S") {
                                    $embalado = true;
                                }
                            } else {
                                $embalagens = $etiquetaSeparacao->getProduto()->getEmbalagens();
                                foreach ($embalagens as $emb){
                                    if ($emb->getIsPadrao() == "S") {
                                        if ($embalagem->getQuantidade() < $emb->getQuantidade()) {
                                            $embalado = true;
                                        }
                                        break;
                                    }
                                }

                            }
                        }

                        if ($embalado == true) {

                            if ($modeloSeparacao->getTipoQuebraVolume() == "C") {
                                $idCliente     = $etiquetaSeparacao->getPedido()->getPessoa()->getCodClienteExterno();
                                $idTipoVolume = $idCliente;
                            } else {
                                $idCarga       = $etiquetaSeparacao->getPedido()->getCarga()->getCodCargaExterno();
                                $idTipoVolume = $idCarga;
                            }

                            $operacao = "Conferencia de Embalados";
                            $url = "/mobile/volume-patrimonio/ler-codigo-barra-volume/idExpedicao/$idExpedicao/idTipoVolume/$idTipoVolume";
                            return array('operacao'=>$operacao,'url'=>$url, 'expedicao'=>$idExpedicao ,'carga'=>$carga, 'parcialmenteFinalizado'=>false);
                        } else {
                            $operacao = "Conferencia de Etiquetas de Separação";
                            $url = "/mobile/expedicao/ler-codigo-barras/idExpedicao/$idExpedicao/tipo-conferencia/naoembalado";
                        }
                        break;
                    case EtiquetaSeparacao::STATUS_EXPEDIDO_TRANSBORDO:
                        $expedicaoEn = $etiquetaSeparacao->getPedido()->getCarga()->getExpedicao();

                        if ($expedicaoEn->getStatus()->getId() == Expedicao::STATUS_FINALIZADO) {
                            throw new \Exception("Expedição já finalizada");
                        } else {
                            $idExpedicao = $etiquetaSeparacao->getPedido()->getCarga()->getExpedicao()->getId();
                            $placa       = $etiquetaSeparacao->getPedido()->getCarga()->getPlacaCarga();
                            $carga       = $etiquetaSeparacao->getPedido()->getCarga()->getCodCargaExterno();
                            $operacao    = "Expedição de Transbordo";
                            $url =       "/mobile/expedicao/ler-codigo-barras/idExpedicao/$idExpedicao/placa/$placa";

                            return array('operacao'=>$operacao,'url'=>$url, 'expedicao'=>$idExpedicao ,'placa'=>$placa,'carga'=>$carga, 'parcialmenteFinalizado' => true);
                        }
                        break;
                    case EtiquetaSeparacao::STATUS_RECEBIDO_TRANSBORDO:
                        $idExpedicao = $etiquetaSeparacao->getPedido()->getCarga()->getExpedicao()->getId();
                        $placa       = $etiquetaSeparacao->getPedido()->getCarga()->getPlacaCarga();
                        $carga = $etiquetaSeparacao->getPedido()->getCarga()->getCodCargaExterno();
                        $operacao = "Expedição de Transbordo";
                        $url = "/mobile/expedicao/ler-codigo-barras/idExpedicao/$idExpedicao/placa/$placa";

                        return array('operacao'=>$operacao,'url'=>$url, 'expedicao'=>$idExpedicao ,'placa'=>$placa,'carga'=>$carga, 'parcialmenteFinalizado' => true);
                        break;
                }
            }


            return array('operacao'=>$operacao,'url'=>$url, 'expedicao'=>$idExpedicao ,'parcialmenteFinalizado'=>false);
        }
        if ($tipoEtiqueta == EtiquetaSeparacao::PREFIXO_ETIQUETA_MAE) {
            //ETIQUETA MÃE
            $codBarras = $LeituraColetor->retiraDigitoIdentificador($codBarras);
            $etiquetaMae = $this->getEntityManager()->getRepository("wms:Expedicao\EtiquetaMae")->find($codBarras);
            if ($etiquetaMae == null) throw new \Exception("Nenhuma etiqueta mãe encontrada com este código de barras $codBarras");

            $etiquetas = $this->getEntityManager()->getRepository("wms:Expedicao\EtiquetaSeparacao")->findBy(array('codEtiquetaMae'=>$codBarras));
            $idModeloSeparacao = $this->getSystemParameterValue('MODELO_SEPARACAO_PADRAO');

            $modeloSeparacao = $this->getEntityManager()->getRepository("wms:Expedicao\ModeloSeparacao")->find($idModeloSeparacao);
            if ($modeloSeparacao == null) throw new \Exception("Modelo de Separação não encontrado");

            $embalado = false;
            $idCliente   = 0;
            $idCarga     = 0;
            $idExpedicao = 0;
            foreach ($etiquetas as $etiqueta){
                $idCliente     = $etiqueta->getPedido()->getPessoa()->getCodClienteExterno();
                $idCarga       = $etiqueta->getPedido()->getCarga()->getId();
                $idExpedicao   = $etiqueta->getPedido()->getCarga()->getExpedicao()->getId();

                $embalagem = $etiqueta->getProdutoEmbalagem();
                $embalado = false;
                if ($embalagem != null) {
                    if ($modeloSeparacao->getTipoDefaultEmbalado() == "P") {
                        if ($embalagem->getEmbalado() == "S") {
                            $embalado = true;
                        }
                    } else {
                        $embalagens = $etiqueta->getProduto()->getEmbalagens();
                        foreach ($embalagens as $emb){
                            if ($emb->getIsPadrao() == "S") {
                                if ($embalagem->getQuantidade() < $emb->getQuantidade()) {
                                    $embalado = true;
                                }
                                break;
                            }
                        }
                    }
                }
                if ($embalado == true) break;
            }

            if ($embalado == true) {
                if ($modeloSeparacao->getTipoQuebraVolume() == "C") {
                    $idTipoVolume = $idCliente;
                } else {
                    $idTipoVolume = $idCarga;
                }
                $operacao = "Conferencia de Embalados";
                $url = "/mobile/volume-patrimonio/ler-codigo-barra-volume/idExpedicao/$idExpedicao/idTipoVolume/$idTipoVolume";
            } else {
                $operacao = "Conferencia de Etiquetas de Separação";
                $url = "/mobile/expedicao/ler-codigo-barras/idExpedicao/$idExpedicao/tipo-conferencia/naoembalado";
            }
            return array('operacao'=>$operacao,'url'=>$url, 'expedicao'=>$idExpedicao);
        }
        if ($tipoEtiqueta == EtiquetaSeparacao::PREFIXO_MAPA_SEPARACAO) {
            //MAPA DE SEPARAÇÃO
            $codBarras = $LeituraColetor->retiraDigitoIdentificador($codBarras);
            $mapaSeparacao = $this->getEntityManager()->getRepository('wms:Expedicao\MapaSeparacao')->find($codBarras);
            if ($mapaSeparacao == NULL) throw new \Exception("Nenhum mapa de separação encontrado com o códgo ". $codBarras);
            $idExpedicao = $mapaSeparacao->getExpedicao()->getId();
            $operacao = "Conferencia do Mapa cód. $codBarras";
            $url = "/mobile/expedicao/ler-produto-mapa/idMapa/$codBarras/idExpedicao/$idExpedicao";
            return array('operacao'=>$operacao,'url'=>$url, 'expedicao'=>$idExpedicao);
        }
        if ($tipoEtiqueta == EtiquetaSeparacao::PREFIXO_ETIQUETA_VOLUME) {
            //ETIQUETA DE VOLUME
            $volumeRepo  = $this->getEntityManager()->getRepository("wms:Expedicao\VolumePatrimonio");
            $volumeEn = $volumeRepo->find($codBarras);
            if ($volumeEn == null) throw new \Exception("Nenhum volume patrimonio encontrado com o códgo ". $codBarras);
            $idExpedicao = $volumeRepo->getExpedicaoByVolume($codBarras,'arr');
            if (is_array($idExpedicao)) {
                $idExpedicao = $idExpedicao[0]['expedicao'];
            } else {
                throw new \Exception("Nenhuma expedição com o volume ". $codBarras);
            }

            $operacao = "Conferencia dos volumes no box";
            $url = "/mobile/volume-patrimonio/ler-codigo-barra-volume/idExpedicao/$idExpedicao/box/1";
            return array('operacao'=>$operacao,'url'=>$url, 'expedicao'=>$idExpedicao);
        }

        throw new \Exception("Código de barras invalido");

    }

    public function qtdTotalVolumePatrimonio($idExpedicao)
    {
        $sql = $this->_em->createQueryBuilder()
            ->select('COUNT(DISTINCT evp.volumePatrimonio) as qtdTotal')
            ->from('wms:Expedicao\ExpedicaoVolumePatrimonio', 'evp')
            ->where("evp.expedicao = $idExpedicao");

        return $sql->getQuery()->getResult();
    }

    public function qtdConferidaVolumePatrimonio($idExpedicao)
    {
        $sql = $this->_em->createQueryBuilder()
            ->select('COUNT(DISTINCT evp.volumePatrimonio) as qtdConferida')
            ->from('wms:Expedicao\ExpedicaoVolumePatrimonio', 'evp')
            ->where("evp.expedicao = $idExpedicao AND evp.dataConferencia is not null");

        return $sql->getQuery()->getResult();

    }


    public function getPedidosByParams($parametros, $idDepositoLogado = null){

        $where = "";
        $orderBy = " ORDER BY P.COD_PEDIDO";
        if (isset($idDepositoLogado)) {
            $where .= ' AND P.CENTRAL_ENTREGA = ' . $idDepositoLogado;
        }

        if (is_array($parametros['centrais'])) {
            $central = implode(',',$parametros['centrais']);
            $where .= " AND ( P.CENTRAL_ENTREGA in(".$central.") OR P.PONTO_TRANSBORDO in(".$central.") )";
        }

        if (isset($parametros['placa']) && !empty($parametros['placa'])) {
            $where.= " AND E.DSC_PLACA_EXPEDICAO = '".$parametros['placa']."'";
        }

        if (isset($parametros['dataInicial1']) && (!empty($parametros['dataInicial1']))){
            $where.= " AND E.DTH_INICIO >= TO_DATE('".$parametros['dataInicial1']." 00:00', 'DD-MM-YYYY HH24:MI')";
        }

        if (isset($parametros['dataInicial2']) && (!empty($parametros['dataInicial2']))){
            $where.= " AND E.DTH_INICIO <= TO_DATE('".$parametros['dataInicial2']." 23:59', 'DD-MM-YYYY HH24:MI')";
        }

        if (isset($parametros['dataFinal1']) && (!empty($parametros['dataFinal1']))) {
            $where.= " AND E.DTH_FINALIZACAO >= TO_DATE('".$parametros['dataFinal1']." 00:00', 'DD-MM-YYYY HH24:MI')";
        }

        if (isset($parametros['dataFinal2']) && (!empty($parametros['dataFinal2']))) {
            $where.= " AND E.DTH_FINALIZACAO <= TO_DATE('".$parametros['dataFinal2']." 23:59', 'DD-MM-YYYY HH24:MI')";
        }

        if (isset($parametros['status']) && (!empty($parametros['status']))) {
            $where.= " AND S.COD_SIGLA = ".$parametros['status']."";
        }
        if (isset($parametros['idExpedicao']) && !empty($parametros['idExpedicao'])) {
            $where = " AND E.COD_EXPEDICAO = ".$parametros['idExpedicao']."";
        }

        if (isset($parametros['pedido']) && !empty($parametros['pedido'])) {
            $where = " AND P.COD_PEDIDO = ".$parametros['pedido']."";
        }

        if (isset($parametros['codCargaExterno']) && !empty($parametros['codCargaExterno'])) {
            $where = " AND CA.COD_CARGA_EXTERNO = ".$parametros['codCargaExterno']."";
        }

        $SQL = "
        SELECT P.COD_PEDIDO,
               CLI.COD_CLIENTE_EXTERNO as COD_CLIENTE,
               PES.NOM_PESSOA as CLIENTE,
               E.COD_EXPEDICAO,
               C.COD_CARGA_EXTERNO,
               E.DSC_PLACA_EXPEDICAO,
               S.DSC_SIGLA,
               NVL(ETQ.QTD,0) as ETIQUETAS_GERADAS,
               PROD.QTD as QTD_PRODUTOS
          FROM PEDIDO P
          LEFT JOIN PESSOA PES ON P.COD_PESSOA = PES.COD_PESSOA
          LEFT JOIN CLIENTE CLI ON CLI.COD_PESSOA = PES.COD_PESSOA
          LEFT JOIN CARGA C ON C.COD_CARGA = P.COD_CARGA
          LEFT JOIN EXPEDICAO E ON E.COD_EXPEDICAO = C.COD_EXPEDICAO
          LEFT JOIN SIGLA S ON S.COD_SIGLA = E.COD_STATUS
          LEFT JOIN (SELECT COUNT(*) as QTD, COD_PEDIDO FROM PEDIDO_PRODUTO GROUP BY COD_PEDIDO) PROD ON PROD.COD_PEDIDO = P.COD_PEDIDO
          LEFT JOIN (SELECT COUNT(COD_ETIQUETA_SEPARACAO) as QTD, COD_PEDIDO FROM ETIQUETA_SEPARACAO GROUP BY COD_PEDIDO) ETQ ON ETQ.COD_PEDIDO = P.COD_PEDIDO
          WHERE 1 = 1";

        $result=$this->getEntityManager()->getConnection()->query($SQL . $where . $orderBy)->fetchAll(\PDO::FETCH_ASSOC);
        return $result;

    }


    public function getPedidosParaCorteByParams($params){
        $SQL = "
        SELECT DISTINCT
               P.COD_PEDIDO,
               CLI.COD_CLIENTE_EXTERNO as CLIENTE,
               PES.NOM_PESSOA,
               PE.DSC_ENDERECO,
               PE.NOM_BAIRRO,
               PE.NOM_LOCALIDADE,
               UF.COD_REFERENCIA_SIGLA as UF
          FROM PEDIDO P
          LEFT JOIN CARGA C ON C.COD_CARGA = P.COD_CARGA
          LEFT JOIN CLIENTE CLI ON P.COD_PESSOA = CLI.COD_PESSOA
          LEFT JOIN PESSOA PES ON PES.COD_PESSOA = P.COD_PESSOA
          LEFT JOIN PEDIDO_ENDERECO PE ON PE.COD_PEDIDO = P.COD_PEDIDO
          LEFT JOIN SIGLA UF ON UF.COD_SIGLA = PE.COD_UF
         WHERE 1 = 1";

        if (isset($params['idExpedicao']) && ($params['idExpedicao']!= null)){
            $idExpedicao = $params['idExpedicao'];
            $SQL .= " AND C.COD_EXPEDICAO = $idExpedicao ";
        }

        if (isset($params['clientes']) && ($params['clientes']!= null)){
            $clientes = implode(',',$params['clientes']);
            $SQL .= " AND CLI.COD_CLIENTE_EXTERNO IN ($clientes) ";
        }

        if (isset($params['pedidos']) && ($params['pedidos']!= null)){
            $pedidos = implode(',',$params['pedidos']);
            $SQL .= " AND P.COD_PEDIDO IN ($pedidos) ";
        }

        if (isset($params['idMapa']) && ($params['idMapa']!= null)){
            $idMapa = $params['idMapa'];
            $SQL .= " AND P.COD_PEDIDO IN ( SELECT PP.COD_PEDIDO FROM MAPA_SEPARACAO_PRODUTO MSP
                                              LEFT JOIN PEDIDO_PRODUTO PP ON PP.COD_PEDIDO_PRODUTO = MSP.COD_PEDIDO_PRODUTO
                                             WHERE MSP.COD_MAPA_SEPARACAO = $idMapa) ";
        }

        $SQLWhereProdutos = "";
        if (isset($params['idProduto']) && ($params['idProduto']!= null)){
            $idProduto = $params['idProduto'];
            $SQLWhereProdutos .= " AND PP.COD_PRODUTO = '$idProduto' ";
        }
        if (isset($params['grade']) && ($params['grade']!= null)){
            $grade = $params['grade'];
            $SQLWhereProdutos .= " AND PP.DSC_GRADE = '$grade' ";
        }

        if (isset($idProduto) OR (isset($grade))) {
            $SQL .= " AND P.COD_PEDIDO IN ( SELECT COD_PEDIDO FROM PEDIDO_PRODUTO PP
                                             WHERE 1 = 1 $SQLWhereProdutos )";
        }

        $SQL .= " ORDER BY PES.NOM_PESSOA DESC, P.COD_PEDIDO";
        $result = $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);
        return $result;
    }

    public function getProdutosParaCorteByParams($params) {
        $idPedido = $params['idPedido'];
        $SQL = "
        SELECT DISTINCT PP.COD_PRODUTO,
               PP.DSC_GRADE,
               P.DSC_PRODUTO,
               PP.QUANTIDADE as QTD_PEDIDO,
               PP.QTD_ATENDIDA,
               PP.QTD_CORTADA
          FROM PEDIDO_PRODUTO PP
          LEFT JOIN PRODUTO P ON P.COD_PRODUTO = PP.COD_PRODUTO AND P.DSC_GRADE = PP.DSC_GRADE
          LEFT JOIN MAPA_SEPARACAO_PRODUTO MSP ON MSP.COD_PEDIDO_PRODUTO = PP.COD_PEDIDO_PRODUTO
          WHERE COD_PEDIDO = $idPedido";

        if ($params['pedidoCompleto'] == false) {
            if (isset($params['idProduto']) && ($params['idProduto']!= null)){
                $idProduto = $params['idProduto'];
                $SQL .= " AND PP.COD_PRODUTO = '$idProduto' ";
            }
            if (isset($params['grade']) && ($params['grade']!= null)){
                $grade = $params['grade'];
                $SQL .= " AND PP.DSC_GRADE = '$grade' ";
            }
            if (isset($params['idMapa']) && ($params['idMapa']!= null)){
                $idMapa = $params['idMapa'];
                $SQL .= " AND MSP.COD_MAPA_SEPARACAO = $idMapa ";
            }
        }

        $result = $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);
        return $result;
    }

    public function executaCortePedido($cortes, $motivo) {
        //exemplo: $qtdCorte['codPedido']['codProduto']['grade'];
        foreach ($cortes as $codPedido => $produtos) {
            foreach ($produtos as $codProduto => $grades) {
                foreach ($grades as $grade => $quantidade) {
                    if (!($quantidade > 0)) continue;
                    $this->cortaPedido($codPedido, $codProduto, $grade, $quantidade, $motivo);
                }
            }
        }
    }

    private function cortaPedido($codPedido, $codProduto, $grade, $qtdCortar, $motivo){

        $entidadePedidoProduto = $this->getEntityManager()->getRepository('wms:Expedicao\PedidoProduto')->findOneBy(array('codPedido'=>$codPedido,
            'codProduto'=>$codProduto,
            'grade'=>$grade));
        $entidadeMapaProduto = $this->getEntityManager()->getRepository('wms:Expedicao\MapaSeparacaoProduto')->findBy(array('codPedidoProduto'=>$entidadePedidoProduto->getId(),
            'codProduto'=>$codProduto,
            'dscGrade'=>$grade));
        $entidadeReservasEstoqueExpedicao = $this->getEntityManager()->getRepository('wms:Ressuprimento\ReservaEstoqueExpedicao')->findBy(array('pedido'=>$codPedido));
        $repositoryReservaEstoqueProduto = $this->getEntityManager()->getRepository('wms:Ressuprimento\ReservaEstoqueProduto');
        $repositoryReservaEstoque = $this->getEntityManager()->getRepository('wms:Ressuprimento\ReservaEstoque');
        
        $qtdCortada  = $entidadePedidoProduto->getQtdCortada();
        $qtdPedido   = $entidadePedidoProduto->getQuantidade();

        //TRAVA PARA GARANTIR QUE NÃO CORTE QUANTIDADE MAIOR QUE TEM NO PEDIDO
        if (($qtdCortar + $qtdCortada) > $qtdPedido) {
            $qtdCortar = ($qtdPedido - $qtdCortada);
        }

        foreach ($entidadeReservasEstoqueExpedicao as $reservaEstoqueExpedicao) {
//            if ($reservaEstoqueExpedicao->getExpedicao()->getStatus()->getId() == Expedicao::STATUS_FINALIZADO)
//                throw new \Exception('Não é possível cortar pedido/produto com a expedição já finalizada!');

            $entityReservaEstoqueProduto = $repositoryReservaEstoqueProduto->findBy(array('reservaEstoque' => $reservaEstoqueExpedicao->getReservaEstoque()));
            $entityReservaEstoque = $repositoryReservaEstoque->find($reservaEstoqueExpedicao->getReservaEstoque()->getId());
            $entityReservaEstoque->setAtendida('C');
            $entityReservaEstoque->setDataAtendimento(new \DateTime());
            $this->getEntityManager()->persist($entityReservaEstoque);
            foreach ($entityReservaEstoqueProduto as $reservaEstoqueProduto) {
                $qtdReservada = $reservaEstoqueProduto->getQtd();
                if ($qtdCortar + $qtdReservada == 0) {
                    $this->getEntityManager()->remove($reservaEstoqueProduto);
                } else {
                    $reservaEstoqueProduto->setQtd($qtdReservada + $qtdCortar);
                    $this->getEntityManager()->persist($reservaEstoqueProduto);
                }
            }
        }

        $entidadePedidoProduto->setQtdCortada($entidadePedidoProduto->getQtdCortada() + $qtdCortar);
        $this->getEntityManager()->persist($entidadePedidoProduto);

        $qtdMapa = 0;

        foreach ($entidadeMapaProduto as $mapa) {
            $qtdMapa = $qtdMapa + ($mapa->getQtdEmbalagem() * $mapa->getQtdSeparar());
            $qtdCortadoMapa = $mapa->getQtdCortado();
            $qtdCortarMapa = $qtdCortar;
            if ($qtdCortarMapa > ($qtdMapa - $qtdCortadoMapa)) {
                $qtdCortarMapa = $qtdMapa - $qtdCortadoMapa;
            }
            $mapa->setQtdCortado($qtdCortarMapa);
            $this->getEntityManager()->persist($mapa);
            $qtdCortar = $qtdCortar - $qtdCortarMapa;
        }

        $this->getEntityManager()->flush();
    }

    public function getProdutosExpedicaoCorte ($idExpedicao){
        $SQL = "SELECT PP.COD_PRODUTO,
                       PP.DSC_GRADE,
                       PROD.DSC_PRODUTO,
                       SUM(PP.QUANTIDADE) as QTD,
                       SUM(PP.QTD_CORTADA) as QTD_CORTADA
                  FROM PEDIDO_PRODUTO PP
                  LEFT JOIN PEDIDO P ON P.COD_PEDIDO = PP.COD_PEDIDO
                  LEFT JOIN CARGA C ON C.COD_CARGA  = P.COD_CARGA
                  LEFT JOIN PRODUTO PROD ON PROD.COD_PRODUTO = PP.COD_PRODUTO AND PROD.DSC_GRADE = PP.DSC_GRADE
                 WHERE C.COD_EXPEDICAO = $idExpedicao
                 GROUP BY PP.COD_PRODUTO, PP.DSC_GRADE, PROD.DSC_PRODUTO
                 ORDER BY COD_PRODUTO, DSC_GRADE";
        $result = $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);
        return $result;
    }

}