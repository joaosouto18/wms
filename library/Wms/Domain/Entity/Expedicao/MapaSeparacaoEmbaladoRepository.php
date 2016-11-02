<?php
namespace Wms\Domain\Entity\Expedicao;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Wms\Domain\Entity\Expedicao;

class MapaSeparacaoEmbaladoRepository extends EntityRepository
{

    public function save($idMapa,$codPessoa,$mapaSeparacaoEmbalado=null)
    {
        $pessoaEn = $this->getEntityManager()->getReference('wms:Pessoa',$codPessoa);
        $mapaSeparacaoEn = $this->getEntityManager()->getReference('wms:Expedicao\MapaSeparacao',$idMapa);
        $siglaEn = $this->getEntityManager()->getReference('wms:Util\Sigla',MapaSeparacaoEmbalado::CONFERENCIA_EMBALADO_INICIADO);
        $sequencia = 1;
        if (isset($mapaSeparacaoEmbalado) && !empty($mapaSeparacaoEmbalado)) {
            $sequencia = $mapaSeparacaoEmbalado->getSequencia() + 1;
        }

        $mapaSeparacaoEmbalado = new MapaSeparacaoEmbalado();
        $mapaSeparacaoEmbalado->setMapaSeparacao($mapaSeparacaoEn);
        $mapaSeparacaoEmbalado->setPessoa($pessoaEn);
        $mapaSeparacaoEmbalado->setSequencia($sequencia);
        $mapaSeparacaoEmbalado->setStatus($siglaEn);


        $this->getEntityManager()->persist($mapaSeparacaoEmbalado);
        $this->getEntityManager()->flush();
    }

    public function fecharMapaSeparacaoEmbalado($idMapa,$idPessoa,$mapaSeparacaoEmbaladoRepo)
    {

        $mapaSeparacaoEmbaladoEn = $mapaSeparacaoEmbaladoRepo->findOneBy(array('mapaSeparacao' => $idMapa, 'pessoa' => $idPessoa, 'status' => Expedicao\MapaSeparacaoEmbalado::CONFERENCIA_EMBALADO_INICIADO));
        if (!isset($mapaSeparacaoEmbaladoEn) || empty($mapaSeparacaoEmbaladoEn)) {
            throw new \Exception('Não existe conferencia de embalados em aberto para esse Cliente!');
        }

        $siglaEn = $this->getEntityManager()->getReference('wms:Util\Sigla',MapaSeparacaoEmbalado::CONFERENCIA_EMBALADO_FINALIZADO);
        $mapaSeparacaoEmbaladoEn->setStatus($siglaEn);

        $this->getEntityManager()->persist($mapaSeparacaoEmbaladoEn);
        $this->getEntityManager()->flush();

        return $mapaSeparacaoEmbaladoEn;
    }

    public function imprimirVolumeEmbalado($mapaSeparacaoEmbaladoEn,$existeItensPendentes)
    {

        $etiqueta = $this->getDadosEmbalado($mapaSeparacaoEmbaladoEn->getId());

        $gerarEtiqueta = new \Wms\Module\Expedicao\Report\EtiquetaEmbalados("P", 'mm', array(110, 50));
        $gerarEtiqueta->imprimirExpedicaoModelo1($etiqueta,$existeItensPendentes);

    }

