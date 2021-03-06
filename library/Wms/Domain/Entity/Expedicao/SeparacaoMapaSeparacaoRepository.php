<?php
namespace Wms\Domain\Entity\Expedicao;

use Doctrine\ORM\EntityRepository;

class SeparacaoMapaSeparacaoRepository extends EntityRepository{

    public function separaProduto($codigoBarras, $codMapaSeparacao, $codOs, $codDepositoEndereco, $qtdSeparar, $lote = null){
        $produtoRepo = $this->getEntityManager()->getRepository("wms:Produto");
        $produtoEn = $produtoRepo->getProdutoByCodBarrasOrCodProduto($codigoBarras);
        $embalagemRepo = $this->getEntityManager()->getRepository("wms:Produto\Embalagem");
        $embalagem = $embalagemRepo->getEmbalagemByCodigo($codigoBarras);
        if(!empty($embalagem)){
            $qtdSepararVerifica = $embalagem[0]['quantidade'] * $qtdSeparar;
        }
        if($this->verificaProdutoSeparar($produtoEn->getId(), $produtoEn->getGrade(), $codMapaSeparacao, $codDepositoEndereco, $qtdSepararVerifica, $lote)){
            if(empty($embalagem)){
                $volumeRepo = $this->getEntityManager()->getRepository("wms:Produto\Volume");
                $volume = $volumeRepo->getVolumeByCodigo($codigoBarras);
                $this->save($produtoEn, $codMapaSeparacao, $codOs, $qtdSeparar, null, null, $volume[0]['id'], $lote);
            }else {
                $this->save($produtoEn, $codMapaSeparacao, $codOs, $qtdSeparar, $embalagem[0]['id'], $embalagem[0]['quantidade'], null, $lote);
            }
            $this->geraProdutividadeSeparacao($codOs, $codMapaSeparacao);
        }

    }

    private function geraProdutividadeSeparacao($codOs, $codMapaSeparacao)
    {
        $ordemServicoEntity = $this->getEntityManager()->getReference('wms:OrdemServico',$codOs);
        $pessoaEntity = $ordemServicoEntity->getPessoa();

        $mapaSeparacaoEntity = $this->getEntityManager()->getReference('wms:Expedicao\MapaSeparacao',$codMapaSeparacao);

        if (isset($pessoaEntity) && isset($mapaSeparacaoEntity)) {
            $apontamentoMapaRepository = $this->getEntityManager()->getRepository('wms:Expedicao\ApontamentoMapa');
            $apontamentoMapaEntity = $apontamentoMapaRepository->findOneBy(array('usuario' => $pessoaEntity, 'mapaSeparacao' => $mapaSeparacaoEntity));

            if (!$apontamentoMapaEntity) {
                $apontamentoMapaEntity = new ApontamentoMapa();
                $apontamentoMapaEntity->setMapaSeparacao($mapaSeparacaoEntity);
                $apontamentoMapaEntity->setUsuario($pessoaEntity);
                $apontamentoMapaEntity->setDataConferencia(new \DateTime());

                $this->_em->persist($apontamentoMapaEntity);
            }

            $mapaSeparacaoRepository = $this->getEntityManager()->getRepository('wms:Expedicao\MapaSeparacao');
            $conferenciaPendentes = $mapaSeparacaoRepository->getMapaPendenteSeparacao(null,$codMapaSeparacao);

            $tudoConferido = true;
            foreach ($conferenciaPendentes as $conferencia) {
                if ($conferencia['PERCENTUAL'] < 100) {
                    $tudoConferido = false; break;
                }
            }

            if ($tudoConferido) {
                $apontamentoMapaEntities = $apontamentoMapaRepository->findBy(array('mapaSeparacao' => $mapaSeparacaoEntity));
                $separacaoMapaEntities = $this->getMaxData($codMapaSeparacao);

                foreach ($apontamentoMapaEntities as $apontamentoMapaEntity) {
                    foreach ($separacaoMapaEntities as $separacaoMapaEntity) {
                        if ((int)$separacaoMapaEntity['COD_PESSOA'] == $apontamentoMapaEntity->getCodUsuario()) {
                            $dataSeparacao = new \DateTime($separacaoMapaEntity['DTH_SEPARACAO']);
                            $apontamentoMapaEntity->setDataFimConferencia($dataSeparacao);
                            $this->_em->persist($apontamentoMapaEntity);
                            break;
                        }
                    }
                }
            }
            $this->_em->flush();
        }
    }

