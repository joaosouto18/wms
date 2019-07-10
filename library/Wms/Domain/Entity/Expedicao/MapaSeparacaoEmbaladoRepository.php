<?php
namespace Wms\Domain\Entity\Expedicao;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Id\SequenceGenerator;
use Doctrine\ORM\Query;
use Wms\Domain\Entity\Expedicao;

class MapaSeparacaoEmbaladoRepository extends EntityRepository
{

    public function save($idMapa, $codPessoa, $paramsModeloSeparacao, $mapaSeparacaoEmbalado = null, $flush = true)
    {
        $pessoaEn = $this->getEntityManager()->getReference('wms:Pessoa',$codPessoa);
        $mapaSeparacaoEn = $this->getEntityManager()->getReference('wms:Expedicao\MapaSeparacao',$idMapa);
        $siglaEn = $this->getEntityManager()->getReference('wms:Util\Sigla',MapaSeparacaoEmbalado::CONFERENCIA_EMBALADO_INICIADO);
        $sequencia = 1;
        if (!empty($mapaSeparacaoEmbalado)) {
            $sequencia = $mapaSeparacaoEmbalado->getSequencia() + 1;
        }

        $mapaSeparacaoEmbalado = new MapaSeparacaoEmbalado();
        $mapaSeparacaoEmbalado->setMapaSeparacao($mapaSeparacaoEn);
        $mapaSeparacaoEmbalado->setPessoa($pessoaEn);
        $mapaSeparacaoEmbalado->setSequencia($sequencia);
        $mapaSeparacaoEmbalado->setStatus($siglaEn);
        $mapaSeparacaoEmbalado->setUltimoVolume('N');

        if ($paramsModeloSeparacao['agrupContEtiquetas'] == 'S') {
            list($lastVol, $nVolsExp) = self::getCountVolumesEmbExpedicaoByMapa($idMapa);
            if (empty($lastVol)) {
                $mapaSeparacaoEmbalado->setPosVolume($nVolsExp + 1);
            } else {
                $mapaSeparacaoEmbalado->setPosVolume($lastVol + 1);
            }
        }

        $this->getEntityManager()->persist($mapaSeparacaoEmbalado);
        $mapaSeparacaoEmbalado->setId('14'.$mapaSeparacaoEmbalado->getId());
        $this->getEntityManager()->persist($mapaSeparacaoEmbalado);
        if ($flush == true) {
            $this->getEntityManager()->flush();
        }

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

        $qtdPendenteConferencia = $this->getProdutosConferidosByCliente($idMapa,$idPessoa);
        if (count($qtdPendenteConferencia) <= 0) {
            $this->getEntityManager()->beginTransaction();

            $mapaSeparacaoEmbaladoEn->setUltimoVolume('S');

            $this->getEntityManager()->persist($mapaSeparacaoEmbaladoEn);
            $this->getEntityManager()->flush();
            $this->getEntityManager()->commit();
        }

        $modeloEtiqueta = $this->getSystemParameterValue('MODELO_VOLUME_EMBALADO');
        $xy = explode(",",$this->getSystemParameterValue('TAMANHO_ETIQUETA_VOLUME_EMBALADO'));
        switch ($modeloEtiqueta) {
            case 1:
                //LAYOUT CASA DO CONFEITEIRO
                $gerarEtiqueta = new \Wms\Module\Expedicao\Report\EtiquetaEmbalados("P", 'mm', array(75,45));
                break;
            case 2:
                //LAYOUT WILSO
                $gerarEtiqueta = new \Wms\Module\Expedicao\Report\EtiquetaEmbalados("P", 'mm', array(105,75));
                break;
            case 3:
                //LAYOUT ABRAFER
                $gerarEtiqueta = new \Wms\Module\Expedicao\Report\EtiquetaEmbalados("P", 'mm', array(105,75));
                break;
            case 4:
                //LAYOUT HIDRAU
                $gerarEtiqueta = new \Wms\Module\Expedicao\Report\EtiquetaEmbalados("P", 'mm', $xy);
                break;
            case 5:
                //LAYOUT ETIQUETAS AGRUPADAS BASEADO MODELO 1
                $gerarEtiqueta = new \Wms\Module\Expedicao\Report\EtiquetaEmbalados("P", 'mm', array(105,75));
                break;
            default:
                $gerarEtiqueta = new \Wms\Module\Expedicao\Report\EtiquetaEmbalados("P", 'mm', array(105,75));
                break;

        }
        $gerarEtiqueta->imprimirExpedicaoModelo($etiqueta,$mapaSeparacaoEmbaladoRepo,$modeloEtiqueta);

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
                    return 'Existem volumes embalados pendentes de CONFERENCIA!';
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
        if (!empty($idMapaSeparacaoEmabalado)) {
            $andWhere .= " AND MSE.COD_MAPA_SEPARACAO_EMB_CLIENTE = $idMapaSeparacaoEmabalado ";
        }
        if (!empty($idExpedicao)) {
            $andWhere .= " AND MS.COD_EXPEDICAO = $idExpedicao ";
            $andWhere .= " AND MSE.COD_STATUS <> $status ";
        }
        $sql = "SELECT 
                      E.COD_EXPEDICAO, MAX(C.COD_CARGA_EXTERNO) COD_CARGA_EXTERNO, 
                      I.DSC_ITINERARIO,  MAX(C.DSC_PLACA_CARGA) DSC_PLACA_CARGA, 
                      P.NOM_PESSOA, MSE.NUM_SEQUENCIA,  MSE.COD_MAPA_SEPARACAO_EMB_CLIENTE,
                      PE.DSC_ENDERECO, PE.NUM_ENDERECO, PE.NOM_BAIRRO, PE.NOM_LOCALIDADE, 
                      SIGLA.COD_REFERENCIA_SIGLA, MIN(PED.COD_EXTERNO) AS COD_PEDIDO,
                      MSE.POS_VOLUME
                    FROM MAPA_SEPARACAO MS
                    LEFT JOIN MAPA_SEPARACAO_EMB_CLIENTE MSE ON MSE.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO
                    INNER JOIN EXPEDICAO E ON MS.COD_EXPEDICAO = E.COD_EXPEDICAO
                    INNER JOIN CARGA C ON E.COD_EXPEDICAO = C.COD_EXPEDICAO
                    INNER JOIN PEDIDO PED ON PED.COD_CARGA = C.COD_CARGA
                    LEFT JOIN PEDIDO_ENDERECO PE ON PE.COD_PEDIDO = PED.COD_PEDIDO
                    LEFT JOIN PESSOA ON PESSOA.COD_PESSOA = PED.COD_PESSOA
                    LEFT JOIN SIGLA ON SIGLA.COD_SIGLA = PE.COD_UF
                    LEFT JOIN ITINERARIO I ON PED.COD_ITINERARIO = I.COD_ITINERARIO
                    LEFT JOIN MAPA_SEPARACAO_CONFERENCIA MSC ON MSC.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO AND MSE.COD_MAPA_SEPARACAO_EMB_CLIENTE = MSC.COD_MAPA_SEPARACAO_EMBALADO
                    INNER JOIN PESSOA P ON P.COD_PESSOA = MSE.COD_PESSOA AND P.COD_PESSOA = PED.COD_PESSOA
                WHERE 1 = 1
                $andWhere
                AND MSE.COD_MAPA_SEPARACAO_EMB_CLIENTE IS NOT NULL
                GROUP BY E.COD_EXPEDICAO, I.DSC_ITINERARIO, P.NOM_PESSOA, MSE.NUM_SEQUENCIA, MSE.COD_MAPA_SEPARACAO_EMB_CLIENTE,
                PE.DSC_ENDERECO, PE.NUM_ENDERECO, PE.NOM_BAIRRO, PE.NOM_LOCALIDADE, SIGLA.COD_REFERENCIA_SIGLA";

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

    public function getCountVolumesEmbExpedicaoByMapa($idMapa)
    {
        $sql = "SELECT NVL(MAX(MSPEC.POS_VOLUME), 0) LAST_POS, MS.COD_EXPEDICAO, E.COUNT_VOLUMES
                FROM MAPA_SEPARACAO_EMB_CLIENTE MSPEC
                INNER JOIN MAPA_SEPARACAO MS ON MS.COD_MAPA_SEPARACAO = MSPEC.COD_MAPA_SEPARACAO
                INNER JOIN EXPEDICAO E ON E.COD_EXPEDICAO = MS.COD_EXPEDICAO
                WHERE MS.COD_EXPEDICAO IN (SELECT COD_EXPEDICAO FROM MAPA_SEPARACAO MS2 WHERE MS2.COD_MAPA_SEPARACAO = $idMapa)
                GROUP BY MS.COD_EXPEDICAO, E.COUNT_VOLUMES";

        $result = $this->_em->getConnection()->query($sql)->fetchAll();
        return [$result[0]['LAST_POS'], $result[0]['COUNT_VOLUMES']];
    }
}