    public function getDadosEmbalado($idMapaSeparacaoEmabalado)
    {
        $sql = "SELECT E.COD_EXPEDICAO, C.COD_CARGA_EXTERNO, I.DSC_ITINERARIO, C.DSC_PLACA_CARGA, P.NOM_PESSOA, MSE.NUM_SEQUENCIA, MSE.COD_MAPA_SEPARACAO_EMB_CLIENTE
                    FROM MAPA_SEPARACAO_EMB_CLIENTE MSE
                    INNER JOIN MAPA_SEPARACAO MS ON MSE.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO
                    INNER JOIN EXPEDICAO E ON MS.COD_EXPEDICAO = E.COD_EXPEDICAO
                    INNER JOIN CARGA C ON E.COD_EXPEDICAO = C.COD_EXPEDICAO
                    INNER JOIN PEDIDO PED ON PED.COD_CARGA = C.COD_CARGA
                    INNER JOIN ITINERARIO I ON PED.COD_ITINERARIO = I.COD_ITINERARIO
                    INNER JOIN MAPA_SEPARACAO_CONFERENCIA MSC ON MSC.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO AND MSE.COD_MAPA_SEPARACAO_EMB_CLIENTE = MSC.COD_MAPA_SEPARACAO_EMBALADO
                    INNER JOIN PESSOA P ON P.COD_PESSOA = MSE.COD_PESSOA
                WHERE MSE.COD_MAPA_SEPARACAO_EMB_CLIENTE = $idMapaSeparacaoEmabalado
                GROUP BY E.COD_EXPEDICAO, C.COD_CARGA_EXTERNO, I.DSC_ITINERARIO, C.DSC_PLACA_CARGA, P.NOM_PESSOA, MSE.NUM_SEQUENCIA, MSE.COD_MAPA_SEPARACAO_EMB_CLIENTE";

        return $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getProdutosConferidosByCliente($idMapa, $idPessoa)
    {
        $sql = "SELECT SUM(MSP.QTD_EMBALAGEM * MSP.QTD_SEPARAR - NVL(MSP.QTD_CORTADO,0)) QTD_SEPARAR, NVL(MSC.QTD_CONFERIDA,0) QTD_CONFERIDA, MSP.COD_PRODUTO, MSP.DSC_GRADE, PESSOA.COD_PESSOA
                    FROM MAPA_SEPARACAO_PRODUTO MSP
                    INNER JOIN MAPA_SEPARACAO MS ON MSP.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO
                    INNER JOIN (
                      SELECT SUM(MSC.QTD_EMBALAGEM * MSC.QTD_CONFERIDA) QTD_CONFERIDA, MSC.COD_PRODUTO, MSC.DSC_GRADE, MS.COD_MAPA_SEPARACAO
                        FROM MAPA_SEPARACAO_CONFERENCIA MSC
                        INNER JOIN MAPA_SEPARACAO MS ON MSC.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO
                      WHERE MS.COD_MAPA_SEPARACAO = $idMapa
                      GROUP BY MSC.COD_PRODUTO, MSC.DSC_GRADE, MS.COD_MAPA_SEPARACAO
                    ) MSC ON MSC.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO AND MSC.COD_PRODUTO = MSP.COD_PRODUTO AND MSC.DSC_GRADE = MSP.DSC_GRADE
                    INNER JOIN (
                      SELECT MS.COD_MAPA_SEPARACAO, P.COD_PESSOA
                        FROM MAPA_SEPARACAO MS
                        INNER JOIN EXPEDICAO E ON MS.COD_EXPEDICAO = E.COD_EXPEDICAO
                        INNER JOIN CARGA C ON E.COD_EXPEDICAO = C.COD_EXPEDICAO
                        INNER JOIN PEDIDO P ON P.COD_CARGA = C.COD_CARGA
                      WHERE MS.COD_MAPA_SEPARACAO = $idMapa AND P.COD_PESSOA = $idPessoa
                    ) PESSOA ON PESSOA.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO
                    WHERE MS.COD_MAPA_SEPARACAO = $idMapa AND PESSOA.COD_PESSOA = $idPessoa
                    GROUP BY MSP.COD_PRODUTO, MSP.DSC_GRADE, MSC.QTD_CONFERIDA, PESSOA.COD_PESSOA
                    HAVING SUM(MSP.QTD_EMBALAGEM * MSP.QTD_SEPARAR - NVL(MSP.QTD_CORTADO,0)) - NVL(MSC.QTD_CONFERIDA,0) > 0";

        return $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }
}

