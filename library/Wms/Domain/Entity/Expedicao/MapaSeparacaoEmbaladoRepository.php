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
        $mapaSeparacaoEmbalado->setUltimoVolume('N');
        $this->getEntityManager()->persist($mapaSeparacaoEmbalado);
        $mapaSeparacaoEmbalado->setId('14'.$mapaSeparacaoEmbalado->getId());
        $this->getEntityManager()->persist($mapaSeparacaoEmbalado);
        $this->getEntityManager()->flush();
    }

    /** ocorre quando o conferente bipou os produtos do mapa e lacrou aquele determinado volume embalado */
    public function fecharMapaSeparacaoEmbalado($mapaSeparacaoEmbaladoEn)
    {
        $siglaEn = $this->getEntityManager()->getReference('wms:Util\Sigla',MapaSeparacaoEmbalado::CONFERENCIA_EMBALADO_FINALIZADO);
        $mapaSeparacaoEmbaladoEn->setStatus($siglaEn);

        $this->getEntityManager()->persist($mapaSeparacaoEmbaladoEn);
        $this->getEntityManager()->flush();

        return $mapaSeparacaoEmbaladoEn;
    }

    /** ocorre quando o conferente está bipando nos volumes ja lacrados */
    public function conferirVolumeEmbalado($idEmbalado,$idExpedicao,$idMapa)
    {
//        $mapaSeparacaoEmbaladoEn = $this->getEntityManager()->getRepository('wms:Expedicao\MapaSeparacaoEmbalado')->findOneBy(array('id' => $idEmbalado));

        $sql = $this->getEntityManager()->createQueryBuilder()
            ->select('mse')
            ->from('wms:Expedicao\MapaSeparacaoEmbalado','mse')
            ->innerJoin('mse.mapaSeparacao', 'ms')
            ->innerJoin('ms.expedicao', 'e')
            ->where("mse.id = $idEmbalado")
            ->andWhere("e.id = $idExpedicao");

        $mapaSeparacaoEmbaladoEntities = $sql->getQuery()->getResult();

        if (count($mapaSeparacaoEmbaladoEntities) <= 0) {
            throw new \Exception(utf8_encode('Volume Embalado nao encontrado ou nao pertencente a expedicao '.$idExpedicao));
        }
        $siglaEn = $this->getEntityManager()->getReference('wms:Util\Sigla',MapaSeparacaoEmbalado::CONFERENCIA_EMBALADO_FECHADO_FINALIZADO);

        foreach ($mapaSeparacaoEmbaladoEntities as $mapaSeparacaoEmbaladoEntity) {
            $mapaSeparacaoEmbaladoEntity->setStatus($siglaEn);
            $this->getEntityManager()->persist($mapaSeparacaoEmbaladoEntity);
        }
        $this->getEntityManager()->flush();

        return true;
    }

    public function imprimirVolumeEmbalado($mapaSeparacaoEmbaladoEn,$idMapa,$idPessoa)
    {

        /** @var \Wms\Domain\Entity\Expedicao\MapaSeparacaoEmbaladoRepository $mapaSeparacaoEmbaladoRepo */
        $mapaSeparacaoEmbaladoRepo = $this->getEntityManager()->getRepository('wms:Expedicao\MapaSeparacaoEmbalado');
        $etiqueta = $this->getDadosEmbalado($mapaSeparacaoEmbaladoEn->getId());
        if (!isset($etiqueta) || empty($etiqueta) || count($etiqueta) <= 0) {
            throw new \Exception(utf8_encode('Não existe produtos conferidos para esse volume embalado!'));
        }

        $this->getEntityManager()->beginTransaction();
        $qtdPendenteConferencia = $this->getProdutosConferidosByCliente($idMapa,$idPessoa);
        if (count($qtdPendenteConferencia) <= 0) {
            $mapaSeparacaoEmbaladoEn->setUltimoVolume('S');
        }
        $this->getEntityManager()->persist($mapaSeparacaoEmbaladoEn);
        $this->getEntityManager()->flush();
        $this->getEntityManager()->commit();

        $gerarEtiqueta = new \Wms\Module\Expedicao\Report\EtiquetaEmbalados("P", 'mm', array(75, 45));
        $gerarEtiqueta->imprimirExpedicaoModelo1($etiqueta,$mapaSeparacaoEmbaladoRepo);

    }

    public function validaVolumesEmbaladoConferidos($idExpedicao)
    {
        $mapaSeparacaoEmbaladoRepo = $this->getEntityManager()->getRepository('wms:Expedicao\MapaSeparacaoEmbalado');
        $mapaSeparacaoEn = $this->getEntityManager()->getRepository('wms:Expedicao\MapaSeparacao')->findBy(array('codExpedicao' => $idExpedicao));
        foreach ($mapaSeparacaoEn as $mapaSeparacao) {
            $mapaSeparacaoEmbaladoEn = $mapaSeparacaoEmbaladoRepo->findBy(array('mapaSeparacao' => $mapaSeparacao));
            foreach ($mapaSeparacaoEmbaladoEn as $mapaSeparacaoEmbalado) {
                $statusMapaEmbalado = $mapaSeparacaoEmbalado->getStatus()->getId();
                if ($statusMapaEmbalado != MapaSeparacaoEmbalado::CONFERENCIA_EMBALADO_FECHADO_FINALIZADO) {
                    return false;
                }
            }
        }
        return true;
    }

    public function validaVolumesEmbaladoConferidosByMapa($idMapa)
    {
        $mapaSeparacaoEmbaladoRepo = $this->getEntityManager()->getRepository('wms:Expedicao\MapaSeparacaoEmbalado');
        $mapaSeparacaoEn = $this->getEntityManager()->getReference('wms:Expedicao\MapaSeparacao',$idMapa);
        $siglaEn = $this->getEntityManager()->getReference('wms:Util\Sigla',MapaSeparacaoEmbalado::CONFERENCIA_EMBALADO_FINALIZADO);
        $mapaSeparacaoEmbaladoEn = $mapaSeparacaoEmbaladoRepo->findBy(array('mapaSeparacao' => $mapaSeparacaoEn));

        foreach ($mapaSeparacaoEmbaladoEn as $mapaSeparacaoEmbalado) {
            $statusMapaEmbalado = $mapaSeparacaoEmbalado->getStatus()->getId();
            if ($statusMapaEmbalado == MapaSeparacaoEmbalado::CONFERENCIA_EMBALADO_INICIADO) {
                $mapaSeparacaoEmbalado->setStatus($siglaEn);
                $mapaSeparacaoEmbalado->setUltimoVolume('S');
            }
        }
        return true;
    }

    public function getDadosEmbalado($idMapaSeparacaoEmabalado = null, $idExpedicao = null)
    {
        $andWhere = '';
        $status = MapaSeparacaoEmbalado::CONFERENCIA_EMBALADO_INICIADO;
        if (isset($idMapaSeparacaoEmabalado) && !empty($idMapaSeparacaoEmabalado)) {
            $andWhere .= " AND MSE.COD_MAPA_SEPARACAO_EMB_CLIENTE = $idMapaSeparacaoEmabalado ";
        }
        if (isset($idExpedicao) && !empty($idExpedicao)) {
            $andWhere .= " AND MS.COD_EXPEDICAO = $idExpedicao ";
            $andWhere .= " AND MSE.COD_STATUS <> $status ";
        }
        $sql = "SELECT E.COD_EXPEDICAO, MAX(C.COD_CARGA_EXTERNO) COD_CARGA_EXTERNO, I.DSC_ITINERARIO, MAX(C.DSC_PLACA_CARGA) DSC_PLACA_CARGA, P.NOM_PESSOA, MSE.NUM_SEQUENCIA, MSE.COD_MAPA_SEPARACAO_EMB_CLIENTE
                    FROM MAPA_SEPARACAO MS
                    LEFT JOIN MAPA_SEPARACAO_EMB_CLIENTE MSE ON MSE.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO
                    INNER JOIN EXPEDICAO E ON MS.COD_EXPEDICAO = E.COD_EXPEDICAO
                    INNER JOIN CARGA C ON E.COD_EXPEDICAO = C.COD_EXPEDICAO
                    INNER JOIN PEDIDO PED ON PED.COD_CARGA = C.COD_CARGA
                    INNER JOIN ITINERARIO I ON PED.COD_ITINERARIO = I.COD_ITINERARIO
                    LEFT JOIN MAPA_SEPARACAO_CONFERENCIA MSC ON MSC.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO AND MSE.COD_MAPA_SEPARACAO_EMB_CLIENTE = MSC.COD_MAPA_SEPARACAO_EMBALADO
                    INNER JOIN PESSOA P ON P.COD_PESSOA = MSE.COD_PESSOA AND P.COD_PESSOA = PED.COD_PESSOA
                WHERE 1 = 1
                $andWhere
                AND MSE.COD_MAPA_SEPARACAO_EMB_CLIENTE IS NOT NULL
                GROUP BY E.COD_EXPEDICAO, I.DSC_ITINERARIO, P.NOM_PESSOA, MSE.NUM_SEQUENCIA, MSE.COD_MAPA_SEPARACAO_EMB_CLIENTE";

        return $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getProdutosConferidosByCliente($idMapa, $idPessoa)
    {
        $sql = "SELECT SUM(DISTINCT MSP.QTD_EMBALAGEM * MSP.QTD_SEPARAR - NVL(MSP.QTD_CORTADO,0)) QTD_SEPARAR, NVL(MSC.QTD_CONFERIDA,0) QTD_CONFERIDA, MSP.COD_PRODUTO,
                MSP.DSC_GRADE, PESSOA.COD_PESSOA, PESSOA.NOM_PESSOA
                FROM MAPA_SEPARACAO_PRODUTO MSP
                INNER JOIN PEDIDO_PRODUTO PP ON PP.COD_PEDIDO_PRODUTO = MSP.COD_PEDIDO_PRODUTO
                INNER JOIN PEDIDO P ON PP.COD_PEDIDO = P.COD_PEDIDO AND P.COD_PESSOA = $idPessoa
                INNER JOIN MAPA_SEPARACAO MS ON MSP.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO
                LEFT JOIN (
                  SELECT SUM(MSC.QTD_EMBALAGEM * MSC.QTD_CONFERIDA) QTD_CONFERIDA, MSC.COD_PRODUTO, MSC.DSC_GRADE, MS.COD_MAPA_SEPARACAO
                  FROM MAPA_SEPARACAO_CONFERENCIA MSC
                  INNER JOIN MAPA_SEPARACAO MS ON MSC.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO
                  WHERE MS.COD_MAPA_SEPARACAO = $idMapa
                  GROUP BY MSC.COD_PRODUTO, MSC.DSC_GRADE, MS.COD_MAPA_SEPARACAO ) MSC ON MSC.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO AND MSC.COD_PRODUTO = MSP.COD_PRODUTO AND MSC.DSC_GRADE = MSP.DSC_GRADE
                LEFT JOIN (
                  SELECT MS.COD_MAPA_SEPARACAO, P.COD_PESSOA, P.NOM_PESSOA
                  FROM MAPA_SEPARACAO MS
                  INNER JOIN EXPEDICAO E ON MS.COD_EXPEDICAO = E.COD_EXPEDICAO
                  INNER JOIN CARGA C ON E.COD_EXPEDICAO = C.COD_EXPEDICAO
                  INNER JOIN PEDIDO PED ON PED.COD_CARGA = C.COD_CARGA
                  INNER JOIN PESSOA P ON P.COD_PESSOA = PED.COD_PESSOA WHERE MS.COD_MAPA_SEPARACAO = $idMapa AND P.COD_PESSOA = $idPessoa ) PESSOA ON PESSOA.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO
                WHERE MS.COD_MAPA_SEPARACAO = $idMapa AND PESSOA.COD_PESSOA = $idPessoa
                GROUP BY MSP.COD_PRODUTO, MSP.DSC_GRADE, MSC.QTD_CONFERIDA, PESSOA.COD_PESSOA, PESSOA.NOM_PESSOA
                  HAVING SUM(DISTINCT MSP.QTD_EMBALAGEM * MSP.QTD_SEPARAR - NVL(MSP.QTD_CORTADO,0)) - NVL(MSC.QTD_CONFERIDA,0) > 0";

        return $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    /*
    public function getEmbaladosByExpedicao($idExpedicao)
    {
        $SQL = "SELECT C.COD_CARGA_EXTERNO CARREGAMENTO, P.NOM_PESSOA CLIENTE, MSE.COD_MAPA_SEPARACAO_EMB_CLIENTE COD_EMBALADO, MSE.NUM_SEQUENCIA SEQUENCIA
                    FROM MAPA_SEPARACAO MS
                    INNER JOIN MAPA_SEPARACAO_EMB_CLIENTE MSE ON MSE.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO
                    INNER JOIN PESSOA P ON P.COD_PESSOA = MSE.COD_PESSOA
                    INNER JOIN PEDIDO PED ON PED.COD_PESSOA = P.COD_PESSOA
                    INNER JOIN CARGA C ON C.COD_CARGA = PED.COD_CARGA AND C.COD_EXPEDICAO = $idExpedicao
                    INNER JOIN SIGLA S ON S.COD_SIGLA = MSE.COD_STATUS
                WHERE MS.COD_EXPEDICAO = $idExpedicao
                GROUP BY MSE.COD_MAPA_SEPARACAO_EMB_CLIENTE, P.NOM_PESSOA, C.COD_CARGA_EXTERNO, MSE.NUM_SEQUENCIA
                ORDER BY C.COD_CARGA_EXTERNO ASC, P.NOM_PESSOA ASC, MSE.NUM_SEQUENCIA ASC";

        return $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);
    }
    */

}