    private function getMaxData($codMapaSeparacao)
    {
        $sql = "select to_date(to_char(max(dth_separacao),'dd/mm/yyyy hh24:mi:ss'),'dd/mm/yyyy hh24:mi:ss') dth_separacao, os.cod_pessoa
                    from separacao_mapa_separacao msp
                    inner join ordem_servico os on os.cod_os = msp.cod_os
                where cod_mapa_separacao = $codMapaSeparacao
                group by os.cod_pessoa";

        return $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function verificaLoteSeparacao ($codProduto,$grade,$codMapaSeparacao, $codDepositoEndereco, $lote) {
        $sql = "SELECT DISTINCT DSC_LOTE
                  FROM MAPA_SEPARACAO_PRODUTO MPS 
                 WHERE MPS.COD_PRODUTO = '$codProduto'
                   AND MPS.DSC_GRADE = '$grade'
                   AND MPS.COD_MAPA_SEPARACAO = '$codMapaSeparacao'
                   AND MPS.COD_DEPOSITO_ENDERECO = '$codDepositoEndereco'
                   AND MPS.DSC_LOTE IS NOT NULL";
        $result = $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        if (count($result) >0) {
            if ($lote == null) {
                throw new \Exception("Quantidade a separar para o produto exige que se informe um lote");
            }

            $encontrou = false;
            foreach ($result as $mapaPedido) {
                if ($mapaPedido['DSC_LOTE'] == $lote) {
                    $encontrou = true;
                    continue;
                }
            }

            if ($encontrou == false) {
                throw new \Exception("Lote informado não é o solicitado para o produto pelo mapa");
            }

        } else {
            if ($lote != null) {
                throw new \Exception("Quantidade a separar para o produto não exige lote");
            }
        }

    }

    public function verificaProdutoSeparar($codProduto, $grade, $codMapaSeparacao, $codDepositoEndereco, $qtdSeparar, $lote = null){

        $this->verificaLoteSeparacao($codProduto,$grade,$codMapaSeparacao,$codDepositoEndereco,$lote);

        $vetQtd = $this->getQtdSeparadaProduto($codProduto, $grade, $codMapaSeparacao, $lote);
        $qtdTotalSeparar = $vetQtd[0]['TOTAL'] * -1;
        $where = '';
        if(!empty($lote)){
            $where = " AND MPS.DSC_LOTE = '".$lote."'";
        }

        $sql = "SELECT
                    (MPS.QTD_SEPARAR - MPS.QTD_CORTADO) AS SEPARAR,
                    P.DSC_PRODUTO,
                    P.DSC_GRADE,
                    MPS.COD_DEPOSITO_ENDERECO,
                    MPS.QTD_EMBALAGEM
                FROM 
                  MAPA_SEPARACAO_PRODUTO MPS
                  INNER JOIN PRODUTO P ON (P.COD_PRODUTO = MPS.COD_PRODUTO AND P.DSC_GRADE = MPS.DSC_GRADE)
                WHERE 
                  MPS.COD_PRODUTO = $codProduto AND
                  MPS.DSC_GRADE = '$grade' AND
                  MPS.COD_DEPOSITO_ENDERECO = $codDepositoEndereco AND
                  MPS.COD_MAPA_SEPARACAO = $codMapaSeparacao
                  $where";
        $result = $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        if(!empty($result)){
            foreach ($result as $value){
                $qtdTotalSeparar += $value['SEPARAR'] * $value['QTD_EMBALAGEM'];
            }
            if($qtdTotalSeparar == 0){
                $this->finalizaSeparacaoProduto($codProduto, $grade, $codMapaSeparacao, $codDepositoEndereco, $lote);
                throw new \Exception("Produto totalmente separado.");
            }else {
                if(($qtdTotalSeparar - $qtdSeparar) === 0){
                    $this->finalizaSeparacaoProduto($codProduto, $grade, $codMapaSeparacao, $codDepositoEndereco, $lote);
                }
                if ($qtdSeparar <= $qtdTotalSeparar) {
                    return true;
                } else {
                    throw new \Exception("Quantidade separada excede a quantidade a pedida.");
                }
            }
        }else{
            throw new \Exception("Produto não encontrado nesse endereço.");
        }
    }

    public function finalizaSeparacaoProduto($codProduto, $grade, $codMapaSeparacao, $codDepositoEndereco, $lote){
        $mapaSeparacaoProdRepo = $this->getEntityManager()->getRepository('wms:Expedicao\MapaSeparacaoProduto');
        if(!empty($lote)){
            $mapaProd = $mapaSeparacaoProdRepo->findBy(array('codProduto' => $codProduto, 'dscGrade' => $grade, 'lote' => $lote, 'mapaSeparacao' => $codMapaSeparacao, 'depositoEndereco' => $codDepositoEndereco));
        }else {
            $mapaProd = $mapaSeparacaoProdRepo->findBy(array('codProduto' => $codProduto, 'dscGrade' => $grade, 'mapaSeparacao' => $codMapaSeparacao, 'depositoEndereco' => $codDepositoEndereco));
        }
        foreach ($mapaProd as $mapaEn) {
            $mapaEn->setIndSeparado('S');
            $this->_em->persist($mapaEn);
        }
    }

    public function getQtdSeparadaProduto($codProduto, $grade, $codMapaSeparacao, $lote = null){
        $where = '';
        if(!empty($lote)){
            $where = " AND SMS.DSC_LOTE = '".$lote."'";
        }
        $sql = "SELECT SUM(QTD_SEPARADA * SMS.QTD_EMBALAGEM) AS TOTAL
                FROM  SEPARACAO_MAPA_SEPARACAO SMS
                WHERE SMS.COD_PRODUTO = $codProduto AND
                SMS.DSC_GRADE = '$grade' AND SMS.COD_MAPA_SEPARACAO = $codMapaSeparacao
                $where";
        return $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function save($produtoEn, $codMapaSeparacao, $codOs, $qtdSeparar, $idEmbalagem, $qtdEmb, $idVol = null, $lote = null){
        $separacao = new SeparacaoMapaSeparacao();
        $separacao->setCodMapaSeparacao($codMapaSeparacao);
        $separacao->setCodOs($codOs);
        $separacao->setProduto($produtoEn);
        $separacao->setCodProduto($produtoEn->getId());
        $separacao->setGrade($produtoEn->getGrade());
        $separacao->setCodProdutoEmbalagem($idEmbalagem);
        $separacao->setQtdEmbalagem($qtdEmb);
        $separacao->setCodProdutoVolume($idVol);
        $separacao->setDthSeparacao(new \DateTime());
        $separacao->setQtdSeparada($qtdSeparar);
        $separacao->setLote($lote);
        $this->_em->persist($separacao);
        $this->_em->flush();
    }
}