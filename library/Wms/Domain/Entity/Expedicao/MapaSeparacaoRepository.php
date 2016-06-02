<?php
namespace Wms\Domain\Entity\Expedicao;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Symfony\Component\Console\Output\NullOutput;
use Wms\Domain\Entity\Expedicao;

class MapaSeparacaoRepository extends EntityRepository
{

    public function getDetalhesConferenciaMapaProduto ($idMapa, $idProduto, $grade, $numConferencia) {
        $SQL = "SELECT OS.COD_OS,
                       P.NOM_PESSOA,
                       NVL(PV.DSC_VOLUME, PE.DSC_EMBALAGEM || ' (' || MSC.QTD_EMBALAGEM || ')') as EMBALAGEM,
                       MSC.QTD_CONFERIDA,
                       TO_CHAR(DTH_CONFERENCIA, 'DD/MM/YYYY HH24:MI:SS') as DTH_CONFERENCIA
                  FROM MAPA_SEPARACAO_CONFERENCIA MSC
                  LEFT JOIN PRODUTO_VOLUME PV ON PV.COD_PRODUTO_VOLUME = MSC.COD_PRODUTO_VOLUME
                  LEFT JOIN PRODUTO_EMBALAGEM PE ON PE.COD_PRODUTO_EMBALAGEM = MSC.COD_PRODUTO_EMBALAGEM
                  LEFT JOIN ORDEM_SERVICO OS ON OS.COD_OS = MSC.COD_OS
                  LEFT JOIN PESSOA P ON P.COD_PESSOA = OS.COD_PESSOA
                 WHERE MSC.COD_MAPA_SEPARACAO = $idMapa
                   AND MSC.COD_PRODUTO = '$idProduto'
                   AND MSC.DSC_GRADE = '$grade'
                   AND MSC.NUM_CONFERENCIA = $numConferencia
                 ORDER BY MSC.DTH_CONFERENCIA";
        $result = $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);
        return $result;
    }

    public function getResumoConferenciaMapaProduto($idMapa) {
        $SQL = "SELECT MSP.COD_MAPA_SEPARACAO,
                       P.COD_PRODUTO,
                       P.DSC_GRADE,
                       P.DSC_PRODUTO,
                       NVL(CONF.NUM_CONFERENCIA,0) as NUM_CONFERENCIA,
                       SUM(MSP.QTD_EMBALAGEM * MSP.QTD_SEPARAR) as QTD_SEPARAR,
                       SUM(MSP.QTD_CORTADO) as QTD_CORTADO,
                       NVL(CONF.QTD_CONFERIDA,0) as QTD_CONFERIDA,
                       CASE WHEN (MSP.IND_CONFERIDO = 'S') OR NVL(CONF.QTD_CONFERIDA,0) + SUM(MSP.QTD_CORTADO) = SUM(MSP.QTD_EMBALAGEM * MSP.QTD_SEPARAR) OR NVL(CONF.QTD_CONFERIDA,0) = SUM(MSP.QTD_EMBALAGEM * MSP.QTD_SEPARAR) THEN 'CONFERIDO'
                            WHEN (SUM(MSP.QTD_CORTADO) = SUM(MSP.QTD_EMBALAGEM * MSP.QTD_SEPARAR)) THEN 'CORTADO'
                            ELSE 'PENDENTE'
                       END AS CONFERIDO
                  FROM MAPA_SEPARACAO_PRODUTO MSP
                  LEFT JOIN PRODUTO P ON P.COD_PRODUTO = MSP.COD_PRODUTO AND P.DSC_GRADE = MSP.DSC_GRADE
                  LEFT JOIN (SELECT MSC.NUM_CONFERENCIA, MSC.COD_PRODUTO, MSC.DSC_GRADE, MSC.COD_MAPA_SEPARACAO, SUM (MSC.QTD_EMBALAGEM * MSC.QTD_CONFERIDA) as QTD_CONFERIDA
                               FROM MAPA_SEPARACAO_CONFERENCIA MSC
                              INNER JOIN (SELECT MAX(NUM_CONFERENCIA) MAX_CONFERENCIA, COD_PRODUTO, DSC_GRADE, NVL(COD_PRODUTO_VOLUME,0) VOLUME, COD_MAPA_SEPARACAO
                                            FROM MAPA_SEPARACAO_CONFERENCIA
                                           GROUP BY COD_PRODUTO, DSC_GRADE , NVL(COD_PRODUTO_VOLUME,0), COD_MAPA_SEPARACAO) MAX_MSC
                                 ON MAX_MSC.COD_MAPA_SEPARACAO = MSC.COD_MAPA_SEPARACAO
                                AND MAX_MSC.COD_PRODUTO = MSC.COD_PRODUTO
                                AND MAX_MSC.DSC_GRADE = MSC.DSC_GRADE
                                AND MAX_MSC.MAX_CONFERENCIA = MSC.NUM_CONFERENCIA
                              GROUP BY MSC.NUM_CONFERENCIA, MSC.COD_PRODUTO, MSC.DSC_GRADE, MSC.COD_MAPA_SEPARACAO) CONF
                    ON CONF.COD_PRODUTO = MSP.COD_PRODUTO
                   AND CONF.DSC_GRADE = MSP.DSC_GRADE
                   AND CONF.COD_MAPA_SEPARACAO = MSP.COD_MAPA_SEPARACAO
                 WHERE MSP.COD_MAPA_SEPARACAO = $idMapa
                 GROUP BY P.COD_PRODUTO,
                          P.DSC_GRADE,
                          P.DSC_PRODUTO,
                        MSP.COD_MAPA_SEPARACAO,
                       CONF.NUM_CONFERENCIA,
                       CONF.QTD_CONFERIDA,
                        MSP.IND_CONFERIDO";
        $result = $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);
        return $result;
    }

    public function getResumoConferenciaMapaByExpedicao ($idExpedicao){
        $SQL = "SELECT MS.COD_MAPA_SEPARACAO, MS.DTH_CRIACAO, TRIM(MS.DSC_QUEBRA) QUEBRA, NVL(SUM(MSP.QTD_TOTAL),0) QTD_TOTAL,
                (CASE WHEN MSP.IND_CONFERIDO = 'S'
                  THEN NVL(SUM(MSP.QTD_TOTAL),0)
                  ELSE
                  NVL(MSC.QTD_CONFERIDA,0)
                  END) AS QTD_CONF,
                (CASE WHEN MSP.IND_CONFERIDO = 'S'
                  THEN '100%'
                  ELSE
                    (CASE WHEN NVL(MSC.QTD_CONFERIDA,0) * 100 / NVL(SUM(MSP.QTD_TOTAL),0) > 100
                    THEN '100%'
                    ELSE
                      CAST(NVL(MSC.QTD_CONFERIDA,0) * 100 / NVL(SUM(MSP.QTD_TOTAL),0) AS NUMBER(6,2)) || '%' END) END) AS PERCENTUAL,
                      MS.COD_EXPEDICAO
                FROM MAPA_SEPARACAO MS
                LEFT JOIN (
                  SELECT SUM(MSC.QTD_CONFERIDA) QTD_CONFERIDA, MS.COD_MAPA_SEPARACAO
                  FROM MAPA_SEPARACAO MS
                  INNER JOIN MAPA_SEPARACAO_CONFERENCIA MSC ON MSC.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO
                  GROUP BY MS.COD_MAPA_SEPARACAO) MSC ON MSC.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO
                LEFT JOIN (
                  SELECT SUM(MSP.QTD_SEPARAR) QTD_TOTAL, MS.COD_MAPA_SEPARACAO, MSP.COD_MAPA_SEPARACAO_PRODUTO, MSP.IND_CONFERIDO
                  FROM MAPA_SEPARACAO_PRODUTO MSP
                  INNER JOIN MAPA_SEPARACAO MS ON MSP.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO
                  GROUP BY MS.COD_MAPA_SEPARACAO, MSP.COD_MAPA_SEPARACAO_PRODUTO, MSP.IND_CONFERIDO ) MSP ON MSP.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO
                WHERE MS.COD_EXPEDICAO = $idExpedicao
                GROUP BY MS.COD_MAPA_SEPARACAO, MS.DTH_CRIACAO, MS.DSC_QUEBRA, MS.COD_EXPEDICAO, MSP.IND_CONFERIDO, MSC.QTD_CONFERIDA";

        $result = $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);
        return $result;
    }

    public function verificaMapaSeparacao($idExpedicao){
        $conferenciaFinalizada = $this->validaConferencia($idExpedicao);

        if ($conferenciaFinalizada == false) {
            return 'Existem mapas de separação que ainda não foram totalmente conferidos nesta expedição';
        } else {
            $this->fechaConferencia($idExpedicao);
        }
        return $conferenciaFinalizada;
    }

    private function fechaConferencia($idExpedicao){
        $mapas = $this->findBy(array('expedicao'=>$idExpedicao));
        $mapaConferenciaRepo = $this->getEntityManager()->getRepository("wms:Expedicao\MapaSeparacaoConferencia");

        foreach ($mapas as $mapa){
            $mapaConferenciaEn = $mapaConferenciaRepo->findBy(array('mapaSeparacao'=>$mapa->getId(),'indConferenciaFechada'=>'N'));
            foreach ($mapaConferenciaEn as $conferenciaEn){
                $conferenciaEn->setIndConferenciaFechada('S');
                $this->getEntityManager()->persist($conferenciaEn);
            }
        }
        $this->getEntityManager()->flush();
    }

    private function validaConferencia($idExpedicao){

        $mapaSeparacaoProdutoRepo = $this->getEntityManager()->getRepository("wms:Expedicao\MapaSeparacaoProduto");
        $pedidoProdutoRepo = $this->getEntityManager()->getRepository("wms:Expedicao\PedidoProduto");

        $SQL = "SELECT M.COD_EXPEDICAO,
                       M.COD_MAPA_SEPARACAO,
                       M.COD_PRODUTO,
                       M.DSC_GRADE,
                       M.VOLUME,
                       M.QTD_SEPARAR,
                       NVL(C.QTD_CONFERIDA,0) as QTD_CONFERIDA
                  FROM (SELECT M.COD_EXPEDICAO, MP.COD_MAPA_SEPARACAO, MP.COD_PRODUTO, MP.DSC_GRADE, NVL(MP.COD_PRODUTO_VOLUME,0) as VOLUME, SUM(MP.QTD_EMBALAGEM * MP.QTD_SEPARAR) - SUM(MP.QTD_CORTADO) as QTD_SEPARAR
                          FROM MAPA_SEPARACAO_PRODUTO MP
                          LEFT JOIN MAPA_SEPARACAO M ON M.COD_MAPA_SEPARACAO = MP.COD_MAPA_SEPARACAO
                         WHERE MP.IND_CONFERIDO = 'N'
                         GROUP BY M.COD_EXPEDICAO, MP.COD_MAPA_SEPARACAO, MP.COD_PRODUTO, MP.DSC_GRADE, NVL(MP.COD_PRODUTO_VOLUME,0)) M
             LEFT JOIN (SELECT COD_MAPA_SEPARACAO, COD_PRODUTO, DSC_GRADE, NVL(COD_PRODUTO_VOLUME,0) as VOLUME, SUM(QTD_EMBALAGEM * QTD_CONFERIDA) as QTD_CONFERIDA
                          FROM MAPA_SEPARACAO_CONFERENCIA
                         WHERE IND_CONFERENCIA_FECHADA = 'N'
                         GROUP BY COD_MAPA_SEPARACAO, COD_PRODUTO, DSC_GRADE, NVL(COD_PRODUTO_VOLUME,0)) C
                    ON M.COD_MAPA_SEPARACAO = C.COD_MAPA_SEPARACAO
                   AND M.COD_PRODUTO = C.COD_PRODUTO
                   AND M.DSC_GRADE = C.DSC_GRADE
                   AND M.VOLUME = C.VOLUME
            WHERE M.COD_EXPEDICAO = $idExpedicao
              AND NVL(C.QTD_CONFERIDA,0) >= M.QTD_SEPARAR";

        $result = $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($result as $produto) {
            $arrayFiltro = array();
            $arrayFiltro['mapaSeparacao'] = $produto['COD_MAPA_SEPARACAO'];
            $arrayFiltro['codProduto'] = $produto['COD_PRODUTO'];
            $arrayFiltro['dscGrade'] = $produto['DSC_GRADE'];

            if ($produto['VOLUME'] != "0") $arrayFiltro['produtoVolume'] = $produto['VOLUME'];
            $produtosEn = $mapaSeparacaoProdutoRepo->findBy($arrayFiltro);
            foreach ($produtosEn as $produtoEn) {

                $produtoEn->setIndConferido('S');
                $this->getEntityManager()->persist($produtoEn);

                $pedidoProdutoEn = $pedidoProdutoRepo->find($produtoEn->getCodPedidoProduto());
                $pedidoProdutoEn->setQtdAtendida($pedidoProdutoEn->getQtdAtendida() + ($produtoEn->getQtdEmbalagem() * $produtoEn->getQtdSeparar()) - $produtoEn->getQtdCortado());

                $this->getEntityManager()->persist($pedidoProdutoEn);
                $this->getEntityManager()->flush();
            }
        }

        $conferido = true;
        $mapas = $this->findBy(array('expedicao'=>$idExpedicao));
        foreach ($mapas as $mapa) {
            $mapaProduto = $mapaSeparacaoProdutoRepo->findBy(array('mapaSeparacao'=>$mapa->getId(),'indConferido'=>'N'));
            if (count($mapaProduto) == 0) {
                $mapa->setCodStatus(EtiquetaSeparacao::STATUS_CONFERIDO);
                $this->getEntityManager()->persist($mapa);
            } else {
                $conferido = false;
            }
        }

        $this->getEntityManager()->flush();
        return $conferido;
    }

    public function getQtdProdutoMapa($embalagemEn, $volumeEn, $mapaEn){
        $sqlVolume = "";
        $idMapa = $mapaEn->getId();
        if ($embalagemEn != null) {
            $grade = $embalagemEn->getProduto()->getGrade();
            $idProduto = $embalagemEn->getProduto()->getId();
        } else {
            $grade = $volumeEn->getProduto()->getGrade();
            $idProduto = $volumeEn->getProduto()->getId();
            $sqlVolume = "AND M.COD_PRODUTO_VOLUME = " .$volumeEn->getId();
        }

        $SQL = "SELECT SUM(M.QTD_EMBALAGEM * M.QTD_SEPARAR) as QTD
                  FROM MAPA_SEPARACAO_PRODUTO M
                 WHERE M.COD_PRODUTO = '$idProduto'
                   AND M.DSC_GRADE = '$grade'
                   $sqlVolume
                   AND M.COD_MAPA_SEPARACAO = $idMapa";

        $result = $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);
        if (count($result) > 0) {
            return $result[0]['QTD'];
        } else {
            return 0;
        }
    }

    public function getQtdConferenciaAberta($embalagemEn, $volumeEn, $mapaEn){
        $sqlVolume = "";
        $idMapa = $mapaEn->getId();
        if ($embalagemEn != null) {
            $grade = $embalagemEn->getProduto()->getGrade();
            $idProduto = $embalagemEn->getProduto()->getId();
        } else {
            $grade = $volumeEn->getProduto()->getGrade();
            $idProduto = $volumeEn->getProduto()->getId();
            $sqlVolume = " AND C.COD_PRODUTO_VOLUME = " .$volumeEn->getId();
        }

        $SQL = "SELECT C.NUM_CONFERENCIA, SUM(QTD_EMBALAGEM * QTD_CONFERIDA) as QTD_CONFERIDA
                  FROM MAPA_SEPARACAO_CONFERENCIA C
                 WHERE C.COD_PRODUTO = '$idProduto'
                   AND C.DSC_GRADE = '$grade'
                   AND C.COD_MAPA_SEPARACAO = '$idMapa'
                   $sqlVolume
                   AND C.IND_CONFERENCIA_FECHADA = 'N'
              GROUP BY C.NUM_CONFERENCIA";

        $result = $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);
        if (count($result) > 0) {
            return array('numConferencia'=>$result[0]['NUM_CONFERENCIA'],
                         'qtd'=>$result[0]['QTD_CONFERIDA']);
        } else {
            return null;
        }
    }

    public function getQtdCortadaByMapa($mapaEn,$embalagemEn,$volumeEn){
        if ($embalagemEn != null) {
            $produtoEn = $embalagemEn->getProduto();
        } else {
            $produtoEn = $volumeEn->getProduto();
        }

        $entidadeMapaProduto = $this->getEntityManager()->getRepository('wms:Expedicao\MapaSeparacaoProduto')->findBy(array('mapaSeparacao'=>$mapaEn->getId(),
                                                                                                                            'codProduto'=>$produtoEn->getId(),
                                                                                                                            'dscGrade'=>$produtoEn->getGrade()));
        $qtdCortada = 0;
        foreach ($entidadeMapaProduto as $mapaProduto){
            $qtdCortada = $qtdCortada + $mapaProduto->getQtdCortado();
        }

        return $qtdCortada;
    }

    public function adicionaQtdConferidaMapa ($embalagemEn,$volumeEn,$mapaEn,$volumePatrimonioEn,$quantidade){

        $numConferencia = 1;
        $qtdConferida = 0;
        $qtdCortada = 0;

        $ultConferencia = $this->getQtdConferenciaAberta($embalagemEn,$volumeEn,$mapaEn);
        $qtdMapa = $this->getQtdProdutoMapa($embalagemEn,$volumeEn,$mapaEn);

        if ($ultConferencia != null) {
            $numConferencia = $ultConferencia['numConferencia'];
            $qtdConferida = $ultConferencia['qtd'];
        }

        $qtdEmbalagem = 1;
        if ($embalagemEn != null) {
            $produtoEn = $embalagemEn->getProduto();
            $qtdEmbalagem = $embalagemEn->getQuantidade();
        } else {
            $produtoEn = $volumeEn->getProduto();
        }

        if (($qtdConferida + $qtdCortada + ($qtdEmbalagem * $quantidade)) > $qtdMapa) {
            throw new \Exception("Quantidade informada excede a quantidade solicitada no mapa");
        }
        $sessao = new \Zend_Session_Namespace('coletor');

        $novaConferencia = new MapaSeparacaoConferencia();
        $novaConferencia->setMapaSeparacao($mapaEn);
        $novaConferencia->setCodOS($sessao->osID);
        $novaConferencia->setCodProduto($produtoEn->getId());
        $novaConferencia->setDscGrade($produtoEn->getGrade());
        $novaConferencia->setProduto($produtoEn);
        $novaConferencia->setIndConferenciaFechada("N");
        $novaConferencia->setNumConferencia($numConferencia);
        $novaConferencia->setProdutoEmbalagem($embalagemEn);
        $novaConferencia->setProdutoVolume($volumeEn);
        $novaConferencia->setQtdEmbalagem($qtdEmbalagem);
        $novaConferencia->setQtdConferida($quantidade);
        $novaConferencia->setVolumePatrimonio($volumePatrimonioEn);
        $novaConferencia->setDataConferencia(new \DateTime());
        $this->getEntityManager()->persist($novaConferencia);
        $this->getEntityManager()->flush();

    }

    public function forcaConferencia($idExpedicao) {
        $mapaSeparacaoProdutoRepo = $this->getEntityManager()->getRepository("wms:Expedicao\MapaSeparacaoProduto");
        $pedidoProdutoRepo = $this->getEntityManager()->getRepository("wms:Expedicao\PedidoProduto");

        $mapas = $this->findBy(array('expedicao' => $idExpedicao));
        foreach ($mapas as $mapa) {
            $mapaProduto = $mapaSeparacaoProdutoRepo->findBy(array('mapaSeparacao' => $mapa->getId(), 'indConferido' => 'N'));
            foreach ($mapaProduto as $produtoEn) {
                $produtoEn->setIndConferido('S');
                $pedidoProdutoEn = $pedidoProdutoRepo->find($produtoEn->getCodPedidoProduto());
                $pedidoProdutoEn->setQtdAtendida($pedidoProdutoEn->getQtdAtendida() + ($produtoEn->getQtdEmbalagem() * $produtoEn->getQtdSeparar()));
                $this->getEntityManager()->persist($pedidoProdutoEn);
                $this->getEntityManager()->persist($produtoEn);
            }
            $mapa->setCodStatus(EtiquetaSeparacao::STATUS_CONFERIDO);
            $this->getEntityManager()->persist($mapa);
        }
        $this->getEntityManager()->flush();
    }

    /**
     * @param $mapaSeparacaoRepo
     * @param $idExpedicao
     * @param $mapaSeparacaoProdutoRepo
     * @param $produtoEn
     * @return mixed
     */
    public function getMapaByProdutoAndExpedicao($idExpedicao, $mapaSeparacaoProdutoRepo, $produtoEn)
    {
        $mapasEn = $this->findBy(array('expedicao' => $idExpedicao));
        foreach ($mapasEn as $mapaEn) {
            $mapaProdutoEn = $mapaSeparacaoProdutoRepo->findOneBy(array('mapaSeparacao' => $mapaEn->getId(),
                'codProduto' => $produtoEn->getId(),
                'dscGrade' => $produtoEn->getGrade()));
            if ($mapaProdutoEn != null) {
                $mapaEn = $mapaProdutoEn->getMapaSeparacao();
                break;
            }
        }
        return array(
            'mapaEn' => $mapaEn,
            'mapaProdutoEn' => $mapaProdutoEn);
    }

    public function validaProdutoMapa($idExpedicao, $codBarras, $embalagemEn, $volumeEn, $mapaEn, $modeloSeparacaoEn, $volumePatrimonioEn)
    {
        $mensagemColetor = false;
        $produtoEn = null;

        try {
            if (($embalagemEn == null) && ($volumeEn == null)) {
                $mensagemColetor = false;
                throw new \Exception("Nenhum produto encontrado para o código de barras $codBarras");
            }
            if ($embalagemEn != null)
                $produtoEn = $embalagemEn->getProduto();
            else
                $produtoEn = $volumeEn->getProduto();

            $mapaSeparacaoProdutoRepo = $this->getEntityManager()->getRepository("wms:Expedicao\MapaSeparacaoProduto");
            $resultProdutoMapa = $this->getMapaByProdutoAndExpedicao($idExpedicao,$mapaSeparacaoProdutoRepo, $produtoEn);
            if ($resultProdutoMapa['mapaEn'] == null) {
                $mensagemColetor = true;
                throw new \Exception("O produto " . $produtoEn->getId() . " / " . $produtoEn->getGrade() . " - " . $produtoEn->getDescricao() . " não se encontra no mapa selecionado");
            }

            if ($resultProdutoMapa['mapaProdutoEn']->getIndConferido() == "S") {
                $mensagemColetor = true;
                throw new \Exception("O produto " . $produtoEn->getId() . " / " . $produtoEn->getGrade() . " - " . $produtoEn->getDescricao() . " já está conferido no mapa selecionado");
            }

            $embalado = false;
            if ($embalagemEn != null) {
                if ($modeloSeparacaoEn->getTipoDefaultEmbalado() == "P") {
                    if ($embalagemEn->getEmbalado() == "S") {
                        $embalado = true;
                    }
                } else {
                    $embalagens = $embalagemEn->getProduto()->getEmbalagens();
                    foreach ($embalagens as $emb) {
                        if ($emb->getIsPadrao() == "S") {
                            if ($embalagemEn->getQuantidade() < $emb->getQuantidade()) {
                                $embalado = true;
                            }
                            break;
                        }
                    }
                }
            }

            $dscEmbalagem = "";
            if ($embalagemEn != null) {
                $dscEmbalagem = " - " . $embalagemEn->getDescricao() . " (" . $embalagemEn->getQuantidade() . ") - ";
            }
            if ((isset($volumePatrimonioEn)) && ($volumePatrimonioEn != null) && ($embalado == false)) {
                $mensagemColetor = true;
                throw new \Exception("O produto " . $produtoEn->getId() . " / " . $produtoEn->getGrade() . " - " . $produtoEn->getDescricao() . $dscEmbalagem . " não é embalado");
            }

            if ((!(isset($volumePatrimonioEn)) || ($volumePatrimonioEn == null)) && ($embalado == true)) {
                $mensagemColetor = true;
                throw new \Exception("O produto " . $produtoEn->getId() . " / " . $produtoEn->getGrade() . " - " . $produtoEn->getDescricao() . $dscEmbalagem . " é embalado");
            }
        } catch (\Exception $e) {
            if ($mensagemColetor == true) {
                return array('return' => false, 'message' => $e->getMessage());
            } else {
                throw new \Exception($e->getMessage());
            }
        }
        return array('return' => true);
    }

    public function getQtdConferidaByVolumePatrimonio($idExpedicao, $idVolume)
    {
        $SQL = "SELECT NVL(SUM(MC.QTD_EMBALAGEM * MC.QTD_CONFERIDA),0) as QTD_CONFERIDA
                  FROM MAPA_SEPARACAO_CONFERENCIA MC
                 INNER JOIN (SELECT MAX(NUM_CONFERENCIA) MAX_C, COD_PRODUTO, DSC_GRADE , NVL(COD_PRODUTO_VOLUME,0) VOLUME, COD_MAPA_SEPARACAO
                               FROM MAPA_SEPARACAO_CONFERENCIA
                              GROUP BY COD_PRODUTO, DSC_GRADE , NVL(COD_PRODUTO_VOLUME,0), COD_MAPA_SEPARACAO) MAX_C
                    ON MAX_C.COD_PRODUTO = MC.COD_PRODUTO
                   AND MAX_C.DSC_GRADE = MC.DSC_GRADE
                   AND MAX_C.COD_MAPA_SEPARACAO = MC.COD_MAPA_SEPARACAO
                   AND MAX_C.VOLUME = NVL(MC.COD_PRODUTO_VOLUME,0)
                  LEFT JOIN MAPA_SEPARACAO MS ON MS.COD_MAPA_SEPARACAO = MC.COD_MAPA_SEPARACAO
                 WHERE MC.COD_VOLUME_PATRIMONIO = $idVolume
                   AND MS.COD_EXPEDICAO = $idExpedicao";
        $result = $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);

        return $result[0]['QTD_CONFERIDA'];
    }

    public function getMapaSeparacaoByExpedicao($idExpedicao)
    {
        $dql = $this->getEntityManager()->createQueryBuilder()
            ->select('ms.id codBarras')
            ->from('wms:Expedicao\MapaSeparacao', 'ms')
            ->where("ms.expedicao = $idExpedicao");

        return $dql->getQuery()->getResult();
    }

    public function verificaConferenciaProduto($idMapaSeparacao,$embalagem,$volume)
    {
        $sql = "SELECT SUM(NVL(MSP.QTD_SEPARAR, 0)) - SUM(NVL(MSC.QTD_CONFERIDA, 0)) AS QTD_PRODUTO_CONFERIR
                FROM MAPA_SEPARACAO MS
                INNER JOIN MAPA_SEPARACAO_PRODUTO MSP ON MSP.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO
                LEFT JOIN (
                  SELECT SUM(MSC.QTD_CONFERIDA) QTD_CONFERIDA, MS1.COD_MAPA_SEPARACAO, MSC.COD_PRODUTO, MSC.DSC_GRADE
                  FROM MAPA_SEPARACAO MS1
                  INNER JOIN MAPA_SEPARACAO_CONFERENCIA MSC ON MSC.COD_MAPA_SEPARACAO = MS1.COD_MAPA_SEPARACAO
                  GROUP BY MSC.COD_PRODUTO, MSC.DSC_GRADE, MS1.COD_MAPA_SEPARACAO
                  ) MSC ON MSC.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO AND (MSC.COD_PRODUTO = MSP.COD_PRODUTO) AND (MSC.DSC_GRADE = MSP.DSC_GRADE)
                WHERE MS.COD_MAPA_SEPARACAO = $idMapaSeparacao ";

        if (isset($embalagem) && !is_null($embalagem)) {
            $produto = $embalagem->getCodProduto();
            $grade = $embalagem->getGrade();
            $sql .= " AND MSP.COD_PRODUTO = '$produto' AND MSP.DSC_GRADE = '$grade'";
        }


        if (isset($volume) && !is_null($volume))
            $sql .= " AND MSP.COD_PRODUTO_VOLUME = " . $volume->getId();

        $sql .= " GROUP BY MSP.COD_PRODUTO, MSP.DSC_GRADE";
        return $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function verificaConferenciaMapa($idMapaSeparacao)
    {
        $sql = "SELECT SUM(NVL(MSP.QTD_SEPARAR, 0)) - SUM(NVL(MSC.QTD_CONFERIDA, 0)) AS QTD_PRODUTO_CONFERIR
                FROM MAPA_SEPARACAO MS
                INNER JOIN MAPA_SEPARACAO_PRODUTO MSP ON MSP.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO
                LEFT JOIN (
                  SELECT SUM(MSC.QTD_CONFERIDA) QTD_CONFERIDA, MS1.COD_MAPA_SEPARACAO, MSC.COD_PRODUTO, MSC.DSC_GRADE
                  FROM MAPA_SEPARACAO MS1
                  INNER JOIN MAPA_SEPARACAO_CONFERENCIA MSC ON MSC.COD_MAPA_SEPARACAO = MS1.COD_MAPA_SEPARACAO
                  GROUP BY MSC.COD_PRODUTO, MSC.DSC_GRADE, MS1.COD_MAPA_SEPARACAO
                  ) MSC ON MSC.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO AND MSC.COD_PRODUTO = MSP.COD_PRODUTO AND MSC.DSC_GRADE = MSP.DSC_GRADE
                WHERE MS.COD_MAPA_SEPARACAO = $idMapaSeparacao
                GROUP BY MSP.COD_PRODUTO, MSP.DSC_GRADE ";
        return $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }
    

}