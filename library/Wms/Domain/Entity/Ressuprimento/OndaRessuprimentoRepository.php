<?php

namespace Wms\Domain\Entity\Ressuprimento;

use Doctrine\ORM\EntityRepository,
    Wms\Domain\Entity\OrdemServico as OrdemServicoEntity,
    Wms\Domain\Entity\Atividade as AtividadeEntity
;

class OndaRessuprimentoRepository extends EntityRepository
{
    public function getOndasEmAberto($codProduto, $grade){
            $query = $this->getEntityManager()->createQueryBuilder()
                ->select("os.id as OS,
                          w.id as Onda,
                          e.descricao as Endereco,
                          wos.id as OndaOsId,
                          wos.sequencia")
                ->from("wms:Ressuprimento\OndaRessuprimentoOs",'wos')
                ->leftJoin("wos.produtos",'osp')
                ->leftJoin('osp.produto','prod')
                ->leftJoin("wos.os","os")
                ->leftJoin("wos.endereco", 'e')
                ->leftJoin("wos.ondaRessuprimento","w")
                ->where("wos.status = ". \Wms\Domain\Entity\Ressuprimento\OndaRessuprimentoOs::STATUS_ONDA_GERADA)
                ->orderBy("wos.sequencia")
                ->distinct(true);

            if ($codProduto != null) {
                $query->andWhere("prod.id = $codProduto AND prod.grade ='$grade'");
            }
        $result = $query->getQuery()->getArrayResult();
        return $result;
    }

    public function getDadosOnda($OS){
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
                    pk.descricao as Picking
                ")
            ->from("wms:Ressuprimento\OndaRessuprimentoOs","o")
            ->leftJoin("o.produtos", "ps")
            ->leftJoin("o.ondaRessuprimento", "ores")
            ->leftJoin("ps.produto", "p")
            ->leftJoin("o.os", "os")
            ->leftJoin("o.endereco", "e")
            ->leftJoin("o.os","os2")
            ->leftJoin("wms:Ressuprimento\ReservaEstoqueOnda",'reo','WITH','reo.ondaRessuprimentoOs = o.id')
            ->leftJoin("reo.reservaEstoque",'res')
            ->leftJoin("res.endereco",'pk')
            ->where("o.id = $OS")
            ->andWhere("res.tipoReserva = 'E'");

        $result =$dql->getQuery()->getArrayResult();
        return $result[0];
    }


    public function getOndasEmAbertoCompleto($dataInicial, $dataFinal, $status, $showOsId = false, $idProduto = null, $idExpedicao = null, $operador = null)
    {
        $SqlWhere = "  WHERE RES.TIPO_RESERVA = 'E'";
        $osId = "";
        $siglaId = "";

        if ($showOsId == true) {
            $osId = "O.COD_ONDA_RESSUPRIMENTO_OS as ID,";
            $siglaId = "SIGLA.COD_SIGLA as COD_STATUS,";
        }

        if (!empty($status)) {
            $SqlWhere .= " AND O.COD_STATUS = $status";
        }

        if (!empty($idProduto)) {
            $SqlWhere .= " AND P.COD_PRODUTO = $idProduto";
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
    public function finalizaOnda($ondaOs)
    {
        try {
            $this->getEntityManager()->beginTransaction();
            $idOnda = $ondaOs->getId();

            /** @var \Wms\Domain\Entity\Ressuprimento\ReservaEstoqueRepository $reservaEstoqueRepo */
            $reservaEstoqueRepo = $this->getEntityManager()->getRepository("wms:Ressuprimento\ReservaEstoque");
            $pessoaRepo = $this->getEntityManager()->getRepository("wms:Pessoa");

            /** @var \Wms\Domain\Entity\OrdemServico $osEn */
            $osEn = $ondaOs->getOs();
            $idOs = $osEn->getId();
            $idUsuario  = \Zend_Auth::getInstance()->getIdentity()->getId();
            $usuarioEn = $pessoaRepo->find($idUsuario);

            $produtos = array();
            foreach ($ondaOs->getProdutos() as $produto) {
                $produtoArray = array();
                $produtoArray['codProdutoEmbalagem'] = $produto->getCodProdutoEmbalagem();
                $produtoArray['codProdutoVolume'] = $produto->getCodProdutoVolume();
                $produtoArray['codProduto'] = $produto->getProduto()->getId() ;
                $produtoArray['grade'] = $produto->getProduto()->getGrade();
                $produtoArray['qtd'] = $produto->getQtd();
                $produtos[] = $produtoArray;
            }

            $reservaEstoqueRepo->efetivaReservaEstoque(NULL,$produtos,"E","O",$ondaOs->getId(),$idUsuario,$idOs);
            $reservaEstoqueRepo->efetivaReservaEstoque(NULL,$produtos,"S","O",$ondaOs->getId(),$idUsuario,$idOs);

            $ondaOs = $this->getEntityManager()->getRepository("wms:Ressuprimento\OndaRessuprimentoOs")->findOneBy(array('id'=>$idOnda));

            $statusEn = $this->getEntityManager()->getRepository("wms:Util\Sigla")->findOneBy(array('id'=>\Wms\Domain\Entity\Ressuprimento\OndaRessuprimentoOs::STATUS_FINALIZADO));
            $ondaOs->setStatus($statusEn);
            $this->getEntityManager()->persist($ondaOs);

            $osEn->setDataFinal(new \DateTime());
            $osEn->setPessoa($usuarioEn);

            $this->getEntityManager()->flush();
            $this->getEntityManager()->commit();
        } catch(\Exception $e) {
            $this->getEntityManager()->rollback();
            throw new \Exception($e->getMessage());
        }
    }

    public function geraNovaOnda (){
        $idUsuario  = \Zend_Auth::getInstance()->getIdentity()->getId();
        $usuarioRepo = $this->getEntityManager()->getRepository("wms:Usuario");
        $usuarioEn = $usuarioRepo->find($idUsuario);

        $ondaEn = new \Wms\Domain\Entity\Ressuprimento\OndaRessuprimento();
        $ondaEn->setDataCriacao(new \DateTime());
        $ondaEn->setDscObservacao("");
        $ondaEn->setUsuario($usuarioEn);
        $this->getEntityManager()->persist($ondaEn);
        return $ondaEn;
    }

    public function gerarReservaSaidaPicking ($produtos){
        /** @var \Wms\Domain\Entity\ProdutoRepository $produtoRepo */
        $produtoRepo = $this->getEntityManager()->getRepository("wms:Produto");
        /** @var \Wms\Domain\Entity\Ressuprimento\ReservaEstoqueRepository $reservaEstoqueRepo */
        $reservaEstoqueRepo = $this->getEntityManager()->getRepository("wms:Ressuprimento\ReservaEstoque");

        foreach ($produtos as $produto) {
            $codExpedicao = $produto['COD_EXPEDICAO'];
            $codProduto = $produto['COD_PRODUTO'];
            $grade = $produto['DSC_GRADE'];
            $qtd = $produto['QTD']* -1;

            $produtoEn = $produtoRepo->findOneBy(array('id'=>$codProduto,'grade'=>$grade));
            if ($produtoEn->getTipoComercializacao()->getId() == 1) {
                $embalagensEn = $this->getEntityManager()->getRepository("wms:Produto\Embalagem")->findBy(array('codProduto'=>$codProduto,'grade'=>$grade),array('quantidade'=>'ASC'));
                $embalagem = $embalagensEn[0];
                $idPicking = $embalagem->getEndereco()->getId();

                $produtosArray = array();
                    $produtoArray = array();
                    $produtoArray['codProdutoEmbalagem'] = $embalagem->getId();
                    $produtoArray['codProdutoVolume'] = null;
                    $produtoArray['codProduto'] = $codProduto;
                    $produtoArray['grade'] = $grade;
                    $produtoArray['qtd'] = $qtd;
                $produtosArray[] = $produtoArray;

                $reservaEstoqueRepo->adicionaReservaEstoque($idPicking,$produtosArray,"S","E",$codExpedicao);
            } else {
                $normas = $this->getEntityManager()->getRepository("wms:Produto\Volume")->getNormasByProduto($codProduto,$grade);
                foreach ($normas as $norma) {
                    $volumes = $this->getEntityManager()->getRepository("wms:Produto\Volume")->getVolumesByNorma($norma->getId(),$codProduto,$grade);
                    $produtosArray = array();
                    $idPicking = null;
                    foreach ($volumes as $volume) {
                        $produtoArray = array();
                        $produtoArray['codProdutoEmbalagem'] = null;
                        $produtoArray['codProdutoVolume'] = $volume->getId();
                        $produtoArray['codProduto'] = $codProduto;
                        $produtoArray['grade'] = $grade;
                        $produtoArray['qtd'] = $qtd;
                        $produtosArray[] = $produtoArray;
                        $idPicking = $volume->getEndereco()->getId();
                    }
                    $reservaEstoqueRepo->adicionaReservaEstoque($idPicking,$produtosArray,"S","E",$codExpedicao);
                }
            }
        }
    }

    public function relacionaOndaPedidosExpedicao ($pedidosProdutosRessuprir, $ondaEn){
        /** @var \Wms\Domain\Entity\Expedicao\PedidoRepository $pedidoRepo */
        $pedidoRepo = $this->getEntityManager()->getRepository("wms:Expedicao\Pedido");
        /** @var \Wms\Domain\Entity\ProdutoRepository $produtoRepo */
        $produtoRepo = $this->getEntityManager()->getRepository("wms:Produto");

        foreach ($pedidosProdutosRessuprir as $pedidoProduto){

            $codPedido = $pedidoProduto['COD_PEDIDO'];
            $codProduto = $pedidoProduto['COD_PRODUTO'];
            $grade = $pedidoProduto['DSC_GRADE'];
            $qtd = $pedidoProduto['QTD'];

            $produtoEn = $produtoRepo->findOneBy(array('id'=>$codProduto,'grade'=>$grade));
            $pedidoEn = $pedidoRepo->findOneBy(array('id'=>$codPedido));

            $ondaPedido = new \Wms\Domain\Entity\Ressuprimento\OndaRessuprimentoPedido();
            $ondaPedido->setOndaRessuprimento($ondaEn);
            $ondaPedido->setPedido($pedidoEn);
            $ondaPedido->setProduto($produtoEn);
            $ondaPedido->setQtd($qtd);
            $this->getEntityManager()->persist($ondaPedido);
        }
    }

    private function saveOs ($produtoEn,$embalagens, $volumes,$qtdOnda, $ondaEn,$enderecoPulmaoEn, $idPicking){
        /** @var \Wms\Domain\Entity\OrdemServicoRepository $ordemServicoRepo */
        $ordemServicoRepo = $this->_em->getRepository('wms:OrdemServico');
        /** @var \Wms\Domain\Entity\Ressuprimento\ReservaEstoqueRepository $reservaEstoqueRepo */
        $reservaEstoqueRepo = $this->getEntityManager()->getRepository("wms:Ressuprimento\ReservaEstoque");
        /** @var \Wms\Domain\Entity\Util\SiglaRepository $siglaRepo */
        $siglaRepo = $this->getEntityManager()->getRepository("wms:Util\Sigla");

        $statusEn = $siglaRepo->findOneBy(array('id'=>\Wms\Domain\Entity\Ressuprimento\OndaRessuprimentoOs::STATUS_ONDA_GERADA));

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
        $produtosSaida   = array();

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
            $produtosEntrada[] = $produtoArray;

            $produtoArray['qtd'] = $qtdOnda * -1;
            $produtosSaida[] = $produtoArray;

        }

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
            $produtosEntrada[] = $produtoArray;

            $produtoArray['qtd'] = $qtdOnda * -1;
            $produtosSaida[] = $produtoArray;
        }

        //$this->getEntityManager()->flush();

        //ADICIONA AS RESERVAS DE ESTOQUE
        $reservaEstoqueRepo->adicionaReservaEstoque($idPicking,$produtosEntrada,"E","O",$ondaRessuprimentoOs,$osEn);
        $reservaEstoqueRepo->adicionaReservaEstoque($enderecoPulmaoEn->getId(),$produtosSaida,"S","O",$ondaRessuprimentoOs,$osEn);
    }

    private function geraOsByPicking ($picking,$ondaEn) {
        $capacidadePicking = $picking['capacidadePicking'];
        $pontoReposicao = $picking['pontoReposicao'];
        $idPicking = $picking['idPicking'];
        $codProduto = $picking['codProduto'];
        $grade = $picking['grade'];
        $volumes = $picking['volumes'];
        $embalagens = $picking['embalagens'];

        $idVolume = null;
        if (count($volumes) >0){
            $idVolume = $volumes[0];
        }

        /** @var \Wms\Domain\Entity\Enderecamento\EstoqueRepository $estoqueRepo */
        $estoqueRepo = $this->getEntityManager()->getRepository("wms:Enderecamento\Estoque");
        /** @var \Wms\Domain\Entity\Ressuprimento\ReservaEstoqueRepository $reservaEstoqueRepo */
        $reservaEstoqueRepo = $this->getEntityManager()->getRepository("wms:Ressuprimento\ReservaEstoque");
        /** @var \Wms\Domain\Entity\Deposito\EnderecoRepository $enderecoRepo */
        $enderecoRepo = $this->getEntityManager()->getRepository("wms:Deposito\Endereco");

        //CALCULO A QUANTIDADE PARA RESSUPRIR
        $qtdPickingReal = $estoqueRepo->getQtdProdutoByVolumesOrProduct($codProduto,$grade,$idPicking, $volumes);
        $reservaEntradaPicking = $reservaEstoqueRepo->getQtdReservadaByProduto($codProduto,$grade,$idVolume,$idPicking,"E");
        $reservaSaidaPicking = $reservaEstoqueRepo->getQtdReservadaByProduto($codProduto,$grade,$idVolume, $idPicking,"S");
        $saldo = $qtdPickingReal + $reservaEntradaPicking + $reservaSaidaPicking;

        $produtoEn = $this->getEntityManager()->getRepository('wms:Produto')->findOneBy(array('id'=>$codProduto, 'grade'=>$grade));

        if ($saldo <= $pontoReposicao) {
            $qtdRessuprir = $saldo * -1;
            $qtdRessuprirMax = $qtdRessuprir + $capacidadePicking;

            //GERO AS OS DE ACORDO COM OS ENDEREÇOS DE PULMAO
            $estoquePulmao = $estoqueRepo->getEstoquePulmaoByProduto($codProduto, $grade,$idVolume, false);
            foreach ($estoquePulmao as $estoque) {
                $qtdEstoque = $estoque['SALDO'];
                $idPulmao = $estoque['COD_DEPOSITO_ENDERECO'];

                $enderecoPulmaoEn = $enderecoRepo->findOneBy(array('id'=>$idPulmao));

                //CALCULO A QUANTIDADE DO PALETE
                if ($qtdRessuprirMax >= $qtdEstoque) {
                    $qtdOnda = $qtdEstoque;
                }else {
                    if ($capacidadePicking >= $qtdRessuprir){
                        $qtdOnda = $capacidadePicking;
                    } else {
                        $qtdOnda = ((int) ($qtdRessuprirMax / $capacidadePicking))* $capacidadePicking;
                    }
                    if ($qtdOnda > $qtdEstoque)
                        $qtdOnda = $qtdEstoque;
                }

                if ($qtdOnda > 0) {
                    $this->saveOs($produtoEn,$embalagens,$volumes,$qtdOnda,$ondaEn,$enderecoPulmaoEn,$idPicking);
                }

                $qtdRessuprir = $qtdRessuprir - $qtdOnda;
                $qtdRessuprirMax = $qtdRessuprirMax - $qtdOnda;
                if ($qtdRessuprir <= 0) break;
            }
        }

    }

    public function sequenciaOndasOs(){
        $OndasOs =$this->getOndasNaoSequenciadas();

        foreach ($OndasOs as $os) {
            $os->setSequencia($os->getNextSequenciaSQ());
            $this->getEntityManager()->persist($os);
        }
        $this->getEntityManager()->flush();
    }

    public function geraOsRessuprimento($produtosRessuprir, $ondaEn){
        /** @var \Wms\Domain\Entity\ProdutoRepository $produtoRepo */
        $produtoRepo = $this->getEntityManager()->getRepository("wms:Produto");

        foreach ($produtosRessuprir as $produto){
            $codProduto = $produto['COD_PRODUTO'];
            $grade = $produto['DSC_GRADE'];

            $produtoEn = $produtoRepo->findOneBy(array('id'=>$codProduto,'grade'=>$grade));
            $pickings = array();

            if ($produtoEn->getTipoComercializacao()->getId() == 1) {
                $embalagensEn = $this->getEntityManager()->getRepository("wms:Produto\Embalagem")->findBy(array('codProduto'=>$codProduto,'grade'=>$grade),array('quantidade'=>'ASC'));
                $embalagem = $embalagensEn[0];
                $embalagens = array();
                $embalagens[] = $embalagem->getId();

                $picking = array();
                    $picking['volumes'] = null;
                    $picking['embalagens'] = $embalagens;
                    $picking['capacidadePicking'] = $embalagem->getCapacidadePicking();
                    $picking['pontoReposicao'] = $embalagem->getPontoReposicao();
                    $picking['idPicking'] = $embalagem->getEndereco()->getId();
                    $picking['codProduto'] = $codProduto;
                    $picking['grade'] = $grade;
                $pickings[] = $picking;
            } else {
                $normas = $this->getEntityManager()->getRepository("wms:Produto\Volume")->getNormasByProduto($codProduto,$grade);
                foreach ($normas as $norma) {
                    $volumesEn = $this->getEntityManager()->getRepository("wms:Produto\Volume")->getVolumesByNorma($norma->getId(),$codProduto,$grade);
                    $picking = array();
                    $picking['volumes'] = array();
                    $picking['codProduto'] = $codProduto;
                    $picking['grade'] = $grade;
                    $picking['embalagens'] = null;
                    foreach ($volumesEn as $volume) {
                        $picking['volumes'][] = $volume->getId();
                        $picking['idPicking'] = $volume->getEndereco()->getId();
                        $picking['capacidadePicking'] = $volume->getCapacidadePicking();
                        $picking['pontoReposicao'] = $volume->getPontoReposicao();
                    }
                    $pickings[] = $picking;
                }
            }

            foreach ($pickings as $picking) {
                $this->geraOsByPicking($picking, $ondaEn);
            }

        }
    }

    private function getOndasNaoSequenciadas (){
        $sql = $this->getEntityManager()->createQueryBuilder()
            ->select("o")
            ->from("wms:Ressuprimento\OndaRessuprimentoOs",'o')
            ->innerJoin('o.endereco','e')
            ->where("o.sequencia IS NULL")
            ->orderBy("e.descricao");
        $result = $sql->getQuery()->getResult();
        return $result;
    }

}
