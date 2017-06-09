<?php
namespace Wms\Domain\Entity\Expedicao;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Symfony\Component\Console\Output\NullOutput;
use Wms\Domain\Entity\Expedicao;
use Wms\Domain\Entity\Expedicao\EtiquetaSeparacao as Etiqueta;

class MapaSeparacaoRepository extends EntityRepository
{
    protected $math;

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
        $SQL = "SELECT MS.COD_MAPA_SEPARACAO, MS.DTH_CRIACAO, TRIM(MS.DSC_QUEBRA) as QUEBRA, MSP.QTD_SEPARAR as QTD_TOTAL, NVL(MSC.QTD_CONF,0) as QTD_CONF,
                     CAST((MSC.QTD_CONF/MSP.QTD_SEPARAR) * 100 as NUMBER(6,2)) || '%' as PERCENTUAL,
                     MS.COD_EXPEDICAO
                FROM MAPA_SEPARACAO MS
                LEFT JOIN (SELECT MSP.COD_MAPA_SEPARACAO, SUM((MSP.QTD_SEPARAR * MSP.QTD_EMBALAGEM)- MSP.QTD_CORTADO) as QTD_SEPARAR
                             FROM MAPA_SEPARACAO MS
                            INNER JOIN MAPA_SEPARACAO_PRODUTO MSP ON MSP.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO
                            GROUP BY MSP.COD_MAPA_SEPARACAO) MSP ON MSP.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO
                LEFT JOIN (SELECT COD_MAPA_SEPARACAO, SUM(QTD_CONFERIDA * QTD_EMBALAGEM) AS QTD_CONF
                             FROM MAPA_SEPARACAO_CONFERENCIA GROUP BY COD_MAPA_SEPARACAO) MSC ON MSC.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO
                WHERE MS.COD_EXPEDICAO = $idExpedicao
                ORDER BY MS.COD_MAPA_SEPARACAO";
        $result = $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);
        return $result;
    }

    /*
    public function verificaMapaSeparacao($expedicaoEn, $idMapa){
        $mapaSeparacaoRepo  = $this->getEntityManager()->getRepository('wms:Expedicao\MapaSeparacao');
        $mapaSeparacaoProdutoRepo = $this->getEntityManager()->getRepository('wms:Expedicao\MapaSeparacaoProduto');

        $conferenciaFinalizada = $this->validaConferencia($expedicaoEn->getId(), true, $idMapa, 'A');
        $this->alteraStatusMapaAndMapaProdutos($expedicaoEn,$idMapa);

        if ($this->getSystemParameterValue('RESETA_CONFERENCIA_MAPA') == 'S') {
            $this->fechaConferencia($expedicaoEn, $idMapa);
        }

        if (count($conferenciaFinalizada) > 0) {
            $mapaSeparacaoEn = $mapaSeparacaoRepo->findBy(array('expedicao' => $expedicaoEn));
            foreach ($mapaSeparacaoEn as $mapaSeparacao) {
                $mapaSeparacaoProdutos = $mapaSeparacaoProdutoRepo->findBy(array('mapaSeparacao' => $mapaSeparacao->getId()));
                foreach ($mapaSeparacaoProdutos as $mapaProduto) {
                    $mapaProduto->setDivergencia('N');
                    $this->getEntityManager()->persist($mapaProduto);
                }
            }

            foreach ($conferenciaFinalizada as $mapaSeparacaoProduto) {
                $mapaSeparacaoProdutoEn = $this->getEntityManager()->getReference('wms:Expedicao\MapaSeparacaoProduto', (int)$mapaSeparacaoProduto['COD_MAPA_SEPARACAO_PRODUTO']);
                $mapaSeparacaoProdutoEn->setDivergencia('S');
                $this->getEntityManager()->persist($mapaSeparacaoProdutoEn);
            }
            $this->getEntityManager()->flush();
            $this->getEntityManager()->commit();
            return 'Existem produtos para serem Conferidos nesta Expedição';
        }
        return true;
    }
    */

    public function verificaMapaSeparacao($expedicaoEn, $idMapa){

        $result = $this->alteraStatusMapaAndMapaProdutos($expedicaoEn,$idMapa);
        if (is_string($result))
            return $result;

        if ($this->getSystemParameterValue('RESETA_CONFERENCIA_MAPA') == 'S') {
            $this->fechaConferencia($expedicaoEn, $idMapa);
        }

        $mapas = $this->findBy(array('codExpedicao'=>$expedicaoEn->getid()));
        foreach ($mapas as $mapaEn) {
            if ($mapaEn->getCodStatus() != Etiqueta::STATUS_CONFERIDO){
                $this->getEntityManager()->commit();
                return 'Existem Mapas para conferir nesta Expedição';
            }
        }

        return true;
    }

    private function alteraStatusMapaAndMapaProdutos($expedicaoEn, $idMapa) {

        $mapaSeparacaoProdutoRepo = $this->getEntityManager()->getRepository("wms:Expedicao\MapaSeparacaoProduto");

        $acertos      = $this->validaConferencia($expedicaoEn->getId(), true, $idMapa, 'A');
        $divergencias = $this->validaConferencia($expedicaoEn->getId(), true, $idMapa, 'D');
        foreach ($acertos as $acerto) {
            $idMapaSeparacaoProduto = $acerto['COD_MAPA_SEPARACAO_PRODUTO'];
            $mapaProdutoEn = $mapaSeparacaoProdutoRepo->findOneBy(array('id'=>$idMapaSeparacaoProduto));
            $mapaProdutoEn->setIndConferido('S');
            $mapaProdutoEn->setDivergencia('N');
            $this->getEntityManager()->persist($mapaProdutoEn);
        }
        $this->getEntityManager()->flush();

        foreach ($divergencias as $divergenciaProduto) {
            $idMapaProduto = $divergenciaProduto['COD_MAPA_SEPARACAO_PRODUTO'];
            $mapaProdutoEn = $mapaSeparacaoProdutoRepo->findOneBy(array('id'=>$idMapaProduto));
            $mapaProdutoEn->setIndConferido('N');
            $mapaProdutoEn->setDivergencia('S');
            $this->getEntityManager()->persist($mapaProdutoEn);
        }
        $this->getEntityManager()->flush();

        if (count($divergencias) > 0) {
            $this->getEntityManager()->commit();
            if ($idMapa == null) {
                return 'Existem produtos para serem Conferidos nesta Expedição';
            } else {
                return 'Existem produtos para serem Conferidos neste Mapa';
            }
        }

        if ($idMapa != null) {
            $mapas = $this->findBy(array('id'=>$idMapa));
        } else {
            $mapas = $this->findBy(array('codExpedicao'=>$expedicaoEn->getId()));
        }

        foreach ($mapas as $mapaEn){
            $produtosPendentes = $mapaSeparacaoProdutoRepo->findBy(array('mapaSeparacao' => $mapaEn,  'divergencia' => 'S'));
            if (count($produtosPendentes) == 0) {
                $mapaEn->setCodStatus(Etiqueta::STATUS_CONFERIDO);
                $this->getEntityManager()->persist($mapaEn);
            }
        }

        $this->getEntityManager()->flush();
        return 0;
    }

    private function fechaConferencia($expedicaoEn, $idMapa = null){
        $mapaSeparacaoRepo  = $this->getEntityManager()->getRepository('wms:Expedicao\MapaSeparacao');
        $mapaConferenciaRepo = $this->getEntityManager()->getRepository("wms:Expedicao\MapaSeparacaoConferencia");

        if ($idMapa != null) {
            $mapaSeparacaoEn = $mapaSeparacaoRepo->findBy(array('id' => $idMapa));
        } else {
            $mapaSeparacaoEn = $mapaSeparacaoRepo->findBy(array('expedicao' => $expedicaoEn));
        }

        foreach ($mapaSeparacaoEn as $mapaSeparacao) {
            $mapaConferenciaEn = $mapaConferenciaRepo->findBy(array('mapaSeparacao'=>$mapaSeparacao->getId(),'indConferenciaFechada'=>'N'));
            foreach ($mapaConferenciaEn as $mapaConferencia) {
                $mapaConferencia->setIndConferenciaFechada('S');
                $this->getEntityManager()->persist($mapaConferencia);
            }
        }
        $this->getEntityManager()->flush();

    }

    public function validaConferencia($expedicao, $setDivergencia = false, $idMapa = null, $tipoRetorno = 'D')
    {

        if ($tipoRetorno == "D") {
            // EXIBE SOMENTE AS DIVERGENCIAS
            $sinal = " < ";
        } else {
            // EXIBE SOMENTE OS ACERTOS
            $sinal = " = ";
        }

        $modeloSeparacaoEn = $this->getEntityManager()->getReference('wms:Expedicao\ModeloSeparacao',$this->getSystemParameterValue('MODELO_SEPARACAO_PADRAO'));
        $andWhere = ' ';
        if ($setDivergencia == false) {
            $andWhere = " AND MSP.IND_DIVERGENCIA = 'S' ";
        }

        if (isset($modeloSeparacaoEn) && !empty($modeloSeparacaoEn)) {
            $quebra = $modeloSeparacaoEn->getUtilizaQuebraColetor();
            if ($quebra == 'S') {
                if (isset($idMapa) && !empty($idMapa)) {
                    $andWhere .= " AND M.COD_MAPA_SEPARACAO = $idMapa";
                }
            }
        }

          $sql = " SELECT M.COD_MAPA_SEPARACAO,
                          M.COD_PRODUTO,
                          M.DSC_GRADE,
                          P.DSC_PRODUTO,
                          M.QTD_SEPARAR,
                          NVL(C.QTD_CONFERIDA,0) as QTD_CONFERIDA,
                          M.QTD_SEPARAR - NVL(C.QTD_CONFERIDA,0) as QTD_CONFERIR,
                          NVL(MIN(PE.COD_BARRAS), MIN(PV.COD_BARRAS)) as COD_BARRAS,
                          DE.DSC_DEPOSITO_ENDERECO,
                          MSP.COD_MAPA_SEPARACAO_PRODUTO
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
                LEFT JOIN MAPA_SEPARACAO_PRODUTO MSP
                  ON MSP.COD_MAPA_SEPARACAO = M.COD_MAPA_SEPARACAO
                 AND MSP.COD_PRODUTO = M.COD_PRODUTO
                 AND MSP.DSC_GRADE = M.DSC_GRADE
                LEFT JOIN PRODUTO_EMBALAGEM PE ON PE.COD_PRODUTO_EMBALAGEM = MSP.COD_PRODUTO_EMBALAGEM
                LEFT JOIN PRODUTO_VOLUME PV ON PV.COD_PRODUTO_VOLUME = MSP.COD_PRODUTO_VOLUME
                LEFT JOIN DEPOSITO_ENDERECO DE ON DE.COD_DEPOSITO_ENDERECO = PE.COD_DEPOSITO_ENDERECO OR DE.COD_DEPOSITO_ENDERECO = PE.COD_DEPOSITO_ENDERECO
                LEFT JOIN PRODUTO P ON P.COD_PRODUTO = M.COD_PRODUTO AND P.DSC_GRADE = M.DSC_GRADE
              WHERE M.COD_EXPEDICAO = $expedicao
                AND NVL(C.QTD_CONFERIDA,0) $sinal M.QTD_SEPARAR
                $andWhere
                GROUP BY M.COD_MAPA_SEPARACAO,
                         M.COD_PRODUTO,
                         M.DSC_GRADE,
                         P.DSC_PRODUTO,
                         M.QTD_SEPARAR,
                         C.QTD_CONFERIDA,
                         DE.DSC_DEPOSITO_ENDERECO,
                         MSP.COD_MAPA_SEPARACAO_PRODUTO
            ORDER BY COD_MAPA_SEPARACAO, M.COD_PRODUTO";

        $result = $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);

        return $result;

    }

    public function getQtdProdutoMapa($embalagemEn, $volumeEn, $mapaEn, $codPessoa){
        $sqlVolume = "";
        $sqlPessoa = "";
        $idMapa = $mapaEn->getId();
        $idExpedicao = $mapaEn->getExpedicao()->getId();

        $modeloSeparacaoEn = $this->getEntityManager()->getRepository('wms:Expedicao\ModeloSeparacao')->find(1);
        $quebraColetor = $modeloSeparacaoEn->getUtilizaQuebraColetor();
        if ($quebraColetor == 'S') {
            $whereQuebra = " AND M.COD_MAPA_SEPARACAO = $idMapa";
        } else {
            $whereQuebra = " AND MS.COD_EXPEDICAO = $idExpedicao";
        }
        if ($embalagemEn != null) {
            $grade = $embalagemEn->getProduto()->getGrade();
            $idProduto = $embalagemEn->getProduto()->getId();
        } else {
            $grade = $volumeEn->getProduto()->getGrade();
            $idProduto = $volumeEn->getProduto()->getId();
            $sqlVolume = " AND M.COD_PRODUTO_VOLUME = " .$volumeEn->getId();
        }
        if (isset($codPessoa) && !empty($codPessoa)) {
            $sqlPessoa = " AND M.COD_PEDIDO_PRODUTO IN (
                            SELECT COD_PEDIDO_PRODUTO FROM PEDIDO_PRODUTO WHERE COD_PEDIDO IN (SELECT COD_PEDIDO FROM PEDIDO WHERE COD_PESSOA = $codPessoa)
                         )";
        }

        $SQL = "SELECT SUM(M.QTD_EMBALAGEM * M.QTD_SEPARAR) as QTD, SUM(NVL(M.QTD_CORTADO,0)) QTD_CORTADO
                  FROM MAPA_SEPARACAO_PRODUTO M
                  INNER JOIN MAPA_SEPARACAO MS ON MS.COD_MAPA_SEPARACAO = M.COD_MAPA_SEPARACAO
                 WHERE M.COD_PRODUTO = '$idProduto'
                   AND M.DSC_GRADE = '$grade'
                   $sqlVolume
                   $whereQuebra
                   $sqlPessoa ";

        $result = $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);
        if (count($result) > 0) {
            return $result;
        } else {
            return null;
        }
    }

    public function getQtdConferenciaAberta($embalagemEn, $volumeEn, $mapaEn, $codPessoa){
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

        if ($codPessoa == null){
            $sqlPessoa = " IS NULL";
        } else {
            $sqlPessoa = " = ". $codPessoa;
        }

        $SQL = "SELECT C.NUM_CONFERENCIA, SUM(QTD_EMBALAGEM * QTD_CONFERIDA) as QTD_CONFERIDA
                  FROM MAPA_SEPARACAO_CONFERENCIA C
                 WHERE C.COD_PRODUTO = '$idProduto'
                   AND C.DSC_GRADE = '$grade'
                   AND C.COD_MAPA_SEPARACAO = '$idMapa'
                   $sqlVolume
                   AND C.IND_CONFERENCIA_FECHADA = 'N'
                   AND C.COD_PESSOA ". $sqlPessoa."
              GROUP BY C.NUM_CONFERENCIA
              ORDER BY C.NUM_CONFERENCIA DESC";

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

    public function verificaConferenciaProduto($mapaEn, $idProduto, $grade) {
        $idMapa = $mapaEn->getId();
        $SQL = "SELECT SEP.COD_PRODUTO, SEP.DSC_GRADE, SEP.QTD_SEP, CONF.QTD_CONF, SEP.QTD_SEP - CONF.QTD_CONF as QTD_PEND
                  FROM (SELECT COD_PRODUTO, DSC_GRADE, SUM((QTD_EMBALAGEM* QTD_SEPARAR)- QTD_CORTADO) as QTD_SEP
                          FROM MAPA_SEPARACAO_PRODUTO
                         WHERE COD_MAPA_SEPARACAO = $idMapa
                         GROUP BY COD_PRODUTO, DSC_GRADE) SEP
                 LEFT JOIN (SELECT COD_PRODUTO, DSC_GRADE, SUM(QTD_EMBALAGEM * QTD_CONFERIDA) as QTD_CONF
                               FROM MAPA_SEPARACAO_CONFERENCIA
                              WHERE COD_MAPA_SEPARACAO = $idMapa
                              GROUP BY COD_PRODUTO, DSC_GRADE) CONF
                    ON CONF.COD_PRODUTO = SEP.COD_PRODUTO AND CONF.DSC_GRADE = SEP.DSC_GRADE
                 WHERE SEP.QTD_SEP - NVL(CONF.QTD_CONF,0) > 0";
        $result = $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);

        if (count($result) == 0) {
            $status = $this->getEntityManager()->find('wms:Util\Sigla', Etiqueta::STATUS_CONFERIDO);
            $mapaEn->setStatus($status);
            $this->getEntityManager()->persist($mapaEn);
            $this->getEntityManager()->flush();
            return array('result'=> true ,
                         'msg' => 'Todo o Mapa foi conferido com sucesso!');
        }

        foreach ($result as $produto) {
            if (($produto['COD_PRODUTO'] == $idProduto) && ($produto['DSC_GRADE'] == $grade)) {
                return array('result'=> true ,
                             'msg' => 'Quantidade conferida com sucesso');
            }
        }

        return array('result'=> true ,
                     'msg' => 'Todos os Produtos ' . $idProduto .' - '. $grade. ' foram conferidos com sucesso!');

    }

    public function adicionaQtdConferidaMapa ($embalagemEn,$volumeEn,$mapaEn,$volumePatrimonioEn,$quantidade,$codPessoa=null,$ordemServicoId=null,$forcaFinalizacao=false){

        $numConferencia = 1;
        $qtdConferida = 0;
        $qtdCortada = 0;
        $qtdMapa = 0;

        $ultConferencia = $this->getQtdConferenciaAberta($embalagemEn,$volumeEn,$mapaEn,$codPessoa);
        $qtdProdutoMapa = $this->getQtdProdutoMapa($embalagemEn,$volumeEn,$mapaEn,$codPessoa);

        if (!empty($qtdProdutoMapa)){
            $qtdMapa = number_format($qtdProdutoMapa[0]['QTD'],2,'.','');
            $qtdCortada = number_format($qtdProdutoMapa[0]['QTD_CORTADO'],2,'.','');
        }

        $qtdEmbalagem = 1;
        if ($embalagemEn != null) {
            $produtoEn = $embalagemEn->getProduto();
            $qtdEmbalagem = number_format($embalagemEn->getQuantidade(),2,'.','');
        } else {
            $produtoEn = $volumeEn->getProduto();
        }
        if (isset($ordemServicoId) && !empty($ordemServicoId)) {
            $qtdEmbalagem = 1;
        }

        if ($ultConferencia != null) {
            $numConferencia = $ultConferencia['numConferencia'];
            $qtdConferida = number_format($ultConferencia['qtd'],2,'.','');
        } else {
            $mapaSeparacaoConferenciaEn = $this->getEntityManager()->getRepository('wms:Expedicao\MapaSeparacaoConferencia')
                ->findBy(array('mapaSeparacao' => $mapaEn, 'codProduto' => $produtoEn->getId(), 'dscGrade' => $produtoEn->getGrade(), 'indConferenciaFechada' => 'S'), array('id' => 'DESC'));
            if (isset($mapaSeparacaoConferenciaEn) && !empty($mapaSeparacaoConferenciaEn))
                $numConferencia = $mapaSeparacaoConferenciaEn[0]->getNumConferencia() + 1;
        }

        if ($forcaFinalizacao == false) {
            $qtdDigitada = number_format($qtdEmbalagem,3,'.','') * number_format($quantidade,3,'.','');
        } else {
            $qtdDigitada = number_format($quantidade,3,'.','');
        }
        $qtdBanco    = number_format($qtdConferida,3,'.','') + number_format($qtdCortada,3,'.','');
        $qtdMapa     = number_format($qtdMapa,3,'.','');

        $quantidadeConferida = $this->math->totalAdicao($qtdBanco, $qtdDigitada);
        if ($quantidadeConferida > $qtdMapa) {
            throw new \Exception("Quantidade informada(".$qtdEmbalagem * $quantidade.") + $qtdConferida excede a quantidade solicitada no mapa para esse cliente! Produto: " .$produtoEn->getId() . " Mapa:" . $mapaEn->getId());
        }

        /** @var \Wms\Domain\Entity\Expedicao\MapaSeparacaoEmbaladoRepository $mapaSeparacaoEmbaladoRepo */
        $mapaSeparacaoEmbaladoRepo = $this->getEntityManager()->getRepository('wms:Expedicao\MapaSeparacaoEmbalado');
        $mapaSeparacaoEmbaladoEn = $mapaSeparacaoEmbaladoRepo->findOneBy(array('mapaSeparacao' => $mapaEn->getId(), 'pessoa' => $codPessoa, 'status' => MapaSeparacaoEmbalado::CONFERENCIA_EMBALADO_INICIADO));

        if (is_null($ordemServicoId)) {
            $sessao = new \Zend_Session_Namespace('coletor');
            $ordemServicoId = $sessao->osID;
        }

        $novaConferencia = new MapaSeparacaoConferencia();
        $novaConferencia->setMapaSeparacao($mapaEn);
        $novaConferencia->setCodOS($ordemServicoId);
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
        $novaConferencia->setMapaSeparacaoEmbalado($mapaSeparacaoEmbaladoEn);
        $novaConferencia->setDataConferencia(new \DateTime());
        $novaConferencia->setCodPessoa($codPessoa);
        $this->getEntityManager()->persist($novaConferencia);
        $this->getEntityManager()->flush();

    }

    public function conferenciaMapa($idMapa)
    {
        /** @var \Wms\Domain\Entity\Expedicao\MapaSeparacaoRepository $mapaSeparacaoRepo */
        $mapaSeparacaoRepo = $this->getEntityManager()->getRepository("wms:Expedicao\MapaSeparacao");
        $listaProdutosNãoConferidosMapa = $mapaSeparacaoRepo->verificaConferenciaMapa($idMapa);
        $todoMapaConferido = true;

        foreach ($listaProdutosNãoConferidosMapa as $produtoNaoConferidoMapa) {
            if ($produtoNaoConferidoMapa['QTD_PRODUTO_CONFERIR'] != 0) {
                $todoMapaConferido = false;
                break;
            }
        }

        if ($todoMapaConferido == true) {
            $mapaSeparacaoEn = $this->getEntityManager()->getReference('wms:Expedicao\MapaSeparacao', $idMapa);
            $mapaSeparacaoEn->setCodStatus(Etiqueta::STATUS_CONFERIDO);
            $this->getEntityManager()->persist($mapaSeparacaoEn);
            $this->getEntityManager()->flush();
        }
        return $todoMapaConferido;
    }

    public function forcaConferencia($idExpedicao) {
        $mapaSeparacaoProdutoRepo = $this->getEntityManager()->getRepository("wms:Expedicao\MapaSeparacaoProduto");

        $mapas = $this->findBy(array('expedicao' => $idExpedicao));
        foreach ($mapas as $mapa) {
            $expedicaoEn = $mapa->getExpedicao();
            $mapaProduto = $mapaSeparacaoProdutoRepo->findBy(array('mapaSeparacao' => $mapa->getId(),
                                                                   'indConferido' => 'N'));
            foreach ($mapaProduto as $produtoEn) {
                $produtoEn->setIndConferido('S');
                $this->getEntityManager()->persist($produtoEn);
            }

            $mapa->setCodStatus(Etiqueta::STATUS_CONFERIDO);
            $this->getEntityManager()->persist($mapa);
        }

        if (count($mapas) >0) {
            $this->fechaConferencia($expedicaoEn);
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

    private function findMapaByProdutoAndExpedicao($produtoEn, $expedicaoEn) {

        $idProduto = $produtoEn->getId();
        $grade = $produtoEn->getGrade();
        $idExpedicao = $expedicaoEn->getId();

        $SQL = " SELECT MSP.COD_MAPA_SEPARACAO 
                   FROM MAPA_SEPARACAO_PRODUTO MSP
                   LEFT JOIN MAPA_SEPARACAO MS ON MSP.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO
                  WHERE MSP.COD_PRODUTO = '$idProduto'
                    AND MSP.DSC_GRADE = '$grade'
                    AND MS.COD_EXPEDICAO = '$idExpedicao'";
        $result = $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);
        if (count($result) >0) {
            return $result[0]['COD_MAPA_SEPARACAO'];
        }
        return null;
    }

    public function validaProdutoMapa($codBarras, $embalagemEn, $volumeEn, $mapaEn, $modeloSeparacaoEn, $volumePatrimonioEn, $codPessoa = null) {
        /** @var MapaSeparacaoProdutoRepository $mapaSeparacaoProdutoRepo */
        $mapaSeparacaoProdutoRepo = $this->getEntityManager()->getRepository("wms:Expedicao\MapaSeparacaoProduto");
        $mensagemColetor = false;
        $produtoEn = null;
        $idMapa = $mapaEn->getId();
        try {
            if (($embalagemEn == null) && ($volumeEn == null)) {
                $mensagemColetor = false;
                throw new \Exception("Nenhum produto encontrado para o código de barras $codBarras");
            }
            if ($embalagemEn != null)
                $produtoEn = $embalagemEn->getProduto();
            else
                $produtoEn = $volumeEn->getProduto();

            $mapaSeparacaoProduto = $mapaSeparacaoProdutoRepo->findBy(array('mapaSeparacao'=> $mapaEn->getId(),
                'codProduto' => $produtoEn->getId(), 'dscGrade' => $produtoEn->getGrade()));
            if ($mapaSeparacaoProduto == null) {
                if ($modeloSeparacaoEn->getUtilizaQuebraColetor() == "S") {
                    $mensagemColetor = true;
                    throw new \Exception("O produto " . $produtoEn->getId() . " / " . $produtoEn->getGrade(). " - " . $produtoEn->getDescricao() . " não se encontra no mapa selecionado");
                } else {
                    $idMapa = $this->findMapaByProdutoAndExpedicao($produtoEn,$mapaEn->getExpedicao());
                    if ($idMapa == null) {
                        $mensagemColetor = true;
                        throw new \Exception("O produto " . $produtoEn->getId() . " / " . $produtoEn->getGrade(). " - " . $produtoEn->getDescricao() . " não se encontra na expedição selecionada");
                    }
                    $mapaSeparacaoProduto = $mapaSeparacaoProdutoRepo->findBy(array('mapaSeparacao'=> $idMapa,
                        'codProduto' => $produtoEn->getId(), 'dscGrade' => $produtoEn->getGrade()));

                }
            }

            $result = $this->getClientesByMapa($idMapa, $codPessoa, $produtoEn->getId(), $produtoEn->getGrade());

            if (count($result) <= 0) {
                $pessoaEn = $this->getEntityManager()->getRepository('wms:Pessoa')->find($codPessoa);
                $mensagemColetor = true;
                throw new \Exception("O produto " . $produtoEn->getId() . " / " . $produtoEn->getGrade(). " - " . $produtoEn->getDescricao() . " não pertence ao cliente ". $pessoaEn->getNome());
            }

            if ($mapaSeparacaoProduto[0]->getIndConferido() == "S") {
                $mensagemColetor = true;
                throw new \Exception("O produto " . $produtoEn->getId() . " / " . $produtoEn->getGrade(). " - " . $produtoEn->getDescricao() . " já está conferido no mapa selecionado");
            }

            $embalado = false;
            if ($embalagemEn != null) {
                if ($modeloSeparacaoEn->getTipoDefaultEmbalado() == "P") {
                    if ($embalagemEn->getEmbalado() == "S") {
                        $embalado = true;
                    }
                } else {
                    $embalagens = $embalagemEn->getProduto()->getEmbalagens();
                    foreach ($embalagens as $emb){
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
            if ($embalagemEn != null){
                $dscEmbalagem = " - " . $embalagemEn->getDescricao() . " (".$embalagemEn->getQuantidade().") - ";
            }
            if ($modeloSeparacaoEn->getUtilizaVolumePatrimonio() == 'S') {
                if ((isset($volumePatrimonioEn)) && ($volumePatrimonioEn != null) && ($embalado == false)) {
                    $mensagemColetor = true;
                    throw new \Exception("O produto " . $produtoEn->getId() . " / " . $produtoEn->getGrade(). " - " . $produtoEn->getDescricao() . $dscEmbalagem . " não é embalado");
                }

                if ((!(isset($volumePatrimonioEn)) || ($volumePatrimonioEn == null)) && ($embalado == true)) {
                    $mensagemColetor = true;
                    throw new \Exception("O produto " . $produtoEn->getId() . " / " . $produtoEn->getGrade(). " - " . $produtoEn->getDescricao() . $dscEmbalagem . " é embalado");
                }
            }
        } catch (\Exception $e) {
            if ($mensagemColetor == true) {
                return array('return'=>false, 'message'=>$e->getMessage());
            } else {
                throw new \Exception($e->getMessage());
            }
        }
        return array('return'=>true,'idMapa'=>$idMapa);
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


    public function verificaConferenciaMapa($idMapaSeparacao)
    {
        $sql = "SELECT SUM(NVL(MSP.QTD_SEPARAR * MSP.QTD_EMBALAGEM, 0)) - (NVL(MSC.QTD_CONFERIDA, 0) + SUM(MSP.QTD_CORTADO)) AS QTD_PRODUTO_CONFERIR, SUM(NVL(MSP.QTD_SEPARAR * MSP.QTD_EMBALAGEM, 0)), NVL(MSC.QTD_CONFERIDA, 0), MSP.COD_PRODUTO
                FROM MAPA_SEPARACAO MS
                INNER JOIN MAPA_SEPARACAO_PRODUTO MSP ON MSP.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO
                LEFT JOIN (
                  SELECT SUM(MSC.QTD_CONFERIDA * MSC.QTD_EMBALAGEM) QTD_CONFERIDA, MS1.COD_MAPA_SEPARACAO, MSC.COD_PRODUTO, MSC.DSC_GRADE
                  FROM MAPA_SEPARACAO MS1
                  INNER JOIN MAPA_SEPARACAO_CONFERENCIA MSC ON MSC.COD_MAPA_SEPARACAO = MS1.COD_MAPA_SEPARACAO
                  GROUP BY MSC.COD_PRODUTO, MSC.DSC_GRADE, MS1.COD_MAPA_SEPARACAO ) MSC ON MSC.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO AND MSC.COD_PRODUTO = MSP.COD_PRODUTO AND MSC.DSC_GRADE = MSP.DSC_GRADE
                WHERE MS.COD_MAPA_SEPARACAO = $idMapaSeparacao
                GROUP BY MSP.COD_PRODUTO, MSP.DSC_GRADE, MSC.QTD_CONFERIDA ";

        return $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getClientesByMapa($idMapaSeparacao,$codPessoa = null,$idProduto = null,$grade = null)
    {
        $andWhere = '';
        if (isset($codPessoa) && !empty($codPessoa)) {
            $andWhere = ' AND P.COD_PESSOA = '.$codPessoa;
        }
        if (isset($idProduto) && !empty($idProduto) && isset($grade) && !empty($grade)) {
            $andWhere .= " AND PROD.COD_PRODUTO = '$idProduto' AND PROD.DSC_GRADE = '$grade' ";
        }
        $sql = "SELECT P.NOM_PESSOA, PED.COD_PEDIDO, MSPROD.NUM_CAIXA_PC_INI, MSPROD.NUM_CAIXA_PC_FIM, P.COD_PESSOA
                    FROM MAPA_SEPARACAO MS
                    INNER JOIN MAPA_SEPARACAO_PEDIDO MSP ON MSP.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO
                    INNER JOIN PEDIDO_PRODUTO PP ON PP.COD_PEDIDO_PRODUTO = MSP.COD_PEDIDO_PRODUTO
                    INNER JOIN PEDIDO PED ON PP.COD_PEDIDO = PED.COD_PEDIDO
                    INNER JOIN PESSOA P ON P.COD_PESSOA = PED.COD_PESSOA
                    LEFT JOIN (
                      SELECT MSP.NUM_CAIXA_PC_INI, MSP.NUM_CAIXA_PC_FIM, MSP.COD_MAPA_SEPARACAO, MSP.COD_PEDIDO_PRODUTO
                      FROM MAPA_SEPARACAO_PRODUTO MSP
                      WHERE MSP.COD_MAPA_SEPARACAO = $idMapaSeparacao
                    ) MSPROD ON MSPROD.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO AND MSPROD.COD_PEDIDO_PRODUTO = PP.COD_PEDIDO_PRODUTO
                    INNER JOIN PRODUTO PROD ON PROD.COD_PRODUTO = PP.COD_PRODUTO AND PROD.DSC_GRADE = PP.DSC_GRADE

                WHERE MS.COD_MAPA_SEPARACAO = $idMapaSeparacao
                $andWhere
                GROUP BY P.NOM_PESSOA, PED.COD_PEDIDO, MSPROD.NUM_CAIXA_PC_INI, MSPROD.NUM_CAIXA_PC_FIM, P.COD_PESSOA
                ORDER BY MSPROD.NUM_CAIXA_PC_INI";

        return $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getClientesByConferencia($idMapaSeparacao)
    {
        $statusEmbalado = MapaSeparacaoEmbalado::CONFERENCIA_EMBALADO_INICIADO;
        $sql = "SELECT P.NOM_PESSOA, P.COD_PESSOA,
                LISTAGG(MSPROD.NUM_CAIXA_PC_INI, ',') WITHIN GROUP (ORDER BY MSPROD.NUM_CAIXA_PC_INI) AS NUM_CAIXA_PC_INI
                    FROM MAPA_SEPARACAO MS
                    INNER JOIN MAPA_SEPARACAO_PEDIDO MSP ON MSP.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO
                    INNER JOIN PEDIDO_PRODUTO PP ON PP.COD_PEDIDO_PRODUTO = MSP.COD_PEDIDO_PRODUTO
                    INNER JOIN PEDIDO PED ON PP.COD_PEDIDO = PED.COD_PEDIDO
                    INNER JOIN PESSOA P ON P.COD_PESSOA = PED.COD_PESSOA
                    INNER JOIN (
                      SELECT SUM(DISTINCT MSP.QTD_EMBALAGEM * MSP.QTD_SEPARAR - NVL(MSP.QTD_CORTADO,0)) QTD_SEPARAR,
                      MSP.NUM_CAIXA_PC_INI, MSP.NUM_CAIXA_PC_FIM,
                      MSP.COD_MAPA_SEPARACAO,
                      MSP.COD_PEDIDO_PRODUTO, MSP.COD_PRODUTO, MSP.DSC_GRADE
                      FROM MAPA_SEPARACAO_PRODUTO MSP
                      WHERE MSP.COD_MAPA_SEPARACAO = $idMapaSeparacao
                      GROUP BY MSP.NUM_CAIXA_PC_INI, MSP.NUM_CAIXA_PC_FIM, MSP.COD_MAPA_SEPARACAO,
                      MSP.COD_PEDIDO_PRODUTO, MSP.COD_PRODUTO, MSP.DSC_GRADE
                    ) MSPROD ON MSPROD.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO AND MSPROD.COD_PEDIDO_PRODUTO = PP.COD_PEDIDO_PRODUTO
                    INNER JOIN PRODUTO PROD ON PROD.COD_PRODUTO = PP.COD_PRODUTO AND PROD.DSC_GRADE = PP.DSC_GRADE
                    LEFT JOIN (
                      SELECT SUM(MSC.QTD_EMBALAGEM * MSC.QTD_CONFERIDA) QTD_CONFERIDA, MSC.COD_PRODUTO, MSC.DSC_GRADE, MS.COD_MAPA_SEPARACAO, MSC.COD_PESSOA
                      FROM MAPA_SEPARACAO_CONFERENCIA MSC
                      INNER JOIN MAPA_SEPARACAO MS ON MSC.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO
                      WHERE MSC.COD_MAPA_SEPARACAO = $idMapaSeparacao
                      GROUP BY MSC.COD_PRODUTO, MSC.DSC_GRADE, MS.COD_MAPA_SEPARACAO, MSC.COD_PESSOA) MSC ON MSC.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO AND MSC.COD_PRODUTO = PROD.COD_PRODUTO AND MSC.DSC_GRADE = PROD.DSC_GRADE AND MSC.COD_PESSOA = P.COD_PESSOA
                WHERE MS.COD_MAPA_SEPARACAO = $idMapaSeparacao AND MSPROD.QTD_SEPARAR > NVL(MSC.QTD_CONFERIDA,0)
                GROUP BY P.NOM_PESSOA, P.COD_PESSOA
                ORDER BY NUM_CAIXA_PC_INI";

        return $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getMapaSeparacaoById($codMapas)
    {
        $dql = $this->getEntityManager()->createQueryBuilder()
            ->select('ms')
            ->from('wms:Expedicao\MapaSeparacao', 'ms')
            ->where("ms.id IN ($codMapas)");

        return $dql->getQuery()->getResult();
    }

    public function getResumoConferenciaEmbalados($idExpedicao)
    {
        $sql = "SELECT MS.COD_MAPA_SEPARACAO, MSC.COD_MAPA_SEPARACAO_EMB_CLIENTE, P.NOM_PESSOA, S.DSC_SIGLA
                    FROM MAPA_SEPARACAO MS
                    INNER JOIN MAPA_SEPARACAO_EMB_CLIENTE MSC ON MSC.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO
                    INNER JOIN PESSOA P ON P.COD_PESSOA = MSC.COD_PESSOA
                    INNER JOIN SIGLA S ON S.COD_SIGLA = MSC.COD_STATUS
                    WHERE MS.COD_EXPEDICAO = $idExpedicao
                    ORDER BY MS.COD_MAPA_SEPARACAO ASC, MSC.COD_MAPA_SEPARACAO_EMB_CLIENTE ASC";

        return $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getProdutosConferidosByClientes($idMapa,$codPessoa){

        $sql = "SELECT P.NOM_PESSOA, P.COD_PESSOA, LISTAGG(MSPROD.NUM_CAIXA_PC_INI, ',') WITHIN GROUP (ORDER BY MSPROD.NUM_CAIXA_PC_INI) AS NUM_CAIXA_PC_INI, MSPROD.COD_PRODUTO, MSPROD.DSC_GRADE, PROD.DSC_PRODUTO
                FROM MAPA_SEPARACAO MS
                INNER JOIN MAPA_SEPARACAO_PEDIDO MSP ON MSP.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO
                INNER JOIN PEDIDO_PRODUTO PP ON PP.COD_PEDIDO_PRODUTO = MSP.COD_PEDIDO_PRODUTO
                INNER JOIN PEDIDO PED ON PP.COD_PEDIDO = PED.COD_PEDIDO
                INNER JOIN PESSOA P ON P.COD_PESSOA = PED.COD_PESSOA AND P.COD_PESSOA = $codPessoa
                INNER JOIN (
                  SELECT SUM(DISTINCT MSP.QTD_EMBALAGEM * MSP.QTD_SEPARAR - NVL(MSP.QTD_CORTADO,0)) QTD_SEPARAR, MSP.NUM_CAIXA_PC_INI,
                  MSP.NUM_CAIXA_PC_FIM, MSP.COD_MAPA_SEPARACAO, MSP.COD_PEDIDO_PRODUTO, MSP.COD_PRODUTO, MSP.DSC_GRADE
                  FROM MAPA_SEPARACAO_PRODUTO MSP
                  WHERE MSP.COD_MAPA_SEPARACAO = $idMapa
                  GROUP BY MSP.NUM_CAIXA_PC_INI, MSP.NUM_CAIXA_PC_FIM, MSP.COD_MAPA_SEPARACAO, MSP.COD_PEDIDO_PRODUTO, MSP.COD_PRODUTO, MSP.DSC_GRADE, MSP.COD_MAPA_SEPARACAO ) MSPROD ON MSPROD.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO AND MSPROD.COD_PEDIDO_PRODUTO = PP.COD_PEDIDO_PRODUTO
                INNER JOIN PRODUTO PROD ON PROD.COD_PRODUTO = PP.COD_PRODUTO AND PROD.DSC_GRADE = PP.DSC_GRADE
                LEFT JOIN (
                  SELECT SUM(MSC.QTD_EMBALAGEM * MSC.QTD_CONFERIDA) QTD_CONFERIDA, MSC.COD_PRODUTO, MSC.DSC_GRADE, MS.COD_MAPA_SEPARACAO, MSC.COD_PESSOA
                  FROM MAPA_SEPARACAO_CONFERENCIA MSC
                  INNER JOIN MAPA_SEPARACAO MS ON MSC.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO
                  WHERE MSC.COD_MAPA_SEPARACAO = $idMapa
                  GROUP BY MSC.COD_PRODUTO, MSC.DSC_GRADE, MS.COD_MAPA_SEPARACAO, MSC.COD_PESSOA) MSC ON MSC.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO AND MSC.COD_PRODUTO = PROD.COD_PRODUTO AND MSC.DSC_GRADE = PROD.DSC_GRADE AND MSC.COD_PESSOA = P.COD_PESSOA
                WHERE MS.COD_MAPA_SEPARACAO = $idMapa AND MSPROD.QTD_SEPARAR > NVL(MSC.QTD_CONFERIDA,0)
                GROUP BY P.NOM_PESSOA, P.COD_PESSOA, MSPROD.COD_PRODUTO, MSPROD.DSC_GRADE, PROD.DSC_PRODUTO
                ORDER BY NUM_CAIXA_PC_INI";
        return $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function validaExclusaoPedido()
    {

    }

}