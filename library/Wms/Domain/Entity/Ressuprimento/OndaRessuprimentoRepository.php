<?php

namespace Wms\Domain\Entity\Ressuprimento;

use Doctrine\ORM\EntityRepository,
    Wms\Domain\Entity\OrdemServico as OrdemServicoEntity,
    Wms\Domain\Entity\Atividade as AtividadeEntity;
use Wms\Math;

class OndaRessuprimentoRepository extends EntityRepository {

    public function getOndasEmAberto($codProduto, $grade, $codEndereco = null) {
        $query = $this->getEntityManager()->createQueryBuilder()
                ->select("os.id as OS,
                          w.id as Onda,
                          e.descricao as Endereco,
                          wos.id as OndaOsId,
                          wos.sequencia")
                ->from("wms:Ressuprimento\OndaRessuprimentoOs", 'wos')
                ->leftJoin("wos.produtos", 'osp')
                ->leftJoin('osp.produto', 'prod')
                ->leftJoin("wos.os", "os")
                ->leftJoin("wos.endereco", 'e')
                ->leftJoin("wos.ondaRessuprimento", "w")
                ->where("wos.status = " . \Wms\Domain\Entity\Ressuprimento\OndaRessuprimentoOs::STATUS_ONDA_GERADA)
                ->orderBy("wos.sequencia")
                ->distinct(true);

        if ($codProduto != null) {
            $query->andWhere("prod.id = '$codProduto' AND prod.grade ='$grade'");
        }
        if ($codEndereco != null) {
            $query->andWhere("e.id = " . $codEndereco);
        }
        $result = $query->getQuery()->getArrayResult();
        return $result;
    }

    public function getDadosOnda($OS) {
        $dql = $this->getEntityManager()->createQueryBuilder()
                ->select("
                    os2.id as Ordem_Servico,
                    ores.dataCriacao as Data_Criacao,
                    p.id as Codigo,
                    p.grade as Grade,
                    p.descricao as Produto,
                    ps.qtd as Qtde,
                    e.descricao as Pulmao,
                    e.id as idPulmao,
                    pe.descricao as dscEmbalagem,
                    pe.quantidade as fator,
                    pk.descricao as Picking
                ")
                ->from("wms:Ressuprimento\OndaRessuprimentoOs", "o")
                ->leftJoin("o.produtos", "ps")
                ->leftJoin("o.ondaRessuprimento", "ores")
                ->leftJoin("ps.produto", "p")
                ->leftJoin("o.os", "os")
                ->leftJoin("o.endereco", "e")
                ->leftJoin("o.os", "os2")
                ->leftJoin("wms:Ressuprimento\ReservaEstoqueOnda", 'reo', 'WITH', 'reo.ondaRessuprimentoOs = o.id')
                ->leftJoin("reo.reservaEstoque", 'res')
                ->leftJoin("res.endereco", 'pk')
                ->leftJoin("wms:Produto\Embalagem", "pe", "WITH", "pe.id = ps.codProdutoEmbalagem")
                ->where("o.id = $OS")
                ->andWhere("res.tipoReserva = 'E'");

        $result = $dql->getQuery()->getArrayResult();
        return $result[0];
    }

    public function getOndasEmAbertoCompleto($dataInicial, $dataFinal, $status, $showOsId = false, $idProduto = null, $idExpedicao = null, $operador = null, $exibrCodBarrasProduto = false) {
        $SqlWhere = "  WHERE RES.TIPO_RESERVA = 'E'";
        $osId = "";
        $siglaId = "";
        $codBarrasProduto = "";

        if ($showOsId == true) {
            $osId = "O.COD_ONDA_RESSUPRIMENTO_OS as ID,";
            $siglaId = "SIGLA.COD_SIGLA as COD_STATUS,";
        }

        if ($exibrCodBarrasProduto == true) {
            $codBarrasProduto = ", CB_PROD.COD_BARRAS ";
        }

        if (!empty($status)) {
            $SqlWhere .= " AND O.COD_STATUS = $status";
        }

        if (!empty($idProduto)) {
            $SqlWhere .= " AND P.COD_PRODUTO = '$idProduto'";
        }

        if (!empty($idExpedicao)) {
            $SqlWhere .= " AND OS.COD_EXPEDICAO = $idExpedicao";
        }

        if (!empty($operador)) {
            $SqlWhere .= " AND OS.COD_PESSOA = $operador";
        }

        $SqlOrderBy = " ORDER BY OND.COD_ONDA_RESSUPRIMENTO, DE1.DSC_DEPOSITO_ENDERECO,  DE2.DSC_DEPOSITO_ENDERECO";

        $Sql = "
        SELECT DISTINCT
               $osId
               OND.COD_ONDA_RESSUPRIMENTO ONDA,
               OND.DTH_CRIACAO as \"DT. CRIACAO\",
               P.COD_PRODUTO as \"COD.\",
               P.DSC_GRADE as GRADE,
               CASE WHEN LENGTH(P.DSC_PRODUTO) >= 35 THEN CONCAT(SUBSTR(P.DSC_PRODUTO,0,30),'...') ELSE P.DSC_PRODUTO END as PRODUTO,
               CASE WHEN LENGTH(VOLS.VOLUMES) >= 63 THEN CONCAT(SUBSTR(VOLS.VOLUMES,0,30),'...') ELSE VOLS.VOLUMES END as VOLUMES,
               PRODS.QTD/QTDEMB.QTD as QTD,
               DE1.DSC_DEPOSITO_ENDERECO as PULMAO,
               DE2.DSC_DEPOSITO_ENDERECO as PICKING,
               $siglaId
               SIGLA.DSC_SIGLA STATUS
               $codBarrasProduto
          FROM ONDA_RESSUPRIMENTO_OS O
          INNER JOIN SIGLA ON SIGLA.COD_SIGLA = O.COD_STATUS
          LEFT JOIN ORDEM_SERVICO OS ON O.COD_OS = OS.COD_OS
          LEFT JOIN ONDA_RESSUPRIMENTO OND ON OND.COD_ONDA_RESSUPRIMENTO = O.COD_ONDA_RESSUPRIMENTO
          LEFT JOIN ONDA_RESSUPRIMENTO_OS_PRODUTO PRODS ON PRODS.COD_ONDA_RESSUPRIMENTO_OS = O.COD_ONDA_RESSUPRIMENTO_OS
          LEFT JOIN PRODUTO P ON P.COD_PRODUTO = PRODS.COD_PRODUTO AND P.DSC_GRADE = PRODS.DSC_GRADE
          LEFT JOIN DEPOSITO_ENDERECO DE1 ON DE1.COD_DEPOSITO_ENDERECO = O.COD_DEPOSITO_ENDERECO
          LEFT JOIN RESERVA_ESTOQUE_ONDA_RESSUP REOND ON REOND.COD_ONDA_RESSUPRIMENTO_OS = O.COD_ONDA_RESSUPRIMENTO_OS
          LEFT JOIN RESERVA_ESTOQUE RES ON RES.COD_RESERVA_ESTOQUE = REOND.COD_RESERVA_ESTOQUE
          LEFT JOIN DEPOSITO_ENDERECO DE2 ON RES.COD_DEPOSITO_ENDERECO = DE2.COD_DEPOSITO_ENDERECO
          LEFT JOIN (SELECT DISTINCT O.COD_ONDA_RESSUPRIMENTO_OS, NVL(PE.QTD_EMBALAGEM, 1) AS QTD
                       FROM ONDA_RESSUPRIMENTO_OS_PRODUTO O
                       LEFT JOIN PRODUTO_VOLUME    PV ON O.COD_PRODUTO_VOLUME = PV.COD_PRODUTO_VOLUME
                       LEFT JOIN PRODUTO_EMBALAGEM PE ON O.COD_PRODUTO_EMBALAGEM = PE.COD_PRODUTO_EMBALAGEM) QTDEMB
            ON QTDEMB.COD_ONDA_RESSUPRIMENTO_OS = O.COD_ONDA_RESSUPRIMENTO_OS
          LEFT JOIN (SELECT O.COD_ONDA_RESSUPRIMENTO_OS,
                            LISTAGG (NVL(PV.DSC_VOLUME,PE.DSC_EMBALAGEM || ' ('||PE.QTD_EMBALAGEM || ')' ),',') WITHIN GROUP (ORDER BY O.COD_ONDA_RESSUPRIMENTO_OS) VOLUMES
                       FROM ONDA_RESSUPRIMENTO_OS_PRODUTO O
                       LEFT JOIN PRODUTO_VOLUME    PV ON O.COD_PRODUTO_VOLUME = PV.COD_PRODUTO_VOLUME
                       LEFT JOIN PRODUTO_EMBALAGEM PE ON O.COD_PRODUTO_EMBALAGEM = PE.COD_PRODUTO_EMBALAGEM
                      GROUP BY O.COD_ONDA_RESSUPRIMENTO_OS) VOLS ON VOLS.COD_ONDA_RESSUPRIMENTO_OS = O.COD_ONDA_RESSUPRIMENTO_OS
          LEFT JOIN (SELECT O.COD_ONDA_RESSUPRIMENTO_OS,
                        MAX(NVL(PV.COD_BARRAS,PE.COD_BARRAS)) as COD_BARRAS
                       FROM ONDA_RESSUPRIMENTO_OS_PRODUTO O
                       LEFT JOIN PRODUTO_VOLUME    PV ON O.COD_PRODUTO_VOLUME = PV.COD_PRODUTO_VOLUME
                       LEFT JOIN PRODUTO_EMBALAGEM PE ON O.COD_PRODUTO_EMBALAGEM = PE.COD_PRODUTO_EMBALAGEM
                       GROUP BY O.COD_ONDA_RESSUPRIMENTO_OS) CB_PROD ON CB_PROD.COD_ONDA_RESSUPRIMENTO_OS = O.COD_ONDA_RESSUPRIMENTO_OS
                      ";

        if (isset($dataInicial) && (!empty($dataInicial))) {
            $SqlWhere .= " AND OND.DTH_CRIACAO >= TO_DATE('$dataInicial 00:00','DD-MM-YYYY HH24:MI')";
        }
        if (isset($dataFinal) && (!empty($dataFinal))) {
            $SqlWhere .= " AND OND.DTH_CRIACAO <= TO_DATE('$dataFinal 23:59','DD-MM-YYYY HH24:MI')";
        }

        $result = $this->getEntityManager()->getConnection()->query($Sql . $SqlWhere . $SqlOrderBy)->fetchAll(\PDO::FETCH_ASSOC);

        return $result;
    }

    /** @var \Wms\Domain\Entity\Ressuprimento\OndaRessuprimentoOs $ondaOs */
    public function finalizaOnda($ondaOs) {
        try {
            $this->getEntityManager()->beginTransaction();
            $idOnda = $ondaOs->getId();

            /** @var \Wms\Domain\Entity\Ressuprimento\ReservaEstoqueRepository $reservaEstoqueRepo */
            $reservaEstoqueRepo = $this->getEntityManager()->getRepository("wms:Ressuprimento\ReservaEstoque");
            $pessoaRepo = $this->getEntityManager()->getRepository("wms:Pessoa");

            /** @var \Wms\Domain\Entity\OrdemServico $osEn */
            $osEn = $ondaOs->getOs();
            $idOs = $osEn->getId();
            $idUsuario = \Zend_Auth::getInstance()->getIdentity()->getId();
            $usuarioEn = $pessoaRepo->find($idUsuario);

            $produtos = array();
            foreach ($ondaOs->getProdutos() as $produto) {
                $produtoArray = array();
                $produtoArray['codProdutoEmbalagem'] = $produto->getCodProdutoEmbalagem();
                $produtoArray['codProdutoVolume'] = $produto->getCodProdutoVolume();
                $produtoArray['codProduto'] = $produto->getProduto()->getId();
                $produtoArray['grade'] = $produto->getProduto()->getGrade();
                $produtoArray['qtd'] = $produto->getQtd();
                $produtos[] = $produtoArray;
            }

            $reservaEstoqueRepo->efetivaReservaEstoque(NULL, $produtos, "E", "O", $ondaOs->getId(), $idUsuario, $idOs);
            $reservaEstoqueRepo->efetivaReservaEstoque(NULL, $produtos, "S", "O", $ondaOs->getId(), $idUsuario, $idOs);

            $ondaOs = $this->getEntityManager()->getRepository("wms:Ressuprimento\OndaRessuprimentoOs")->findOneBy(array('id' => $idOnda));

            $statusEn = $this->getEntityManager()->getRepository("wms:Util\Sigla")->findOneBy(array('id' => \Wms\Domain\Entity\Ressuprimento\OndaRessuprimentoOs::STATUS_FINALIZADO));
            $ondaOs->setStatus($statusEn);
            $this->getEntityManager()->persist($ondaOs);

            $osEn->setDataFinal(new \DateTime());
            $osEn->setPessoa($usuarioEn);

            $this->getEntityManager()->flush();
            $this->getEntityManager()->commit();
        } catch (\Exception $e) {
            $this->getEntityManager()->rollback();
            throw new \Exception($e->getMessage());
        }
    }

    public function geraNovaOnda() {

        /* $idUsuario  = \Zend_Auth::getInstance()->getIdentity()->getId();

          $sql = "INSERT INTO ONDA_RESSUPRIMENTO (COD_ONDA_RESSUPRIMENTO, DTH_CRIACAO, DSC_OBSERVACAO, COD_USUARIO)
          VALUES (SQ_ONDA_RESSUPRIMENTO.NEXTVAL, :dthCriacao, :dscObs, :usuario)";
          $dth = new \DateTime();

          $conn = $this->_em->getConnection();
          $stmt = $conn->prepare($sql);
          $stmt->bindValue('dthCriacao', "'".$dth->format('d/m/Y')."'");
          $stmt->bindValue('dscObs', '');
          $stmt->bindValue('usuario', $idUsuario);
          $stmt->execute();

          $ondaEn = $this->find($conn->lastInsertId()); */

        $idUsuario = \Zend_Auth::getInstance()->getIdentity()->getId();
        $usuarioRepo = $this->getEntityManager()->getRepository("wms:Usuario");
        $usuarioEn = $usuarioRepo->find($idUsuario);

        $ondaEn = new \Wms\Domain\Entity\Ressuprimento\OndaRessuprimento();
        $ondaEn->setDataCriacao(new \DateTime());
        $ondaEn->setDscObservacao("");
        $ondaEn->setUsuario($usuarioEn);
        $ondaEn->setTipoOnda("C");
        $this->getEntityManager()->persist($ondaEn);

        return $ondaEn;
    }

    public function getArrayProdutosPorTipoSaida($produtos, $dadosProdutos, $repositorios) {
        $arraySaidaPicking = array();
        $arraySaidaPulmao = array();
        $volumeRepo = $repositorios['volumeRepo'];

        foreach ($produtos as $produto) {
            $codExpedicao = $produto['COD_EXPEDICAO'];
            $codProduto = $produto['COD_PRODUTO'];
            $grade = $produto['DSC_GRADE'];
            $qtd = $produto['QTD'] * -1;
            $codPedido = $produto['COD_PEDIDO'];

            $produtoEn = $dadosProdutos[$codProduto][$grade]['entidade'];
            if ($produtoEn->getTipoComercializacao()->getId() == 1) {
                $embalagensEn = $dadosProdutos[$codProduto][$grade]['embalagensASC'];

                if (!isset($embalagensEn[0])) {
                    throw new \Exception("Produto " . $codProduto . " Grade " . $grade . " não possui embalagem cadastrada!");
                }

                $embalagem = $embalagensEn[0];

                $idPicking = null;
                if ($embalagem->getEndereco() != null) {
                    $idPicking = $embalagem->getEndereco()->getId();
                }
                $saidaProduto = array(
                    'idPicking' => $idPicking,
                    'idExpedicao' => $codExpedicao,
                    'idPedido' => $codPedido,
                    'produtos' => array(array('codProdutoEmbalagem' => $embalagem->getId(),
                            'codProdutoVolume' => null,
                            'codProduto' => $codProduto,
                            'grade' => $grade,
                            'qtd' => $qtd)));

                if ($idPicking == null) {
                    $arraySaidaPulmao[] = $saidaProduto;
                } else {
                    $arraySaidaPicking[] = $saidaProduto;
                }
            } else {
                $normas = $volumeRepo->getNormasByProduto($codProduto, $grade);
                foreach ($normas as $norma) {
                    $volumes = $volumeRepo->getVolumesByNorma($norma->getId(), $codProduto, $grade);
                    $produtosArray = array();
                    $idPicking = null;
                    foreach ($volumes as $volume) {
                        $produtoArray = array(
                            'codProdutoEmbalagem' => null,
                            'codProdutoVolume' => $volume->getId(),
                            'codProduto' => $codProduto,
                            'grade' => $grade,
                            'qtd' => $qtd
                        );
                        $produtosArray[] = $produtoArray;
                        if ($volume->getEndereco() != null) {
                            $idPicking = $volume->getEndereco()->getId();
                        }
                    }
                    $saidaProduto = array(
                        'idPicking' => $idPicking,
                        'idExpedicao' => $codExpedicao,
                        'idPedido' => $codPedido,
                        'produtos' => $produtosArray
                    );

                    if ($idPicking != null) {
                        $arraySaidaPicking[] = $saidaProduto;
                    } else {
                        $arraySaidaPulmao[] = $saidaProduto;
                    }
                }
            }
        }

        return array(
            'picking' => $arraySaidaPicking,
            'pulmao' => $arraySaidaPulmao
        );
    }

    public function relacionaOndaPedidosExpedicao($pedidosProdutosRessuprir, $ondaEn, $dadosProdutos, $repositorios) {

        $pedidoRepo = $repositorios['pedidoRepo'];

        foreach ($pedidosProdutosRessuprir as $pedidoProduto) {

            $codPedido = $pedidoProduto['COD_PEDIDO'];
            $codProduto = $pedidoProduto['COD_PRODUTO'];
            $grade = $pedidoProduto['DSC_GRADE'];
            $qtd = $pedidoProduto['QTD'];

            $produtoEn = $dadosProdutos[$codProduto][$grade]['entidade'];
            $pedidoEn = $pedidoRepo->findOneBy(array('id' => $codPedido));

            /* $sql = "INSERT INTO ONDA_RESSUPRIMENTO_PEDIDO (COD_ONDA_RESSUPRIMENTO_PEDIDO, COD_ONDA_RESSUPRIMENTO, COD_PEDIDO, COD_PRODUTO, QTD)
              VALUES (SQ_ONDA_RESSUPRIMENTO_PEDIDO.NEXTVAL, :idOnda, :idPedido, :idProduto, :qtd )";

              $conn = $this->_em->getConnection();
              $stmt = $conn->prepare($sql);
              $stmt->bindValue('idOnda', $ondaEn->getId());
              $stmt->bindValue('idPedido', $pedidoEn->getId());
              $stmt->bindValue('idProduto', $produtoEn->getId());
              $stmt->bindValue('qtd', $qtd);
              $stmt->execute(); */

            $ondaPedido = new \Wms\Domain\Entity\Ressuprimento\OndaRessuprimentoPedido();
            $ondaPedido->setOndaRessuprimento($ondaEn);
            $ondaPedido->setPedido($pedidoEn);
            $ondaPedido->setProduto($produtoEn);
            $ondaPedido->setQtd($qtd);
            $this->getEntityManager()->persist($ondaPedido);
        }
    }

    public function saveOs($produtoEn, $embalagens, $volumes, $qtdOnda, $ondaEn, $enderecoPulmaoEn, $idPicking, $repositorios = null, $validade = null, $reservaEntrada = true) {
        /** @var \Wms\Domain\Entity\Util\SiglaRepository $siglaRepo */
        /** @var \Wms\Domain\Entity\Ressuprimento\ReservaEstoqueRepository $reservaEstoqueRepo */
        /** @var \Wms\Domain\Entity\OrdemServicoRepository $ordemServicoRepo */
        if ($repositorios == null) {
            $ordemServicoRepo = $this->_em->getRepository('wms:OrdemServico');
            $reservaEstoqueRepo = $this->getEntityManager()->getRepository("wms:Ressuprimento\ReservaEstoque");
            $siglaRepo = $this->getEntityManager()->getRepository("wms:Util\Sigla");
        } else {
            $ordemServicoRepo = $repositorios['osRepo'];
            $reservaEstoqueRepo = $repositorios['reservaEstoqueRepo'];
            $siglaRepo = $repositorios['siglaRepo'];
        }

        $statusEn = $siglaRepo->findOneBy(array('id' => \Wms\Domain\Entity\Ressuprimento\OndaRessuprimentoOs::STATUS_ONDA_GERADA));

        //CRIA A ORDEM DE SERVICO
        $osEn = $ordemServicoRepo->save(new OrdemServicoEntity, array(
            'identificacao' => array(
                'tipoOrdem' => 'ressuprimento',
                'idAtividade' => AtividadeEntity::RESSUPRIMENTO,
                'formaConferencia' => OrdemServicoEntity::COLETOR,
            ),
                ), false, "Object");

        //RELACIONO A ORDEM DE SERVICO A ONDA DE RESSUPRIMENTO NA TABELA ONDA_RESSUPRIMENTO_OS
        $ondaRessuprimentoOs = new \Wms\Domain\Entity\Ressuprimento\OndaRessuprimentoOs();
        $ondaRessuprimentoOs->setOndaRessuprimento($ondaEn);
        $ondaRessuprimentoOs->setEndereco($enderecoPulmaoEn);
        $ondaRessuprimentoOs->setStatus($statusEn);
        $ondaRessuprimentoOs->setOs($osEn);
        $this->getEntityManager()->persist($ondaRessuprimentoOs);

        $produtosEntrada = array();
        $produtosSaida = array();

        if (!empty($volumes))
            foreach ($volumes as $volume) {
                $ondaRessuprimentoOsProduto = new OndaRessuprimentoOsProduto();
                $ondaRessuprimentoOsProduto->setQtd($qtdOnda);
                $ondaRessuprimentoOsProduto->setOndaRessuprimentoOs($ondaRessuprimentoOs);
                $ondaRessuprimentoOsProduto->setCodProdutoVolume($volume);
                $ondaRessuprimentoOsProduto->setCodProdutoEmbalagem(null);
                $ondaRessuprimentoOsProduto->setProduto($produtoEn);
                $this->getEntityManager()->persist($ondaRessuprimentoOsProduto);

                $produtoArray = array();
                $produtoArray['codProduto'] = $produtoEn->getId();
                $produtoArray['grade'] = $produtoEn->getGrade();
                $produtoArray['codProdutoVolume'] = $volume;
                $produtoArray['codProdutoEmbalagem'] = null;
                $produtoArray['qtd'] = $qtdOnda;
                $produtoArray['validade'] = $validade;
                $produtosEntrada[] = $produtoArray;

                $produtoArray['qtd'] = $qtdOnda * -1;
                $produtosSaida[] = $produtoArray;
            }

        if (!empty($embalagens))
            foreach ($embalagens as $embalagem) {
                $ondaRessuprimentoOsProduto = new OndaRessuprimentoOsProduto();
                $ondaRessuprimentoOsProduto->setQtd($qtdOnda);
                $ondaRessuprimentoOsProduto->setOndaRessuprimentoOs($ondaRessuprimentoOs);
                $ondaRessuprimentoOsProduto->setCodProdutoVolume(null);
                $ondaRessuprimentoOsProduto->setCodProdutoEmbalagem($embalagem);
                $ondaRessuprimentoOsProduto->setProduto($produtoEn);
                $this->getEntityManager()->persist($ondaRessuprimentoOsProduto);

                $produtoArray = array();
                $produtoArray['codProduto'] = $produtoEn->getId();
                $produtoArray['grade'] = $produtoEn->getGrade();
                $produtoArray['codProdutoVolume'] = null;
                $produtoArray['codProdutoEmbalagem'] = $embalagem;
                $produtoArray['qtd'] = $qtdOnda;
                $produtoArray['validade'] = $validade;
                $produtosEntrada[] = $produtoArray;

                $produtoArray['qtd'] = $qtdOnda * -1;
                $produtosSaida[] = $produtoArray;
            }

        //ADICIONA AS RESERVAS DE ESTOQUE
        if ($reservaEntrada == true) {
            $reservaEstoqueRepo->adicionaReservaEstoque($idPicking, $produtosEntrada, "E", "O", $ondaRessuprimentoOs, $osEn, null, null, null, $repositorios);
        }
        $reservaEstoqueRepo->adicionaReservaEstoque($enderecoPulmaoEn->getId(), $produtosSaida, "S", "O", $ondaRessuprimentoOs, $osEn, null, null, null, $repositorios);
    }

    private function calculaRessuprimentoByPicking($picking, $ondaEn, $dadosProdutos, $repositorios) {
        $qtdOsGerada = 0;
        $capacidadePicking = $picking['capacidadePicking'];
        $pontoReposicao = $picking['pontoReposicao'];
        $idPicking = $picking['idPicking'];
        $codProduto = $picking['codProduto'];
        $grade = $picking['grade'];
        $volumes = $picking['volumes'];
        $embalagens = $picking['embalagens'];

        $idVolume = null;
        if (count($volumes) > 0) {
            $idVolume = $volumes[0];
        }

        /** @var \Wms\Domain\Entity\Enderecamento\EstoqueRepository $estoqueRepo */
        $estoqueRepo = $repositorios['estoqueRepo'];
        /** @var \Wms\Domain\Entity\Ressuprimento\ReservaEstoqueRepository $reservaEstoqueRepo */
        $reservaEstoqueRepo = $repositorios['reservaEstoqueRepo'];
        /** @var \Wms\Domain\Entity\Deposito\EnderecoRepository $enderecoRepo */
        $enderecoRepo = $repositorios['enderecoRepo'];

        //CALCULO A QUANTIDADE PARA RESSUPRIR

        $qtdPickingReal = $estoqueRepo->getQtdProdutoByVolumesOrProduct($codProduto, $grade, $idPicking, $volumes);
        $reservaEntradaPicking = $reservaEstoqueRepo->getQtdReservadaByProduto($codProduto, $grade, $idVolume, $idPicking, "E");
        $reservaSaidaPicking = $reservaEstoqueRepo->getQtdReservadaByProduto($codProduto, $grade, $idVolume, $idPicking, "S");
        $saldo = $qtdPickingReal + $reservaEntradaPicking + $reservaSaidaPicking;
        $produtoEn = $dadosProdutos[$codProduto][$grade]['entidade'];
        if ($saldo <= $pontoReposicao) {
            $qtdRessuprir = $saldo * -1;
            $qtdRessuprirMax = $qtdRessuprir + $capacidadePicking;

            //GERA A O.S DE ACORDO COM OS ENDEREÇOS DE PULMAO
            $params = array(
                'idProduto' => $codProduto,
                'grade' => $grade,
                'idVolume' => $volumes,
                'idEnderecoIgnorar' => $idPicking
            );
            $estoquePulmao = $estoqueRepo->getEstoqueByParams($params);
            foreach ($estoquePulmao as $estoque) {
                $qtdEstoque = $estoque['SALDO'];
                $validadeEstoque = $estoque['DTH_VALIDADE'];
                $idPulmao = $estoque['COD_DEPOSITO_ENDERECO'];

                $enderecoPulmaoEn = $enderecoRepo->findOneBy(array('id' => $idPulmao));

                //CALCULO A QUANTIDADE DO PALETE
                if ($qtdRessuprirMax >= $qtdEstoque) {
                    $qtdOnda = $qtdEstoque;
                } else {
                    if ($capacidadePicking >= $qtdRessuprir) {
                        $qtdOnda = $capacidadePicking;
                    } else {
                        //Todo Reavaliar o cálculo de ressuprimento
                        $qtdOnda = ((int) ($qtdRessuprirMax / $capacidadePicking)) * $capacidadePicking;
                    }
                    if ($qtdOnda > $qtdEstoque)
                        $qtdOnda = $qtdEstoque;
                }
                //GERA AS RESERVAS PARA OS PULMOES E PICKING
                if ($qtdOnda > 0) {
                    $this->saveOs($produtoEn, $embalagens, $volumes, $qtdOnda, $ondaEn, $enderecoPulmaoEn, $idPicking, $repositorios, $validadeEstoque);
                    $qtdOsGerada ++;
                }

                $qtdRessuprir = $qtdRessuprir - $qtdOnda;
                $qtdRessuprirMax = $qtdRessuprirMax - $qtdOnda;
                if ($qtdRessuprir <= 0) {
                    break;
                }
            }
        }
        return $qtdOsGerada;
    }

    public function sequenciaOndasOs() {
        $OndasOs = $this->getOndasNaoSequenciadas();

        foreach ($OndasOs as $os) {
            $os->setSequencia($os->getNextSequenciaSQ());
            $this->getEntityManager()->persist($os);
        }
        $this->getEntityManager()->flush();
    }

    public function calculaRessuprimentoByProduto($produtosRessuprir, $ondaEn, $dadosProdutos, $repositorios) {
        $volumeRepo = $repositorios['volumeRepo'];
        $qtdRessuprimentos = 0;
        foreach ($produtosRessuprir as $produto) {
            $codProduto = $produto['COD_PRODUTO'];
            $grade = $produto['DSC_GRADE'];

            $produtoEn = $dadosProdutos[$codProduto][$grade]['entidade'];

            $pickings = array();
            if ($produtoEn->getTipoComercializacao()->getId() == 1) {

                $embalagensEn = $dadosProdutos[$codProduto][$grade]['embalagensASC'];

                $embalagem = $embalagensEn[0];
                $embalagens = array();
                $embalagens[] = $embalagem->getId();

                if ($embalagem->getEndereco() != null) {
                    $picking = array();
                    $picking['volumes'] = null;
                    $picking['embalagens'] = $embalagens;
                    $picking['capacidadePicking'] = $embalagem->getCapacidadePicking();
                    $picking['pontoReposicao'] = $embalagem->getPontoReposicao();
                    $picking['idPicking'] = $embalagem->getEndereco()->getId();
                    $picking['codProduto'] = $codProduto;
                    $picking['grade'] = $grade;
                    $pickings[] = $picking;
                }
            } else {
                $normas = $volumeRepo->getNormasByProduto($codProduto, $grade);
                foreach ($normas as $norma) {
                    $volumesEn = $volumeRepo->getVolumesByNorma($norma->getId(), $codProduto, $grade);
                    $picking = array();
                    $picking['volumes'] = array();
                    $picking['codProduto'] = $codProduto;
                    $picking['grade'] = $grade;
                    $picking['embalagens'] = null;
                    $idPicking = null;
                    foreach ($volumesEn as $volume) {
                        if ($volume->getEndereco() != null) {
                            $idPicking = $volume->getEndereco()->getId();
                        }
                        $picking['volumes'][] = $volume->getId();
                        $picking['idPicking'] = $idPicking;
                        $picking['capacidadePicking'] = $volume->getCapacidadePicking();
                        $picking['pontoReposicao'] = $volume->getPontoReposicao();
                    }
                    if ($idPicking != null) {
                        $pickings[] = $picking;
                    }
                }
            }

            foreach ($pickings as $picking) {
                $qtdRessuprimentos = $qtdRessuprimentos + $this->calculaRessuprimentoByPicking($picking, $ondaEn, $dadosProdutos, $repositorios);
            }
        }

        return $qtdRessuprimentos;
    }

    public function calculaRessuprimentoPreventivoByParams($parametros) {

        $reservaEstoqueRepo = $this->getEntityManager()->getRepository("wms:Ressuprimento\ReservaEstoque");
        $enderecoRepo = $this->getEntityManager()->getRepository("wms:Deposito\Endereco");
        $estoqueRepo = $this->getEntityManager()->getRepository("wms:Enderecamento\Estoque");
        $repositorios = array(
            'reservaEstoqueRepo' => $reservaEstoqueRepo,
            'enderecoRepo' => $enderecoRepo,
            'estoqueRepo' => $estoqueRepo
        );
        $result = $this->getQueryRessuprimentoPreventivo($parametros);

        $pickings = array();
        /*
         * TRATA RESULTADO DA QUERY
         */
        if (!empty($result) && is_array($result)) {
            $volumeRepo = $this->getEntityManager()->getRepository("wms:Produto\Volume");
            $embalagemRepo = $this->getEntityManager()->getRepository("wms:Produto\Embalagem");
            foreach ($result as $key => $value) {
                $result[$key]['PONTO_REPOSICAO'] = $result[$key]['CAPACIDADE_PICKING'];
                $pickings[$key]['pontoReposicao'] = $result[$key]['CAPACIDADE_PICKING'];
                $pickings[$key]['saldoPicking'] = $result[$key]['SALDO_PICKING'];
                $result[$key]['SALDO_PICKING_INPUT'] = $result[$key]['SALDO_PICKING'];

                $result[$key]['OCUPACAO'] = number_format($result[$key]['OCUPACAO'], 2, '.', '');
                $embalagensEn = $embalagemRepo->findBy(array('codProduto' => $value['COD_PRODUTO'], 'grade' => $value['DSC_GRADE'], 'dataInativacao' => null), array('quantidade' => 'DESC'));

                $pickings[$key]['idPicking'] = $result[$key]['COD_DEPOSITO_ENDERECO'];
                $pickings[$key]['norma'] = $result[$key]['NUM_NORMA'];
                $pickings[$key]['tiporessuprimento'] = '';
                if (isset($parametros['tiporessuprimento'])) {
                    $pickings[$key]['tiporessuprimento'] = $parametros['tiporessuprimento'];
                }
                $pickings[$key]['volumes'] = null;
                $result[$key]['ID_PICKING'] = $result[$key]['COD_DEPOSITO_ENDERECO'];
                /*
                 * CONTROI ARRAY DE DADOS PRODUTO PARA CALCULO DO RESSUPRIMENTO
                 */
                if (count($embalagensEn) > 0) {
                    /*
                     * CONVERTRE PARA CAIXA MASTER SOMENTE PARA EXIBIR
                     */
                    if ($value['SALDO_PICKING'] > 0) {
                        $vetEstoque = $embalagemRepo->getQtdEmbalagensProduto($value['COD_PRODUTO'], $value['DSC_GRADE'], $value['SALDO_PICKING']);
                        $result[$key]['SALDO_PICKING'] = implode('<br />', $vetEstoque);
                    }
                    if ($value['CAPACIDADE_PICKING'] > 0) {
                        $vetEstoque = $embalagemRepo->getQtdEmbalagensProduto($value['COD_PRODUTO'], $value['DSC_GRADE'], $value['CAPACIDADE_PICKING']);
                        $result[$key]['CAPACIDADE_PICKING'] = implode('<br />', $vetEstoque);
                    }
                    $embalagem = $embalagensEn[0];
                    $embalagens = array();
                    $embalagens[] = $embalagem->getId();
                    $result[$key]['EMBALAGENS'] = $embalagens;
                    $pickings[$key]['qtdEmbalagens'] = $embalagem->getQuantidade();
                } else {
                    $pickings[$key]['qtdEmbalagens'] = null;
                    $normas = $volumeRepo->getNormasByProduto($value['COD_PRODUTO'], $value['DSC_GRADE']);
                    foreach ($normas as $norma) {
                        $volumesEn = $volumeRepo->getVolumesByNorma($norma->getId(), $value['COD_PRODUTO'], $value['DSC_GRADE']);
                        $result[$key]['VOLUMES'] = array();
                        $result[$key]['EMBALAGENS'] = null;
                        $idPicking = null;
                        foreach ($volumesEn as $volume) {
                            if ($volume->getEndereco() != null) {
                                $idPicking = $volume->getEndereco()->getId();
                            }
                            $result[$key]['VOLUMES'][] = $volume->getId();
                            $pickings[$key]['volumes'][] = $volume->getId();
                            $result[$key]['ID_PICKING'] = $idPicking;
                            $result[$key]['CAPACIDADE_PICKING'] = $volume->getCapacidadePicking();
                            $pickings[$key]['idPicking'] = $idPicking;
                        }
                    }
                }
                $pickings[$key]['embalagens'] = $result[$key]['EMBALAGENS'];
                $pickings[$key]['capacidadePicking'] = $result[$key]['PONTO_REPOSICAO'];
                $pickings[$key]['codProduto'] = $result[$key]['COD_PRODUTO'];
                $pickings[$key]['grade'] = $result[$key]['DSC_GRADE'];
                /*
                 * FUNÇÃO QUE CALCULA RESSUPRIMENTO
                 */
                $os = $this->calculaRessuprimentoPreventivoByPicking($pickings[$key], $repositorios);
                $vetEmb = $vetVol = $vetPulmoes = $vetOnda = $vetExibePulmao = array();
                $result[$key]['TOTAL_ONDA'] = $totalOnda = 0;
                if (count($os) > 0) {
                    foreach ($os as $value) {
                        $vetExibePulmao[$value['enderecoPulmao']] = $value['enderecoPulmao'];
                        if ($value['enderecoPulmao'] != "null") {
                            $vetOnda[$value['enderecoPulmao']] = $value['qtdOnda'];
                            $totalOnda += $value['qtdOnda'];
                        }
                        if ($value['volumes'] != "null") {
                            $vetVol[$value['enderecoPulmao']][] = json_decode($value['volumes']);
                        } elseif ($value['embalagens'] != "null") {
                            $vetEmb[$value['enderecoPulmao']][] = json_decode($value['embalagens']);
                        }
                    }
                    foreach ($vetExibePulmao as $value) {
                        $vetPulmoes[] = $value;
                    }
                    $result[$key]['TOTAL_ONDA'] = implode('<br />', $vetEstoque);
                    if (empty($vetVol)) {
                        if ($totalOnda > 0) {
                            $vetEstoque = $embalagemRepo->getQtdEmbalagensProduto($result[$key]['COD_PRODUTO'], $result[$key]['DSC_GRADE'], $totalOnda);
                            $result[$key]['TOTAL_ONDA'] = implode('<br />', $vetEstoque);
                        }
                    }
                    $result[$key]['EMBALAGENS'] = json_encode($vetEmb);
                    $result[$key]['VOLUMES'] = json_encode($vetVol);
                    $result[$key]['PULMOES'] = json_encode($vetPulmoes);
                    $result[$key]['PULMAO'] = '';
                    $result[$key]['VALIDADE_ESTOQUE'] = '';
                    $result[$key]['ID_PIKING'] = null;
                    $result[$key]['QTD_ONDA'] = 0;
                    $osFirst = reset($os);
                    $result[$key]['VALIDADE_ESTOQUE'] = $osFirst['validadeEstoque'];
                    $result[$key]['PULMAO'] = implode(' <br /> ', $vetExibePulmao);
                    $result[$key]['ID_PIKING'] = $osFirst['idPicking'];
                    $result[$key]['QTD_ONDA'] = json_encode($vetOnda);
                } else {
                    unset($result[$key]);
                }
            }
        }
        return $result;
    }

    public function getQueryRessuprimentoPreventivo($parametros) {
        $SQL = "SELECT DISTINCT P.COD_PRODUTO,
                    P.DSC_GRADE,
                    DE.DSC_DEPOSITO_ENDERECO,
                    NP.NUM_NORMA,
                    P.DSC_PRODUTO,
                    NVL(PE.COD_DEPOSITO_ENDERECO,PV.COD_DEPOSITO_ENDERECO) as COD_DEPOSITO_ENDERECO,
                    NVL(PE.CAPACIDADE_PICKING, PV.CAPACIDADE_PICKING) as CAPACIDADE_PICKING,
                    NVL(ESTOQUE_PICKING.QTD,0) as SALDO_PICKING,
                    DECODE(ESTOQUE_PICKING.QTD,null,0,(ESTOQUE_PICKING.QTD / NVL(PE.CAPACIDADE_PICKING, PV.CAPACIDADE_PICKING))) * 100 as OCUPACAO
               FROM PRODUTO P
               LEFT JOIN PRODUTO_EMBALAGEM PE ON PE.COD_PRODUTO = P.COD_PRODUTO AND P.DSC_GRADE = PE.DSC_GRADE AND PE.CAPACIDADE_PICKING > 0
               LEFT JOIN PRODUTO_VOLUME PV ON PV.COD_PRODUTO = P.COD_PRODUTO AND P.DSC_GRADE = PV.DSC_GRADE AND PV.CAPACIDADE_PICKING > 0
               LEFT JOIN PRODUTO_DADO_LOGISTICO PDL ON (PE.COD_PRODUTO_EMBALAGEM = PDL.COD_PRODUTO_EMBALAGEM)
               LEFT JOIN NORMA_PALETIZACAO NP ON (NP.COD_NORMA_PALETIZACAO = NVL(PDL.COD_NORMA_PALETIZACAO, PV.COD_NORMA_PALETIZACAO))
               INNER JOIN (SELECT E.COD_PRODUTO, E.COD_DEPOSITO_ENDERECO,
                                 SUM(E.QTD) as QTD, NVL(E.COD_PRODUTO_VOLUME,0) as COD_PRODUTO_VOLUME,
                                 E.DSC_GRADE
                            FROM ESTOQUE E
                            INNER JOIN DEPOSITO_ENDERECO DE2 ON (E.COD_DEPOSITO_ENDERECO = DE2.COD_DEPOSITO_ENDERECO)
                            WHERE DE2.COD_CARACTERISTICA_ENDERECO != 38
                           GROUP BY E.COD_PRODUTO, E.DSC_GRADE, E.COD_DEPOSITO_ENDERECO, E.COD_PRODUTO_VOLUME) ESTOQUE_PICKING
                     ON ESTOQUE_PICKING.COD_DEPOSITO_ENDERECO = NVL(PE.COD_DEPOSITO_ENDERECO, PV.COD_DEPOSITO_ENDERECO)
                    AND ESTOQUE_PICKING.COD_PRODUTO = P.COD_PRODUTO
                    AND NVL(PV.COD_PRODUTO_VOLUME,0) = ESTOQUE_PICKING.COD_PRODUTO_VOLUME
               INNER JOIN DEPOSITO_ENDERECO DE ON DE.COD_DEPOSITO_ENDERECO = NVL(PE.COD_DEPOSITO_ENDERECO, PV.COD_DEPOSITO_ENDERECO)
               INNER JOIN (SELECT E.COD_PRODUTO, E.COD_DEPOSITO_ENDERECO,
                                 SUM(E.QTD) as QTD, NVL(E.COD_PRODUTO_VOLUME,0) as COD_PRODUTO_VOLUME,
                                 E.DSC_GRADE
                            FROM ESTOQUE E
                            INNER JOIN DEPOSITO_ENDERECO DE2 ON (E.COD_DEPOSITO_ENDERECO = DE2.COD_DEPOSITO_ENDERECO)
                            WHERE DE2.COD_CARACTERISTICA_ENDERECO = 38
                           GROUP BY E.COD_PRODUTO, E.DSC_GRADE, E.COD_DEPOSITO_ENDERECO, E.COD_PRODUTO_VOLUME ORDER BY NVL(E.DTH_VALIDADE, E.DTH_PRIMEIRA_MOVIMENTACAO)) ESTOQUE_PULMAO
                    ON ESTOQUE_PULMAO.COD_PRODUTO = ESTOQUE_PICKING.COD_PRODUTO
                    AND ESTOQUE_PULMAO.DSC_GRADE = ESTOQUE_PICKING.DSC_GRADE
                    AND ESTOQUE_PICKING.COD_PRODUTO_VOLUME = ESTOQUE_PULMAO.COD_PRODUTO_VOLUME
                    LEFT JOIN (SELECT RE.COD_DEPOSITO_ENDERECO, SUM(REP.QTD_RESERVADA) QTD_RESERVA, REP.COD_PRODUTO, REP.DSC_GRADE, NVL(REP.COD_PRODUTO_VOLUME,0) as VOLUME
                                FROM RESERVA_ESTOQUE RE
                           LEFT JOIN RESERVA_ESTOQUE_PRODUTO REP ON REP.COD_RESERVA_ESTOQUE = RE.COD_RESERVA_ESTOQUE
                               WHERE TIPO_RESERVA = 'S'
                                 AND IND_ATENDIDA = 'N'
                               GROUP BY RE.COD_DEPOSITO_ENDERECO, REP.COD_PRODUTO, REP.DSC_GRADE, REP.COD_PRODUTO_VOLUME) RS
                     ON RS.COD_PRODUTO = ESTOQUE_PULMAO.COD_PRODUTO
                    AND RS.DSC_GRADE = ESTOQUE_PULMAO.DSC_GRADE
                    AND RS.COD_DEPOSITO_ENDERECO = ESTOQUE_PULMAO.COD_DEPOSITO_ENDERECO
                    AND ((RS.VOLUME = ESTOQUE_PULMAO.COD_PRODUTO_VOLUME) OR (RS.VOLUME = 0 AND ESTOQUE_PULMAO.COD_PRODUTO_VOLUME IS NULL))
              WHERE (PE.COD_DEPOSITO_ENDERECO IS NOT NULL OR PV.COD_DEPOSITO_ENDERECO IS NOT NULL)
                AND (PE.CAPACIDADE_PICKING IS NOT NULL OR PV.CAPACIDADE_PICKING IS NOT NULL)
                AND NP.IND_PADRAO = 'S'
                AND ((ESTOQUE_PULMAO.QTD + NVL(RS.QTD_RESERVA,0)) > 0)";
        $SQLWhere = " ";
        if (isset($parametros['ocupacao']) && !empty($parametros['ocupacao'])) {
            $SQLWhere .= "AND (DECODE(ESTOQUE_PICKING.QTD,null,0,(ESTOQUE_PICKING.QTD / NVL(PE.CAPACIDADE_PICKING, PV.CAPACIDADE_PICKING))) * 100) <= " . $parametros['ocupacao'];
        } else {
            $SQLWhere .= "AND (DECODE(ESTOQUE_PICKING.QTD,null,0,(ESTOQUE_PICKING.QTD / NVL(PE.CAPACIDADE_PICKING, PV.CAPACIDADE_PICKING))) * 100) = 0";
        }
        if (isset($parametros['tipoEndereco']) && !empty($parametros['tipoEndereco'])) {
            $SQLWhere .= " AND DE.COD_TIPO_ENDERECO = " . $parametros['tipoEndereco'];
        }
        if (isset($parametros['linhaSeparacao']) && !empty($parametros['linhaSeparacao'])) {
            $SQLWhere .= " AND P.COD_LINHA_SEPARACAO = " . $parametros['linhaSeparacao'];
        }
        if (isset($parametros['rua']) && !empty($parametros['rua'])) {
            $SQLWhere .= " AND DE.NUM_RUA >= " . $parametros['rua'];
        }
        if (isset($parametros['predio']) && !empty($parametros['predio'])) {
            $SQLWhere .= " AND DE.NUM_PREDIO >= " . $parametros['predio'];
        }
        if (isset($parametros['nivel']) && !empty($parametros['nivel'])) {
            $SQLWhere .= " AND DE.NUM_NIVEL >= " . $parametros['nivel'];
        }
        if (isset($parametros['apto']) && !empty($parametros['apto'])) {
            $SQLWhere .= " AND DE.NUM_APARTAMENTO >= " . $parametros['apto'];
        }
        if (isset($parametros['ruaFinal']) && !empty($parametros['ruaFinal'])) {
            $SQLWhere .= " AND DE.NUM_RUA <= " . $parametros['ruaFinal'];
        }
        if (isset($parametros['predioFinal']) && !empty($parametros['predioFinal'])) {
            $SQLWhere .= " AND DE.NUM_PREDIO <= " . $parametros['predioFinal'];
        }
        if (isset($parametros['nivelFinal']) && !empty($parametros['nivelFinal'])) {
            $SQLWhere .= " AND DE.NUM_NIVEL <= " . $parametros['nivelFinal'];
        }
        if (isset($parametros['aptoFinal']) && !empty($parametros['aptoFinal'])) {
            $SQLWhere .= " AND DE.NUM_APARTAMENTO <= " . $parametros['aptoFinal'];
        }

        switch ($parametros['ladoRua']) {
            case 1:
                $SQLWhere .= " AND MOD(DE.NUM_RUA, 2) = 0 ";
                break;
            case 2:
                $SQLWhere .= " AND MOD(DE.NUM_RUA, 2) != 0 ";
                break;
        }
        $SQLOrderBy = " GROUP BY P.COD_PRODUTO, 
                 P.DSC_GRADE,
                  DE.DSC_DEPOSITO_ENDERECO,
                  NP.NUM_NORMA,
                  NVL(PV.COD_PRODUTO_VOLUME,0),
                  NVL(PE.COD_DEPOSITO_ENDERECO,PV.COD_DEPOSITO_ENDERECO),
                  NVL(PE.CAPACIDADE_PICKING, PV.CAPACIDADE_PICKING),
                  NVL(ESTOQUE_PICKING.QTD,0),
                  P.DSC_PRODUTO,
                  DECODE(ESTOQUE_PICKING.QTD,null,0,(ESTOQUE_PICKING.QTD / NVL(PE.CAPACIDADE_PICKING, PV.CAPACIDADE_PICKING))) * 100  
                  ORDER BY DE.DSC_DEPOSITO_ENDERECO";
        return $this->getEntityManager()->getConnection()->query($SQL . $SQLWhere . $SQLOrderBy)->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function calculaProdutoAcumuladoByParams($parametros) {
        $SQLWhere = $where = "";
        if (isset($parametros['ocupacao']) && !empty($parametros['ocupacao'])) {
            $where .= " AND (PA.QTD_VENDIDA * 100) / PRODUTO_EMBALAGEM.CAPACIDADE_PICKING >= " . $parametros['ocupacao'];
        } else {
            $where .= " AND (PA.QTD_VENDIDA * 100) / PRODUTO_EMBALAGEM.CAPACIDADE_PICKING = 0";
        }

        if (isset($parametros['tipoEndereco']) && !empty($parametros['tipoEndereco'])) {
            $SQLWhere .= " AND DE.COD_TIPO_ENDERECO = " . $parametros['tipoEndereco'];
        }
        if (isset($parametros['linhaSeparacao']) && !empty($parametros['linhaSeparacao'])) {
            $SQLWhere .= " AND P.COD_LINHA_SEPARACAO = " . $parametros['linhaSeparacao'];
        }
        if (isset($parametros['rua']) && !empty($parametros['rua'])) {
            $SQLWhere .= " AND DE.NUM_RUA >= " . $parametros['rua'];
        }
        if (isset($parametros['predio']) && !empty($parametros['predio'])) {
            $SQLWhere .= " AND DE.NUM_PREDIO >= " . $parametros['predio'];
        }
        if (isset($parametros['nivel']) && !empty($parametros['nivel'])) {
            $SQLWhere .= " AND DE.NUM_NIVEL >= " . $parametros['nivel'];
        }
        if (isset($parametros['apto']) && !empty($parametros['apto'])) {
            $SQLWhere .= " AND DE.NUM_APARTAMENTO >= " . $parametros['apto'];
        }
        if (isset($parametros['ruaFinal']) && !empty($parametros['ruaFinal'])) {
            $SQLWhere .= " AND DE.NUM_RUA <= " . $parametros['ruaFinal'];
        }
        if (isset($parametros['predioFinal']) && !empty($parametros['predioFinal'])) {
            $SQLWhere .= " AND DE.NUM_PREDIO <= " . $parametros['predioFinal'];
        }
        if (isset($parametros['nivelFinal']) && !empty($parametros['nivelFinal'])) {
            $SQLWhere .= " AND DE.NUM_NIVEL <= " . $parametros['nivelFinal'];
        }
        if (isset($parametros['aptoFinal']) && !empty($parametros['aptoFinal'])) {
            $SQLWhere .= " AND DE.NUM_APARTAMENTO <= " . $parametros['aptoFinal'];
        }

        $sql = "SELECT
                    PA.COD_PRODUTO,
                    PA.DSC_GRADE,
                    PA.QTD_VENDIDA,
                    ESTOQUE_PULMAO.QTD AS QTD_ESTOQUE,
                    ESTOQUE_PULMAO.DSC_DEPOSITO_ENDERECO,
                    ESTOQUE_PULMAO.DTH_VALIDADE AS VALIDADE_ESTOQUE,
                    ESTOQUE_PULMAO.COD_DEPOSITO_ENDERECO AS END_PULMAO,
                    PRODUTO_EMBALAGEM.CAPACIDADE_PICKING,
                    PRODUTO_EMBALAGEM.DSC_DEPOSITO_ENDERECO AS DSC_PICKING,
                    PRODUTO_EMBALAGEM.COD_DEPOSITO_ENDERECO AS END_PICKING,
                    PRODUTO_EMBALAGEM.DSC_PRODUTO,
                    (PA.QTD_VENDIDA * 100) / PRODUTO_EMBALAGEM.CAPACIDADE_PICKING AS PERCENTUAL
                FROM 
                    PEDIDO_ACUMULADO PA 
                    INNER JOIN (
                                    SELECT 
                                        E.COD_PRODUTO, 
                                        SUM(E.QTD) as QTD,
                                        E.DSC_GRADE,
                                        DE2.DSC_DEPOSITO_ENDERECO,
                                        DE2.COD_DEPOSITO_ENDERECO,
                                        E.DTH_VALIDADE
                                    FROM 
                                        ESTOQUE E
                                        INNER JOIN DEPOSITO_ENDERECO DE2 ON (E.COD_DEPOSITO_ENDERECO = DE2.COD_DEPOSITO_ENDERECO)
                                    WHERE 
                                        DE2.COD_CARACTERISTICA_ENDERECO = 38
                                    GROUP BY 
                                        E.COD_PRODUTO, 
                                        E.DSC_GRADE,
                                        DE2.DSC_DEPOSITO_ENDERECO,
                                        DE2.COD_DEPOSITO_ENDERECO,
                                        E.DTH_VALIDADE
                                    ORDER BY 
                                        NVL(E.DTH_VALIDADE, E.DTH_PRIMEIRA_MOVIMENTACAO), E.COD_PRODUTO
                                ) ESTOQUE_PULMAO
                ON ESTOQUE_PULMAO.COD_PRODUTO = PA.COD_PRODUTO
                    AND ESTOQUE_PULMAO.DSC_GRADE = PA.DSC_GRADE
                INNER JOIN (
                                SELECT
                                    PE.COD_PRODUTO,
                                    PE.DSC_GRADE,
                                    PE.CAPACIDADE_PICKING,
                                    PE.COD_DEPOSITO_ENDERECO,
                                    DE.DSC_DEPOSITO_ENDERECO,
                                    MIN(PE.QTD_EMBALAGEM),
                                    P.DSC_PRODUTO
                                FROM 
                                    PRODUTO_EMBALAGEM PE
                                    INNER JOIN DEPOSITO_ENDERECO DE ON (PE.COD_DEPOSITO_ENDERECO = DE.COD_DEPOSITO_ENDERECO)
                                    INNER JOIN PRODUTO P ON (P.COD_PRODUTO = PE.COD_PRODUTO AND P.DSC_GRADE = PE.DSC_GRADE)
                                WHERE
                                    1 = 1
                                    $SQLWhere
                                GROUP BY 
                                    PE.COD_PRODUTO,
                                    PE.DSC_GRADE,
                                    PE.CAPACIDADE_PICKING,
                                    PE.COD_DEPOSITO_ENDERECO,
                                    DE.DSC_DEPOSITO_ENDERECO,
                                    P.DSC_PRODUTO
                            ) PRODUTO_EMBALAGEM
                ON PRODUTO_EMBALAGEM.COD_PRODUTO = PA.COD_PRODUTO
                AND PRODUTO_EMBALAGEM.DSC_GRADE = PA.DSC_GRADE
                INNER JOIN DEPOSITO_ENDERECO DE ON DE.COD_DEPOSITO_ENDERECO = PRODUTO_EMBALAGEM.COD_DEPOSITO_ENDERECO
            WHERE 1 = 1 $where --AND PA.COD_PRODUTO = 15504
            ORDER 
                BY PA.COD_PRODUTO";

        $result = $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);

        $eliminaLinha = $qtdOnda = $restante = $qtdRessuprir = 0;
        $arrayQtd = $arrayPulmao = array();
        $reservaEstoqueRepo = $this->getEntityManager()->getRepository("wms:Ressuprimento\ReservaEstoque");
        $embalagemRepo = $this->getEntityManager()->getRepository("wms:Produto\Embalagem");
        foreach ($result as $key => $value) {
            $embalagensEn = $embalagemRepo->findOneBy(array('codProduto' => $value['COD_PRODUTO'], 'grade' => $value['DSC_GRADE'], 'dataInativacao' => null), array('quantidade' => 'ASC'));
            $result[$key]['EMBALAGENS'] = json_encode(array($value['DSC_DEPOSITO_ENDERECO'] => array($embalagensEn->getId())));
            $result[$key]['PERCENTUAL'] = number_format($result[$key]['PERCENTUAL'], 2, '.', '');
            if (isset($value['VALIDADE_ESTOQUE'])) {
                $result[$key]['VALIDADE_ESTOQUE'] = date("d/m/Y", strtotime($value['VALIDADE_ESTOQUE']));
            }
            $result[$key]['PERCENTUAL'] = number_format($result[$key]['PERCENTUAL'], 2, '.', '');
            $reservaSaidaPicking = $reservaEstoqueRepo->getQtdReservadaByProduto($value['COD_PRODUTO'], $value['DSC_GRADE'], null, $value['END_PULMAO'], "S");
            $qtdEstoque = $value['QTD_ESTOQUE'] + $reservaSaidaPicking;
            $qtdVendida = $value['QTD_VENDIDA'];

            if ($value['QTD_VENDIDA'] > $value['CAPACIDADE_PICKING']) {
                $qtdVendida = $value['CAPACIDADE_PICKING'];
            }

            if ($eliminaLinha !== $value['COD_PRODUTO'] . '-' . $value['DSC_GRADE']) {

                $eliminaLinha = 0;


                if ($qtdVendida > $value['CAPACIDADE_PICKING']) {
                    $qtdVendida = $value['CAPACIDADE_PICKING'];
                }
                $saldoEstoque = $qtdEstoque;
                if ($saldoEstoque > 0) {
                    if ($restante != 0) {
                        $qtdVendida = $restante;
                    }
                    if ($qtdVendida <= $saldoEstoque) {
                        $qtdOnda = $qtdVendida;
                        $qtdRessuprir += $qtdOnda;
                        $eliminaLinha = $value['COD_PRODUTO'] . '-' . $value['DSC_GRADE'];
                        $arrayQtd[$value['DSC_DEPOSITO_ENDERECO']] = $qtdOnda;
                        $arrayPulmao[] = $value['DSC_DEPOSITO_ENDERECO'];
                        $result[$key]['TOTAL_ONDA'] = $qtdRessuprir;
                        $result[$key]['SALDO_PICKING'] = $value['CAPACIDADE_PICKING'] - $value['QTD_VENDIDA'];
                        $result[$key]['QTD_ONDA'] = json_encode($arrayQtd);
                        $result[$key]['PULMAO'] = implode(' <br /> ', $arrayPulmao);
                        $result[$key]['PULMOES'] = json_encode($arrayPulmao);
                        $arrayQtd = $arrayPulmao = array();
                        $restante = $qtdRessuprir = 0;
                    } else {
                        $qtdOnda = $saldoEstoque;
                        $arrayPulmao[] = $value['DSC_DEPOSITO_ENDERECO'];
                        $arrayQtd[$value['DSC_DEPOSITO_ENDERECO']] = $qtdOnda;
                        $restante = $qtdVendida - $saldoEstoque;
                        unset($result[$key]);
                        $qtdRessuprir += $qtdOnda;
                    }
                } else {
                    $restante = 0;
                    $qtdOnda = $arrayQtd = $arrayPulmao = array();
                    unset($result[$key]);
                }
            } else {
                $restante = 0;
                $qtdOnda = $arrayQtd = $arrayPulmao = array();
                unset($result[$key]);
            }
        }
        return $result;
    }

    private function calculaRessuprimentoPreventivoByPicking($picking, $repositorios) {
        $osGeradas = array();
        $capacidadePicking = $picking['capacidadePicking'];
        $idPicking = $picking['idPicking'];
        $codProduto = $picking['codProduto'];
        $grade = $picking['grade'];
        $volumes = $picking['volumes'];
        $embalagens = $picking['embalagens'][0];
        $estoqueRepo = $repositorios['estoqueRepo'];
        $qtdEmbalagens = $picking['qtdEmbalagens'];
        $reservaEstoqueRepo = $repositorios['reservaEstoqueRepo'];
        $idVolume = null;
        if (count($volumes) > 0) {
            $idVolume = $volumes[0];
        }
        $qtdPickingReal = $estoqueRepo->getQtdProdutoByVolumesOrProduct($codProduto, $grade, $idPicking, $volumes);
        $reservaEntradaPicking = $reservaEstoqueRepo->getQtdReservadaByProduto($codProduto, $grade, $idVolume, $idPicking, "E");
        $reservaSaidaPicking = $reservaEstoqueRepo->getQtdReservadaByProduto($codProduto, $grade, $idVolume, $idPicking, "S");
        $saldoPicking = $qtdPickingReal + $reservaEntradaPicking + $reservaSaidaPicking;
        $qtdRessuprir = $capacidadePicking - $saldoPicking;
        //GERA A O.S DE ACORDO COM OS ENDEREÇOS DE PULMAO
        $params = array(
            'idProduto' => $codProduto,
            'grade' => $grade,
            'idVolume' => $volumes,
            'idEnderecoIgnorar' => $idPicking,
            'capacidadePicking' => $capacidadePicking,
            'qtdRessuprir' => $qtdRessuprir,
            'norma' => $picking['norma'],
            'tiporessuprimento' => $picking['tiporessuprimento']
        );
        //CALCULO A QUANTIDADE PARA RESSUPRIR
        $estoquePulmao = $estoqueRepo->getEstoqueByParams($params);
        $restante = 0;
        foreach ($estoquePulmao as $estoque) {
            $qtdEstoque = $estoque['SALDO'];
            $validadeEstoque = $estoque['DTH_VALIDADE'];
            if (($capacidadePicking - $saldoPicking) > 0) {
                if ($picking['tiporessuprimento'] == 1) {
                    if ($saldoPicking <= $capacidadePicking) {
                        $restante = $capacidadePicking - $saldoPicking;
                        if ($restante >= $qtdEstoque) {
                            $qtdOnda = $qtdEstoque;
                        } else {
                            if ($restante == 0) {
                                $restante = $qtdEstoque;
                            }
                            $qtdOnda = $restante;
                        }
                        if ($qtdEmbalagens != null) {
                            $qtdOnda = (floor($qtdOnda / $qtdEmbalagens)) * $qtdEmbalagens;
                        }
                        if ($qtdOnda > 0) {
                            $osGeradas[$estoque['DSC_DEPOSITO_ENDERECO']] = array(
                                'embalagens' => json_encode($embalagens),
                                'volumes' => json_encode($volumes),
                                'qtdOnda' => $qtdOnda,
                                'enderecoPulmao' => $estoque['DSC_DEPOSITO_ENDERECO'],
                                'idPicking' => $idPicking,
                                'validadeEstoque' => $validadeEstoque
                            );
                        }
                        if ($capacidadePicking < $picking['norma']) {
                            break;
                        }
                        $saldoPicking = ($saldoPicking + $qtdEstoque);
                    }
                } else {
                    $saldoPicking = ($saldoPicking + $qtdEstoque);
                    if ($saldoPicking <= $capacidadePicking) {
                        $osGeradas[$estoque['DSC_DEPOSITO_ENDERECO']] = array(
                            'embalagens' => json_encode($embalagens),
                            'volumes' => json_encode($volumes),
                            'qtdOnda' => $qtdEstoque,
                            'enderecoPulmao' => $estoque['DSC_DEPOSITO_ENDERECO'],
                            'idPicking' => $idPicking,
                            'validadeEstoque' => $validadeEstoque
                        );
                        if ($capacidadePicking < $picking['norma']) {
                            break;
                        }
                    } else {
                        break;
                    }
                }
            } else {
                break;
            }
        }
        return $osGeradas;
    }

    private function getOndasNaoSequenciadas() {
        $sql = $this->getEntityManager()->createQueryBuilder()
                ->select("o")
                ->from("wms:Ressuprimento\OndaRessuprimentoOs", 'o')
                ->innerJoin('o.endereco', 'e')
                ->where("o.sequencia IS NULL")
                ->orderBy("e.descricao");
        $result = $sql->getQuery()->getResult();
        return $result;
    }

}
