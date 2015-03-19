<?php

namespace Wms\Domain\Entity;

use Core\Grid\Exception;
use Doctrine\ORM\EntityRepository,
    Wms\Domain\Entity\Expedicao as ExpedicaoEntity,
    Wms\Domain\Entity\Atividade as AtividadeEntity,
    Wms\Domain\Entity\Expedicao\EtiquetaSeparacao as EtiquetaSeparacao,
    Wms\Domain\Entity\OrdemServico as OrdemServicoEntity,
    Wms\Domain\Entity\Expedicao\Andamento;


class ExpedicaoRepository extends EntityRepository
{

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
                         SUM (PP.QUANTIDADE) as QTD
                    FROM PEDIDO P
                    LEFT JOIN PEDIDO_PRODUTO PP ON PP.COD_PEDIDO = P.COD_PEDIDO
                    LEFT JOIN CARGA          C ON C.COD_CARGA = P.COD_CARGA
                    LEFT JOIN EXPEDICAO      E ON E.COD_EXPEDICAO = C.COD_EXPEDICAO
                    WHERE P.COD_PEDIDO NOT IN (SELECT COD_PEDIDO FROM ONDA_RESSUPRIMENTO_PEDIDO)
                          AND E.COD_EXPEDICAO IN (".$expedicoes.")
                          AND P.CENTRAL_ENTREGA = $filialExterno
                    GROUP BY PP.COD_PRODUTO, PP.DSC_GRADE";
        $result = $this->getEntityManager()->getConnection()->query($Query)-> fetchAll(\PDO::FETCH_ASSOC);
        return $result;
    }

    public function getProdutosSemOndaByExpedicao($expedicoes, $filialExterno)
    {
        $Query = "SELECT PP.COD_PRODUTO,
                         PP.DSC_GRADE,
                         SUM (PP.QUANTIDADE) as QTD,
                         E.COD_EXPEDICAO
                    FROM PEDIDO P
                    LEFT JOIN PEDIDO_PRODUTO PP ON PP.COD_PEDIDO = P.COD_PEDIDO
                    LEFT JOIN CARGA          C ON C.COD_CARGA = P.COD_CARGA
                    LEFT JOIN EXPEDICAO      E ON E.COD_EXPEDICAO = C.COD_EXPEDICAO
                    LEFT JOIN PRODUTO        P ON P.COD_PRODUTO = PP.COD_PRODUTO AND P.DSC_GRADE = PP.DSC_GRADE
                    WHERE P.COD_PEDIDO NOT IN (SELECT COD_PEDIDO FROM ONDA_RESSUPRIMENTO_PEDIDO)
                          AND E.COD_EXPEDICAO IN (".$expedicoes.")
                          AND P.CENTRAL_ENTREGA = $filialExterno
                    GROUP BY PP.COD_PRODUTO, PP.DSC_GRADE,E.COD_EXPEDICAO";
        $result = $this->getEntityManager()->getConnection()->query($Query)-> fetchAll(\PDO::FETCH_ASSOC);
        return $result;
    }

    public function getPedidoProdutoSemOnda($expedicoes,$filialExterno)
    {
        $Query = "SELECT PP.COD_PRODUTO,
                         PP.DSC_GRADE,
                         PP.QUANTIDADE as QTD,
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

            /** @var \Wms\Domain\Entity\Ressuprimento\OndaRessuprimentoRepository $ondaRepo */
            $ondaRepo = $this->getEntityManager()->getRepository("wms:Ressuprimento\OndaRessuprimento");

                $result = $this->validaPickingProdutosByExpedicao( $produtosRessuprir);
                if ($result['resultado'] != true) return $result;
                $ondaEn = $ondaRepo->geraNovaOnda();
                $ondaRepo->gerarReservaSaidaPicking($produtosReservaSaida);
                $ondaRepo->relacionaOndaPedidosExpedicao($pedidosProdutosRessuprir, $ondaEn);
                    $this->getEntityManager()->flush();
                $ondaRepo->geraOsRessuprimento($produtosRessuprir,$ondaEn);
                $this->getEntityManager()->flush();
                $ondaRepo->sequenciaOndasOs();
                    $this->getEntityManager()->commit();

            $resultado = array();
            $resultado['observacao'] = "Ondas Geradas com sucesso";
            $resultado['resultado'] = true;

            return $resultado;
        } catch(\Exception $e) {
            $this->getEntityManager()->rollback();
            throw new \Exception($e->getMessage());
        }

    }


    public function findPedidosProdutosSemEtiquetaById($idExpedicao, $central, $cargas = null) {

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
                        WHERE c.expedicao = $idExpedicao
                          $whereCargas
                          AND ped.centralEntrega = '$central'
                          AND ped.id NOT IN (
                             SELECT pp2.codPedido
                               FROM wms:Expedicao\EtiquetaSeparacao ep
                              INNER JOIN wms:Expedicao\PedidoProduto pp2
                               WITH pp2.pedido = ep.pedido
                              INNER JOIN ep.produto p2
                              INNER JOIN ep.pedido ped2
                        )";

        switch ($sequencia) {
            case 2:
                $order = " ORDER BY c.placaExpedicao,
                                    ls.descricao,
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
    public function countPedidosNaoCancelados($idExpedicao) {
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

    public function finalizarExpedicao ($idExpedicao, $central, $validaStatusEtiqueta = true)
    {
        /** @var \Wms\Domain\Entity\Expedicao\EtiquetaSeparacaoRepository $EtiquetaRepo */
        $EtiquetaRepo = $this->_em->getRepository('wms:Expedicao\EtiquetaSeparacao');
        $expedicaoEn  = $this->findOneBy(array('id'=>$idExpedicao));

        $pedidoProdutoSemEtiquetas = $this->findProdutosSemEtiquetasById($idExpedicao);
        if (count($pedidoProdutoSemEtiquetas) > 0) {
            return 'Existem produtos sem etiquetas impressas';
        }

        if ($this->getExistsPendenciaCorte($expedicaoEn,$central)) {
            return 'Existem etiquetas pendentes de corte nesta expedição';
        }
        ini_set('max_execution_time', 3000);
        Try {
            $this->getEntityManager()->beginTransaction();
            if ($validaStatusEtiqueta == true) {
                $result = $this->validaStatusEtiquetas($expedicaoEn,$central);
                if (is_string($result)) {
                    return $result;
                }
                $result = $this->validaVolumesPatrimonio($idExpedicao);
                if (is_string($result)) {
                    return $result;
                }
            } else {
                if ($this->validaCargaFechada($idExpedicao) == false) return 'Existem cargas com pendencias de fechamento';
                $EtiquetaRepo->finalizaEtiquetasSemConferencia($idExpedicao, $central);
            }

            $result = $this->finalizar($idExpedicao,$central);
            $this->getEntityManager()->commit();
            return $result;
        } catch(\Exception $e) {
            $this->getEntityManager()->rollback();
            return $e->getMessage();
        }
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

        foreach($cargas as $carga) {
            if ($carga->getDataFechamento() == null) {
                return false;
            }
        }
        return true;
    }

        /**
     * @param array $cargas
     * @return bool
     */
    private function finalizar($idExpedicao, $centralEntrega)
    {
        if ($this->validaCargaFechada($idExpedicao) == false) return 'Existem cargas com pendencias de fechamento';

        /** @var \Wms\Domain\Entity\Expedicao\PedidoRepository $pedidoRepo */
        $pedidoRepo = $this->_em->getRepository('wms:Expedicao\Pedido');
        /** @var \Wms\Domain\Entity\Expedicao\AndamentoRepository $andamentoRepo */
        $andamentoRepo  = $this->_em->getRepository('wms:Expedicao\Andamento');
        /** @var \Wms\Domain\Entity\Ressuprimento\ReservaEstoqueRepository $reservaEstoqueRepo */
        $reservaEstoqueRepo = $this->getEntityManager()->getRepository("wms:Ressuprimento\ReservaEstoque");
        $estoqueRepo = $this->getEntityManager()->getRepository("wms:Enderecamento\Estoque");
        $usuarioRepo = $this->getEntityManager()->getRepository("wms:Usuario");
        $usuarioRepo = $this->getEntityManager()->getRepository("wms:Usuario");

        /** @var \Wms\Domain\Entity\Expedicao $expedicaoEntity */
        $expedicaoEntity = $this->find($idExpedicao);
        $expedicaoEntity->setDataFinalizacao(new \DateTime());

        $this->finalizeOSByExpedicao($expedicaoEntity->getId());
        $pedidoRepo->finalizaPedidosByCentral($centralEntrega,$expedicaoEntity->getId());

        $pedidosNaoConferidos = $pedidoRepo->findPedidosNaoConferidos($expedicaoEntity->getId());
        if ($pedidosNaoConferidos == null) {
            $novoStatus = Expedicao::STATUS_FINALIZADO;
            $andamentoRepo->save("Expedição Finalizada com Sucesso", $expedicaoEntity->getId());
        } else {
            $novoStatus = Expedicao::STATUS_PARCIALMENTE_FINALIZADO;
            $andamentoRepo->save("Expedição Parcialmente Finalizada com Sucesso", $expedicaoEntity->getId());
        }

        $this->liberarVolumePatrimonioByExpedicao($expedicaoEntity->getId());
        $this->alteraStatus($expedicaoEntity,$novoStatus);

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
        $this->getEntityManager()->flush();
        return true;
    }

    public function liberarVolumePatrimonioByExpedicao($idExpedicao){
        $volumes = $this->getVolumesPatrimonioByExpedicao($idExpedicao);

        foreach ($volumes as $key => $volume){
            $volumeRepo = $this->getEntityManager()->getRepository('wms:Expedicao\VolumePatrimonio');
            $volumeEn = $volumeRepo->findOneBy(array('id'=> $key));

            $volumeEn->setOcupado('N');
            $this->getEntityManager()->persist($volumeEn);
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
            $Query = $Query . " AND E.COD_CARGA_EXTERNO = " . $parametros['codCargaExterno'];
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

        $result = $this->getEntityManager()->getConnection()->query($Query)-> fetchAll(\PDO::FETCH_ASSOC);

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
            throw new \Exception();
        }

        return $enExpedicao;
    }

    /**
     * @param $idExpedicao
     * @return array
     */
    public function getItinerarios($idExpedicao)
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
    public function buscar($parametros)
    {
        $where="";
        $whereSubQuery="";
        $and="";
        $andSub="";
        $cond="";
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
                       to_char(E.DTH_INICIO,\'dd/mm/yyyy hh:mi:ss\') AS "dataInicio",
                       to_char(E.DTH_FINALIZACAO,\'dd/mm/yyyy hh:mi:ss\') AS "dataFinalizacao",
                       C.CARGAS AS "carga",
                       S.DSC_SIGLA AS "status",
                       P.QTD AS "prodSemEtiqueta",
                       PESO.NUM_PESO as "peso",
                       PESO.NUM_CUBAGEM as "cubagem",
                       I.ITINERARIOS AS "itinerario"
                  FROM EXPEDICAO E
                  LEFT JOIN SIGLA S ON S.COD_SIGLA = E.COD_STATUS
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
                  LEFT JOIN (SELECT COUNT(DISTINCT PP.COD_PRODUTO||PP.DSC_GRADE) as QTD,
                                    C.COD_EXPEDICAO
                               FROM PEDIDO_PRODUTO PP
                              INNER JOIN PEDIDO P ON P.COD_PEDIDO = PP.COD_PEDIDO
                              INNER JOIN CARGA C ON C.COD_CARGA = P.COD_CARGA
                               LEFT JOIN ETIQUETA_SEPARACAO ES ON ES.COD_PEDIDO = PP.COD_PEDIDO AND ES.COD_PRODUTO = PP.COD_PRODUTO AND ES.DSC_GRADE = PP.DSC_GRADE
                               LEFT JOIN EXPEDICAO EX ON EX.COD_EXPEDICAO=C.COD_EXPEDICAO
                              WHERE ((ES.COD_ETIQUETA_SEPARACAO IS NULL OR ES.COD_STATUS = 522) '.$andSub.$whereSubQuery.'  )
                              GROUP BY C.COD_EXPEDICAO) P ON P.COD_EXPEDICAO = E.COD_EXPEDICAO
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
                              GROUP BY C.COD_EXPEDICAO) PESO ON PESO.COD_EXPEDICAO = E.COD_EXPEDICAO
                 WHERE '.$where.'
                 GROUP BY E.COD_EXPEDICAO,
                          E.DSC_PLACA_EXPEDICAO,
                          E.DTH_INICIO,
                          E.DTH_FINALIZACAO,
                          C.CARGAS,
                          S.DSC_SIGLA,
                          P.QTD,
                          PESO.NUM_PESO,
                          PESO.NUM_CUBAGEM,
                          I.ITINERARIOS
                 ORDER BY E.COD_EXPEDICAO DESC
                     ';

        //print "<pre>"; print_r($sql); die();
       $result=$this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);

        return $result;
    }


    public function getExpedicaoIntegradas(){

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

        if (is_array($central)) {
            $central = implode(',',$central);
            $source->andWhere("pedido.centralEntrega in ($central)")
                    ->orWhere("pedido.pontoTransbordo in ($central)");

        } else if ($central) {
            $source->andWhere('pedido.centralEntrega = :central')
                    ->orWhere("pedido.pontoTransbordo = :central");
            $source->setParameter('central', $central);
        }

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
    public function getResumoConferenciaByID ($idExpedicao){
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
            ->addSelect("(
                         SELECT COUNT(es2.id)
                           FROM wms:Expedicao\EtiquetaSeparacao es2
                          LEFT JOIN es2.pedido ped2
                          LEFT JOIN ped2.carga c2
                          WHERE c2.codExpedicao = e.id
                            AND es2.codStatus in ( 526, 531, 532 )
                          GROUP BY c2.codExpedicao
                          ) as qtdConferidas")
            ->where('e.id = :idExpedicao')
            ->setParameter('idExpedicao', $idExpedicao);

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

    public function getRelatorioSaidaProdutos($codProduto, $grade)
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
            ->select("count(es.codExpedicao) as nEmbalados")
            ->from("wms:Expedicao\VEtiquetaSeparacao","es")
            ->innerJoin('wms:Produto', 'p', 'WITH', 'es.codProduto = p.id')
            ->innerJoin('p.embalagens', 'pe')
            ->where('es.codExpedicao = :idExpedicao')
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

    public function getDadosExpedicao ($params) {
        $dataInicial = $params['dataInicial'];
        $dataFim = $params['dataFim'];
        $statusCancelado = \Wms\Domain\Entity\Expedicao::STATUS_CANCELADO;

        $sql = "SELECT E.COD_EXPEDICAO as \"COD.EXPEDICAO\",
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
                       CLIENTE.NOM_PESSOA \"CLIENTE\",
                       CLIENTE.DSC_ENDERECO \"ENDERECO CLIENTE\",
                       CLIENTE.NOM_LOCALIDADE \"CIDADE CLIENTE\",
                       CLIENTE.DSC_SIGLA \"ESTADO CLIENTE\",
                       CLIENTE.NOM_BAIRRO \"NOME BAIRRO\"
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
                LEFT JOIN (SELECT CL.COD_PESSOA,
                                  PE.NOM_PESSOA,
                                  PENDERECO.DSC_ENDERECO,
                                  PENDERECO.NOM_LOCALIDADE,
                                  S.DSC_SIGLA,
                                  PENDERECO.NOM_BAIRRO
                             FROM CLIENTE CL
                            INNER JOIN PESSOA PE ON CL.COD_PESSOA = PE.COD_PESSOA
                            INNER JOIN PESSOA_ENDERECO PENDERECO ON PE.COD_PESSOA = PENDERECO.COD_PESSOA
                            INNER JOIN SIGLA S ON PENDERECO.COD_UF = S.COD_SIGLA) CLIENTE
                  ON P.COD_PESSOA = CLIENTE.COD_PESSOA
               WHERE (E.COD_STATUS <> $statusCancelado)
                 AND ((E.DTH_INICIO >= TO_DATE('$dataInicial 00:00', 'DD-MM-YYYY HH24:MI'))
                 AND (E.DTH_INICIO <= TO_DATE('$dataFim 23:59', 'DD-MM-YYYY HH24:MI')))
                ORDER BY E.DTH_INICIO";

        $resultado = $this->getEntityManager()->getConnection()->query($sql)-> fetchAll(\PDO::FETCH_ASSOC);
        return $resultado;
    }

    public function getCarregamentoByExpedicao($codExpedicao, $codStatus = null)
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

        if (!is_null($codExpedicao)) {
            $source->andWhere("car.codExpedicao = " . $codExpedicao);
        }

        if ($codStatus != NULL){
            $source->andWhere("es.codStatus = $codStatus ");
        }

        return $source->getQuery()->getResult();
    }

    public function getProdutosSemEstoqueByExpedicao($idExpedicao) {

        $sql = "SELECT EXP.COD_PRODUTO,
                       EXP.DSC_GRADE,
                       P.DSC_PRODUTO,
                       EXP.QTD_EXP,
                       NVL(EST.QTD_ESTOQUE,0) as QTD_ESTOQUE,
                       NVL(RES.QTD_RESERVADA,0) as QTD_RESERV,
                       (NVL(EST.QTD_ESTOQUE,0) - NVL(RES.QTD_RESERVADA,0)) as SALDO_FINAL,
                       CASE WHEN (EXP.VOLUME = 0) THEN 'PRODUTO UNITARIO'
                            WHEN (PV.COD_PRODUTO_VOLUME IS NOT NULL) THEN PV.DSC_VOLUME
                      END as VOLUME
                  FROM (SELECT SUM(PP.QUANTIDADE) as QTD_EXP, PP.COD_PRODUTO, PP.DSC_GRADE ,NVL(PV.COD_PRODUTO_VOLUME, '0') as VOLUME
                          FROM EXPEDICAO E
                          LEFT JOIN CARGA C ON C.COD_EXPEDICAO = E.COD_EXPEDICAO
                          LEFT JOIN PEDIDO P ON P.COD_CARGA = C.COD_CARGA
                          LEFT JOIN PEDIDO_PRODUTO PP ON PP.COD_PEDIDO = P.COD_PEDIDO
                          LEFT JOIN PRODUTO_VOLUME PV ON PV.COD_PRODUTO = PP.COD_PRODUTO AND PV.DSC_GRADE = PP.DSC_GRADE
                         WHERE E.COD_EXPEDICAO IN (".$idExpedicao.")
                         GROUP BY  PP.COD_PRODUTO, PP.DSC_GRADE, PV.COD_PRODUTO_VOLUME
                         ) EXP
                LEFT JOIN (SELECT SUM(E.QTD) as QTD_ESTOQUE, COD_PRODUTO, DSC_GRADE, VOLUME
                             FROM (SELECT E.COD_PRODUTO,
                                          E.DSC_GRADE,
                                          E.COD_DEPOSITO_ENDERECO,
                                          NVL(PV.COD_PRODUTO_VOLUME,0) as VOLUME,
                                          CASE WHEN DE.COD_CARACTERISTICA_ENDERECO = 37 THEN NVL(PE.COD_DEPOSITO_ENDERECO, PV.COD_DEPOSITO_ENDERECO)
                                               ELSE E.COD_DEPOSITO_ENDERECO
                                          END AS COD_DEPOSITO_ENDERECO_CORRETO,
                                          E.QTD
                                     FROM ESTOQUE E
                                     LEFT JOIN DEPOSITO_ENDERECO DE ON DE.COD_DEPOSITO_ENDERECO = E.COD_DEPOSITO_ENDERECO
                                     LEFT JOIN PRODUTO_EMBALAGEM PE ON PE.COD_PRODUTO_EMBALAGEM = E.COD_PRODUTO_EMBALAGEM
                                     LEFT JOIN PRODUTO_VOLUME    PV ON PV.COD_PRODUTO_VOLUME = E.COD_PRODUTO_VOLUME) E
                            WHERE E.COD_DEPOSITO_ENDERECO = E.COD_DEPOSITO_ENDERECO_CORRETO
                            GROUP BY E.COD_PRODUTO, E.DSC_GRADE, E.VOLUME) EST
                      ON EST.COD_PRODUTO = EXP.COD_PRODUTO
                     AND EST.DSC_GRADE = EXP.DSC_GRADE
                     AND EST.VOLUME = EXP.VOLUME
                LEFT JOIN (SELECT SUM(REP.QTD_RESERVADA) as QTD_RESERVADA, NVL(COD_PRODUTO_VOLUME, '0') as VOLUME, COD_PRODUTO, DSC_GRADE
                             FROM RESERVA_ESTOQUE RE
                            INNER JOIN RESERVA_ESTOQUE_PRODUTO REP ON RE.COD_RESERVA_ESTOQUE = REP.COD_RESERVA_ESTOQUE
                            WHERE RE.IND_ATENDIDA = 'N'
                            GROUP BY COD_PRODUTO, DSC_GRADE, COD_PRODUTO_VOLUME) RES
                       ON RES.COD_PRODUTO = EXP.COD_PRODUTO
                      AND RES.DSC_GRADE = EXP.DSC_GRADE
                      AND RES.VOLUME = EXP.VOLUME
                  LEFT JOIN PRODUTO P ON P.COD_PRODUTO = EXP.COD_PRODUTO
                                     AND P.DSC_GRADE = EXP.DSC_GRADE
                  LEFT JOIN PRODUTO_VOLUME PV ON PV.COD_PRODUTO_VOLUME = EXP.VOLUME
                  WHERE        (NVL(EST.QTD_ESTOQUE,0) - NVL(RES.QTD_RESERVADA,0)) <0";

        $result=$this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        return $result;

    }

}