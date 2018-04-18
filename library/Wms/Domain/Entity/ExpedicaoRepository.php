<?php

namespace Wms\Domain\Entity;

use Doctrine\ORM\EntityRepository,
    Wms\Domain\Entity\Expedicao,
    Wms\Domain\Entity\Atividade as AtividadeEntity,
    Wms\Domain\Entity\Expedicao\EtiquetaSeparacao as EtiquetaSeparacao,
    Wms\Domain\Entity\OrdemServico as OrdemServicoEntity;
use Wms\Domain\Entity\Deposito\Endereco;
use Wms\Domain\Entity\Produto\Embalagem;
use Wms\Domain\Entity\Produto\EmbalagemRepository;
use Wms\Domain\Entity\Produto\NormaPaletizacao;
use Wms\Domain\Entity\Produto\Volume;
use Wms\Domain\Entity\Produto\VolumeRepository;
use Wms\Domain\Entity\Ressuprimento\ReservaEstoqueExpedicao;
use Wms\Math;

class ExpedicaoRepository extends EntityRepository {

    public function validaPedidosImpressos($idExpedicao) {
        $SQL = "SELECT C.COD_EXPEDICAO
                  FROM PEDIDO P
             LEFT JOIN CARGA C ON C.COD_CARGA = P.COD_CARGA
             LEFT JOIN ETIQUETA_SEPARACAO ES ON ES.COD_PEDIDO = P.COD_PEDIDO
             LEFT JOIN MAPA_SEPARACAO MS ON MS.COD_EXPEDICAO = C.COD_EXPEDICAO
                 WHERE (ES.COD_STATUS = 522 OR P.IND_ETIQUETA_MAPA_GERADO = 'N' OR MS.COD_STATUS = 522)
                   AND P.DTH_CANCELAMENTO IS NULL
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

    public function getProdutosSemOnda($expedicoes, $filialExterno) {
        $Query = "SELECT PP.COD_PRODUTO,
                         PP.DSC_GRADE,
                         SUM (NVL(PP.QUANTIDADE,0)) - SUM(NVL(PP.QTD_CORTADA,0)) as QTD
                    FROM PEDIDO P
                    INNER JOIN PEDIDO_PRODUTO PP ON PP.COD_PEDIDO = P.COD_PEDIDO
                    INNER JOIN CARGA          C ON C.COD_CARGA = P.COD_CARGA
                    INNER JOIN EXPEDICAO      E ON E.COD_EXPEDICAO = C.COD_EXPEDICAO
                    WHERE P.COD_PEDIDO NOT IN (SELECT COD_PEDIDO FROM ONDA_RESSUPRIMENTO_PEDIDO)
                          AND E.COD_EXPEDICAO IN (" . $expedicoes . ")
                          AND P.CENTRAL_ENTREGA = $filialExterno
                          AND (NVL(PP.QUANTIDADE,0) - NVL(PP.QTD_CORTADA,0))>0
                          AND P.DTH_CANCELAMENTO IS NULL
                    GROUP BY PP.COD_PRODUTO, PP.DSC_GRADE";
        $result = $this->getEntityManager()->getConnection()->query($Query)->fetchAll(\PDO::FETCH_ASSOC);
        return $result;
    }

    public function getProdutosSemOndaByExpedicao($expedicoes, $filialExterno) {
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
                          AND E.COD_EXPEDICAO IN (" . $expedicoes . ")
                          AND PED.CENTRAL_ENTREGA = $filialExterno
                          AND (NVL(PP.QUANTIDADE,0) - NVL(PP.QTD_CORTADA,0)) > 0
                          AND PED.DTH_CANCELAMENTO IS NULL
                    GROUP BY PP.COD_PRODUTO, PP.DSC_GRADE, E.COD_EXPEDICAO, PED.COD_PEDIDO";
        $result = $this->getEntityManager()->getConnection()->query($Query)->fetchAll(\PDO::FETCH_ASSOC);
        return $result;
    }

    public function getPedidoProdutoSemOnda($expedicoes, $filialExterno)
    {
        $Query = "SELECT 
                      E.COD_EXPEDICAO,
                      C.COD_CARGA,
                      P.COD_PEDIDO,
                      P.COD_PESSOA AS COD_CLIENTE,
                      PESS.NOM_PESSOA,
                      CL.COD_PRACA,
                      CL.COD_ROTA,
                      PP.COD_PEDIDO_PRODUTO,
                      PP.COD_PRODUTO,
                      PP.DSC_GRADE,
                      (NVL(PP.QUANTIDADE,0) - NVL(PP.QTD_CORTADA,0)) as QTD
                  FROM PEDIDO P
                  INNER JOIN PEDIDO_PRODUTO PP ON PP.COD_PEDIDO = P.COD_PEDIDO
                  INNER JOIN CARGA C ON C.COD_CARGA = P.COD_CARGA
                  INNER JOIN EXPEDICAO E ON E.COD_EXPEDICAO = C.COD_EXPEDICAO
                  INNER JOIN CLIENTE CL ON CL.COD_PESSOA = P.COD_PESSOA
                  INNER JOIN PESSOA PESS ON PESS.COD_PESSOA = P.COD_PESSOA
                  WHERE P.COD_PEDIDO NOT IN (SELECT COD_PEDIDO FROM ONDA_RESSUPRIMENTO_PEDIDO)
                      AND (NVL(PP.QUANTIDADE,0) - NVL(PP.QTD_CORTADA,0)) > 0
                      AND E.COD_EXPEDICAO IN ($expedicoes)
                      AND P.CENTRAL_ENTREGA = $filialExterno
                      AND P.DTH_CANCELAMENTO IS NULL";

        $result = $this->getEntityManager()->getConnection()->query($Query)->fetchAll(\PDO::FETCH_ASSOC);
        return $result;
    }

    private function validaPickingProdutosByExpedicao($produtosRessuprir) {
        /** @var \Wms\Domain\Entity\ProdutoRepository $produtoRepo */
        $produtoRepo = $this->getEntityManager()->getRepository("wms:Produto");

        foreach ($produtosRessuprir as $produto) {
            $codProduto = $produto['COD_PRODUTO'];
            $grade = $produto['DSC_GRADE'];

            $produtoEn = $produtoRepo->findOneBy(array('id' => $codProduto, 'grade' => $grade));
            $volumes = $produtoEn->getVolumes();
            $embalagens = $produtoEn->getEmbalagens();

            if ((count($volumes) == 0) && (count($embalagens) == 0)) {
                $resultado = array();
                $resultado['observacao'] = "Existem produtos sem picking nesta(s) expedição(ões)";
                $resultado['resultado'] = false;
                return $resultado;
            }

            if ($produtoEn->getTipoComercializacao()->getId() == 1) {
                if (count($embalagens) == 0) {
                    throw new \Exception("O Produto cód. $codProduto - $grade não possui nenhuma embalagem cadastrada");
                }
            } else {
                if (count($volumes) == 0) {
                    throw new \Exception("O Produto cód. $codProduto - $grade não possui nenhum volume cadastrado");
                }
            }

            foreach ($volumes as $volume) {
                if ($volume->getCapacidadePicking() == 0) {
                    throw new \Exception("O Produto cód. $codProduto - $grade possui volumes sem capacidade de picking definida");
                }
                if ($volume->getPontoReposicao() >= $volume->getCapacidadePicking()) {
                    throw new \Exception("O Produto cód. $codProduto - $grade possui volumes com o ponto de reposição definido incorretamente");
                }

                if ($volume->getEndereco() == null) {
                    $resultado = array();
                    $resultado['observacao'] = "Existem produtos sem picking nesta(s) expedição(ões)";
                    $resultado['resultado'] = false;
                    return $resultado;
                }
            }
            foreach ($embalagens as $embalagem) {
                if ($embalagem->getCapacidadePicking() == 0) {
                    throw new \Exception("O Produto cód. $codProduto - $grade possui volumes sem capacidade de picking definida");
                }
                if ($embalagem->getPontoReposicao() >= $embalagem->getCapacidadePicking()) {
                    throw new \Exception("O Produto cód. $codProduto - $grade possui volumes com o ponto de reposição definido incorretamente");
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

    public function gerarOnda($strExpedicao) {
        try {
            /** @var \Wms\Domain\Entity\ExpedicaoRepository $expedicaoRepo */
            $expedicaoRepo = $this->getEntityManager()->getRepository("wms:Expedicao");
            $sessao = new \Zend_Session_Namespace('deposito');
            $deposito = $this->_em->getReference('wms:Deposito', $sessao->idDepositoLogado);
            $central = $deposito->getFilial()->getCodExterno();
            if ($deposito->getFilial()->getIndUtilizaRessuprimento() == "N") {
                throw new \Exception("A Filial " . $deposito->getFilial()->getPessoa()->getNomeFantasia() . " não utiliza ressuprimento");
            }

            $countmodeloSeparacao = $this->countModeloSeparacaoByExpedicoes($strExpedicao);
            if (count($countmodeloSeparacao) > 1)
                throw new \Exception('Não é possível gerar onda de ressuprimento para '.count($countmodeloSeparacao).' modelos distintos');

            $modeloId = $this->getSystemParameterValue("MODELO_SEPARACAO_PADRAO");
            /** @var Expedicao\ModeloSeparacao $modeloSeparacaoEn */
            $modeloSeparacaoEn = $this->_em->find("wms:Expedicao\ModeloSeparacao",$modeloId);

            //OBTEM O MODELO DE SEPARACAO VINCULADO A EXPEDICAO
            $codEexpedicoes = explode(',',$strExpedicao);
            foreach ($codEexpedicoes as $codExpedicao) {
                $expedicaoEntity = $expedicaoRepo->find($codExpedicao);
                if (!is_null($expedicaoEntity->getModeloSeparacao())) {
                    $modeloSeparacaoEn = $expedicaoEntity->getModeloSeparacao();
                    break;
                }
            }

            $quebraPulmaoDoca = $modeloSeparacaoEn->getQuebraPulmaDoca();

            $pedidosProdutosRessuprir = $this->getPedidoProdutoSemOnda($strExpedicao, $central);

            if (empty($pedidosProdutosRessuprir)) {
                throw new \Exception("Não foi encontrado produto pendente de onda de ressuprimento ou a quantidade cortada é equivalente a do pedido");
            }

            /** @var \Wms\Domain\Entity\ProdutoRepository $produtoRepo */
            $produtoRepo = $this->getEntityManager()->getRepository("wms:Produto");
            /** @var \Wms\Domain\Entity\Produto\EmbalagemRepository $embalagemRepo */
            $embalagemRepo = $this->getEntityManager()->getRepository("wms:Produto\Embalagem");
            /** @var \Wms\Domain\Entity\Ressuprimento\ReservaEstoqueExpedicaoRepository $reservaEstoqueExpedicaoRepo */
            $reservaEstoqueExpedicaoRepo = $this->getEntityManager()->getRepository("wms:Ressuprimento\ReservaEstoqueExpedicao");
            /** @var \Wms\Domain\Entity\Ressuprimento\OndaRessuprimentoRepository $ondaRepo */
            $ondaRepo = $this->getEntityManager()->getRepository("wms:Ressuprimento\OndaRessuprimento");
            /** @var \Wms\Domain\Entity\Expedicao\PedidoRepository $pedidoRepo */
            $pedidoRepo = $this->getEntityManager()->getRepository("wms:Expedicao\Pedido");
            /** @var \Wms\Domain\Entity\Produto\VolumeRepository $volumeRepo */
            $volumeRepo = $this->getEntityManager()->getRepository("wms:Produto\Volume");
            /** @var \Wms\Domain\Entity\Ressuprimento\ReservaEstoqueRepository $reservaEstoqueRepo */
            $reservaEstoqueRepo = $this->getEntityManager()->getRepository("wms:Ressuprimento\ReservaEstoque");
            /** @var \Wms\Domain\Entity\Deposito\EnderecoRepository $enderecoRepo */
            $enderecoRepo = $this->getEntityManager()->getRepository("wms:Deposito\Endereco");
            $usuarioRepo = $this->getEntityManager()->getRepository("wms:Usuario");
            /** @var \Wms\Domain\Entity\Enderecamento\EstoqueRepository $estoqueRepo */
            $estoqueRepo = $this->getEntityManager()->getRepository("wms:Enderecamento\Estoque");
            $ordemServicoRepo = $this->_em->getRepository('wms:OrdemServico');
            $siglaRepo = $this->getEntityManager()->getRepository("wms:Util\Sigla");
            $reservaEstoqueOndaRepo = $this->getEntityManager()->getRepository("wms:Ressuprimento\ReservaEstoqueOnda");

            $repositorios = array(
                'produtoRepo' => $produtoRepo,
                'embalagemRepo' => $embalagemRepo,
                'reservaEstoqueExpRepo' => $reservaEstoqueExpedicaoRepo,
                'reservaEstoqueOndaRepo' => $reservaEstoqueOndaRepo,
                'reservaEstoqueRepo' => $reservaEstoqueRepo,
                'ondaRepo' => $ondaRepo,
                'pedidoRepo' => $pedidoRepo,
                'volumeRepo' => $volumeRepo,
                'enderecoRepo' => $enderecoRepo,
                'usuarioRepo' => $usuarioRepo,
                'expedicaoRepo' => $expedicaoRepo,
                'estoqueRepo' => $estoqueRepo,
                'osRepo' => $ordemServicoRepo,
                'siglaRepo' => $siglaRepo
            );

            $dadosProdutos = self::getProdutoElements($pedidosProdutosRessuprir, $repositorios);

            $ondaEn = $ondaRepo->geraNovaOnda();
            $ondaRepo->relacionaOndaPedidosExpedicao($pedidosProdutosRessuprir, $ondaEn, $dadosProdutos, $repositorios);

            /* Prepara os itens para picking ou pulmão de acordo com a quebra do pulmão-doca, caso utilize */
            $itensReservar = self::prepareArrayRessup($pedidosProdutosRessuprir, $quebraPulmaoDoca, $dadosProdutos, $repositorios);

            $reservaEstoqueExpedicaoRepo->gerarReservaSaida($itensReservar, $repositorios);
            $this->getEntityManager()->flush();

            $itensSairDoPicking = self::filtrarSaidaPicking($itensReservar);

            $qtdOsGerada = 0;
            if (!empty($itensSairDoPicking)) {
                $qtdOsGerada = $ondaRepo->calculaRessuprimentoByProduto($itensSairDoPicking, $ondaEn, $dadosProdutos, $repositorios);
                $this->getEntityManager()->flush();
            }

            $ondaRepo->sequenciaOndasOs();

            if ($this->getSystemParameterValue('VALIDA_RESERVA_SAIDA_GERACAO_RESSUPRIMENTO') == 'S') {
                if ($this->validaReservaSaidaCorretaByExpedicao($strExpedicao) === false) {
                    throw new \Exception("Existiram falhas gerando reserva de estoque para estas expedições. Consulte a equipe de desenvolvimento");
                }
            }

            $resultado = array();
            $msg = "Ondas Geradas com sucesso";

            if ($qtdOsGerada == 0) {
                $msg = "Nenhuma Os gerada";
            }

            $resultado['observacao'] = $msg;
            $resultado['resultado'] = true;

            return $resultado;
        } catch (\Exception $e) {

            $resultado = array();
            $resultado['observacao'] = $e->getMessage();
            $resultado['resultado'] = false;

            return $resultado;
        }
    }

    private function filtrarSaidaPicking($expedicoes)
    {
        $arrItensPicking = array();
        $arrRegroup = array();
        foreach ($expedicoes as $idExpedicao => $produtos) {
            foreach ($produtos as $codProduto => $produto) {
                foreach ($produto as $dscGrade => $grade) {
                    foreach ($grade as $quebraPD => $criterios) {
                        foreach ($criterios as $codCriterio => $separacoes) {
                            foreach ($separacoes['tiposSaida'] as $tipoSaida => $enderecos) {
                                if ($tipoSaida == ReservaEstoqueExpedicao::SAIDA_PICKING) {
                                    $arrRegroup[$codProduto][$dscGrade] = true;
                                }
                            }
                        }
                    }
                }
            }
        }

        foreach ($arrRegroup as $codProduto => $grade) {
            foreach ($grade as $dscGrade => $ok) {
                $arrItensPicking[] = array(
                    'codProduto' => $codProduto,
                    'grade' => $dscGrade
                );
            }
        }

        return $arrItensPicking;
    }

    /**
     * @param $itensExpedicoes
     * @param $repositorios
     * @return array
     * @throws \Exception
     */
    private function getProdutoElements($itensExpedicoes, $repositorios)
    {
        /** @var ProdutoRepository $produtoRepo */
        $produtoRepo = $repositorios['produtoRepo'];
        /** @var EmbalagemRepository $embalagemRepo */
        $embalagemRepo = $repositorios['embalagemRepo'];
        /** @var VolumeRepository $volumeRepo */
        $volumeRepo = $repositorios['volumeRepo'];

        $dadosProdutos = array();
        foreach ($itensExpedicoes as $produto) {
            $codProduto = $produto['COD_PRODUTO'];
            $grade = $produto['DSC_GRADE'];
            if (!isset($dadosProdutos[$codProduto][$grade])) {
                /** @var Produto $produtoEn */
                $produtoEn = $produtoRepo->findOneBy(array('id' => $codProduto, 'grade' => $grade));
                $embalagem = array();
                $volumes = array();

                if ($produtoEn->getTipoComercializacao()->getId() == 1) {
                    $embalagens = $embalagemRepo->findBy(array('codProduto' => $codProduto, 'grade' => $grade, 'dataInativacao' => null), array('quantidade' => 'ASC'));
                    if (empty($embalagens)) {
                        throw new \Exception("Produto " . $codProduto . " Grade " . $grade . " não possui embalagem cadastrada ou ativa!");
                    } else {
                        /** @var Embalagem $embalagemEn */
                        $embalagemEn = reset($embalagens);

                        $pickingEn = null;
                        $endereco = $embalagemEn->getEndereco();
                        if (!empty($endereco)) {
                            $pickingEn = $embalagemEn->getEndereco();
                        }

                        $normaPD = $embalagemRepo->getNormaPD($codProduto, $grade);

                        $embalagem = array (
                            "embalagemEn" => $embalagemEn,
                            "pickingEn" => $pickingEn,
                            "normaPD" => $normaPD
                        );
                    }
                } elseif ($produtoEn->getTipoComercializacao()->getId() == Produto::TIPO_COMPOSTO) {
                    $normas = $volumeRepo->getNormasByProduto($codProduto, $grade);
                    if (empty($normas)) {
                        $checkEmb = $produtoEn->getEmbalagens()->toArray();
                        if (!empty($checkEmb)) {
                            throw new \Exception("O produto $codProduto grade $grade está configurado como COMPOSTO tento embalagem cadastrada, verifique a possibilidade de alterar o tipo de comercialização para UNITARIO");
                        }
                        throw new \Exception("O produto $codProduto grade $grade não tem norma e volume cadastrados");
                    }
                    /** @var NormaPaletizacao $norma */
                    foreach ($normas as $norma) {
                        $volumesArr = $volumeRepo->getVolumesByNorma($norma->getId(), $codProduto, $grade);
                        if (empty($volumesArr)) {
                            throw new \Exception("O produto $codProduto grade $grade não tem volume cadastrado");
                        }

                        $pickingEn = null;
                        $numNorma = $norma->getNumNorma();

                        /** @var Volume $volume */
                        foreach ($volumesArr as $volume) {
                            if ($volume->getEndereco() != null) {
                                $pickingEn = $volume->getEndereco();
                            }
                            $volumes['normas'][$norma->getId()][] = array(
                                'volumeEn' => $volume,
                                'pickingEn' => $pickingEn,
                                'normaPD' => $numNorma
                            );
                        }
                    }
                }

                $dadosProdutos[$codProduto][$grade] = array(
                    'codProduto' => $codProduto,
                    'grade' => $grade,
                    'entidade' => $produtoEn,
                    'embalagem' => $embalagem,
                    'volumes' => $volumes
                );
            }
        }

        return $dadosProdutos;
    }

    /**
     * @param $arrItens
     * @param string $quebraPulmaoDoca
     * @param $dadosProdutos
     * @param $repositorios
     * @return array|mixed
     * @throws \Exception
     */
    private function prepareArrayRessup($arrItens, $quebraPulmaoDoca = Expedicao\ModeloSeparacao::QUEBRA_PULMAO_DOCA_NAO_USA, $dadosProdutos, $repositorios)
    {
        $args = [
            Expedicao\ModeloSeparacao::QUEBRA_PULMAO_DOCA_EXPEDICAO => "COD_EXPEDICAO",
            Expedicao\ModeloSeparacao::QUEBRA_PULMAO_DOCA_CARGA => "COD_CARGA",
            Expedicao\ModeloSeparacao::QUEBRA_PULMAO_DOCA_ROTA => "COD_ROTA",
            Expedicao\ModeloSeparacao::QUEBRA_PULMAO_DOCA_PRACA => "COD_PRACA",
            Expedicao\ModeloSeparacao::QUEBRA_PULMAO_DOCA_CLIENTE => "COD_CLIENTE"
        ];

        if ($quebraPulmaoDoca != Expedicao\ModeloSeparacao::QUEBRA_PULMAO_DOCA_NAO_USA) {
            return self::getArraysByCriterio($quebraPulmaoDoca, $arrItens, $args[$quebraPulmaoDoca], $dadosProdutos, $repositorios);
        } else {
            return self::getArraysSaidaPadrao($quebraPulmaoDoca, $arrItens, $dadosProdutos, $repositorios);
        }
    }

    private function getArraysByCriterio($quebra, $arrItens, $strCriterio, $dadosProdutos, $repositorios)
    {
        $sumQtdItemExpedicao = array();
        foreach($arrItens as $itemPedido) {
            $codProduto = $itemPedido['COD_PRODUTO'];
            $grade = $itemPedido['DSC_GRADE'];
            $idExpedicao = $itemPedido['COD_EXPEDICAO'];
            $codCriterio = $itemPedido[$strCriterio];
            if (empty($codCriterio)) {
                $campo = explode($strCriterio)[1];
                throw new \Exception("O cliente $itemPedido[NOM_PESSOA] não tem $campo cadastrado(a), 
                por isso não pode ser agrupado nesta quebra de pulmão doca na expedição $idExpedicao");
            }

            $idPedido = $itemPedido['COD_PEDIDO'];

            $sumQtdItemExpedicao[$idExpedicao][$codCriterio][$codProduto][$grade][$idPedido]['qtd'] = $itemPedido['QTD'];
        }

        $itensReservar = array();
        $arrEstoqueReservado = array();

        foreach ($sumQtdItemExpedicao as $expedicao => $criterio) {
            foreach ($criterio as $codCriterio => $produtoArr) {
                foreach ($produtoArr as $codProduto => $gradeArr) {
                    foreach ($gradeArr as $grade => $pedidos) {
                        /** @var Produto $produtoEn */
                        $produtoEn = $dadosProdutos[$codProduto][$grade]['entidade'];
                        list($itensReservar, $arrEstoqueReservado) = self::setDestinoSeparacao($expedicao, $quebra, $produtoEn, $codCriterio, $pedidos, $dadosProdutos, $itensReservar, $arrEstoqueReservado, $repositorios);
                    }
                }
            }
        }

        return $itensReservar;
    }

    private function getArraysSaidaPadrao($quebra, $arrItens, $dadosProdutos, $repositorios)
    {
        $itensReservar = array();
        $arrEstoqueReservado = array();
        foreach($arrItens as $itemPedido) {
            $codProduto = $itemPedido['COD_PRODUTO'];
            $grade = $itemPedido['DSC_GRADE'];
            $expedicao = $itemPedido['COD_EXPEDICAO'];
            $criterio = $itemPedido['COD_PEDIDO'];
            $pedido = array($itemPedido['COD_PEDIDO'] => array('qtd' => $itemPedido['QTD']));

            $produtoEn = $dadosProdutos[$codProduto][$grade]['entidade'];
            list($itensReservar, $arrEstoqueReservado) = self::setDestinoSeparacao($expedicao, $quebra, $produtoEn, $criterio, $pedido, $dadosProdutos, $itensReservar, $arrEstoqueReservado, $repositorios);
        }
        return $itensReservar;
    }

    /**
     * @param $idExpedicao
     * @param $quebra
     * @param $produtoEn Produto
     * @param $criterio
     * @param $pedidos
     * @param $dadosProdutos
     * @param $itensReservados
     * @param $arrEstoqueReservado
     * @param $repositorios
     * @return array
     */
    private function setDestinoSeparacao($idExpedicao, $quebra, $produtoEn, $criterio, $pedidos, $dadosProdutos, $itensReservados, $arrEstoqueReservado, $repositorios)
    {
        $codProduto = $produtoEn->getId();
        $dscGrade = $produtoEn->getGrade();
        if ($produtoEn->getTipoComercializacao()->getId() == Produto::TIPO_UNITARIO) {
            /** @var Embalagem $embalagemElem */
            $embalagemElem = $dadosProdutos[$codProduto][$dscGrade]['embalagem'];
            list($itensReservados, $arrEstoqueReservado) = self::triagemPorDestino($idExpedicao, $produtoEn,'EMBALAGEM', array($embalagemElem), 0, $pedidos, $quebra, $criterio, $itensReservados, $arrEstoqueReservado, $repositorios);
        } elseif ($produtoEn->getTipoComercializacao()->getId() == Produto::TIPO_COMPOSTO) {
            $volumes = $dadosProdutos[$codProduto][$dscGrade]['volumes'];
            foreach ($volumes['normas'] as $codNorma => $itens ) {
                list($itensReservados, $arrEstoqueReservado) = self::triagemPorDestino($idExpedicao, $produtoEn, "VOLUMES", $itens, $codNorma, $pedidos, $quebra, $criterio, $itensReservados, $arrEstoqueReservado, $repositorios);
            }
        }
        return array($itensReservados, $arrEstoqueReservado);
    }

    /**
     * @param $idExpedicao
     * @param $produtoEn Produto
     * @param $caracteristica
     * @param $elementosArr
     * @param $pedidos
     * @param $quebra
     * @param int $criterio
     * @param $itensReservados
     * @param $arrEstoqueReservado
     * @param $repositorios
     * @return array
     */
    private function triagemPorDestino ($idExpedicao, $produtoEn, $caracteristica, $elementosArr, $codNorma, $pedidos, $quebra, $criterio = 0, $itensReservados, $arrEstoqueReservado, $repositorios)
    {

        $codProduto = $produtoEn->getId();
        $dscGrade = $produtoEn->getGrade();

        /** @var \Wms\Domain\Entity\Enderecamento\EstoqueRepository $estoqueRepo */
        $estoqueRepo = $repositorios['estoqueRepo'];

        $naoUsaPD = Expedicao\ModeloSeparacao::QUEBRA_PULMAO_DOCA_NAO_USA;

        $qtdBase = 0;
        foreach ($pedidos as $codPedido => $qtdItem) {
            $qtdBase = Math::adicionar($qtdBase, $qtdItem['qtd']);
        }

        $estoquePulmao = null;
        $enderecos = array();
        $idEndereco = null;
        $enderecoPulmaoAutal = null;
        $idsElementos = array();
        $embalagem = null;
        $volume = null;

        $elemento = reset($elementosArr);

        // Só vai forçar a sair do picking quando a saida direta no pulmão não for possível por:
        // critério de validade
        // ou quantidade insuficiente
        $forcarSairDoPicking = false;

        $qtdRestante = $qtdBase;
        /** @var Endereco $enderecoPicking */
        $enderecoPicking = $elemento['pickingEn'];
        $normaPD = $elemento['normaPD'];
        $idElemento = null;

        if ($caracteristica == "EMBALAGEM") {
            /** @var Embalagem $embalagem */
            $embalagem = $elemento['embalagemEn'];
            $idElemento = $embalagem->getId();
            $idsElementos[] = $idElemento;
        } else {
            foreach ($elementosArr as $elemento) {
                if (empty($idElemento)) {
                    /** @var Volume $volume */
                    $volume = $elemento['volumeEn'];
                    $idElemento = $volume->getId();
                }
                $idsElementos[] = $elemento['volumeEn']->getId();
            }
        }

        if ($quebra != $naoUsaPD || ($quebra == $naoUsaPD && empty($enderecoPicking))) {
            // Separação no estoque que não é o próprio picking do produto.
            $params = array(
                'idProduto' => $codProduto,
                'grade' => $dscGrade,
                'idVolume' => (empty($volume)) ? null : $volume->getId(),
                'idEnderecoIgnorar' => (!empty($enderecoPicking))? $enderecoPicking->getId() : null
            );
            $estoquePulmao = $estoqueRepo->getEstoqueByParams($params);

            while ($qtdRestante > 0) {
                if (empty($estoquePulmao)) {
                    $forcarSairDoPicking = true;
                    break;
                } else {
                    foreach ($estoquePulmao as $estoque) {
                        $qtdEstoque = $estoque['SALDO'];
                        $idEndereco = $estoque['COD_DEPOSITO_ENDERECO'];
                        $zerouEstoque = false;
                        $saiuQtdNorma = false;
                        $nextEndereco = false;

                        if (isset($arrEstoqueReservado[$idEndereco][$codProduto][$dscGrade][$caracteristica][$idElemento])) {
                            $reserva = $arrEstoqueReservado[$idEndereco][$codProduto][$dscGrade][$caracteristica][$idElemento];
                            if ($reserva['estoqueReservado']) {
                                $nextEndereco = true;
                            } else {
                                $qtdEstoque = Math::subtrair($qtdEstoque, $reserva['qtdReservada']);
                            }
                        }

                        if ($nextEndereco) {
                            if ($estoque == end($estoquePulmao)) $forcarSairDoPicking = true;
                            continue;
                        }
                        $qtdReservar = 0;

                        if (Math::compare($qtdRestante, $qtdEstoque, ">=")) {
                            $qtdReservar = $qtdEstoque;
                            $zerouEstoque = true;
                        } else {
                            if (($quebra != $naoUsaPD) && !empty($normaPD)
                                && Math::compare($qtdRestante, $normaPD, ">=")
                                && Math::compare($normaPD, $qtdEstoque, "<")) {
                                $restoNormaPedido = Math::resto($qtdRestante, $normaPD);
                                $fatorNormaPedido = Math::dividir(Math::subtrair($qtdRestante, $restoNormaPedido), $normaPD);
                                $xNorma = Math::multiplicar($fatorNormaPedido, $normaPD);
                                if (Math::compare($xNorma, $qtdEstoque, "<")) {
                                    $qtdReservar = $xNorma;
                                    $saiuQtdNorma = true;
                                }
                            } elseif (($quebra != $naoUsaPD) && !empty($enderecoPicking)) {
                                $forcarSairDoPicking = true;
                                break;
                            } else {
                                $qtdReservar = $qtdRestante;
                            }
                        }

                        foreach ($idsElementos as $id) {
                            if (isset($arrEstoqueReservado[$idEndereco][$codProduto][$dscGrade][$caracteristica][$id]['qtdReservada'])) {
                                $qtdReservadaAtual = $arrEstoqueReservado[$idEndereco][$codProduto][$dscGrade][$caracteristica][$id]['qtdReservada'];
                                $arrEstoqueReservado[$idEndereco][$codProduto][$dscGrade][$caracteristica][$id]['qtdReservada'] = Math::adicionar($qtdReservadaAtual, $qtdReservar);
                            } else {
                                $arrEstoqueReservado[$idEndereco][$codProduto][$dscGrade][$caracteristica][$id]['qtdReservada'] = $qtdReservar;
                            }
                            $arrEstoqueReservado[$idEndereco][$codProduto][$dscGrade][$caracteristica][$id]['estoqueReservado'] = $zerouEstoque;
                        }

                        if (($quebra != $naoUsaPD) && ($zerouEstoque || $saiuQtdNorma)) {
                            $tipoSaida = ReservaEstoqueExpedicao::SAIDA_PULMAO_DOCA;
                        } else {
                            $tipoSaida = ReservaEstoqueExpedicao::SAIDA_SEPARACAO_AEREA;
                        }

                        $qtdRestante = Math::subtrair($qtdRestante, $qtdReservar);

                        foreach ($pedidos as $codPedido => $qtdItenPedido) {
                            if ($qtdReservar > 0) {
                                $qtdAtendida = (isset($elemento[$codPedido])) ? $elemento[$codPedido]['atendida'] : 0;
                                if ($qtdAtendida == $qtdItenPedido['qtd']) {
                                    continue;
                                } else {
                                    $qtdPendente = Math::subtrair($qtdItenPedido['qtd'], $qtdAtendida);
                                }

                                if (Math::compare($qtdReservar, $qtdPendente, ">=")) {
                                    $qtdReservada = $qtdPendente;
                                } else {
                                    $qtdReservada = $qtdReservar;
                                }

                                foreach($idsElementos as $id) {
                                    $enderecos[$tipoSaida]['enderecos'][$idEndereco][$codPedido][$caracteristica][$id] = array(
                                        'codProdutoEmbalagem' => ($caracteristica == "EMBALAGEM") ? $id : null,
                                        'codProdutoVolume' => ($caracteristica == "VOLUMES") ? $id : null,
                                        'codProduto' => $codProduto,
                                        'grade' => $dscGrade,
                                        'qtd' => $qtdReservada
                                    );
                                }

                                $elemento[$codPedido]['atendida'] = Math::adicionar($qtdAtendida, $qtdReservada);
                                $qtdReservar = Math::subtrair($qtdReservar, $qtdReservada);
                            } else {
                                break;
                            }
                        }
                        if ($qtdRestante == 0) break;
                    }
                    if ($forcarSairDoPicking) break;
                }
            }
        } else {
            $forcarSairDoPicking = true;
        }

        if ($forcarSairDoPicking) {
            foreach ($pedidos as $codPedido => $qtdItenPedido) {
                if ($qtdRestante > 0) {
                    $qtdAtendida = (isset($elemento[$codPedido])) ? $elemento[$codPedido]['atendida'] : 0;
                    if ($qtdAtendida == $qtdItenPedido['qtd']) {
                        continue;
                    } else {
                        $qtdPendente = Math::subtrair($qtdItenPedido['qtd'], $qtdAtendida);
                    }

                    if (Math::compare($qtdRestante, $qtdPendente, ">=")) {
                        $qtdReservada = $qtdPendente;
                    } else {
                        $qtdReservada = $qtdRestante;
                    }

                    $tipoSaida = ReservaEstoqueExpedicao::SAIDA_PICKING;

                    if ($enderecoPicking == null) {
                        throw new \Exception("Produto $codProduto - $dscGrade sem endereço de picking definido");
                    }


                    $idEndereco = $enderecoPicking->getId();

                    foreach($idsElementos as $id) {
                        $enderecos[$tipoSaida]['enderecos'][$idEndereco][$codPedido][$caracteristica][$id] = array(
                            'codProdutoEmbalagem' => ($caracteristica == "EMBALAGEM") ? $id : null,
                            'codProdutoVolume' => ($caracteristica == "VOLUMES") ? $id : null,
                            'codProduto' => $codProduto,
                            'grade' => $dscGrade,
                            'qtd' => $qtdReservada
                        );
                    }

                    $elemento[$codPedido]['atendida'] = Math::adicionar($qtdAtendida, $qtdReservada);
                    $qtdRestante = Math::subtrair($qtdRestante, $qtdReservada);
                } else {
                    break;
                }
            }
        }

        foreach ($enderecos as $codTipoSaida => $tipoSaida) {
            foreach ($tipoSaida['enderecos'] as $codEndereco => $pedidos) {
                foreach ($pedidos as $codPedido => $elementos) {
                    foreach ($elementos as $itens) {
                        $itensReservados
                            [$idExpedicao]
                                [$codProduto]
                                    [$dscGrade]
                                        [$quebra]
                                            [$criterio]
                                                ['tiposSaida']
                                                    [$codTipoSaida]
                                                        ['enderecos']
                                                            [$codEndereco]
                                                                [$codPedido]
                                                                    [$codNorma] = $itens;
                    }
                }
            }
        }

        return array($itensReservados, $arrEstoqueReservado);
    }

    public function verificaDisponibilidadeEstoquePedido($expedicoes, $gerarNovaOnda = false) {

        $sessao = new \Zend_Session_Namespace('deposito');
        $deposito = $this->_em->getReference('wms:Deposito', $sessao->idDepositoLogado);
        $central = $deposito->getFilial()->getCodExterno();

        $andWhere = '';
        if ($gerarNovaOnda) {
            $andWhere = "AND EXP.IND_PROCESSANDO = 'N'";
        }

        $sql = "
         SELECT DISTINCT
                C.COD_EXPEDICAO,
                C.COD_CARGA_EXTERNO as CARGA,
                PP.COD_PRODUTO as CODIGO,
                PP.DSC_GRADE as GRADE,
                PROD.PRODUTO,
                PROD.PICKING,
                PROD.ESTOQUE,
                PROD.QTD_SEPARAR_TOTAL,               
                PROD.SALDO_FINAL
           FROM (SELECT DISTINCT
                        PEDIDO.COD_PRODUTO AS Codigo,
                        PEDIDO.DSC_GRADE AS Grade,
                        PROD.DSC_PRODUTO as Produto,
                        DE.DSC_DEPOSITO_ENDERECO as Picking,
                        (NVL(E.QTD,0) + NVL(REP.QTD_RESERVADA,0)) AS Estoque,
                        PEDIDO.quantidade_pedido as QTD_SEPARAR_TOTAL,
                        (NVL(E.QTD,0) + NVL(REP.QTD_RESERVADA,0)) - PEDIDO.quantidade_pedido saldo_Final
                   FROM (SELECT SUM(PP.QUANTIDADE - NVL(PP.QTD_CORTADA,0)) quantidade_pedido , PP.COD_PRODUTO, PP.DSC_GRADE 
                           FROM PEDIDO P
                          INNER JOIN PEDIDO_PRODUTO PP ON PP.COD_PEDIDO = P.COD_PEDIDO
                          LEFT JOIN ONDA_RESSUPRIMENTO_PEDIDO ORP ON PP.COD_PEDIDO = ORP.COD_PEDIDO AND PP.COD_PRODUTO = ORP.COD_PRODUTO AND PP.DSC_GRADE = ORP.DSC_GRADE
                          INNER JOIN CARGA C ON P.COD_CARGA = C.COD_CARGA
                          WHERE P.CENTRAL_ENTREGA = $central AND ORP.COD_PEDIDO IS NULL AND P.DTH_CANCELAMENTO IS NULL AND C.COD_EXPEDICAO IN ($expedicoes)
                          GROUP BY PP.COD_PRODUTO, PP.DSC_GRADE) PEDIDO
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
              LEFT JOIN PRODUTO_VOLUME PV ON PV.COD_PRODUTO = PROD.COD_PRODUTO
                                         AND PV.DSC_GRADE = PROD.DSC_GRADE
              LEFT JOIN PRODUTO_EMBALAGEM PE ON PE.COD_PRODUTO = PROD.COD_PRODUTO
                                            AND PE.DSC_GRADE = PROD.DSC_GRADE
              LEFT JOIN DEPOSITO_ENDERECO DE ON DE.COD_DEPOSITO_ENDERECO = PE.COD_DEPOSITO_ENDERECO
                                             OR DE.COD_DEPOSITO_ENDERECO = PV.COD_DEPOSITO_ENDERECO
                  WHERE (NVL(E.QTD,0) + NVL(REP.QTD_RESERVADA,0)) - PEDIDO.quantidade_pedido < 0) PROD
            INNER JOIN PEDIDO_PRODUTO PP ON PP.COD_PRODUTO = PROD.CODIGO AND PP.DSC_GRADE = PROD.GRADE
            INNER JOIN PEDIDO P ON P.COD_PEDIDO = PP.COD_PEDIDO
             LEFT JOIN ONDA_RESSUPRIMENTO_PEDIDO ORP ON PP.COD_PEDIDO = ORP.COD_PEDIDO AND PP.COD_PRODUTO = ORP.COD_PRODUTO AND PP.DSC_GRADE = ORP.DSC_GRADE
            INNER JOIN CARGA C ON P.COD_CARGA = C.COD_CARGA
            INNER JOIN EXPEDICAO EXP ON EXP.COD_EXPEDICAO = C.COD_EXPEDICAO
            WHERE P.CENTRAL_ENTREGA = $central AND ORP.COD_PEDIDO IS NULL AND P.DTH_CANCELAMENTO IS NULL AND C.COD_EXPEDICAO IN ($expedicoes) $andWhere
                  ORDER BY Codigo, Grade, Produto
        ";
        return $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getExpedicaoSemProdutos($codExpedicao, $centralEntrega)
    {
        $sql = $this->getEntityManager()->createQueryBuilder()
            ->select('MAX(e.id) id')
            ->from('wms:Expedicao\PedidoProduto', 'pp')
            ->innerJoin('pp.pedido', 'p')
            ->innerJoin('p.carga', 'c')
            ->innerJoin('c.expedicao', 'e')
            ->leftJoin('wms:Enderecamento\Estoque', 'est', 'WITH', 'est.codProduto = pp.codProduto AND est.grade = pp.grade')
            ->where('est.codProduto is null')
            ->andWhere("e.id IN ($codExpedicao)")
            ->andWhere("p.centralEntrega = $centralEntrega")
            ->groupBy('e.id');
        return $sql->getQuery()->getResult();

    }

    public function campareResumoConferenciaByCarga($qtd, $idCargaExterno) {
        $SQL = "SELECT  C.COD_CARGA_EXTERNO as CARGA,
                                SUM(PP.QUANTIDADE - NVL(pp.QTD_CORTADA,0)) as QTD
                           FROM PEDIDO_PRODUTO PP
                           LEFT JOIN PEDIDO P ON P.COD_PEDIDO = PP.COD_PEDIDO
                           LEFT JOIN CARGA C ON C.COD_CARGA = P.COD_CARGA
                          WHERE C.COD_CARGA_EXTERNO = $idCargaExterno
                          GROUP BY COD_CARGA_EXTERNO";


        $values = $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);
        if (count($values) == 0) {
            throw new \Exception("Carga $idCargaExterno não encontrada no WMS");
        }
        $qtdWms = str_replace(',', '.', $values[0]['QTD']);
        $qtdErp = str_replace(',', '.', $qtd);
        if ($qtdErp == $qtdWms) {
            return true;
        } else {
            return false;
        }
    }

    public function compareConferenciaByCarga($dados, $idCargaExterno) {
        $SQL = "SELECT C.COD_CARGA_EXTERNO as CARGA,
                       P.COD_PEDIDO,
                       PP.COD_PRODUTO,
                       PP.DSC_GRADE,
                       SUM(PP.QUANTIDADE - NVL(pp.QTD_CORTADA,0)) as QTD
                  FROM PEDIDO_PRODUTO PP
                  LEFT JOIN PEDIDO P ON P.COD_PEDIDO = PP.COD_PEDIDO
                  LEFT JOIN CARGA C ON C.COD_CARGA = P.COD_CARGA
                  WHERE C.COD_CARGA_EXTERNO = $idCargaExterno
                  GROUP BY COD_CARGA_EXTERNO,
                           P.COD_PEDIDO,
                           PP.COD_PRODUTO,
                           PP.DSC_GRADE
                  ORDER BY C.COD_CARGA_EXTERNO, P.COD_PEDIDO, PP.COD_PRODUTO";
        $pedidosWMS = $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($pedidosWMS as $pedidoProdutoWms) {
            if (isset($dados[$idCargaExterno][$pedidoProdutoWms['COD_PEDIDO']])) {
                $pedidoERP = $dados[$idCargaExterno][$pedidoProdutoWms['COD_PEDIDO']];
                $encontrouProduto = false;
                foreach ($pedidoERP as $produtoERP) {
                    if (($produtoERP['idProduto'] == $pedidoProdutoWms['COD_PRODUTO']) && ($produtoERP['grade'] == $pedidoProdutoWms['DSC_GRADE'])) {
                        $encontrouProduto = true;
                        $qtdERP = str_replace(',', '.', $produtoERP['qtd']);
                        $qtdWms = str_replace(',', '.', $pedidoProdutoWms['QTD']);
                        if ($qtdERP != $qtdWms) {
                            return "Divergencia de conferencia no produto $pedidoProdutoWms[COD_PRODUTO] - $pedidoProdutoWms[DSC_GRADE], pedido $pedidoProdutoWms[COD_PEDIDO]. Qtd WMS: $qtdWms, Qtd ERP: $qtdERP";
                        }
                    }
                }

                if ($encontrouProduto == false) {
                    return "Produto $pedidoProdutoWms[COD_PRODUTO] - $pedidoProdutoWms[DSC_GRADE] não encontrado no ERP no pedido $pedidoProdutoWms[COD_PEDIDO]";
                }
            } else {
                return "Pedido $pedidoProdutoWms[COD_PEDIDO] não encontrado na conferencia com o ERP";
            }
        }

        return "Divergencia de conferencia com o ERP na carga " . $idCargaExterno;
    }

    public function findPedidosProdutosSemEtiquetaById($central, $cargas = null) {
        $sequencia = $this->getSystemParameterValue("SEQUENCIA_ETIQUETA_SEPARACAO");

        $whereCargas = null;
        if (!is_null($cargas) && is_array($cargas)) {
            $cargas = "'".implode("','", $cargas)."'";
            $whereCargas = " AND c.codCargaExterno in ($cargas) ";
        } else if (!is_null($cargas)) {
            $whereCargas = " AND c.codCargaExterno = '$cargas' ";
        }

        $query = "SELECT pp
                        FROM wms:Expedicao\PedidoProduto pp
                        INNER JOIN pp.produto p
                         LEFT JOIN p.linhaSeparacao ls
                        INNER JOIN pp.pedido ped
                        INNER JOIN wms:Expedicao\VProdutoEndereco e WITH p.id = e.codProduto AND p.grade = e.grade
                        INNER JOIN ped.carga c
                        WHERE ped.indEtiquetaMapaGerado != 'S'
                          $whereCargas
                          AND ped.centralEntrega = '$central'
                          AND ped.dataCancelamento is null
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
                $order = " ORDER BY e.nivel,
                                    ls.descricao,
                                    e.rua,
                                    e.predio,
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

        $result = $this->getEntityManager()->createQuery($query . $order)->getResult();

        return array_filter($result, function($item) {
            return ($item->getQuantidade() > $item->getQtdCortada());
        });
    }

    /**
     * @param $idExpedicao
     * @return mixed
     */
    public function countPedidosNaoCancelados($idExpedicao) {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder()
                ->select('count(e.id)')
                ->from('wms:Expedicao\Pedido', 'p')
                ->innerJoin('p.carga', 'c')
                ->innerJoin('c.expedicao', 'e')
                ->where('e.id = :IdExpedicao')
                ->andWhere('p.dataCancelamento is null')
                ->setParameter('IdExpedicao', $idExpedicao);
        return $queryBuilder->getQuery()->getSingleScalarResult();
    }

    /**
     * @param $idExpedicao
     * @return array
     */
    public function getCargas($idExpedicao) {
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
    public function getProdutosSemDadosByExpedicao($idExpedicao) {
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
    public function getCentralEntregaPedidos($idExpedicao, $consideraParcialmenteFinalizado = true) {
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

        $result = $source->getQuery()->getArrayResult();

        if (count($result) == 0) {
            $source = $this->getEntityManager()->createQueryBuilder()
                    ->select('r.codCarga')
                    ->from('wms:Expedicao', 'e')
                    ->innerJoin('wms:Expedicao\Carga', 'c', 'WITH', 'e.id = c.expedicao')
                    ->innerJoin('wms:Expedicao\Reentrega', 'r', 'WITH', 'c.id = r.carga')
                    ->where('e.id = :idExpedicao')
                    ->distinct(true)
                    ->setParameter('idExpedicao', $idExpedicao);
            if (count($source->getQuery()->getArrayResult()) > 0) {
                $sessao = new \Zend_Session_Namespace('deposito');
                $deposito = $this->_em->getReference('wms:Deposito', $sessao->idDepositoLogado);
                $central = $deposito->getFilial()->getCodExterno();

                $result = array(0 => array('centralEntrega' => $central));
            }
        }

        return $result;
    }

    /**
     * @param $idExpedicao
     * @return array
     */
    public function getCodCargasExterno($idExpedicao) {
        $source = $this->getEntityManager()->createQueryBuilder()
                ->select('c.codCargaExterno, c.sequencia')
                ->from('wms:Expedicao', 'e')
                ->innerJoin('wms:Expedicao\Carga', 'c', 'WITH', 'e.id = c.expedicao')
                ->where('e.id = :idExpedicao')
                ->distinct(true)
                ->setParameter('idExpedicao', $idExpedicao);
        return $source->getQuery()->getArrayResult();
    }

    public function getExistsPendenciaCorte($expedicaoEn, $centralEstoque) {
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
        $pedidoRepo = $this->_em->getRepository('wms:Expedicao\Pedido');

        $cargaRepo = $this->_em->getRepository('wms:Expedicao\Carga');
        $cargasEn = $cargaRepo->findBy(array('codExpedicao'=>$expedicaoEn->getId()));

        $msgErro = "";
        foreach ($cargasEn as $cargaEn) {
            $qtdEtiquetasPendenteConferencia = $EtiquetaRepo->countByStatus(\Wms\Domain\Entity\Expedicao\EtiquetaSeparacao::STATUS_ETIQUETA_GERADA, $expedicaoEn, $central, null, $cargaEn->getId());
            $qtdEtiquetasPendenteImpressão = $EtiquetaRepo->countByStatus(\Wms\Domain\Entity\Expedicao\EtiquetaSeparacao::STATUS_PENDENTE_IMPRESSAO, $expedicaoEn, $central, null, $cargaEn->getId());

            $cargaConferida = true;
            if ($qtdEtiquetasPendenteConferencia > 0) {
                $msgErro = 'Existem etiquetas pendentes de conferência nesta expedição';
                $cargaConferida = false;
            } else if ($qtdEtiquetasPendenteImpressão > 0) {
                $msgErro = 'Existem etiquetas pendentes de impressão nesta expedição';
                $cargaConferida = false;
            }

            if ($expedicaoEn->getStatus()->getId() == \Wms\Domain\Entity\Expedicao::STATUS_PARCIALMENTE_FINALIZADO) {

                $qtdEtiquetasConferidas = $EtiquetaRepo->countByStatus(\Wms\Domain\Entity\Expedicao\EtiquetaSeparacao::STATUS_CONFERIDO, $expedicaoEn, $central, null, $cargaEn->getId());
                $qtdEtiquetasRecebidoTransbordo = $EtiquetaRepo->countByStatus(\Wms\Domain\Entity\Expedicao\EtiquetaSeparacao::STATUS_RECEBIDO_TRANSBORDO, $expedicaoEn, $central, null, $cargaEn->getId());

                if ($qtdEtiquetasConferidas > 0) {
                    $msgErro = 'Existem etiquetas de produtos de outra central que ainda não foram conferidas';
                    $cargaConferida = false;
                } else if ($qtdEtiquetasRecebidoTransbordo > 0) {
                    $msgErro = 'Existem etiquetas de produtos de outra central que ainda não foram conferidas';
                    $cargaConferida = false;
                }
            }

            if ($cargaConferida === true) {
                $pedidoRepo->finalizaPedidosByCentral($central,$expedicaoEn->getId(),$cargaEn->getId(),false);
            }
        }

        $this->getEntityManager()->flush();

        if ($this->getSystemParameterValue('CONFERE_EXPEDICAO_REENTREGA') == 'S') {
            $qtdEtiquetasPendenteReentrega = $EtiquetaRepo->getEtiquetasReentrega($expedicaoEn->getId(), EtiquetaSeparacao::STATUS_PENDENTE_REENTREGA, $central);
            if (count($qtdEtiquetasPendenteReentrega) >0) {
                $msgErro = 'Existem etiquetas de reentrega pendentes de conferência nesta expedição';
            }
        }

        if ($msgErro != "") {
            return $msgErro;
        }

    }

    public function importaCortesERP($idExpedicao) {
        /** @var \Wms\Domain\Entity\Integracao\AcaoIntegracaoRepository $acaoIntRepo */
        /** @var \Wms\Domain\Entity\Expedicao\CargaRepository $cargaRepository */
        /** @var \Wms\Domain\Entity\Expedicao\AndamentoRepository $andamentoRepo */
        $acaoIntRepo = $this->getEntityManager()->getRepository('wms:Integracao\AcaoIntegracao');
        $cargaRepository = $this->getEntityManager()->getRepository('wms:Expedicao\Carga');
        $andamentoRepo = $this->_em->getRepository('wms:Expedicao\Andamento');

        $idIntegracaoCorte = $this->getSystemParameterValue('COD_INTEGRACAO_CORTES');
        $idIntegracaoVerificaCargaFinalizada = $this->getSystemParameterValue('COD_INTEGRACAO_VERIFICA_CARGA_FINALIZADA');

        $acaoCorteEn = $acaoIntRepo->find($idIntegracaoCorte);
        $acaoVerificaCargaFinalizadaEn = $acaoIntRepo->find($idIntegracaoVerificaCargaFinalizada);

        $cargaEntities = $cargaRepository->findBy(array('codExpedicao' => $idExpedicao));
        $cargas = array();
        foreach ($cargaEntities as $cargaEntity) {
            $result = $acaoIntRepo->processaAcao($acaoVerificaCargaFinalizadaEn, array(0 => $cargaEntity->getCodCargaExterno()), 'E', "P", null, 611);
            if ($result === false) {
                $cargas[] = $cargaEntity->getCodCargaExterno();
            } else if (is_string($result)) {
                return $result;
            } else {
                $andamentoRepo->save("Carga " . $cargaEntity->getCodCargaExterno() . " se encontra faturada no ERP, não é possível consultar seus cortes", $idExpedicao);
            }
        }
        $idCargas[] = implode(',', $cargas);

        if ((count($idCargas) > 0) && ($idCargas[0] != '')) {
            $result = $acaoIntRepo->processaAcao($acaoCorteEn, $idCargas, 'E', "P", null, 611);
            if (!($result === true)) {
                return $result;
            }
        }

        return true;
    }

    public function finalizarExpedicao($idExpedicao, $central, $validaStatusEtiqueta = true, $tipoFinalizacao = false, $idMapa = null, $idEmbalado = null, $motivo = '') {
        /** @var \Wms\Domain\Entity\Expedicao\EtiquetaSeparacaoRepository $EtiquetaRepo */
        $EtiquetaRepo = $this->_em->getRepository('wms:Expedicao\EtiquetaSeparacao');
        /** @var \Wms\Domain\Entity\Expedicao\MapaSeparacaoRepository $MapaSeparacaoRepo */
        $MapaSeparacaoRepo = $this->_em->getRepository('wms:Expedicao\MapaSeparacao');
        /** @var \Wms\Domain\Entity\Expedicao\MapaSeparacaoEmbaladoRepository $mapaSeparacaoEmbaladoRepo */
        $mapaSeparacaoEmbaladoRepo = $this->_em->getRepository('wms:Expedicao\MapaSeparacaoEmbalado');

        $expedicaoEn  = $this->findOneBy(array('id'=>$idExpedicao));
        $codCargaExterno = $this->validaCargaFechada($idExpedicao);
        if (isset($codCargaExterno) && !empty($codCargaExterno)) {
            return 'As cargas '.$codCargaExterno.' estão com pendencias de fechamento';
        }

        if ($this->getSystemParameterValue('IMPORTA_CORTES_ERP') == 'S') {
            $result = $this->importaCortesERP($idExpedicao);
            if (!($result === true)) {
                return $result;
            }
        }
        if ($this->validaPedidosImpressos($idExpedicao) == false) {
            return 'Existem produtos sem etiquetas impressas';
        }

        if ($this->getExistsPendenciaCorte($expedicaoEn,$central)) {
            return 'Existem etiquetas pendentes de corte nesta expedição';
        }

        ini_set('max_execution_time', 3000);
        Try {
            $transacao = false;
            if ($validaStatusEtiqueta == true) {
                $result = $this->validaStatusEtiquetas($expedicaoEn,$central);
                if (is_string($result)) {
                    return $result;
                }
            }

            $transacao = true;
            $this->getEntityManager()->beginTransaction();

            if ($validaStatusEtiqueta == true) {
                $result = $this->validaVolumesPatrimonio($idExpedicao);
                if (is_string($result)) {
                    return $result;
                }

                $result = $MapaSeparacaoRepo->verificaMapaSeparacao($expedicaoEn, $idMapa);
                if (is_string($result)) {
                    return $result;
                }

                $result = $mapaSeparacaoEmbaladoRepo->validaVolumesEmbaladoConferidos($idExpedicao);
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

            if ($this->getSystemParameterValue('VALIDA_RESERVA_SAIDA_FINALIZACA_EXPEDICAO') == 'S') {
                if ($this->validaReservaSaidaCorretaByExpedicao($idExpedicao) === false) {
                    throw new \Exception("Existiram falhas ao finalizar esta expedição. Consulte a equipe de desenvolvimento");
                }
            }

            if ($this->getSystemParameterValue("EXECUTA_CONFERENCIA_INTEGRACAO_EXPEDICAO") == "S") {
                $result = $this->validaConferenciaERP($expedicaoEn->getId());
                if (is_string($result)) {
                    return $result;
                }
            }

            if (isset($idMapa) && !empty($idMapa)) {
                $mapaSeparacaoEmbaladoRepo->validaVolumesEmbaladoConferidosByMapa($idMapa);
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

            $result = $this->finalizar($idExpedicao, $central, $tipoFinalizacao, $motivo);

            //Finaliza Expedição ERP
            if ($this->getSystemParameterValue('IND_FINALIZA_CONFERENCIA_ERP_INTEGRACAO') == 'S') {
                $idIntegracao = $this->getSystemParameterValue('ID_INTEGRACAO_FINALIZA_CONFERENCIA_ERP');

                /** @var \Wms\Domain\Entity\Integracao\AcaoIntegracaoRepository $acaoIntRepo */
                $acaoIntRepo = $this->getEntityManager()->getRepository('wms:Integracao\AcaoIntegracao');
                $acaoEn = $acaoIntRepo->find($idIntegracao);
                $options = array();

                $cargasEn = $expedicaoEn->getCarga();
                $cargas = array();
                foreach ($cargasEn as $cargaEn) {
                    $cargas[] = $cargaEn->getCodCargaExterno();
                }

                if (!is_null($cargas) && is_array($cargas)) {
                    $options[] = implode(',', $cargas);
                } else if (!is_null($cargas)) {
                    $options = $cargas;
                }

                $resultAcao = $acaoIntRepo->processaAcao($acaoEn, $options, 'E', "P", null, 612);
                if (!$resultAcao === true) {
                    throw new \Exception($resultAcao);
                }
            }
            $this->getEntityManager()->commit();
            return $result;
        } catch(\Exception $e) {
            if ($transacao == true) $this->getEntityManager()->rollback();
            return $e->getMessage();
        }
    }

    public function finalizarReentrega($idExpedicao) {
        /** @var \Wms\Domain\Entity\Expedicao\NotaFiscalSaidaAndamentoRepository $andamentoNFRepo */
        $andamentoNFRepo = $this->_em->getRepository("wms:Expedicao\NotaFiscalSaidaAndamento");
        $reentregaRepo = $this->getEntityManager()->getRepository("wms:Expedicao\Reentrega");
        $nfSaidaRepo = $this->getEntityManager()->getRepository("wms:Expedicao\NotaFiscalSaida");
        $notasFiscais = $reentregaRepo->getReentregasByExpedicao($idExpedicao, false);
        $expedicaoRepo = $this->getEntityManager()->getRepository("wms:Expedicao");

        $expedicaoEn = $expedicaoRepo->findOneBy(array('id' => $idExpedicao));
        $status = $this->getEntityManager()->getRepository('wms:Util\Sigla')->findOneBy(array('id' => Expedicao\NotaFiscalSaida::EXPEDIDO_REENTREGA));

        foreach ($notasFiscais as $notaFiscal) {
            $nfEn = $nfSaidaRepo->findOneBy(array('id' => $notaFiscal['COD_NOTA_FISCAL_SAIDA']));
            $reentregaEn = $reentregaRepo->findOneBy(array('id' => $notaFiscal['COD_REENTREGA']));
            $nfEn->setStatus($status);
            $this->getEntityManager()->persist($nfEn);

            $andamentoNFRepo->save($nfEn, Expedicao\NotaFiscalSaida::EXPEDIDO_REENTREGA, false, $expedicaoEn, $reentregaEn);
        }

        $this->getEntityManager()->flush();
    }

    public function validaConferenciaERP($idExpedicao) {
        try {
            $idAcaoResumo = $this->getSystemParameterValue("COD_ACAO_INTEGRACAO_RESUMO_CONFERENCIA_EXPEDICAO");
            $idAcaoConferencia = $this->getSystemParameterValue("COD_ACAO_INTEGRACAO_CONFERENCIA_EXPEDICAO");

            /** @var \Wms\Domain\Entity\Integracao\AcaoIntegracaoRepository $acaoIntRepo */
            $acaoIntRepo = $this->getEntityManager()->getRepository('wms:Integracao\AcaoIntegracao');

            $cargas = $this->getEntityManager()->getRepository("wms:Expedicao\Carga")->findBy(array('codExpedicao' => $idExpedicao));

            $acaoResumoEn = $acaoIntRepo->find($idAcaoResumo);
            $acaoConferenciaEn = $acaoIntRepo->find($idAcaoConferencia);

            if ($acaoResumoEn == null) {
                throw new \Exception("Ação de Verificação de Resumo da Conferencia não encontrada no sistema");
            }

            if ($acaoConferenciaEn == null) {
                throw new \Exception("Ação de Verificação de Conferencia não encontrada no sistema");
            }

            foreach ($cargas as $cargaEn) {
                $options = array();
                $options[] = $cargaEn->getCodCargaExterno();
                $result = $acaoIntRepo->processaAcao($acaoResumoEn, $options, "E", "P", null, 611);
                if (!($result === true)) {
                    $result = $acaoIntRepo->processaAcao($acaoConferenciaEn, $options, "E", "P", null, 611);
                    if (!($result === true)) {
                        throw new \Exception($result);
                    }
                }
            }
        } catch (\Exception $e) {
            return $e->getMessage();
        }

        return true;
    }

    public function validaVolumesPatrimonio($idExpedicao) {

        $volumesPatrimonioRepo = $this->getEntityManager()->getRepository("wms:Expedicao\ExpedicaoVolumePatrimonio");
        $volumesEn = $volumesPatrimonioRepo->findBy(array('expedicao' => $idExpedicao));

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
        foreach ($cargas as $carga) {
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
    private function finalizar($idExpedicao, $centralEntrega, $tipoFinalizacao = false, $motivo = '')
    {
        $codCargaExterno = $this->validaCargaFechada($idExpedicao);
        if (isset($codCargaExterno) && !empty($codCargaExterno)) {
            return 'As cargas '.$codCargaExterno.' estão com pendencias de fechamento';
        }

        /** @var \Wms\Domain\Entity\Expedicao\PedidoRepository $pedidoRepo */
        $pedidoRepo = $this->_em->getRepository('wms:Expedicao\Pedido');
        /** @var \Wms\Domain\Entity\Expedicao\AndamentoRepository $andamentoRepo */
        $andamentoRepo  = $this->_em->getRepository('wms:Expedicao\Andamento');

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
                    $andamentoRepo->save("Conferencia finalizada com sucesso via desktop com senha de autorização - Motivo: $motivo", $expedicaoEntity->getId());
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

    public function efetivaReservaEstoqueByExpedicao($idExpedicao) {

        /** @var \Wms\Domain\Entity\Ressuprimento\ReservaEstoqueRepository $reservaEstoqueRepo */
        $reservaEstoqueRepo = $this->getEntityManager()->getRepository("wms:Ressuprimento\ReservaEstoque");
        $estoqueRepo = $this->getEntityManager()->getRepository("wms:Enderecamento\Estoque");
        $usuarioRepo = $this->getEntityManager()->getRepository("wms:Usuario");

        $reservaEstoqueExpedicaoRepo = $this->getEntityManager()->getRepository("wms:Ressuprimento\ReservaEstoqueExpedicao");
        $reservaEstoqueArray = $reservaEstoqueExpedicaoRepo->findBy(array('expedicao' => $idExpedicao));

        $idUsuario = \Zend_Auth::getInstance()->getIdentity()->getId();
        $usuarioEn = $usuarioRepo->find($idUsuario);
        $arrayFlush = array();
        foreach ($reservaEstoqueArray as $re) {
            $pedido['codPedido'] = $re->getPedido()->getId();
            $pedido['codProprietario'] = $re->getPedido()->getProprietario();
            $reservaEstoqueEn = $re->getReservaEstoque();
            if ($reservaEstoqueEn->getAtendida() == 'N') {
                $arrayFlush = $reservaEstoqueRepo->efetivaReservaByReservaEntity($estoqueRepo, $reservaEstoqueEn, "E", $idExpedicao, $usuarioEn, null, null, null, $pedido, $arrayFlush);
            }
        }
    }

    public function liberarVolumePatrimonioByExpedicao($idExpedicao) {
        $volumes = $this->getVolumesPatrimonioByExpedicao($idExpedicao);
        $volumeRepo = $this->getEntityManager()->getRepository('wms:Expedicao\VolumePatrimonio');

        foreach ($volumes as $key => $volume) {
            $volumeEn = $volumeRepo->findOneBy(array('id' => $key));
            if ($volumeEn) {
                $volumeEn->setOcupado('N');
                $this->getEntityManager()->persist($volumeEn);
                $this->getEntityManager()->flush();
            }
        }
    }

    public function finalizeOSByExpedicao($idExpedicao) {
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
    public function alteraStatus($expedicaoEntity, $status) {
        $statusEntity = $this->_em->getReference('wms:Util\Sigla', $status);
        $expedicaoEntity->setStatus($statusEntity);
        $this->_em->persist($expedicaoEntity);
        $this->_em->flush();
        return true;
    }

    public function getVolumesPatrimonioByExpedicao($idExpedicao) {

        $result = $this->getVolumesPatrimonio($idExpedicao);
        $arrayResult = array();

        foreach ($result as $line) {
            $arrayResult[$line['id']] = $line['descricao'] . ' ' . $line['id'];
        }

        return $arrayResult;
    }

    public function getVolumesPatrimonio($idExpedicao) {
        $query = $this->getEntityManager()->createQueryBuilder()
                ->select("vp.id,vp.descricao, vp.ocupado,
                      (CASE WHEN ev.dataFechamento is NULL THEN 'EM CONFERENCIA'
                            else 'FECHADO' END) aberto,
                      (CASE WHEN ev.dataFechamento is NULL THEN 'EM CONFERENCIA'
                            WHEN ev.dataConferencia IS NULL THEN 'AGUARDANDO CONFERENCIA NO BOX'
                      else 'CONFERIDO' END) situacao")
                ->from("wms:Expedicao\ExpedicaoVolumePatrimonio", "ev")
                ->leftJoin("ev.volumePatrimonio", 'vp')
                ->where("ev.expedicao = $idExpedicao")
                ->distinct(true);

        return $query->getQuery()->getArrayResult();
    }

    public function getExpedicaoSemOndaByParams($parametros) {
        $sessao = new \Zend_Session_Namespace('deposito');
        $deposito = $this->_em->getReference('wms:Deposito', $sessao->idDepositoLogado);
        $central = $deposito->getFilial()->getCodExterno();
        $statusFinalizado = Expedicao::STATUS_FINALIZADO;
        $statusCancelada = Expedicao::STATUS_CANCELADO;
        $SQLOrder = " ORDER BY E.COD_EXPEDICAO ";
        $idModeloDefault = $this->getSystemParameterValue('MODELO_SEPARACAO_PADRAO');

        $Query = "SELECT DISTINCT E.COD_EXPEDICAO,
                                  TO_CHAR(E.DTH_INICIO,'DD/MM/YYYY') as DTH_INICIO,
                                  '' as ITINERARIO,
                                  '' as CARGA,
                                  S.DSC_SIGLA as STATUS,
                                  E.DSC_PLACA_EXPEDICAO as PLACA,
                                  MS.COD_MODELO_SEPARACAO || ' - ' || MS.DSC_MODELO_SEPARACAO as MODELO
                    FROM PEDIDO P
                    LEFT JOIN CARGA C ON C.COD_CARGA = P.COD_CARGA
                    LEFT JOIN EXPEDICAO E ON E.COD_EXPEDICAO = C.COD_EXPEDICAO
                    LEFT JOIN SIGLA S ON S.COD_SIGLA = E.COD_STATUS
                    LEFT JOIN MODELO_SEPARACAO MS ON (MS.COD_MODELO_SEPARACAO = E.COD_MODELO_SEPARACAO) OR (MS.COD_MODELO_SEPARACAO = $idModeloDefault AND E.COD_MODELO_SEPARACAO IS NULL)
                   WHERE P.COD_PEDIDO NOT IN (SELECT COD_PEDIDO FROM ONDA_RESSUPRIMENTO_PEDIDO)
                   AND E.COD_STATUS <> $statusFinalizado
                   AND E.COD_STATUS <> $statusCancelada
                   AND P.DTH_CANCELAMENTO IS NULL
                   AND P.CENTRAL_ENTREGA = $central
                   AND E.IND_PROCESSANDO = 'N'
                   ";

        if (isset($parametros['idExpedicao']) && !empty($parametros['idExpedicao'])) {
            $Query = $Query . " AND E.COD_EXPEDICAO = " . $parametros['idExpedicao'];
            unset($parametros['dataInicial1']);
            unset($parametros['dataInicial2']);
            unset($parametros['dataFinal1']);
            unset($parametros['dataFinal2']);
        }

        if (isset($parametros['codCargaExterno']) && !empty($parametros['codCargaExterno'])) {
            $Query = $Query . " AND C.COD_CARGA_EXTERNO = " . $parametros['codCargaExterno'];
            unset($parametros['dataInicial1']);
            unset($parametros['dataInicial2']);
            unset($parametros['dataFinal1']);
            unset($parametros['dataFinal2']);
        }

        if (isset($parametros['placa']) && !empty($parametros['placa'])) {
            $Query = $Query . " AND E.DSC_PLACA_EXPEDICAO = '$parametros[placa]'" ;
            unset($parametros['dataInicial1']);
            unset($parametros['dataInicial2']);
            unset($parametros['dataFinal1']);
            unset($parametros['dataFinal2']);
        }

        if (isset($parametros['dataInicial1']) && (!empty($parametros['dataInicial1'])) && (!empty($parametros['dataInicial2']))) {
            $dataInicial = $parametros['dataInicial1'];
            $dataFinal = $parametros['dataInicial2'];
            $Query = $Query . " AND (E.DTH_INICIO BETWEEN TO_DATE('$dataInicial 00:00', 'DD-MM-YYYY HH24:MI') AND TO_DATE('$dataFinal 23:59', 'DD-MM-YYYY HH24:MI'))";
        }

        if (isset($parametros['dataFinal1']) && (!empty($parametros['dataFinal1'])) && (!empty($parametros['dataFinal2']))) {
            $dataInicial = $parametros['dataFinal1'];
            $dataFinal = $parametros['dataFinal2'];
            $Query = $Query . " AND (E.DTH_FINALIZACAO BETWEEN TO_DATE('$dataInicial 00:00', 'DD-MM-YYYY HH24:MI') AND TO_DATE('$dataFinal 23:59', 'DD-MM-YYYY HH24:MI'))";
        }

        if (isset($parametros['status']) && (!empty($parametros['status']))) {
            $Query = $Query . " AND E.COD_STATUS = " . $parametros['status'];
        }

        $result = $this->getEntityManager()->getConnection()->query($Query . $SQLOrder)->fetchAll(\PDO::FETCH_ASSOC);

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
            if (isset($colCarga) && !empty($colCarga))
                $result[$key]['CARGA'] = implode(', ', $colCarga);

            if (isset($colItinerario) && !empty($colItinerario))
                $result[$key]['ITINERARIO'] = implode(', ', $colItinerario);

            unset($colCarga);
            unset($colItinerario);
        }
        return $result;
    }

    public function alteraPrimeiraCentralFinalizada($expedicaoEntity, $centralEntrega) {
        $expedicaoEntity->setCentralEntregaParcFinalizada($centralEntrega);
        $this->_em->persist($expedicaoEntity);
        $this->_em->flush();
        return true;
    }

    /**
     * @param $placaExpedicao
     * @param $runFlush
     * @return Expedicao
     * @throws \Exception
     */
    public function save($placaExpedicao, $runFlush = true) {

        if (empty($placaExpedicao)) {
            throw new \Exception("placaExpedicao não pode ser vazio");
        }

        $em = $this->getEntityManager();

        if ($runFlush)
            $em->beginTransaction();

        try {

            $enExpedicao = new Expedicao;

            $enExpedicao->setPlacaExpedicao($placaExpedicao);
            $statusEntity = $em->getReference('wms:Util\Sigla', Expedicao::STATUS_INTEGRADO);
            $enExpedicao->setStatus($statusEntity);
            $enExpedicao->setDataInicio(new \DateTime);
            $enExpedicao->setIndProcessando("N");

            $em->persist($enExpedicao);
            if ($runFlush) {
                $em->flush();
                $em->commit();
            }
        } catch (\Exception $e) {
            if ($runFlush)
                $em->rollback();
            throw new \Exception($e->getMessage() . ' - ' . $e->getTraceAsString());
        }

        return $enExpedicao;
    }

    /**
     * @param $idExpedicao
     * @return array
     */
    public function getItinerarios($idExpedicao, $carga = null) {
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

    public function getProdutos($idExpedicao, $central, $cargas = null, $linhaSeparacao = null) {
        $source = $this->getEntityManager()->createQueryBuilder()
                ->select('rp, r.id codReentrega')
                ->from('wms:Expedicao\VRelProdutos', 'rp')
                ->leftJoin('wms:Produto', 'p', 'WITH', 'p.id = rp.codProduto AND p.grade = rp.grade')
                ->leftJoin('wms:Expedicao\Carga', 'c', 'WITH', 'rp.codCarga = c.id')
                ->leftJoin('wms:Expedicao\Pedido', 'ped', 'WITH', 'c.id = ped.carga')
                ->leftJoin('wms:Expedicao\NotaFiscalSaidaPedido', 'nfsp', 'WITH', 'ped.id = nfsp.pedido')
                ->leftJoin('nfsp.notaFiscalSaida', 'nfs')
                ->leftJoin('wms:Expedicao\NotaFiscalSaidaProduto', 'nfsprod', 'WITH', 'nfsprod.notaFiscalSaida = nfs.id')
                ->leftJoin('wms:Expedicao\Reentrega', 'r', 'WITH', 'r.codNotaFiscalSaida = nfs.id')
//            ->leftJoin('wms:Expedicao\EtiquetaSeparacao', 'es', 'WITH', 'es.codReentrega = r.id')
                ->where('rp.codExpedicao in (' . $idExpedicao . ')')
                ->andWhere('rp.centralEntrega = :centralEntrega')
                ->setParameter('centralEntrega', $central);

        if (!is_null($linhaSeparacao)) {
            $source->andWhere("p.linhaSeparacao = $linhaSeparacao");
        }

        if (!is_null($cargas) && is_array($cargas)) {
            $cargas = implode(',', $cargas);
            $source->andWhere("rp.codCargaExterno in ($cargas)");
        } else if (!is_null($cargas)) {
            $source->andWhere('rp.codCargaExterno = :cargas')
                    ->setParameter('cargas', $cargas);
        }

        return $source->getQuery()->getResult();
    }

    public function getPlacasByExpedicaoCentral($idExpedicao) {
        $dql = $this->getEntityManager()->createQueryBuilder()
                ->select('c.placaCarga, c.codCargaExterno')
                ->from('wms:Expedicao\Carga', 'c')
                ->innerJoin('wms:Expedicao\Pedido', 'p', 'WITH', 'c.id = p.codCarga')
                ->where('c.codExpedicao = :idExpedicao')
                ->setParameter('idExpedicao', $idExpedicao)
                ->distinct(true);

        return $dql->getQuery()->getArrayResult();
    }

    /**
     * @param $parametros
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getPesos($parametros) {

        $where = "";
        $and = "";
        if (isset($parametros['id']) && (!empty($parametros['id']))) {
            $where .= $and . "c.COD_EXPEDICAO = " . $parametros['id'] . "";
            $and = " and ";
        }


        if (isset($parametros['agrup']) && (!empty($parametros['agrup'])) && $parametros['agrup'] == 'carga') {
            $agrupador = "q.COD_CARGA, q.COD_CARGA_EXTERNO";
        } else if (isset($parametros['agrup']) && (!empty($parametros['agrup'])) && $parametros['agrup'] == 'expedicao') {
            $agrupador = "q.COD_EXPEDICAO";
        }

        $sql = '
                SELECT
                  ' . $agrupador . ',
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
                    SUM(NVL(prod.NUM_CUBAGEM,0) * (pedProd.QUANTIDADE - NVL(pedProd.QTD_CORTADA,0))) as NUM_CUBAGEM,
                    SUM(NVL(prod.NUM_PESO,0) * (pedProd.QUANTIDADE - NVL(pedProd.QTD_CORTADA,0))) as PESO_TOTAL
                  FROM
                    CARGA c
                  LEFT JOIN
                    PEDIDO ped on (c.COD_CARGA=ped.COD_CARGA)
                  LEFT JOIN
                    PEDIDO_PRODUTO pedProd on (ped.COD_PEDIDO=pedProd.COD_PEDIDO)
                  LEFT JOIN PRODUTO_PESO prod on (pedProd.COD_PRODUTO=prod.COD_PRODUTO and pedProd.DSC_GRADE=prod.DSC_GRADE)
                  where
                    ' . $where . '
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
                  ' . $agrupador
        ;

        $result = $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);

        return $result;
    }

    /**
     * @param $parametros
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function buscar($parametros, $idDepositoLogado = null) {

        $where = "";
        $whereSubQuery = "";
        $and = "";
        $andSub = "";
        $cond = "";

        $WhereFinalCarga = "";
        $WhereSigla = "";
        $WhereCarga = "";
        $WhereExpedicao = "";
        $FullWhereFinal = "";

        if (isset($idDepositoLogado)) {
            $andWhere = " AND P.CENTRAL_ENTREGA = '$idDepositoLogado' ";
        } else {
            $andWhere = '';
        }

        if (is_array($parametros['centrais'])) {
            $central = implode("','", $parametros['centrais']);
            $central = "'" . $central . "'";
            $where .= $and . "( PED.CENTRAL_ENTREGA in(" . $central . ")";
            $where .= " OR PED.PONTO_TRANSBORDO in(" . $central . ") )";
            $and = " AND ";
        }

        if (isset($parametros['placa']) && !empty($parametros['placa'])) {
            $where .= $and . " E.DSC_PLACA_EXPEDICAO = '" . $parametros['placa'] . "'";
            $and = " AND ";
            $WhereExpedicao .= " AND (E.DSC_PLACA_EXPEDICAO = '" . $parametros['placa'] . "')";
        }

        if (isset($parametros['dataInicial1']) && (!empty($parametros['dataInicial1']))) {
            $where .= $and . " E.DTH_INICIO >= TO_DATE('" . $parametros['dataInicial1'] . " 00:00', 'DD-MM-YYYY HH24:MI')";
            $and = " AND ";
            $WhereExpedicao .= " AND (E.DTH_INICIO >= TO_DATE('" . $parametros['dataInicial1'] . " 00:00', 'DD-MM-YYYY HH24:MI'))";
        }
        if (isset($parametros['dataInicial2']) && (!empty($parametros['dataInicial2']))) {
            $where .= $and . " E.DTH_INICIO <= TO_DATE('" . $parametros['dataInicial2'] . " 23:59', 'DD-MM-YYYY HH24:MI')";
            $and = " AND ";
            $WhereExpedicao .= " AND (E.DTH_INICIO <= TO_DATE('" . $parametros['dataInicial2'] . " 23:59', 'DD-MM-YYYY HH24:MI'))";
        }

        if (isset($parametros['dataFinal1']) && (!empty($parametros['dataFinal1']))) {
            $where .= $and . "E.DTH_FINALIZACAO >= TO_DATE('" . $parametros['dataFinal1'] . " 00:00', 'DD-MM-YYYY HH24:MI')";
            $and = " AND ";
            $WhereExpedicao .= " AND (E.DTH_FINALIZACAO >= TO_DATE('" . $parametros['dataFinal1'] . " 00:00', 'DD-MM-YYYY HH24:MI'))";
        }

        if (isset($parametros['dataFinal2']) && (!empty($parametros['dataFinal2']))) {
            $where .= $and . "E.DTH_FINALIZACAO <= TO_DATE('" . $parametros['dataFinal2'] . " 23:59', 'DD-MM-YYYY HH24:MI')";
            $and = " AND ";
            $WhereExpedicao .= " AND (E.DTH_FINALIZACAO <= TO_DATE('" . $parametros['dataFinal2'] . " 23:59', 'DD-MM-YYYY HH24:MI'))";
            ;
        }

        if (isset($parametros['status']) && (!empty($parametros['status']))) {
            $where .= $and . "S.COD_SIGLA = " . $parametros['status'] . "";
            $and = " and ";
            $WhereSigla .= "AND (S.COD_SIGLA = " . $parametros['status'] . ")";
        }

        if (isset($parametros['idExpedicao']) && !empty($parametros['idExpedicao'])) {
            $where = " E.COD_EXPEDICAO = " . $parametros['idExpedicao'] . "";
            $whereSubQuery = " C.COD_EXPEDICAO = " . $parametros['idExpedicao'] . "";
            $and = " and ";
            $andSub = " and ";
            $WhereExpedicao .= " AND (E.COD_EXPEDICAO = " . $parametros['idExpedicao'] . ") ";
        }

        if (isset($parametros['codCargaExterno']) && !empty($parametros['codCargaExterno'])) {
            $where = " AND CA.COD_CARGA_EXTERNO = " . $parametros['codCargaExterno'] . "";
            $whereSubQuery = " C.COD_CARGA_EXTERNO = " . $parametros['codCargaExterno'] . "";
            $and = " and ";
            $andSub = " and ";
            $WhereFinalCarga = $WhereCarga . " AND  (E.COD_EXPEDICAO IN (SELECT COD_EXPEDICAO FROM CARGA WHERE COD_CARGA_EXTERNO = " . $parametros['codCargaExterno'] . "))";
            $WhereCarga .= " AND  (COD_CARGA_EXTERNO = " . $parametros['codCargaExterno'] . ")";
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

        $WherePedido = "";
        if (isset($parametros['pedido']) && !empty($parametros['pedido'])) {
            $sql = " SELECT DISTINCT COD_EXPEDICAO FROM CARGA C LEFT JOIN PEDIDO P ON P.COD_CARGA = C.COD_CARGA WHERE P.COD_PEDIDO = '".$parametros['pedido'] . "'";
            $exp = \Wms\Domain\EntityRepository::nativeQuery($sql);

            $arr = array();
            foreach ($exp as $idExp) {
                $arr[] = $idExp['COD_EXPEDICAO'];
            }

            $exp = implode(";",$arr);
            if (count($arr) >0) {
                $WherePedido = " AND E.COD_EXPEDICAO IN (" . $exp . ")";
            } else {
                $WherePedido = " AND 1 = 2 ";
            }

        }


        $FullWhere = $WhereExpedicao . $WhereCarga . $WhereSigla;
        $FullWhereFinal = $WhereExpedicao . $WhereFinalCarga . $WhereSigla . $WherePedido;

        if ($whereSubQuery != "")
            $cond = " WHERE ";

        $sql = '  SELECT E.COD_EXPEDICAO AS "id",
                       E.DSC_PLACA_EXPEDICAO AS "placaExpedicao",
                       to_char(E.DTH_INICIO,\'DD/MM/YYYY HH24:MI:SS\') AS "dataInicio",
                       to_char(E.DTH_FINALIZACAO,\'DD/MM/YYYY HH24:MI:SS\') AS "dataFinalizacao",
                       C.CARGAS AS "carga",
                       S.DSC_SIGLA AS "status",
                       P.IMPRIMIR AS "imprimir",
                       PESO.NUM_PESO + NVL(PESO_REENTREGA.NUM_PESO,0) as "peso",
                       PESO.NUM_CUBAGEM + NVL(PESO_REENTREGA.NUM_CUBAGEM,0) as "cubagem",
                       NVL(REE.QTD,0) as "reentrega",
                       I.ITINERARIOS AS "itinerario",
                       MOT.NOM_MOTORISTA AS "motorista",
                       TIPO_PEDIDO.TIPO_PEDIDO AS "tipopedido",
                       (CASE WHEN ((NVL(MS.QTD_CONFERIDA,0) + NVL(C.CONFERIDA,0)) * 100) = 0 THEN 0
                            ELSE CAST(((NVL(MS.QTD_CONFERIDA,0) + NVL(C.CONFERIDA,0)) * 100) / (NVL(MS.QTD_MAPA_TOTAL,0) + NVL(C.QTDETIQUETA,0)) AS NUMBER(6,2)) END) AS "PercConferencia"
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
                                      WHERE ES.COD_STATUS IN(526, 531, 532) ' . $FullWhere . '
                                      GROUP BY C.COD_EXPEDICAO) C1 ON C1.COD_EXPEDICAO = C.COD_EXPEDICAO
                         WHERE ESEP.COD_STATUS NOT IN(524, 525) ' . $FullWhere . '
                         GROUP BY C1.COD_EXPEDICAO, C1.Etiqueta) C ON C.COD_EXPEDICAO = E.COD_EXPEDICAO
                  LEFT JOIN (SELECT MS.COD_EXPEDICAO,
                                NVL(SUM(QTD_CONF.QTD),0) + NVL(SUM(QTD_SEP.QTD_CORTADO),0) as QTD_CONFERIDA,
                                NVL(SUM(QTD_CONF_M.QTD),0) AS QTD_CONF_MANUAL,
                                NVL(SUM(QTD_SEP.QTD),0) as QTD_MAPA_TOTAL
                               FROM MAPA_SEPARACAO MS
                               LEFT JOIN (SELECT SUM(QTD_CONFERIDA * QTD_EMBALAGEM) as QTD, COD_MAPA_SEPARACAO
                                            FROM MAPA_SEPARACAO_CONFERENCIA
                                           GROUP BY COD_MAPA_SEPARACAO) QTD_CONF ON QTD_CONF.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO
                               LEFT JOIN (SELECT SUM(QTD_SEPARAR * QTD_EMBALAGEM) as QTD, COD_MAPA_SEPARACAO, SUM(QTD_CORTADO) QTD_CORTADO
                                            FROM MAPA_SEPARACAO_PRODUTO
                                           GROUP BY COD_MAPA_SEPARACAO) QTD_SEP ON QTD_SEP.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO
                               LEFT JOIN (SELECT SUM(MSP.QTD_SEPARAR * MSP.QTD_EMBALAGEM) as QTD, MSP.COD_MAPA_SEPARACAO
                                            FROM MAPA_SEPARACAO_PRODUTO MSP
                                            LEFT JOIN MAPA_SEPARACAO_CONFERENCIA MSC ON MSC.COD_MAPA_SEPARACAO = MSP.COD_MAPA_SEPARACAO
                                           WHERE MSP.IND_CONFERIDO = \'N\' AND MSC.COD_MAPA_SEPARACAO_CONFERENCIA IS NULL
                                           GROUP BY MSP.COD_MAPA_SEPARACAO) QTD_CONF_M ON QTD_CONF_M.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO
                               LEFT JOIN EXPEDICAO E ON E.COD_EXPEDICAO = MS.COD_EXPEDICAO
                              WHERE 1 = 1
                                ' . $WhereExpedicao . '
                              GROUP BY MS.COD_EXPEDICAO) MS ON MS.COD_EXPEDICAO = E.COD_EXPEDICAO
                  LEFT JOIN (SELECT C.COD_EXPEDICAO,
                                    LISTAGG (C.COD_CARGA_EXTERNO,\', \') WITHIN GROUP (ORDER BY C.COD_CARGA_EXTERNO) CARGAS
                               FROM CARGA C ' . $JoinExpedicao . $JoinSigla . '
                               WHERE 1 = 1 ' . $WhereExpedicao . $WhereSigla . $WhereCarga . '
                              GROUP BY C.COD_EXPEDICAO) C ON C.COD_EXPEDICAO = E.COD_EXPEDICAO
                  LEFT JOIN (SELECT E.COD_EXPEDICAO,
                                    LISTAGG (MOTORISTA.NOM_MOTORISTA,\', \') WITHIN GROUP (ORDER BY MOTORISTA.NOM_MOTORISTA) NOM_MOTORISTA
                              FROM EXPEDICAO E
							  INNER JOIN SIGLA S ON S.COD_SIGLA = E.COD_STATUS
                              LEFT JOIN (SELECT DISTINCT E.COD_EXPEDICAO,
                                        C.NOM_MOTORISTA
                                    FROM CARGA C
                                    INNER JOIN EXPEDICAO E ON E.COD_EXPEDICAO = C.COD_EXPEDICAO
									INNER JOIN SIGLA S ON S.COD_SIGLA = E.COD_STATUS
                                    WHERE 1 = 1 ' . $WhereExpedicao . $WhereSigla . $WhereCarga . ' 
                                    GROUP BY E.COD_EXPEDICAO, C.NOM_MOTORISTA) MOTORISTA ON MOTORISTA.COD_EXPEDICAO = E.COD_EXPEDICAO
                              WHERE 1 = 1 ' . $WhereExpedicao . $WhereSigla . '
                              GROUP BY E.COD_EXPEDICAO) MOT ON MOT.COD_EXPEDICAO = E.COD_EXPEDICAO
                  LEFT JOIN (SELECT COD_EXPEDICAO,
                                    LISTAGG (DSC_ITINERARIO, \',\') WITHIN GROUP (ORDER BY DSC_ITINERARIO) ITINERARIOS
                              FROM ITINERARIO I
                              INNER JOIN (
                                SELECT DISTINCT C.COD_EXPEDICAO,
                                      P.COD_ITINERARIO
                                 FROM CARGA C
                                INNER JOIN PEDIDO P ON P.COD_CARGA = C.COD_CARGA ' . $JoinExpedicao . $JoinSigla . '
                                WHERE 1 = 1 ' . $FullWhere . '
                                GROUP BY P.COD_ITINERARIO, C.COD_EXPEDICAO) CARGAS ON CARGAS.COD_ITINERARIO = I.COD_ITINERARIO
                        GROUP BY COD_EXPEDICAO
                  )  I ON I.COD_EXPEDICAO = E.COD_EXPEDICAO
                  LEFT JOIN (SELECT C.COD_EXPEDICAO,
                                    CASE WHEN (SUM(CASE WHEN (P.IND_ETIQUETA_MAPA_GERADO = \'N\' AND P.DTH_CANCELAMENTO IS NULL) OR ((R.IND_ETIQUETA_MAPA_GERADO = \'N\' AND PARAM.DSC_VALOR_PARAMETRO = \'S\')) THEN 1 ELSE 0 END)) + NVL(MAP.QTD,0) + NVL(PED.QTD,0) > 0 THEN \'SIM\'
                                         ELSE \'\' END AS IMPRIMIR
                               FROM (SELECT DSC_VALOR_PARAMETRO FROM PARAMETRO WHERE DSC_PARAMETRO = \'CONFERE_EXPEDICAO_REENTREGA\') PARAM,
                                    CARGA C
                               LEFT JOIN REENTREGA R ON R.COD_CARGA = C.COD_CARGA
                               LEFT JOIN PEDIDO P ON P.COD_CARGA = C.COD_CARGA ' . $JoinExpedicao . $JoinSigla . '
                               LEFT JOIN (SELECT C.COD_EXPEDICAO, COUNT(COD_ETIQUETA_SEPARACAO) as QTD
                                            FROM ETIQUETA_SEPARACAO ES
                                            LEFT JOIN PEDIDO P ON P.COD_PEDIDO = ES.COD_PEDIDO
                                            LEFT JOIN CARGA C ON C.COD_CARGA = P.COD_CARGA ' . $JoinExpedicao . $JoinSigla . '
                                           WHERE ES.COD_STATUS = 522 ' . $FullWhere . '
                                           GROUP BY C.COD_EXPEDICAO) PED ON PED.COD_EXPEDICAO = C.COD_EXPEDICAO
                               LEFT JOIN (SELECT COD_EXPEDICAO,
                                                 COUNT(COD_MAPA_SEPARACAO) as QTD
                                            FROM MAPA_SEPARACAO
                                           WHERE COD_STATUS = 522
                                           GROUP BY COD_EXPEDICAO ) MAP ON MAP.COD_EXPEDICAO = C.COD_EXPEDICAO
                                   WHERE 1 = 1 ' . $FullWhere . '
                              GROUP BY C.COD_EXPEDICAO, MAP.QTD, PED.QTD) P ON P.COD_EXPEDICAO = E.COD_EXPEDICAO
                  LEFT JOIN (SELECT E.COD_EXPEDICAO, COUNT(REE.COD_REENTREGA) as QTD
                               FROM REENTREGA REE
                               LEFT JOIN CARGA C ON REE.COD_CARGA = C.COD_CARGA
                               LEFT JOIN EXPEDICAO E ON E.COD_EXPEDICAO = C.COD_EXPEDICAO
                              WHERE REE.IND_ETIQUETA_MAPA_GERADO = \'N\' ' . $WhereExpedicao . '
                              GROUP BY E.COD_EXPEDICAO) REE ON REE.COD_EXPEDICAO = E.COD_EXPEDICAO
                  LEFT JOIN (SELECT C.COD_EXPEDICAO,
                                    SUM(NVL(PESO.NUM_PESO,0) * (PP.QUANTIDADE - NVL(PP.QTD_CORTADA,0))) as NUM_PESO,
                                    SUM(NVL(PESO.NUM_CUBAGEM,0) * (PP.QUANTIDADE - NVL(PP.QTD_CORTADA,0))) as NUM_CUBAGEM
                               FROM CARGA C
                               LEFT JOIN PEDIDO P ON P.COD_CARGA = C.COD_CARGA
                               LEFT JOIN PEDIDO_PRODUTO PP ON PP.COD_PEDIDO = P.COD_PEDIDO ' . $JoinExpedicao . $JoinSigla . '
                               LEFT JOIN PRODUTO_PESO PESO ON PESO.COD_PRODUTO = PP.COD_PRODUTO AND PESO.DSC_GRADE = PP.DSC_GRADE
                               WHERE 1 = 1  ' . $FullWhere . $andWhere . '
                              GROUP BY C.COD_EXPEDICAO) PESO ON PESO.COD_EXPEDICAO = E.COD_EXPEDICAO
                  LEFT JOIN (SELECT C.COD_EXPEDICAO,
                                    SUM(NVL(PESO.NUM_PESO,0) * (NFPROD.QUANTIDADE)) as NUM_PESO,
                                    SUM(NVL(PESO.NUM_CUBAGEM,0) * (NFPROD.QUANTIDADE)) as NUM_CUBAGEM
                               FROM REENTREGA R
                              INNER JOIN CARGA                     C      ON C.COD_CARGA = R.COD_CARGA
                              INNER JOIN NOTA_FISCAL_SAIDA_PRODUTO NFPROD ON NFPROD.COD_NOTA_FISCAL_SAIDA = R.COD_NOTA_FISCAL_SAIDA
                              INNER JOIN NOTA_FISCAL_SAIDA_PEDIDO  NFPED  ON NFPED.COD_NOTA_FISCAL_SAIDA = R.COD_NOTA_FISCAL_SAIDA
                              INNER JOIN PEDIDO                    P      ON P.COD_PEDIDO = NFPED.COD_PEDIDO  ' . $JoinExpedicao . $JoinSigla . '
                              INNER JOIN PRODUTO_PESO              PESO   ON PESO.COD_PRODUTO = NFPROD.COD_PRODUTO AND PESO.DSC_GRADE = NFPROD.DSC_GRADE
                              WHERE 1 = 1  ' . $FullWhere . $andWhere . ' 
                              GROUP BY C.COD_EXPEDICAO) PESO_REENTREGA ON PESO_REENTREGA.COD_EXPEDICAO = E.COD_EXPEDICAO 
                  
                  LEFT JOIN (SELECT PED.COD_EXPEDICAO,
                                  LISTAGG (S.DSC_SIGLA,\',\') WITHIN GROUP (ORDER BY S.DSC_SIGLA) TIPO_PEDIDO
                                  FROM SIGLA S
                                  INNER JOIN (
                                    SELECT CASE WHEN REENTREGA.COD_CARGA IS NOT NULL THEN 621 ELSE P.COD_TIPO_PEDIDO END COD_TIPO_PEDIDO, C.COD_EXPEDICAO 
                                    FROM CARGA C
                                    LEFT JOIN PEDIDO P ON C.COD_CARGA = P.COD_CARGA 
                                    LEFT JOIN (
                                      SELECT R.COD_CARGA, C.COD_EXPEDICAO 
                                      FROM REENTREGA R
                                      INNER JOIN CARGA C ON R.COD_CARGA = C.COD_CARGA
                                    ) REENTREGA ON REENTREGA.COD_EXPEDICAO = C.COD_EXPEDICAO 
                                    GROUP BY P.COD_TIPO_PEDIDO, C.COD_EXPEDICAO, REENTREGA.COD_CARGA 
                                  ) PED ON PED.COD_TIPO_PEDIDO = S.COD_SIGLA
                                  GROUP BY PED.COD_EXPEDICAO) TIPO_PEDIDO ON TIPO_PEDIDO.COD_EXPEDICAO = E.COD_EXPEDICAO 
                                                               
                 WHERE 1 = 1' . $FullWhereFinal . '
                 ORDER BY E.COD_EXPEDICAO DESC
    ';

        return \Wms\Domain\EntityRepository::nativeQuery($sql);
    }

    /**
     * @param $parametros
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function buscarVelho($parametros, $idDepositoLogado = null) {
        $where = "";
        $whereSubQuery = "";
        $and = "";
        $andSub = "";
        $cond = "";

        if (isset($idDepositoLogado)) {
            $andWhere = 'WHERE P.CENTRAL_ENTREGA = ' . $idDepositoLogado;
        } else {
            $andWhere = '';
        }

        if (is_array($parametros['centrais'])) {
            $central = implode(',', $parametros['centrais']);
            $where .= $and . "( PED.CENTRAL_ENTREGA in(" . $central . ")";
            $where .= " OR PED.PONTO_TRANSBORDO in(" . $central . ") )";
            $and = " AND ";
        }

        if (isset($parametros['placa']) && !empty($parametros['placa'])) {
            $where .= $and . " E.DSC_PLACA_EXPEDICAO = '" . $parametros['placa'] . "'";
            $and = " AND ";
        }

        if (isset($parametros['dataInicial1']) && (!empty($parametros['dataInicial1']))) {
            $where .= $and . " E.DTH_INICIO >= TO_DATE('" . $parametros['dataInicial1'] . " 00:00', 'DD-MM-YYYY HH24:MI')";
            $and = " AND ";
        }
        if (isset($parametros['dataInicial2']) && (!empty($parametros['dataInicial2']))) {
            $where .= $and . " E.DTH_INICIO <= TO_DATE('" . $parametros['dataInicial2'] . " 23:59', 'DD-MM-YYYY HH24:MI')";
            $and = " AND ";
        }

        if (isset($parametros['dataFinal1']) && (!empty($parametros['dataFinal1']))) {
            $where .= $and . "E.DTH_FINALIZACAO >= TO_DATE('" . $parametros['dataFinal1'] . " 00:00', 'DD-MM-YYYY HH24:MI')";
            $and = " AND ";
        }
        if (isset($parametros['dataFinal2']) && (!empty($parametros['dataFinal2']))) {
            $where .= $and . "E.DTH_FINALIZACAO <= TO_DATE('" . $parametros['dataFinal2'] . " 23:59', 'DD-MM-YYYY HH24:MI')";
            $and = " AND ";
        }

        if (isset($parametros['status']) && (!empty($parametros['status']))) {
            $where .= $and . "S.COD_SIGLA = " . $parametros['status'] . "";
            $and = " and ";
        }
        if (isset($parametros['idExpedicao']) && !empty($parametros['idExpedicao'])) {
            $where = " E.COD_EXPEDICAO = " . $parametros['idExpedicao'] . "";
            $whereSubQuery = " C.COD_EXPEDICAO = " . $parametros['idExpedicao'] . "";
            $and = " and ";
            $andSub = " and ";
        }

        if (isset($parametros['codCargaExterno']) && !empty($parametros['codCargaExterno'])) {
            $where = " CA.COD_CARGA_EXTERNO = " . $parametros['codCargaExterno'] . "";
            $whereSubQuery = " C.COD_CARGA_EXTERNO = " . $parametros['codCargaExterno'] . "";
            $and = " and ";
            $andSub = " and ";
        }


        if ($whereSubQuery != "")
            $cond = " WHERE ";


        $sql = '  SELECT E.COD_EXPEDICAO AS "id",
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
                               FROM CARGA C ' . $cond . ' ' . $whereSubQuery . '
                              GROUP BY COD_EXPEDICAO) C ON C.COD_EXPEDICAO = E.COD_EXPEDICAO
                  LEFT JOIN (SELECT COD_EXPEDICAO,
                                    LISTAGG (DSC_ITINERARIO,\', \') WITHIN GROUP (ORDER BY DSC_ITINERARIO) ITINERARIOS
                               FROM (SELECT DISTINCT C.COD_EXPEDICAO,
                                            I.DSC_ITINERARIO,
                                            COD_CARGA_EXTERNO
                                       FROM CARGA C
                                      INNER JOIN PEDIDO P ON P.COD_CARGA = C.COD_CARGA
                                      INNER JOIN ITINERARIO I ON P.COD_ITINERARIO = I.COD_ITINERARIO ' . $cond . ' ' . $whereSubQuery . ')
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
                                 ' . $andWhere . '
                              GROUP BY C.COD_EXPEDICAO) PESO ON PESO.COD_EXPEDICAO = E.COD_EXPEDICAO
                 WHERE ' . $where . '
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

//        return \Wms\Domain\EntityRepository::nativeQuery($sql);
        $result = $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        return $result;
    }

    /**
     * @param null $status
     * @return array
     */
    public function getByStatusAndCentral($status = null, $central = null) {
        $source = $this->getEntityManager()->createQueryBuilder()
                ->select('e.id, e.dataInicio, e.codStatus, e.placaExpedicao')
                ->from('wms:Expedicao', 'e')
                ->innerJoin('wms:Expedicao\Carga', 'c', 'WITH', 'e.id = c.expedicao')
                ->innerJoin('wms:Expedicao\Pedido', 'pedido', 'WITH', 'c.id = pedido.carga')
                ->orderBy("e.id", "DESC")
                ->distinct(true);

        $parcialmenteFinalizado = Expedicao::STATUS_PARCIALMENTE_FINALIZADO;
        if (is_array($central)) {
            $central = implode(',', $central);
            $source->andWhere("pedido.centralEntrega in ($central) AND e.codStatus != $parcialmenteFinalizado")
                    ->orWhere("pedido.pontoTransbordo in ($central) AND e.codStatus = $parcialmenteFinalizado");
        } else if ($central) {
            $source->andWhere("pedido.centralEntrega = :central AND e.codStatus != $parcialmenteFinalizado")
                    ->orWhere("pedido.pontoTransbordo = :central AND e.codStatus = $parcialmenteFinalizado");
            $source->setParameter('central', $central);
        }
        $source->andWhere("pedido.conferido = 0 OR pedido.conferido IS NULL");

        if (is_array($status)) {
            $status = implode(',', $status);
            $source->andWhere("e.status in ($status)");
        } else if ($status) {
            $source->andWhere("e.status = :status")
                    ->setParameter('status', $status);
        }

        return $source->getQuery()->getArrayResult();
    }

    /**
     * @param $idExpedicao
     * @return array
     */
    public function criarOrdemServico($idExpedicao) {
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
    public function verificaOSUsuario($idExpedicao) {
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
    public function getResumoConferenciaByID($idExpedicao) {
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

        $expedicaoRepo = $this->_em->getRepository('wms:Expedicao');
        $expedicaoEntity = $expedicaoRepo->find($idExpedicao);
        if ($expedicaoEntity->getStatus()->getId() == Expedicao::STATUS_SEGUNDA_CONFERENCIA) {
            $source->addSelect("(
             SELECT COUNT(es2.id)
               FROM wms:Expedicao\EtiquetaConferencia es2
               LEFT JOIN es2.pedido ped2
               LEFT JOIN ped2.carga c2
              INNER JOIN wms:Expedicao\EtiquetaSeparacao ess WITH es2.codEtiquetaSeparacao = ess.id
              WHERE c2.codExpedicao = e.id
                AND es2.codStatus in ( " . Expedicao::STATUS_SEGUNDA_CONFERENCIA . " )
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

    public function getAndamentoByExpedicao($idExpedicao) {
        $source = $this->_em->createQueryBuilder()
                ->select("a.dscObservacao,
                      a.dataAndamento,
                      p.nome")
                ->from("wms:Expedicao\Andamento", "a")
                ->innerJoin("a.usuario", "u")
                ->innerJoin("u.pessoa", "p")
                ->where('a.expedicao = :idExpedicao')
                ->setParameter('idExpedicao', $idExpedicao)
                ->orderBy("a.id", "DESC");

        $result = $source->getQuery()->getResult();

        return $source;
    }

    public function getOSByUser() {

        $idPessoa = \Zend_Auth::getInstance()->getIdentity()->getId();

        $source = $this->_em->createQueryBuilder()
                ->select("exp.id")
                ->from("wms:OrdemServico", "os")
                ->innerJoin("os.expedicao", "exp")
                ->where("os.pessoa = :idPessoa")
                ->andWhere("exp.status IN (464,463)")
                ->setParameter("idPessoa", $idPessoa);

        $result = $source->getQuery()->getResult();

        $arrayResult = array();
        foreach ($result as $item) {
            $arrayResult = $item;
        }

        return $arrayResult;
    }

    public function getRelatorioSaidaProdutos($codProduto, $grade, $dataInicial = null, $dataFinal = null, $filial = null) {
        $source = $this->_em->createQueryBuilder()
                ->select("es.dataConferencia, i.descricao as itinerario, i.id as idItinerario, c.codCargaExterno, e.id as idExpedicao, cliente.codClienteExterno, es.codProduto, es.dscGrade,
             e.dataInicio, e.dataFinalizacao, p.id as idPedido")
                ->from("wms:Expedicao\EtiquetaSeparacao", "es")
                ->innerJoin('es.pedido', 'p')
                ->innerJoin('p.itinerario', 'i')
                ->innerJoin('p.carga', 'c')
                ->innerJoin('c.expedicao', 'e')
                ->innerJoin('p.pessoa', 'cliente')
                ->where('es.codProduto = :codProduto')
                ->setParameter("codProduto", $codProduto)
                ->orderBy('e.dataFinalizacao', 'DESC');

        if (isset($dataInicial) && (!empty($dataInicial))) {
            $dataInicial = str_replace('/', '-', $dataInicial);
            $data1 = new \DateTime($dataInicial);
            $data1 = $data1->format('Y-m-d') . ' 00:00:00';
            $source->setParameter('dataInicio', $data1)
                    ->andWhere("e.dataFinalizacao >= :dataInicio");
        }

        if (isset($dataFinal) && (!empty($dataFinal))) {
            $dataFinal = str_replace('/', '-', $dataFinal);
            $data2 = new \DateTime($dataFinal);
            $data2 = $data2->format('Y-m-d') . ' 23:59:59';

            $source->setParameter('dataFinal', $data2)
                    ->andWhere('e.dataFinalizacao <= :dataFinal');
        }

        if (isset($grade) && !empty($grade)) {
            $source->andWhere('es.dscGrade = :grade')
                    ->setParameter('grade', $grade);
        }
        if (isset($filial) && !empty($filial)) {
            $source->andWhere('p.centralEntrega = :filial')
                ->setParameter('filial', $filial);
        }

        return $source->getQuery()->getResult();
    }

    public function getEtiquetasConferidasByVolume($idExpedicao, $idVolumePatrimonio) {
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
                ->from('wms:Expedicao\VEtiquetaSeparacao', 'es')
                ->innerJoin('wms:Expedicao\EtiquetaSeparacao', 'etq', 'WITH', 'es.codBarras = etq.id')
                ->leftJoin('wms:OrdemServico', 'os', 'WITH', 'etq.codOS = os.id')
                ->leftJoin('os.pessoa', 'p')
                ->leftJoin('etq.volumePatrimonio', 'vp')
                ->leftJoin('etq.produtoEmbalagem', 'emb')
                ->leftJoin('etq.produtoVolume', 'vol')
                ->where("es.codExpedicao = $idExpedicao")
                ->andWhere('es.codStatus IN (526,531,532)');

        if ($idVolumePatrimonio != NULL) {
            $dql->andWhere("etq.volumePatrimonio = $idVolumePatrimonio");
        }

        $result = $dql->getQuery()->getArrayResult();
        return $result;
    }

    /**
     * @param $idVolume int 'ID do VolumePatrimonio'
     * @param $idExpedicao int 'ID da Expedição'
     * 
     * @return array
     */
    public function getVolumesExpedicaoFinalizadosByVolumeExpedicao($idVolume, $idExpedicao) {

        $sql = "SELECT
                    EMB.COD_BARRAS codBarras,
                    P.COD_PRODUTO codProduto,
                    P.DSC_PRODUTO produto,
                    P.DSC_GRADE grade,
                    CL2.NOM_PESSOA cliente,
                    NVL(ES1.COD_ESTOQUE, ES2.COD_ESTOQUE) codEstoque,
                    CASE WHEN EMB.DSC_EMBALAGEM IS NULL THEN VOL.DSC_VOLUME ELSE EMB.DSC_EMBALAGEM END AS embalagem,
                    CONF.NOM_PESSOA conferente,
                    MSC.DTH_CONFERENCIA dataConferencia,
                    CONCAT(CONCAT(VP.DSC_VOLUME_PATRIMONIO, ' '), VP.COD_VOLUME_PATRIMONIO) volumePatrimonio
                FROM MAPA_SEPARACAO_CONFERENCIA MSC
                INNER JOIN MAPA_SEPARACAO MS ON MSC.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO
                INNER JOIN PRODUTO P ON P.COD_PRODUTO = MSC.COD_PRODUTO AND P.DSC_GRADE = MSC.DSC_GRADE
                LEFT JOIN PRODUTO_VOLUME VOL ON MSC.COD_PRODUTO_VOLUME = VOL.COD_PRODUTO_VOLUME
                LEFT JOIN PRODUTO_EMBALAGEM EMB ON MSC.COD_PRODUTO_EMBALAGEM = EMB.COD_PRODUTO_EMBALAGEM
                INNER JOIN EXPEDICAO_VOLUME_PATRIMONIO EVP ON EVP.COD_EXPEDICAO = MS.COD_EXPEDICAO AND EVP.COD_VOLUME_PATRIMONIO = MSC.COD_VOLUME_PATRIMONIO
                LEFT JOIN CLIENTE CL ON CL.COD_CLIENTE_EXTERNO = EVP.COD_TIPO_VOLUME
                INNER JOIN PESSOA CL2 ON CL.COD_PESSOA = CL2.COD_PESSOA
                LEFT JOIN ESTOQUE ES1 ON ES1.COD_PRODUTO = MSC.COD_PRODUTO AND ES1.COD_PRODUTO_EMBALAGEM = EMB.COD_PRODUTO_EMBALAGEM
                LEFT JOIN ESTOQUE ES2 ON ES2.COD_PRODUTO = MSC.COD_PRODUTO AND ES2.COD_PRODUTO_VOLUME = VOL.COD_PRODUTO_VOLUME
                INNER JOIN PESSOA CONF ON EVP.COD_USUARIO = CONF.COD_PESSOA
                INNER JOIN VOLUME_PATRIMONIO VP ON MSC.COD_VOLUME_PATRIMONIO = VP.COD_VOLUME_PATRIMONIO
                WHERE  MS.COD_EXPEDICAO = $idExpedicao AND MS.COD_STATUS IN (523,526,531,532)";

        if ($idVolume != null) {
            $sql = $sql . " AND MSC.COD_VOLUME_PATRIMONIO = $idVolume";
        }

        $result = $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);

        return $result;
    }

    public function getProdutosEmbalado($idExpedicao) {
        $source = $this->_em->createQueryBuilder()
                ->select("count(DISTINCT exp.id) as nEmbalados")
                ->from("wms:Expedicao\EtiquetaSeparacao", "es")
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

    public function getCargaExternoEmbalados($idExpedicao, $codStatus = EtiquetaSeparacao::STATUS_ETIQUETA_GERADA) {
        $source = $this->_em->createQueryBuilder()
                ->select("es.codCargaExterno")
                ->from("wms:Expedicao\VEtiquetaSeparacao", "es")
                ->innerJoin('wms:Produto', 'p', 'WITH', 'es.codProduto = p.id')
                ->innerJoin('p.embalagens', 'pe')
                ->where('es.codExpedicao = :idExpedicao')
                ->andWhere("pe.embalado = 'S' ")
                ->groupBy('es.codCargaExterno')
                ->setParameter("idExpedicao", $idExpedicao);
        return $source->getQuery()->getResult();
    }

    public function getDadosExpedicao($params) {
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
                         LEFT JOIN MAPA_SEPARACAO_PEDIDO MSP ON MSP.COD_PEDIDO_PRODUTO = PP.COD_PEDIDO_PRODUTO
                         LEFT JOIN MAPA_SEPARACAO MS ON MS.COD_MAPA_SEPARACAO = MSP.COD_MAPA_SEPARACAO
                         LEFT JOIN MAPA_SEPARACAO_CONFERENCIA MSC ON MSC.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO AND MSC.COD_PRODUTO = PP.COD_PRODUTO AND MSC.DSC_GRADE = PP.DSC_GRADE
                         LEFT JOIN SIGLA SES ON SES.COD_SIGLA = ES.COD_STATUS
                         LEFT JOIN PRODUTO_VOLUME PV ON ES.COD_PRODUTO_VOLUME = PV.COD_PRODUTO_VOLUME
                         LEFT JOIN PRODUTO_EMBALAGEM PE ON PE.COD_PRODUTO_EMBALAGEM = ES.COD_PRODUTO_EMBALAGEM
                 LEFT JOIN DEPOSITO_ENDERECO DE1 ON DE1.COD_DEPOSITO_ENDERECO = PE.COD_DEPOSITO_ENDERECO
                 LEFT JOIN DEPOSITO_ENDERECO DE2 ON DE2.COD_DEPOSITO_ENDERECO = PV.COD_DEPOSITO_ENDERECO
                         LEFT JOIN PRODUTO_DADO_LOGISTICO PDL ON PDL.COD_PRODUTO_EMBALAGEM = PE.COD_PRODUTO_EMBALAGEM
                         LEFT JOIN ORDEM_SERVICO OS ON ES.COD_OS = OS.COD_OS OR MSC.COD_OS = OS.COD_OS
                         LEFT JOIN ORDEM_SERVICO OS2 ON ES.COD_OS_TRANSBORDO = OS2.COD_OS
                         LEFT JOIN PESSOA CONFERENTE ON CONFERENTE.COD_PESSOA = OS.COD_PESSOA
                         LEFT JOIN PESSOA CONFERENTE_TRANSBORDO ON CONFERENTE_TRANSBORDO.COD_PESSOA = OS.COD_PESSOA
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

        $resultado = $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        return $resultado;
    }

    public function getCarregamentoByExpedicao($codExpedicao, $codStatus = null, $codCargaExterno = null) {
        $source = $this->_em->createQueryBuilder()
                ->select("
                      ped.sequencia,
                      cli.codClienteExterno                 as codCliente,
                      it.descricao                          as itinerario,
                      NVL(pe.localidade,endere.localidade)  as cidade,
                      NVL(pe.bairro,endere.bairro)          as bairro,
                      NVL(pe.descricao,endere.descricao)    as rua,
                      NVL(pessoa.nome,pj.nomeFantasia)      as cliente,
                      SUM(pp.quantidade)                    as quantidade,
                      COUNT(pp.quantidade)                  as itens")
                ->from("wms:Expedicao\PedidoProduto", "pp")
                ->leftJoin("pp.produto", "prod")
                ->leftJoin("pp.pedido", "ped")
                ->leftJoin("ped.carga", "car")
                ->leftJoin("ped.itinerario", "it")
                ->leftJoin("ped.pessoa", "cli")
                ->leftJoin("cli.pessoa", "pessoa")
                ->leftJoin('wms:Pessoa\Juridica', 'pj', 'WITH', 'pessoa.id = pj.id')
                ->leftJoin("pessoa.enderecos", "endere")
                ->leftJoin('wms:Expedicao\PedidoEndereco', 'pe', 'WITH', 'pe.pedido = ped.id')
                ->distinct(true)
                ->where("prod.linhaSeparacao != 15")
                ->groupBy("cli.codClienteExterno, pe.localidade, pj.nomeFantasia, pe.bairro, pe.descricao, it.descricao, endere.localidade, endere.bairro, endere.descricao, pessoa.nome, ped.sequencia")
                ->orderBy('ped.sequencia, cidade, bairro, rua, cliente, codCliente');

        if (!is_null($codExpedicao) && ($codExpedicao != "")) {
            $source->andWhere("car.codExpedicao = " . $codExpedicao);
        }

        if (!is_null($codCargaExterno) && ($codCargaExterno != "")) {
            $source->andWhere("car.codCargaExterno = " . $codCargaExterno);
        }

        if ($codStatus != NULL) {
            $source->andWhere("es.codStatus = $codStatus ");
        }
        return $source->getQuery()->getResult();
    }

    public function getProdutosSemEstoqueByExpedicao($idExpedicao) {

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

        $result = $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);
        return $result;
    }

    public function finalizacarga($codExpedicao) {

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

    public function getVolumesExpedicaoByExpedicao($idExpedicao) {
        $sql = "SELECT
                  DISTINCT
                    vp.COD_VOLUME_PATRIMONIO as VOLUME, vp.DSC_VOLUME_PATRIMONIO as DESCRICAO, i.DSC_ITINERARIO as ITINERARIO, pes.NOM_PESSOA as CLIENTE
                    FROM EXPEDICAO_VOLUME_PATRIMONIO evp
                INNER JOIN VOLUME_PATRIMONIO vp ON vp.COD_VOLUME_PATRIMONIO = evp.COD_VOLUME_PATRIMONIO
                INNER JOIN CARGA c ON c.COD_EXPEDICAO = evp.COD_EXPEDICAO
                INNER JOIN PEDIDO p ON p.COD_CARGA = C.COD_CARGA
                LEFT JOIN ETIQUETA_SEPARACAO es ON p.COD_PEDIDO = es.COD_PEDIDO AND evp.COD_VOLUME_PATRIMONIO = es.COD_VOLUME_PATRIMONIO
                INNER JOIN PESSOA pes ON pes.COD_PESSOA = p.COD_PESSOA
                INNER JOIN ITINERARIO i ON i.COD_ITINERARIO = p.COD_ITINERARIO
                WHERE evp.COD_EXPEDICAO = $idExpedicao
                ORDER BY vp.COD_VOLUME_PATRIMONIO ASC";

        $result = $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);

        return $result;
    }

    public function getEtiquetaMae($quebras, $modelos, $arrayEtiqueta, $idExpedicao) {

        /** @var \Wms\Domain\Entity\Expedicao\EtiquetaMaeRepository $EtiquetaMaeRepo */
        $EtiquetaMaeRepo = $this->_em->getRepository('wms:Expedicao\EtiquetaMae');
        $tipoFracao = $this->getTipoFracao($arrayEtiqueta, $idExpedicao);

        if (!empty($tipoFracao[0]["TIPO"])) {
            $dscEtiqueta = $tipoFracao[0]["TIPO"] . ";";

            foreach ($quebras as $chv => $vlr) {
                if (!empty($tipoFracao[0]["TIPO"]) && $tipoFracao[0]["TIPO"] == "1") {
                    $fracionados = $vlr['frac'];

                    foreach ($fracionados as $chvFrac => $vlrFrac) {
                        $verificaFrac = false;

                        $sql = "select E.COD_ETIQUETA_MAE
                                from ETIQUETA_MAE E
                                INNER JOIN ETIQUETA_MAE_QUEBRA EQ ON (E.COD_ETIQUETA_MAE=EQ.COD_ETIQUETA_MAE)
                            WHERE E.COD_EXPEDICAO=" . $idExpedicao;

                        $codQuebra = $this->getCodQuebra($tipoFracao, $vlrFrac['tipoQuebra']);
                        if (empty($codQuebra)) {
                            $codQuebra = " is NULL";
                        } else if ($codQuebra == "NULL") {
                            $codQuebra = " is NULL";
                        } else {
                            $codQuebra = "=" . $codQuebra;
                        }

                        $where = " AND EQ.TIPO_FRACAO='FRACIONADOS' AND EQ.COD_QUEBRA" . $codQuebra . " AND EQ.IND_TIPO_QUEBRA='" . $vlrFrac['tipoQuebra'] . "'";

                        $sql .= $where;
                        $result = $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);

                        $dscEtiqueta .= $vlrFrac['tipoQuebra'] . "|" . $this->getCodQuebra($tipoFracao, $vlrFrac['tipoQuebra']) . ";";

                        if (!empty($result[0]['COD_ETIQUETA_MAE']))
                            $verificaFrac = true;
                        else
                            break;
                    }

                    if ($verificaFrac)
                        $codEtiquetaMae = $result[0]['COD_ETIQUETA_MAE'];
                    else
                        $codEtiquetaMae = $EtiquetaMaeRepo->gerarEtiquetaMae($quebras, $tipoFracao, $idExpedicao, $dscEtiqueta);
                } else {
                    $naofracionados = $vlr['frac'];

                    foreach ($naofracionados as $chvNFrac => $vlrNFrac) {
                        $verificaNFrac = false;

                        $sql = "select E.COD_ETIQUETA_MAE from
                                ETIQUETA_MAE E
                                INNER JOIN ETIQUETA_MAE_QUEBRA EQ ON (E.COD_ETIQUETA_MAE=EQ.COD_ETIQUETA_MAE)
                            WHERE E.COD_EXPEDICAO=" . $idExpedicao;

                        $codQuebra = $this->getCodQuebra($tipoFracao, $vlrNFrac['tipoQuebra']);
                        if (empty($codQuebra)) {
                            $codQuebra = " is NULL";
                        } else if ($codQuebra == "NULL") {
                            $codQuebra = " is NULL";
                        } else {
                            $codQuebra = "=" . $codQuebra;
                        }

                        $where = " AND EQ.TIPO_FRACAO='NAOFRACIONADOS' AND EQ.COD_QUEBRA" . $codQuebra . " AND EQ.IND_TIPO_QUEBRA='" . $vlrNFrac['tipoQuebra'] . "'";

                        $sql .= $where;
                        $dscEtiqueta .= $vlrNFrac['tipoQuebra'] . "|" . $this->getCodQuebra($tipoFracao, $vlrNFrac['tipoQuebra']) . ";";

                        $result = $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);

                        if (!empty($result[0]['COD_ETIQUETA_MAE']))
                            $verificaNFrac = true;
                        else
                            break;
                    }

                    if ($verificaNFrac)
                        $codEtiquetaMae = $result[0]['COD_ETIQUETA_MAE'];
                    else
                        $codEtiquetaMae = $EtiquetaMaeRepo->gerarEtiquetaMae($quebras, $tipoFracao, $idExpedicao, $dscEtiqueta);
                }
            }
        } else {
            $codEtiquetaMae = null;
        }

        return $codEtiquetaMae;
    }

    public function getPracaByCliente($idCliente) {
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

    public function getQtdMapasPendentesImpressao($codMapa) {
        $SQL = "SELECT COUNT(COD_MAPA_SEPARACAO) as QTD
                  FROM MAPA_SEPARACAO
                 WHERE COD_STATUS = 522
                   AND COD_MAPA_SEPARACAO IN ($codMapa)";
        $result = $this->getEntityManager()->getConnection()->query($SQL)->fetch(\PDO::FETCH_ASSOC);
        if (count($result) > 0) {
            return $result['QTD'];
        } else {
            return 0;
        }
    }

    public function getQtdMapasPendentesImpressaoByExpedicao($codExpedicao) {
        $SQL = "SELECT COUNT(COD_MAPA_SEPARACAO) as QTD
                  FROM MAPA_SEPARACAO
                 WHERE COD_STATUS = 522
                   AND COD_EXPEDICAO IN ($codExpedicao)";
        $result = $this->getEntityManager()->getConnection()->query($SQL)->fetch(\PDO::FETCH_ASSOC);
        if (count($result) > 0) {
            return $result['QTD'];
        } else {
            return 0;
        }
    }

    public function getQtdEtiquetasPendentesImpressao($codExpedicao) {
        $SQL = "SELECT COUNT(COD_ETIQUETA_SEPARACAO) as QTD
                  FROM ETIQUETA_SEPARACAO ES
                  LEFT JOIN PEDIDO P ON P.COD_PEDIDO = ES.COD_PEDIDO
                  LEFT JOIN CARGA C ON C.COD_CARGA = P.COD_CARGA
                 WHERE COD_STATUS = 522
                   AND C.COD_EXPEDICAO = " . $codExpedicao;
        $result = $this->getEntityManager()->getConnection()->query($SQL)->fetch(\PDO::FETCH_ASSOC);
        if (count($result) > 0) {
            return $result['QTD'];
        } else {
            return 0;
        }
    }

    public function getUrlMobileByCodBarras($codBarras) {
        $codBarras = (float) $codBarras;
        $tipoEtiqueta = null;
        /** @var \Wms\Domain\Entity\Expedicao\ModeloSeparacaoRepository $modeloSeparacaoRepo */
        $modeloSeparacaoRepo = $this->getEntityManager()->getRepository('wms:Expedicao\ModeloSeparacao');

        if (strlen($codBarras) > 2) {
            $arrPrefxEtiquetaSeparacao = array("10","39","68","69");
            $codBarraPrefix = substr($codBarras, 0, 2);
            if (in_array($codBarraPrefix, $arrPrefxEtiquetaSeparacao)) {
                $tipoEtiqueta = EtiquetaSeparacao::PREFIXO_ETIQUETA_SEPARACAO;
            }
            if ($codBarraPrefix == "11") {
                $tipoEtiqueta = EtiquetaSeparacao::PREFIXO_ETIQUETA_MAE;
            }
            if ($codBarraPrefix == "12") {
                $tipoEtiqueta = EtiquetaSeparacao::PREFIXO_MAPA_SEPARACAO;
            }
            if ($codBarraPrefix == "13") {
                $tipoEtiqueta = EtiquetaSeparacao::PREFIXO_ETIQUETA_VOLUME;
            }
            if ($codBarraPrefix == '14') {
                $tipoEtiqueta = EtiquetaSeparacao::PREFIXO_ETIQUETA_EMBALADO;
            }
        }

        $volumePatrimonioRepo = $this->getEntityManager()->getRepository('wms:Expedicao\VolumePatrimonio');
        $volumePatrimonioEn = $volumePatrimonioRepo->find($codBarras);
        if (!empty($volumePatrimonioEn)) {
            $tipoEtiqueta = EtiquetaSeparacao::PREFIXO_ETIQUETA_VOLUME;
        }

        if ($tipoEtiqueta == EtiquetaSeparacao::PREFIXO_ETIQUETA_SEPARACAO) {
            //ETIQUETA DE SEPARAÇÃO
            /** @var Expedicao\EtiquetaSeparacao $etiquetaSeparacao */
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
                switch ($etiquetaSeparacao->getStatus()->getId()) {
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
                        $placa = $etiquetaSeparacao->getPedido()->getCarga()->getPlacaCarga();
                        $carga = $etiquetaSeparacao->getPedido()->getCarga()->getCodCargaExterno();
                        $idStatus = $expedicao->getStatus()->getId();

                        if ($idStatus == Expedicao::STATUS_PARCIALMENTE_FINALIZADO) {
                            $idFilialExterno = $etiquetaSeparacao->getPedido()->getPontoTransbordo();
                            $filialEn = $this->getEntityManager()->getRepository("wms:Filial")->findOneBy(array('codExterno' => $idFilialExterno));
                            if ($filialEn == null) {
                                throw new \Exception("Nenhuma filial encontrada com o código " . $idFilialExterno);
                            }

                            if ($filialEn->getIndRecTransbObg() == "S") {
                                $operacao = "Recebimento de Transbordo";
                                $url = "/mobile/recebimento-transbordo/ler-codigo-barras/idExpedicao/" . $idExpedicao;
                            } else {
                                $operacao = "Expedição de Transbordo";
                                $url = "/mobile/expedicao/ler-codigo-barras/idExpedicao/$idExpedicao/placa/$placa";
                            }
                            return array('operacao' => $operacao, 'url' => $url, 'expedicao' => $idExpedicao, 'placa' => $placa, 'carga' => $carga, 'parcialmenteFinalizado' => true);
                        }
                        if ($idStatus == Expedicao::STATUS_FINALIZADO) {
                            throw new \Exception("Expedição Finalizada");
                        }
                    case EtiquetaSeparacao::STATUS_ETIQUETA_GERADA:
                        $idExpedicao = $etiquetaSeparacao->getPedido()->getCarga()->getExpedicao()->getId();

                        //OBTEM O MODELO DE SEPARACAO VINCULADO A EXPEDICAO
                        $modeloSeparacao = $modeloSeparacaoRepo->getModeloSeparacao($idExpedicao);

                        if ($modeloSeparacao == null)
                            throw new \Exception("Modelo de Separação não encontrado");

                        $embalagem = $etiquetaSeparacao->getProdutoEmbalagem();
                        $embalado = false;
                        if ($embalagem != null) {
                            if ($modeloSeparacao->getTipoDefaultEmbalado() == "P") {
                                if ($embalagem->getEmbalado() == "S") {
                                    $embalado = true;
                                }
                            } else {
                                $embalagens = $etiquetaSeparacao->getProduto()->getEmbalagens();
                                foreach ($embalagens as $emb) {
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
                                $idCliente = $etiquetaSeparacao->getPedido()->getPessoa()->getCodClienteExterno();
                                $idTipoVolume = $idCliente;
                            } else {
                                $idCarga = $etiquetaSeparacao->getPedido()->getCarga()->getCodCargaExterno();
                                $idTipoVolume = $idCarga;
                            }

                            $operacao = "Conferencia de Embalados";
                            $url = "/mobile/volume-patrimonio/ler-codigo-barra-volume/idExpedicao/$idExpedicao/idTipoVolume/$idTipoVolume";
                            return array('operacao' => $operacao, 'url' => $url, 'expedicao' => $idExpedicao, 'carga' => $carga, 'parcialmenteFinalizado' => false);
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
                            $placa = $etiquetaSeparacao->getPedido()->getCarga()->getPlacaCarga();
                            $carga = $etiquetaSeparacao->getPedido()->getCarga()->getCodCargaExterno();
                            $operacao = "Expedição de Transbordo";
                            $url = "/mobile/expedicao/ler-codigo-barras/idExpedicao/$idExpedicao/placa/$placa";

                            return array('operacao' => $operacao, 'url' => $url, 'expedicao' => $idExpedicao, 'placa' => $placa, 'carga' => $carga, 'parcialmenteFinalizado' => true);
                        }
                        break;
                    case EtiquetaSeparacao::STATUS_RECEBIDO_TRANSBORDO:
                        $idExpedicao = $etiquetaSeparacao->getPedido()->getCarga()->getExpedicao()->getId();
                        $placa = $etiquetaSeparacao->getPedido()->getCarga()->getPlacaCarga();
                        $carga = $etiquetaSeparacao->getPedido()->getCarga()->getCodCargaExterno();
                        $operacao = "Expedição de Transbordo";
                        $url = "/mobile/expedicao/ler-codigo-barras/idExpedicao/$idExpedicao/placa/$placa";

                        return array('operacao' => $operacao, 'url' => $url, 'expedicao' => $idExpedicao, 'placa' => $placa, 'carga' => $carga, 'parcialmenteFinalizado' => true);
                        break;
                }
            }


            return array('operacao' => $operacao, 'url' => $url, 'expedicao' => $idExpedicao, 'parcialmenteFinalizado' => false);
        }
        elseif ($tipoEtiqueta == EtiquetaSeparacao::PREFIXO_ETIQUETA_MAE) {
            //ETIQUETA MÃE
            $etiquetaMae = $this->getEntityManager()->getRepository("wms:Expedicao\EtiquetaMae")->find($codBarras);
            if ($etiquetaMae == null)
                throw new \Exception("Nenhuma etiqueta mãe encontrada com este código de barras $codBarras");

            $etiquetas = $this->getEntityManager()->getRepository("wms:Expedicao\EtiquetaSeparacao")->findBy(array('codEtiquetaMae' => $codBarras));

            $embalado = false;
            $idCliente = 0;
            $idCarga = 0;
            $idExpedicao = 0;
            foreach ($etiquetas as $etiqueta) {
                $idCliente = $etiqueta->getPedido()->getPessoa()->getCodClienteExterno();
                $idCarga = $etiqueta->getPedido()->getCarga()->getId();
                $idExpedicao = $etiqueta->getPedido()->getCarga()->getExpedicao()->getId();

                //OBTEM O MODELO DE SEPARACAO VINCULADO A EXPEDICAO
                $modeloSeparacao = $modeloSeparacaoRepo->getModeloSeparacao($idExpedicao);

                if ($modeloSeparacao == null)
                    throw new \Exception("Modelo de Separação não encontrado");

                $embalagem = $etiqueta->getProdutoEmbalagem();
                $embalado = false;
                if ($embalagem != null) {
                    if ($modeloSeparacao->getTipoDefaultEmbalado() == "P") {
                        if ($embalagem->getEmbalado() == "S") {
                            $embalado = true;
                        }
                    } else {
                        $embalagens = $etiqueta->getProduto()->getEmbalagens();
                        foreach ($embalagens as $emb) {
                            if ($emb->getIsPadrao() == "S") {
                                if ($embalagem->getQuantidade() < $emb->getQuantidade()) {
                                    $embalado = true;
                                }
                                break;
                            }
                        }
                    }
                }
                if ($embalado == true)
                    break;
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
            return array('operacao' => $operacao, 'url' => $url, 'expedicao' => $idExpedicao);
        }
        elseif ($tipoEtiqueta == EtiquetaSeparacao::PREFIXO_MAPA_SEPARACAO) {
            //MAPA DE SEPARAÇÃO
            $mapaSeparacao = $this->getEntityManager()->find('wms:Expedicao\MapaSeparacao', $codBarras);
            if (empty($mapaSeparacao))
                throw new \Exception("Nenhum mapa de separação encontrado com o códgo " . $codBarras);
            $idExpedicao = $mapaSeparacao->getExpedicao()->getId();
            $operacao = "Conferencia do Mapa cód. $codBarras";
            $url = "/mobile/expedicao/ler-produto-mapa/idMapa/$codBarras/idExpedicao/$idExpedicao";
            return array('operacao' => $operacao, 'url' => $url, 'expedicao' => $idExpedicao);
        }
        elseif ($tipoEtiqueta == EtiquetaSeparacao::PREFIXO_ETIQUETA_EMBALADO) {
            $mapaSeparacaoEmbalado = $this->getEntityManager()->find('wms:Expedicao\MapaSeparacaoEmbalado', $codBarras);
            if (empty($mapaSeparacaoEmbalado))
                throw new \Exception("Nenhum volume embalado encontrado com o códgo " . $codBarras);
            $idMapa = $mapaSeparacaoEmbalado->getMapaSeparacao()->getId();
            $idExpedicao = $mapaSeparacaoEmbalado->getMapaSeparacao()->getExpedicao()->getId();
            $operacao = "Conferencia dos volumes embalados do Mapa cód. $idMapa";
            $url = "/mobile/expedicao/ler-embalados-mapa/idEmbalado/$codBarras/expedicao/$idExpedicao/idMapa/$idMapa";
            return array('operacao' => $operacao, 'url' => $url, 'expedicao' => $idExpedicao);
        }
        elseif ($tipoEtiqueta == EtiquetaSeparacao::PREFIXO_ETIQUETA_VOLUME) {
            //ETIQUETA DE VOLUME
            $volumeRepo = $this->getEntityManager()->getRepository("wms:Expedicao\VolumePatrimonio");
            $volumeEn = $volumeRepo->find($codBarras);
            if ($volumeEn == null)
                throw new \Exception("Nenhum volume patrimonio encontrado com o códgo " . $codBarras);
            $idExpedicao = $volumeRepo->getExpedicaoByVolume($codBarras, 'arr');
            if (is_array($idExpedicao)) {
                $idExpedicao = $idExpedicao[0]['expedicao'];
            } else {
                throw new \Exception("Nenhuma expedição com o volume " . $codBarras);
            }

            $operacao = "Conferencia dos volumes no box";
            $url = "/mobile/volume-patrimonio/ler-codigo-barra-volume/idExpedicao/$idExpedicao/box/1";
            return array('operacao' => $operacao, 'url' => $url, 'expedicao' => $idExpedicao);
        }

        throw new \Exception("Código de barras invalido");
    }

    public function qtdTotalVolumePatrimonio($idExpedicao) {
        $sql = $this->_em->createQueryBuilder()
                ->select('COUNT(DISTINCT evp.volumePatrimonio) as qtdTotal')
                ->from('wms:Expedicao\ExpedicaoVolumePatrimonio', 'evp')
                ->where("evp.expedicao = $idExpedicao");

        return $sql->getQuery()->getResult();
    }

    public function qtdConferidaVolumePatrimonio($idExpedicao) {
        $sql = $this->_em->createQueryBuilder()
                ->select('COUNT(DISTINCT evp.volumePatrimonio) as qtdConferida')
                ->from('wms:Expedicao\ExpedicaoVolumePatrimonio', 'evp')
                ->where("evp.expedicao = $idExpedicao AND evp.dataConferencia is not null");

        return $sql->getQuery()->getResult();
    }

    public function getPedidosByParams($parametros, $idDepositoLogado = null) {

        $where = "";
        $orderBy = " ORDER BY P.COD_PEDIDO";
        if (isset($idDepositoLogado)) {
            $where .= ' AND P.CENTRAL_ENTREGA = ' . $idDepositoLogado;
        }

        if (is_array($parametros['centrais'])) {
            $central = implode(',', $parametros['centrais']);
            $where .= " AND ( P.CENTRAL_ENTREGA in(" . $central . ") OR P.PONTO_TRANSBORDO in(" . $central . ") )";
        }

        if (isset($parametros['placa']) && !empty($parametros['placa'])) {
            $where .= " AND E.DSC_PLACA_EXPEDICAO = '" . $parametros['placa'] . "'";
        }

        if (isset($parametros['dataInicial1']) && (!empty($parametros['dataInicial1']))) {
            $where .= " AND E.DTH_INICIO >= TO_DATE('" . $parametros['dataInicial1'] . " 00:00', 'DD-MM-YYYY HH24:MI')";
        }

        if (isset($parametros['dataInicial2']) && (!empty($parametros['dataInicial2']))) {
            $where .= " AND E.DTH_INICIO <= TO_DATE('" . $parametros['dataInicial2'] . " 23:59', 'DD-MM-YYYY HH24:MI')";
        }

        if (isset($parametros['dataFinal1']) && (!empty($parametros['dataFinal1']))) {
            $where .= " AND E.DTH_FINALIZACAO >= TO_DATE('" . $parametros['dataFinal1'] . " 00:00', 'DD-MM-YYYY HH24:MI')";
        }

        if (isset($parametros['dataFinal2']) && (!empty($parametros['dataFinal2']))) {
            $where .= " AND E.DTH_FINALIZACAO <= TO_DATE('" . $parametros['dataFinal2'] . " 23:59', 'DD-MM-YYYY HH24:MI')";
        }

        if (isset($parametros['status']) && (!empty($parametros['status']))) {
            $where .= " AND S.COD_SIGLA = " . $parametros['status'] . "";
        }
        if (isset($parametros['idExpedicao']) && !empty($parametros['idExpedicao'])) {
            $where = " AND E.COD_EXPEDICAO = " . $parametros['idExpedicao'] . "";
        }

        if (isset($parametros['pedido']) && !empty($parametros['pedido'])) {
            $where = " AND P.COD_PEDIDO = '" . $parametros['pedido'] . "'";
        }

        if (isset($parametros['codCargaExterno']) && !empty($parametros['codCargaExterno'])) {
            $where = " AND C.COD_CARGA_EXTERNO = " . $parametros['codCargaExterno'] . "";
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
               PROD.QTD as QTD_PRODUTOS,
               PESO.NUM_CUBAGEM,
               PESO.NUM_PESO
          FROM PEDIDO P
          LEFT JOIN PESSOA PES ON P.COD_PESSOA = PES.COD_PESSOA
          LEFT JOIN CLIENTE CLI ON CLI.COD_PESSOA = PES.COD_PESSOA
          LEFT JOIN CARGA C ON C.COD_CARGA = P.COD_CARGA
          LEFT JOIN EXPEDICAO E ON E.COD_EXPEDICAO = C.COD_EXPEDICAO
          LEFT JOIN SIGLA S ON S.COD_SIGLA = E.COD_STATUS
          LEFT JOIN (SELECT PP.COD_PEDIDO,
                            SUM ((PP.QUANTIDADE - NVL(PP.QTD_CORTADA,0)) * NVL(PESO.NUM_PESO,0)) as NUM_PESO,
                            SUM ((PP.QUANTIDADE - NVL(PP.QTD_CORTADA,0)) * NVL(PESO.NUM_CUBAGEM,0)) as NUM_CUBAGEM
                       FROM PEDIDO_PRODUTO PP
                       LEFT JOIN PRODUTO_PESO PESO ON PESO.COD_PRODUTO = PP.COD_PRODUTO
                                                  AND PESO.DSC_GRADE = PP.DSC_GRADE
                       GROUP BY PP.COD_PEDIDO) PESO ON PESO.COD_PEDIDO = P.COD_PEDIDO
          LEFT JOIN (SELECT COUNT(*) as QTD, COD_PEDIDO FROM PEDIDO_PRODUTO GROUP BY COD_PEDIDO) PROD ON PROD.COD_PEDIDO = P.COD_PEDIDO
          LEFT JOIN (SELECT COUNT(COD_ETIQUETA_SEPARACAO) as QTD, COD_PEDIDO FROM ETIQUETA_SEPARACAO GROUP BY COD_PEDIDO) ETQ ON ETQ.COD_PEDIDO = P.COD_PEDIDO
          WHERE 1 = 1";

        $result = $this->getEntityManager()->getConnection()->query($SQL . $where . $orderBy)->fetchAll(\PDO::FETCH_ASSOC);
        return $result;
    }

    public function getPedidosParaCorteByParams($params) {
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

        if (isset($params['idExpedicao']) && ($params['idExpedicao'] != null)) {
            $idExpedicao = $params['idExpedicao'];
            $SQL .= " AND C.COD_EXPEDICAO = $idExpedicao ";
        }

        if (isset($params['clientes']) && ($params['clientes'] != null)) {
            $clientes = implode(',', $params['clientes']);
            $SQL .= " AND CLI.COD_CLIENTE_EXTERNO IN ($clientes) ";
        }

        if (isset($params['pedidos']) && ($params['pedidos'] != null)) {
            $pedidos = implode(',', $params['pedidos']);
            $SQL .= " AND P.COD_PEDIDO IN ($pedidos) ";
        }

        if (isset($params['idMapa']) && ($params['idMapa'] != null)) {
            $idMapa = $params['idMapa'];
            $SQL .= " AND P.COD_PEDIDO IN ( SELECT PP.COD_PEDIDO FROM MAPA_SEPARACAO_PRODUTO MSP
                                              LEFT JOIN PEDIDO_PRODUTO PP ON PP.COD_PEDIDO_PRODUTO = MSP.COD_PEDIDO_PRODUTO
                                             WHERE MSP.COD_MAPA_SEPARACAO = $idMapa) ";
        }

        $SQLWhereProdutos = "";
        if (isset($params['idProduto']) && ($params['idProduto'] != null)) {
            $idProduto = $params['idProduto'];
            $SQLWhereProdutos .= " AND PP.COD_PRODUTO = '$idProduto' ";
        }
        if (isset($params['grade']) && ($params['grade'] != null)) {
            $grade = $params['grade'];
            $SQLWhereProdutos .= " AND PP.DSC_GRADE = '$grade' ";
        }

        if (isset($idProduto) OR ( isset($grade))) {
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
          WHERE COD_PEDIDO = '$idPedido'";

        if ($params['pedidoCompleto'] == false) {
            if (isset($params['idProduto']) && ($params['idProduto'] != null)) {
                $idProduto = $params['idProduto'];
                $SQL .= " AND PP.COD_PRODUTO = '$idProduto' ";
            }
            if (isset($params['grade']) && ($params['grade'] != null)) {
                $grade = $params['grade'];
                $SQL .= " AND PP.DSC_GRADE = '$grade' ";
            }
            if (isset($params['idMapa']) && ($params['idMapa'] != null)) {
                $idMapa = $params['idMapa'];
                $SQL .= " AND MSP.COD_MAPA_SEPARACAO = $idMapa ";
            }
        }

        $result = $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);
        return $result;
    }

    public function diluirCorte($expedicoes, $itensSemEstoque) {
        $arrResult = array();

        foreach ($itensSemEstoque as $item) {
            $sql = "select p.cod_pedido, pp.cod_produto, pp.dsc_grade, pp.quantidade, pp.qtd_cortada 
                from expedicao e
                inner join carga c on c.cod_expedicao = e.cod_expedicao
                inner join pedido p on p.cod_carga = c.cod_carga
                inner join pedido_produto pp on pp.cod_pedido = p.cod_pedido
                where e.cod_expedicao in ($expedicoes) and (pp.cod_produto = '$item[CODIGO]' and dsc_grade = '$item[GRADE]')";

            $result = $this->_em->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
            $divisor = count($result);
            $resto = $item['SALDO_FINAL'] % $divisor;
            $qtdCortar = ($item['SALDO_FINAL'] - $resto) / $divisor;

            foreach ($result as $pedido) {
                if ($pedido === end($result)) {
                    $qtdCortar += $resto;
                }
                $arrResult[$pedido['COD_PEDIDO']][$pedido['COD_PRODUTO']][$pedido['DSC_GRADE']] = ($qtdCortar * -1);
            }
        }

        return $arrResult;
    }

    public function executaCortePedido($cortes, $motivo, $corteAutomatico = null) {
        foreach ($cortes as $codPedido => $produtos) {
            foreach ($produtos as $codProduto => $grades) {
                foreach ($grades as $grade => $quantidade) {
                    if (!($quantidade > 0))
                        continue;
                    $this->cortaPedido($codPedido,null, $codProduto, $grade, $quantidade, $motivo, $corteAutomatico);
                }
            }
        }
    }

    /**
     * @param $codPedido
     * @param $pedidoProdutoEn ExpedicaoEntity\PedidoProduto
     * @param $codProduto
     * @param $grade
     * @param $qtdCortar
     * @param $motivo
     * @param null $corteAutomatico
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Exception
     */
    public function cortaPedido($codPedido, $pedidoProdutoEn, $codProduto, $grade, $qtdCortar, $motivo, $corteAutomatico = null) {

        /** @var Expedicao\AndamentoRepository $expedicaoAndamentoRepo */
        $expedicaoAndamentoRepo = $this->getEntityManager()->getRepository('wms:Expedicao\Andamento');
        $reservaEstoqueProdutoRepo = $this->getEntityManager()->getRepository('wms:Ressuprimento\ReservaEstoqueProduto');
        $mapaSeparacaoPedidoRepo = $this->getEntityManager()->getRepository('wms:Expedicao\MapaSeparacaoPedido');
        $mapaSeparacaoProdutoRepo = $this->getEntityManager()->getRepository('wms:Expedicao\MapaSeparacaoProduto');
        $mapaConferenciaRepo = $this->getEntityManager()->getRepository('wms:Expedicao\MapaSeparacaoConferencia');

        if (empty($pedidoProdutoEn)) {
            /** @var Expedicao\PedidoProdutoRepository $pedidoProdutoRepo */
            $pedidoProdutoRepo = $this->getEntityManager()->getRepository('wms:Expedicao\PedidoProduto');
            /** @var Expedicao\PedidoProduto $entidadePedidoProduto */
            $pedidoProdutoEn = $pedidoProdutoRepo->findOneBy(array('codPedido' => $codPedido,
                'codProduto' => $codProduto,
                'grade' => $grade));
        }

        $qtdCortada = $pedidoProdutoEn->getQtdCortada();
        $qtdPedido = $pedidoProdutoEn->getQuantidade();

        //TRAVA PARA GARANTIR QUE NÃO CORTE QUANTIDADE MAIOR QUE TEM NO PEDIDO
        if (Math::compare(Math::adicionar($qtdCortar, $qtdCortada), $qtdPedido, '>')) {
            $qtdCortar = Math::subtrair($qtdPedido, $qtdCortada);
        }

        $produtoEn = $pedidoProdutoEn->getProduto();

        if ($produtoEn->getValidade() == "N") {
            $ordenacao = "TO_NUMBER(REP.QTD_RESERVADA * -1) ASC";
        } else {
            $ordenacao = "TO_NUMBER(REE.COD_RESERVA_ESTOQUE) DESC";
        }

        $SQL = "SELECT DISTINCT REE.COD_RESERVA_ESTOQUE ID, REP.QTD_RESERVADA QTD
                  FROM RESERVA_ESTOQUE_EXPEDICAO REE
                  LEFT JOIN RESERVA_ESTOQUE_PRODUTO REP ON REE.COD_RESERVA_ESTOQUE = REP.COD_RESERVA_ESTOQUE
                 WHERE REE.COD_PEDIDO = '$codPedido'
                   AND REP.COD_PRODUTO = '$codProduto'
                   AND REP.DSC_GRADE = '$grade'
                 ORDER BY $ordenacao";
        $result = $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);

        $valToNext = 0;
        $qtdRemoveReserva = $qtdCortar;
        foreach ($result as $item) {
            $check = Math::adicionar($qtdRemoveReserva, $item['QTD']);
            $entityReservaEstoqueProduto = $reservaEstoqueProdutoRepo->findBy(array('reservaEstoque' => $item['ID']));
            foreach ($entityReservaEstoqueProduto as $reservaEstoqueProduto) {
                if (Math::compare($check, 0, '>=')) {
                    $this->getEntityManager()->remove($reservaEstoqueProduto);
                    $valToNext = $check;
                } else {
                    $reservaEstoqueProduto->setQtd($check);
                    $this->getEntityManager()->persist($reservaEstoqueProduto);
                    $valToNext = 0;
                }
            }
            $qtdRemoveReserva = $valToNext;
            if ($qtdRemoveReserva <= 0) {
                break;
            }
        }

        //Seta na pedido_produto a quantidade cortada baseada na quantia já cortada mais a nova qtd
        $pedidoProdutoEn->setQtdCortada(Math::adicionar($qtdCortada, $qtdCortar));
        if ($corteAutomatico == 'S') {
            $pedidoProdutoEn->setQtdCortadoAutomatico($pedidoProdutoEn->getQtdCortadoAutomatico() + $qtdCortar);
        }
        $this->getEntityManager()->persist($pedidoProdutoEn);

        //Seta na mapa_separacao_pedido a quantidade cortada baseada na quantia já cortada mais a nova qtd
        /** @var Expedicao\MapaSeparacaoPedido $mapaSeparacaoPedido */
        $mapaSeparacaoPedido = $mapaSeparacaoPedidoRepo->findOneBy(array("pedidoProduto" => $pedidoProdutoEn));
        if (!empty($mapaSeparacaoPedido)) {
            $mapaSeparacaoPedido->addCorte($qtdCortar);
            $this->getEntityManager()->persist($mapaSeparacaoPedido);
        }

        if (!empty($mapaSeparacaoPedido)) {
            $entidadeMapaProduto = $mapaSeparacaoProdutoRepo->findBy(array('mapaSeparacao' => $mapaSeparacaoPedido->getMapaSeparacao(),
                'codProduto' => $codProduto,
                'dscGrade' => $grade));

            if (!empty($entidadeMapaProduto)) {
                /** @var Expedicao\MapaSeparacaoProduto $itemMapa */
                foreach ($entidadeMapaProduto as $itemMapa) {
                    $qtdCortadaMapa = $itemMapa->getQtdCortado();
                    $qtdSeparar = Math::multiplicar($itemMapa->getQtdEmbalagem(), $itemMapa->getQtdSeparar());
                    if (Math::compare($qtdCortadaMapa, $qtdSeparar, '<')) {
                        $qtdDisponivelDeCorte = Math::subtrair($qtdSeparar, $qtdCortadaMapa);
                        if (Math::compare($qtdDisponivelDeCorte, $qtdCortar, '>=')) {
                            $itemMapa->setQtdCortado(Math::adicionar($qtdCortar, $qtdCortadaMapa));
                            $qtdCortar = 0;
                        } else {
                            $itemMapa->setQtdCortado($itemMapa->getQtdSeparar());
                            $qtdCortar = Math::subtrair($qtdCortar, $itemMapa->getQtdSeparar());
                        }
                        $result = Math::subtrair($qtdSeparar, $itemMapa->getQtdCortado());
                        if (empty($result)) {
                            $mapaConferenciaEn = $mapaConferenciaRepo->findBy(array('codMapaSeparacao' => $itemMapa->getMapaSeparacao()->getId(), 'codProduto' => $codProduto, 'dscGrade' => $grade));
                            foreach ($mapaConferenciaEn as $conferencia) {
                                $this->getEntityManager()->remove($conferencia);
                            }
                        }
                        $this->getEntityManager()->persist($itemMapa);
                    }
                    if (empty($qtdCortar)) {
                        break;
                    }
                }
            }
        }

        $expedicaoEn = $pedidoProdutoEn->getPedido()->getCarga()->getExpedicao();
        $observacao = "Item $codProduto - $grade do pedido $codPedido teve $qtdCortar item(ns) cortado(s). Motivo: $motivo";
        $expedicaoAndamentoRepo->save($observacao, $expedicaoEn->getId(), false, false);

        $this->getEntityManager()->flush();

        $SQL = "SELECT * 
                  FROM PEDIDO_PRODUTO PP
                 WHERE COD_PEDIDO = '$codPedido' 
                   AND PP.QUANTIDADE > NVL(PP.QTD_CORTADA,0) ";
        $ppSemCortes = $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);
        if (count($ppSemCortes) == 0) {
            /** @var \Wms\Domain\Entity\Expedicao\PedidoRepository $pedidoRepository */
            $pedidoRepository = $this->_em->getRepository('wms:Expedicao\Pedido');
            $pedidoRepository->cancelar($codPedido,false);
        }

    }

    public function getProdutosExpedicaoCorte($idPedido) {
        $SQL = "SELECT PP.COD_PRODUTO,
                       PP.DSC_GRADE,
                       PROD.DSC_PRODUTO,
                       SUM(PP.QUANTIDADE) as QTD,
                       SUM(PP.QTD_CORTADA) as QTD_CORTADA,
                       PP.COD_PEDIDO
                  FROM PEDIDO_PRODUTO PP
                  LEFT JOIN PEDIDO P ON P.COD_PEDIDO = PP.COD_PEDIDO
                  LEFT JOIN CARGA C ON C.COD_CARGA  = P.COD_CARGA
                  LEFT JOIN PRODUTO PROD ON PROD.COD_PRODUTO = PP.COD_PRODUTO AND PROD.DSC_GRADE = PP.DSC_GRADE
                 WHERE PP.COD_PEDIDO = '$idPedido'
                 GROUP BY PP.COD_PRODUTO, PP.DSC_GRADE, PROD.DSC_PRODUTO, PP.COD_PEDIDO
                 ORDER BY COD_PRODUTO, DSC_GRADE";
        $result = $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);
        return $result;
    }

    public function getClienteByExpedicao() {
        $sql = $this->getEntityManager()->createQueryBuilder()
                ->select('')
                ->from('wms:Expedicao', 'e')
                ->innerJoin('wms:Expedicao\Carga', 'c', 'WITH', 'c.codExpedicao = e.id')
                ->innerJoin('wms:Expedicao\Pedido', 'p', 'WITH', 'p.carga = c.id')
                ->innerJoin('wms:Pessoa', 'pe', 'WITH', 'pe.id = p.pessoa')
                ->where('e.codStatus <> ' . Expedicao::STATUS_FINALIZADO);

        return $sql->getQuery()->getResult();
    }

    public function getCargasFechadasByData($dataInicial, $dataFinal) {
        $SQL = " SELECT  C.COD_CARGA_EXTERNO,
                        C.DSC_PLACA_EXPEDICAO,
                        '' as NOM_MOTORISTA,
                        L.DSC_LINHA_ENTREGA,
                        NVL(SUM(NVL(PROD.NUM_PESO,0) * (PP.QUANTIDADE - NVL(PP.QTD_CORTADA,0))),0) as NUM_PESO,
                        NVL(SUM(NVL(PROD.NUM_CUBAGEM,0) * PP.QUANTIDADE - NVL(PP.QTD_CORTADA,0)),0) as NUM_CUBAGEM,
                        NVL(SUM(NVL(PP.VALOR_VENDA,0)),0) as VLR_CARGA,
                        NVL(SUM(PP.QUANTIDADE - NVL(PP.QTD_CORTADA,0)),0) as VOLUMES,
                        NVL(COUNT(DISTINCT(P.COD_PEDIDO)),0) as QTD_PEDIDOS,
                        NVL(COUNT(DISTINCT(P.COD_PESSOA)),0) as ENTREGAS,
                        TO_CHAR(E.DTH_FINALIZACAO,'DD/MM/YYYY HH24:MI') as DTH_FINALIZACAO
                  FROM CARGA C
                  LEFT JOIN PEDIDO P ON P.COD_CARGA = C.COD_CARGA
                  LEFT JOIN EXPEDICAO E ON E.COD_EXPEDICAO = C.COD_EXPEDICAO
                  LEFT JOIN PEDIDO_PRODUTO PP ON PP.COD_PEDIDO = P.COD_PEDIDO
                  LEFT JOIN PRODUTO_PESO PROD ON PROD.COD_PRODUTO = PP.COD_PRODUTO AND PROD.DSC_GRADE = PP.DSC_GRADE
                  LEFT JOIN (SELECT MAX(NUM_PESO), DSC_LINHA_ENTREGA, COD_CARGA FROM (
                                    SELECT SUM(PROD.NUM_PESO * PP.QUANTIDADE) as NUM_PESO, P.DSC_LINHA_ENTREGA, P.COD_CARGA
                                      FROM PEDIDO P
                                      LEFT JOIN PEDIDO_PRODUTO PP ON PP.COD_PEDIDO = P.COD_PEDIDO
                                      LEFT JOIN PRODUTO_PESO PROD ON PROD.COD_PRODUTO = PP.COD_PRODUTO AND PROD.DSC_GRADE = PP.DSC_GRADE
                                      GROUP BY P.DSC_LINHA_ENTREGA, P.COD_CARGA) GROUP BY COD_CARGA, DSC_LINHA_ENTREGA) L
                         ON L.COD_CARGA = C.COD_CARGA
                  WHERE 1 = 1
                  AND E.DTH_FINALIZACAO >= TO_DATE('$dataInicial','DD/MM/YYYY HH24:MI')
                  AND E.DTH_FINALIZACAO <= TO_DATE('$dataFinal','DD/MM/YYYY HH24:MI')
                  AND E.COD_STATUS IN (530,465)
                  GROUP BY C.COD_CARGA_EXTERNO, C.DSC_PLACA_EXPEDICAO, E.DTH_FINALIZACAO, L.DSC_LINHA_ENTREGA";

        $result = $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);
        return $result;
    }

    public function getSaidaEstoqueByExpedicao($idExpedicoes) {
        $params = array(
            'idExpedicoes' => $idExpedicoes,
            'reservaAtendida' => 'S',
            'statusExpedicoes' => Expedicao::STATUS_FINALIZADO
        );
        return $this->getMovimentacaoEstoqueExpedicaoByParams($params);
    }

    public function validaReservaSaidaCorretaByExpedicao($idExpedicoes) {
        $params = array(
            'idExpedicoes' => $idExpedicoes,
            'apenasDivergencias' => 'S',
            'reservaAtendida' => 'N'
        );
        if (count($this->getMovimentacaoEstoqueExpedicaoByParams($params)) > 0) {
            return false;
        }
        return true;
    }

    public function getMovimentacaoEstoqueExpedicaoByParams($params) {

        $sessao = new \Zend_Session_Namespace('deposito');
        $deposito = $this->_em->getReference('wms:Deposito', $sessao->idDepositoLogado);
        $central = $deposito->getFilial()->getCodExterno();

        $whereReserva = "";
        $whereFinal = "";

        if (isset($params['reservaAtendida'])) {
            $whereReserva .= " AND RE.IND_ATENDIDA = '" . $params['reservaAtendida'] . "'";
        }

        if (isset($params['apenasDivergencias'])) {
            $whereFinal .= " AND ((PP.QUANTIDADE - NVL(PP.QTD_CORTADA,0)) != NVL(R.RESERVA / NVL(PV.N_VOL, 1),0))";
        }

        if (isset($params['idExpedicoes'])) {
            $whereFinal .= " AND E.COD_EXPEDICAO IN (" . $params['idExpedicoes'] . ")";
        }

        if (isset($params['statusExpedicoes'])) {
            $whereFinal .= " AND AND E.COD_STATUS = " . $params['statusExpedicoes'] . "";
        }

        $SQL = "SELECT C.COD_EXPEDICAO,
                       TO_CHAR(E.DTH_INICIO,'DD/MM/YYYY HH24:MI:SS') as INICIO_EXPEDICAO,
                       TO_CHAR(E.DTH_FINALIZACAO,'DD/MM/YYYY HH24:MI:SS') as FIM_EXPEDICAO,
                       C.COD_CARGA_EXTERNO as CARGA,
                       P.COD_PEDIDO,
                       CL.COD_CLIENTE_EXTERNO as COD_CLIENTE,
                       CLI.NOM_PESSOA as CLIENTE,
                       PP.COD_PRODUTO,
                       NVL(PP.QUANTIDADE,0) as QTD_PEDIDO,
                       NVL(PP.QTD_CORTADA,0) as QTD_CORTE,
                       NVL(R.RESERVA / NVL(PV.N_VOL, 1),0) as QTD_SAIDA
                  FROM PEDIDO P
                  LEFT JOIN CLIENTE CL ON CL.COD_PESSOA = P.COD_PESSOA
                  LEFT JOIN PEDIDO_PRODUTO PP ON PP.COD_PEDIDO = P.COD_PEDIDO
                  LEFT JOIN CARGA C ON C.COD_CARGA = P.COD_CARGA
                  LEFT JOIN PESSOA CLI ON CLI.COD_PESSOA = P.COD_PESSOA
                  LEFT JOIN EXPEDICAO E ON E.COD_EXPEDICAO = C.COD_EXPEDICAO
                  LEFT JOIN (SELECT COUNT(COD_PRODUTO) AS N_VOL,COD_PRODUTO, DSC_GRADE 
                            FROM PRODUTO_VOLUME GROUP BY COD_PRODUTO, DSC_GRADE) PV 
                            ON PV.COD_PRODUTO = PP.COD_PRODUTO AND PV.DSC_GRADE = PV.DSC_GRADE
                  LEFT JOIN (SELECT COD_EXPEDICAO, COD_PEDIDO, COD_PRODUTO, DSC_GRADE, SUM(RESERVA) RESERVA
                          FROM ( SELECT REE.COD_EXPEDICAO, REE.COD_PEDIDO, REP.COD_PRODUTO, REP.DSC_GRADE, REP.QTD_RESERVADA *-1 as RESERVA
                                FROM RESERVA_ESTOQUE_EXPEDICAO REE
                               INNER JOIN RESERVA_ESTOQUE RE ON REE.COD_RESERVA_ESTOQUE = RE.COD_RESERVA_ESTOQUE
                               INNER JOIN RESERVA_ESTOQUE_PRODUTO REP ON REP.COD_RESERVA_ESTOQUE = RE.COD_RESERVA_ESTOQUE
                               WHERE 1 = 1
                               $whereReserva)
                               GROUP BY COD_EXPEDICAO, COD_PEDIDO, COD_PRODUTO, DSC_GRADE) R
                        ON R.COD_EXPEDICAO = E.COD_EXPEDICAO
                       AND R.COD_PEDIDO = PP.COD_PEDIDO
                       AND R.COD_PRODUTO = PP.COD_PRODUTO
                  WHERE 1 = 1
                        AND P.CENTRAL_ENTREGA = $central
                        $whereFinal
                  ORDER BY COD_EXPEDICAO, INICIO_EXPEDICAO, FIM_EXPEDICAO, C.COD_CARGA_EXTERNO, COD_CLIENTE_EXTERNO, COD_PEDIDO, COD_PRODUTO";
        $result = $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);
        return $result;
    }

    public function getCortePedido($expedicao) {
        $SQL = "SELECT 
                    PP.COD_PEDIDO AS PEDIDO, 
                    PR.COD_PRODUTO AS PRODUTO, 
                    PR.DSC_PRODUTO AS DESCRICAO, 
                    PR.DSC_GRADE AS GRADE, 
                    PP.QUANTIDADE AS QUANTIDADE, 
                    PP.QTD_CORTADO_AUTOMATICO AS QTD_CORTADA 
                FROM CARGA CG INNER JOIN
                PEDIDO P ON (CG.COD_CARGA = P.COD_CARGA) INNER JOIN
                PEDIDO_PRODUTO PP ON (P.COD_PEDIDO = PP.COD_PEDIDO) INNER JOIN
                PRODUTO PR ON (PR.COD_PRODUTO = PP.COD_PRODUTO)
                WHERE CG.COD_EXPEDICAO IN ($expedicao) AND PP.QTD_CORTADO_AUTOMATICO > 0
                ORDER BY PP.COD_PEDIDO, PR.COD_PRODUTO, PR.DSC_GRADE, PP.QUANTIDADE, PP.QTD_CORTADA";

        return $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getMapaSeparacaoCargasByExpedicao($codExpedicao, $codCarga = null)
    {
        $sql = $this->getEntityManager()->createQueryBuilder()
            ->select('ms.id codMapaSeparacao, c.id codCarga')
            ->from('wms:Expedicao\MapaSeparacao', 'ms')
            ->innerJoin('wms:Expedicao\MapaSeparacaoPedido', 'msp', 'WITH', 'msp.mapaSeparacao = ms.id')
            ->innerJoin('msp.pedidoProduto', 'pp')
            ->innerJoin('pp.pedido', 'p')
            ->innerJoin('p.carga', 'c')
            ->innerJoin('c.expedicao', 'e')
            ->where("e.id = $codExpedicao")
            ->groupBy('ms.id,c.id')
            ->orderBy('ms.id', 'ASC');
        if (isset($codCarga) && !empty($codCarga)) {
            $sql->andWhere("c.id = $codCarga");
        }
        return $sql->getQuery()->getResult();
    }

    public function getExpedicoesPD(){

        $SQL = "SELECT DISTINCT E.COD_EXPEDICAO
                     FROM ETIQUETA_SEPARACAO ES
                    INNER JOIN PEDIDO P ON P.COD_PEDIDO = ES.COD_PEDIDO
                    INNER JOIN CARGA C ON C.COD_CARGA = P.COD_CARGA
                    INNER JOIN EXPEDICAO E ON E.COD_EXPEDICAO = C.COD_EXPEDICAO
                    WHERE ES.DTH_SEPARACAO IS NULL AND ES.TIPO_SAIDA = 3 AND E.COD_STATUS IN (463,464)";
        return $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getEtiquetasPd($codExpedicao){
        $tipoSaida = ReservaEstoqueExpedicao::SAIDA_PULMAO_DOCA;
        $SQL = "   SELECT DISTINCT DE.DSC_DEPOSITO_ENDERECO 
                     FROM ETIQUETA_SEPARACAO ES
                    INNER JOIN PEDIDO P ON P.COD_PEDIDO = ES.COD_PEDIDO
                    INNER JOIN CARGA C ON C.COD_CARGA = P.COD_CARGA
                    INNER JOIN DEPOSITO_ENDERECO DE ON ES.COD_DEPOSITO_ENDERECO = DE.COD_DEPOSITO_ENDERECO
                    WHERE C.COD_EXPEDICAO = $codExpedicao AND ES.DTH_SEPARACAO IS NULL AND ES.TIPO_SAIDA = $tipoSaida";

        return $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getProdutosDescasadosExpedicao($expedicoes)
    {
        $sql = "SELECT DISTINCT
                       PP.COD_EXPEDICAO,
                       PP.COD_PRODUTO,
                       PP.DSC_GRADE,
                       PP.QTD_PEDIDO,
                       NVL(RE.QTD,0) as RESERVA_PICKING,
                       NVL(E.QTD,0) as ESTOQUE_PICKING,
                       NVL(E.QTD,0) + NVL(RE.QTD,0) - PP.QTD_PEDIDO as SALDO_PICKING,
                       NVL(EP.DSC_DEPOSITO_ENDERECO,'') as END_PULMAO,
                       NVL(EP.QTD_VOLUMES_CADASTRO,0) as QTD_VOL_CADASTRO,
                       NVL(EP.QTD_VOLUMES_ENDERECO,0) as QTD_VOL_ENDERECO
                  FROM (SELECT PP.COD_PRODUTO, PP.DSC_GRADE,
                               SUM(PP.QUANTIDADE) as QTD_PEDIDO,
                               C.COD_EXPEDICAO
                          FROM PEDIDO_PRODUTO PP
                         INNER JOIN PEDIDO P ON P.COD_PEDIDO = PP.COD_PEDIDO
                         INNER JOIN CARGA C ON C.COD_CARGA = P.COD_CARGA
                         WHERE C.COD_EXPEDICAO IN ($expedicoes)
                         GROUP BY PP.COD_PRODUTO, PP.DSC_GRADE, C.COD_EXPEDICAO) PP
                 INNER JOIN PRODUTO_VOLUME PV ON PV.COD_PRODUTO = PP.COD_PRODUTO AND PV.DSC_GRADE = PP.DSC_GRADE
                 LEFT JOIN (SELECT RE.COD_DEPOSITO_ENDERECO, REP.COD_PRODUTO_VOLUME,
                                   CASE WHEN SUM(QTD_RESERVADA)IS NULL THEN 0 ELSE SUM(QTD_RESERVADA) END AS QTD
                              FROM RESERVA_ESTOQUE RE
                              LEFT JOIN RESERVA_ESTOQUE_PRODUTO REP ON RE.COD_RESERVA_ESTOQUE = REP.COD_RESERVA_ESTOQUE
                             WHERE RE.IND_ATENDIDA = 'N' AND REP.COD_PRODUTO_VOLUME IS NOT NULL
                             GROUP BY RE.COD_DEPOSITO_ENDERECO, REP.COD_PRODUTO_VOLUME) RE
                        ON RE.COD_PRODUTO_VOLUME = PV.COD_PRODUTO_VOLUME
                       AND RE.COD_DEPOSITO_ENDERECO = PV.COD_DEPOSITO_ENDERECO
                 LEFT JOIN (SELECT E.COD_DEPOSITO_ENDERECO, E.COD_PRODUTO_VOLUME,
                                   SUM(E.QTD) as QTD
                              FROM ESTOQUE E
                             GROUP BY E.COD_DEPOSITO_ENDERECO, E.COD_PRODUTO_VOLUME) E
                        ON E.COD_PRODUTO_VOLUME = PV.COD_PRODUTO_VOLUME
                       AND E.COD_DEPOSITO_ENDERECO = PV.COD_DEPOSITO_ENDERECO
                 LEFT JOIN (SELECT E.COD_DEPOSITO_ENDERECO, DE.DSC_DEPOSITO_ENDERECO, E.COD_PRODUTO, E.DSC_GRADE, PV.COD_NORMA_PALETIZACAO,
                                   CAD.QTD_VOLUMES as QTD_VOLUMES_CADASTRO,
                                   COUNT(E.COD_PRODUTO_VOLUME) as QTD_VOLUMES_ENDERECO
                              FROM ESTOQUE E
                             INNER JOIN PRODUTO_VOLUME PV ON PV.COD_PRODUTO_VOLUME = E.COD_PRODUTO_VOLUME
                              LEFT JOIN DEPOSITO_ENDERECO DE ON DE.COD_DEPOSITO_ENDERECO = E.COD_DEPOSITO_ENDERECO
                              LEFT JOIN (SELECT COD_PRODUTO, DSC_GRADE, COD_NORMA_PALETIZACAO, COUNT(COD_PRODUTO_VOLUME) as QTD_VOLUMES
                                           FROM PRODUTO_VOLUME
                                          GROUP BY COD_PRODUTO, DSC_GRADE, COD_NORMA_PALETIZACAO) CAD
                                     ON CAD.COD_PRODUTO = PV.COD_PRODUTO
                                    AND CAD.DSC_GRADE = PV.DSC_GRADE
                                    AND CAD.COD_NORMA_PALETIZACAO = PV.COD_NORMA_PALETIZACAO
                             GROUP BY E.COD_DEPOSITO_ENDERECO, DE.DSC_DEPOSITO_ENDERECO, E.COD_PRODUTO, E.DSC_GRADE, PV.COD_NORMA_PALETIZACAO, CAD.QTD_VOLUMES) EP
                        ON EP.COD_NORMA_PALETIZACAO = PV.COD_NORMA_PALETIZACAO
                       AND EP.COD_DEPOSITO_ENDERECO <> NVL(PV.COD_DEPOSITO_ENDERECO,0)
                 WHERE 1 = 1
                   AND (NVL(E.QTD,0) + NVL(RE.QTD,0) - PP.QTD_PEDIDO) <=0
                   AND QTD_VOLUMES_CADASTRO <> QTD_VOLUMES_ENDERECO
                 ORDER BY PP.COD_PRODUTO, PP.DSC_GRADE, NVL(EP.DSC_DEPOSITO_ENDERECO,'')";

        return $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function changeStatusExpedicao($idsExpedicoes, $processando = 'N') {
        $sql = "UPDATE EXPEDICAO SET IND_PROCESSANDO = '$processando' WHERE COD_EXPEDICAO IN ($idsExpedicoes)";
        $this->_em->getConnection()->query($sql)->execute();
    }

    public function countModeloSeparacaoByExpedicoes($codExpedicoes)
    {
        $sql = $this->getEntityManager()->createQueryBuilder()
            ->select('distinct ms.id')
            ->from('wms:Expedicao','e')
            ->leftJoin('e.modeloSeparacao', 'ms')
            ->where("e.id IN ($codExpedicoes)")
            ->groupBy('ms.id, e.id');

        return $sql->getQuery()->getResult();

    }

    /**
     * @param $idExpedicao
     * @param $idModelo
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    public function defineModeloSeparacao($idExpedicao, $idModelo, $flush = true) {

        /** @var Expedicao $expedicaoEn */
        $expedicaoEn =  $this->find($idExpedicao);
        /** @var Expedicao\ModeloSeparacao $modeloEn */
        $modeloEn = $this->_em->find("wms:Expedicao\ModeloSeparacao", $idModelo);

        if (!empty($expedicaoEn) && !empty($modeloEn)) {
            $expedicaoEn->setModeloSeparacao($modeloEn);
        }

        $this->_em->persist($expedicaoEn);

        if ($flush) $this->_em->flush();

    }
}
