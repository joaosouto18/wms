<?php
namespace Wms\Domain\Entity\Expedicao;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Symfony\Component\Console\Output\NullOutput;
use Wms\Domain\Entity\Expedicao;

class MapaSeparacaoRepository extends EntityRepository
{

    public function verificaMapaSeparacao($idExpedicao){
        $conferenciaFinalizada = $this->validaConferencia($idExpedicao);
        $this->fechaConferencia($idExpedicao);

        if ($conferenciaFinalizada == false) {
            return 'Existem mapas de separação que ainda não foram totalmente conferidos nesta expedição';
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

        $SQL = "SELECT M.COD_EXPEDICAO,
                       M.COD_MAPA_SEPARACAO,
                       M.COD_PRODUTO,
                       M.DSC_GRADE,
                       M.VOLUME,
                       M.QTD_SEPARAR,
                       NVL(C.QTD_CONFERIDA,0) as QTD_CONFERIDA
                  FROM (SELECT M.COD_EXPEDICAO, MP.COD_MAPA_SEPARACAO, MP.COD_PRODUTO, MP.DSC_GRADE, NVL(MP.COD_PRODUTO_VOLUME,0) as VOLUME, SUM(MP.QTD_EMBALAGEM * MP.QTD_SEPARAR) as QTD_SEPARAR
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
              AND M.QTD_SEPARAR = NVL(C.QTD_CONFERIDA,0) ";

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
            }
        }
        $this->getEntityManager()->flush();

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

    public function adicionaQtdConferidaMapa ($embalagemEn,$volumeEn,$mapaEn,$volumePatrimonioEn,$quantidade){

        $numConferencia = 1;
        $qtdConferida = 0;
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

        if (($qtdConferida + ($qtdEmbalagem*$quantidade)) > $qtdMapa) {
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

        $mapas = $this->findBy(array('expedicao'=>$idExpedicao));
        foreach ($mapas as $mapa) {
            $mapaProduto = $mapaSeparacaoProdutoRepo->findBy(array('mapaSeparacao'=>$mapa->getId(),'indConferido'=>'N'));
            foreach ($mapaProduto as $produtoEn) {
                $produtoEn->setIndConferido('S');
                $this->getEntityManager()->persist($produtoEn);
            }
            $mapa->setCodStatus(EtiquetaSeparacao::STATUS_CONFERIDO);
            $this->getEntityManager()->persist($mapa);
        }
        $this->getEntityManager()->flush();
    }

    public function validaProdutoMapa($codBarras, $embalagemEn, $volumeEn, $mapaEn, $modeloSeparacaoEn, $volumePatrimonioEn) {
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

            $mapaSeparacaoProduto = $this->getEntityManager()->getRepository("wms:Expedicao\MapaSeparacaoProduto")->findBy(array('mapaSeparacao'=> $mapaEn->getId(),
                'codProduto' => $produtoEn->getId(),                                                                                                                             'dscGrade' => $produtoEn->getGrade()));
            if ($mapaSeparacaoProduto == null) {
                $mensagemColetor = true;
                throw new \Exception("O produto " . $produtoEn->getId() . " / " . $produtoEn->getGrade(). " - " . $produtoEn->getDescricao() . " não se encontra no mapa selecionado");
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

            if ((isset($volumePatrimonioEn)) && ($volumePatrimonioEn != null) && ($embalado == false)) {
                $mensagemColetor = true;
                throw new \Exception("O produto " . $produtoEn->getId() . " / " . $produtoEn->getGrade(). " - " . $produtoEn->getDescricao() . " não é embalado");
            }

            if ((!(isset($volumePatrimonioEn)) || ($volumePatrimonioEn == null)) && ($embalado == true)) {
                $mensagemColetor = true;
                throw new \Exception("O produto " . $produtoEn->getId() . " / " . $produtoEn->getGrade(). " - " . $produtoEn->getDescricao() . " é embalado");
            }
        } catch (\Exception $e) {
            if ($mensagemColetor == true) {
                return array('return'=>false, 'message'=>$e->getMessage());
            } else {
                throw new \Exception($e->getMessage());
            }
        }
        return array('return'=>true);
    }
}