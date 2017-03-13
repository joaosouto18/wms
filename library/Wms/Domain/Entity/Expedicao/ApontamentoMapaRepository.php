<?php
namespace Wms\Domain\Entity\Expedicao;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;

class ApontamentoMapaRepository extends EntityRepository
{

    public function save($mapaSeparacao,$codUsuario)
    {
        $em = $this->getEntityManager();
        $apontamentoEn = new ApontamentoMapa();
        $apontamentoEn->setDataConferencia(new \DateTime());
        $apontamentoEn->setCodMapaSeparacao($mapaSeparacao->getId());
        $apontamentoEn->setCodUsuario($codUsuario);
        $apontamentoEn->setMapaSeparacao($mapaSeparacao);

        $apontamentosByUsuario = $this->findBy(array('codUsuario' => $codUsuario, 'dataFimConferencia' => null), array('id' => 'DESC'));
        if (count($apontamentosByUsuario) > 0) {
            $ultimoApontamentoByUsuario = $apontamentosByUsuario[0];
            $ultimoApontamentoByUsuario->setDataFimConferencia(new \DateTime());
        }

        $em->persist($apontamentoEn);
        $em->flush();

        return $apontamentoEn;
    }

    public function update($apontamentoMapaEn)
    {
        $em = $this->getEntityManager();
        $apontamentoMapaEn->setDataFimConferencia(new \DateTime());
        $em->persist($apontamentoMapaEn);
        $em->flush();
        return true;
    }

    public function getApontamentoDetalhado($params)
    {

        $idUsuario = $params['usuario'];
        $idExpedicao = $params['expedicao'];
        $idMapaSeparacao = $params['mapaSeparacao'];
        $tipoQuebra = $params['tipoQuebra'];
        $dataInicio = $params['dataInicio'];
        $horaInicio = $params['horaInicio'];
        $dataFim = $params['dataFim'];
        $horaFim = $params['horaFim'];
        $andWhere = ' ';
        $andWhereConf = ' ';

        if (empty($dataInicio)) {
            $hoje = new \DateTime();
            $hoje->sub(new \DateInterval('P01D'));
            $params['dataInicio'] = $dataInicio = $hoje->format('d/m/Y');
        }
        if (empty($dataFim)) {
            $hoje = new \DateTime();
            $params['dataFim'] = $dataFim = $hoje->format('d/m/Y');
        }
        $dataInicio = str_replace('-','/',$dataInicio);
        $dataFim = str_replace('-','/',$dataFim);

        if (isset($idUsuario) && !empty($idUsuario)) {
            $andWhere     .= " AND P.COD_PESSOA = $idUsuario";
            $andWhereConf .= " AND P.COD_PESSOA = $idUsuario";
        }

        if (isset($tipoQuebra) && !empty($tipoQuebra)) {
            $andWhere     .= " AND QUEBRA.IND_TIPO_QUEBRA = 'T'";
            $andWhereConf .= " AND QUEBRA.IND_TIPO_QUEBRA = 'T'";
        }

        if (isset($idExpedicao) && !empty($idExpedicao)) {
            $andWhere     .= " AND E.COD_EXPEDICAO = $idExpedicao";
            $andWhereConf .= " AND E.COD_EXPEDICAO = $idExpedicao";
        }

        if (isset($idMapaSeparacao) && !empty($idMapaSeparacao)) {
            $andWhere     .= " AND MS.COD_MAPA_SEPARACAO = $idMapaSeparacao";
            $andWhereConf .= " AND MS.COD_MAPA_SEPARACAO = $idMapaSeparacao";
        }

        if (isset($dataInicio) && !empty($dataInicio)) {
            if (isset($horaInicio) && !empty($horaInicio)) {
                $andWhere     .= " AND APONT.DTH_CONFERENCIA >= TO_DATE('$dataInicio $horaInicio', 'DD-MM-YYYY HH24:MI') ";
                $andWhereConf .= " AND CONF.DTH_CONFERENCIA >= TO_DATE('$dataInicio $horaInicio', 'DD-MM-YYYY HH24:MI') ";
            } else {
                $andWhere     .= " AND APONT.DTH_CONFERENCIA >= TO_DATE('$dataInicio 00:00', 'DD-MM-YYYY HH24:MI') ";
                $andWhereConf .= " AND CONF.DTH_CONFERENCIA >= TO_DATE('$dataInicio 00:00', 'DD-MM-YYYY HH24:MI') ";
            }
        }

        if (isset($dataFim) && !empty($dataFim)) {
            if (isset($horaFim) && !empty($horaFim)) {
                $andWhere .= " AND APONT.DTH_CONFERENCIA <= TO_DATE('$dataFim $horaFim', 'DD-MM-YYYY HH24:MI') ";
                $andWhereConf .= " AND CONF.DTH_CONFERENCIA <= TO_DATE('$dataFim $horaFim', 'DD-MM-YYYY HH24:MI') ";
            } else {
                $andWhere .= " AND APONT.DTH_CONFERENCIA <= TO_DATE('$dataFim 23:59', 'DD-MM-YYYY HH24:MI') ";
                $andWhereConf .= " AND CONF.DTH_CONFERENCIA <= TO_DATE('$dataFim 23:59', 'DD-MM-YYYY HH24:MI') ";
            }
        }

        $sql = "SELECT P.NOM_PESSOA,
                    E.COD_EXPEDICAO,
                    MS.COD_MAPA_SEPARACAO,
                    SUM(MSC.QTD_EMBALAGEM * MSC.QTD_CONFERIDA * SPP.NUM_PESO) NUM_PESO,
                    SUM(MSC.QTD_CONFERIDA) VOLUMES,
                    COUNT(DISTINCT PROD.COD_PRODUTO) QTD_PRODUTOS,
                    TO_CHAR(APONT.DTH_CONFERENCIA, 'DD/MM/YYYY HH24:MI:SS') DTH_INICIO,
                    TO_CHAR(APONT.DTH_FIM_CONFERENCIA, 'DD/MM/YYYY HH24:MI:SS') DTH_FIM
                FROM APONTAMENTO_SEPARACAO_MAPA APONT
                  INNER JOIN MAPA_SEPARACAO MS ON MS.COD_MAPA_SEPARACAO = APONT.COD_MAPA_SEPARACAO
                  INNER JOIN MAPA_SEPARACAO_QUEBRA QUEBRA ON QUEBRA.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO
                  INNER JOIN EXPEDICAO E ON MS.COD_EXPEDICAO = E.COD_EXPEDICAO
                  INNER JOIN PESSOA P ON P.COD_PESSOA = APONT.COD_USUARIO
                  INNER JOIN MAPA_SEPARACAO_CONFERENCIA MSC ON MSC.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO
                  INNER JOIN PRODUTO PROD ON PROD.COD_PRODUTO = MSC.COD_PRODUTO AND PROD.DSC_GRADE = MSC.DSC_GRADE
                  INNER JOIN SUM_PESO_PRODUTO SPP ON SPP.COD_PRODUTO = PROD.COD_PRODUTO AND SPP.DSC_GRADE = PROD.DSC_GRADE
                WHERE 1 = 1
                  $andWhere
                GROUP BY P.NOM_PESSOA,
                  E.COD_EXPEDICAO,
                  MS.COD_MAPA_SEPARACAO,
                  APONT.DTH_CONFERENCIA,
                  APONT.DTH_FIM_CONFERENCIA
            UNION
                SELECT P.NOM_PESSOA,
                    E.COD_EXPEDICAO,
                    MS.COD_MAPA_SEPARACAO,
                    SUM(CONF.QTD_EMBALAGEM * CONF.QTD_CONFERIDA * SPP.NUM_PESO) NUM_PESO,
                    SUM(CONF.QTD_CONFERIDA) VOLUMES,
                    COUNT(DISTINCT PROD.COD_PRODUTO) QTD_PRODUTOS,
                    TO_CHAR(MIN(CONF.DTH_CONFERENCIA), 'DD/MM/YYYY HH24:MI:SS') DTH_INICIO,
                    TO_CHAR(MAX(CONF.DTH_CONFERENCIA), 'DD/MM/YYYY HH24:MI:SS') DTH_FIM
                FROM MAPA_SEPARACAO_CONFERENCIA CONF
                  INNER JOIN MAPA_SEPARACAO MS ON MS.COD_MAPA_SEPARACAO = CONF.COD_MAPA_SEPARACAO
                  INNER JOIN MAPA_SEPARACAO_QUEBRA QUEBRA ON QUEBRA.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO
                  INNER JOIN EXPEDICAO E ON MS.COD_EXPEDICAO = E.COD_EXPEDICAO
                  INNER JOIN PRODUTO PROD ON PROD.COD_PRODUTO = CONF.COD_PRODUTO AND PROD.DSC_GRADE = CONF.DSC_GRADE
                  INNER JOIN SUM_PESO_PRODUTO SPP ON SPP.COD_PRODUTO = PROD.COD_PRODUTO AND SPP.DSC_GRADE = PROD.DSC_GRADE
                  INNER JOIN ORDEM_SERVICO OS ON OS.COD_OS = CONF.COD_OS
                  INNER JOIN PESSOA P ON P.COD_PESSOA = OS.COD_PESSOA
                WHERE 1 = 1
                  $andWhereConf
                GROUP BY P.NOM_PESSOA,
                  E.COD_EXPEDICAO,
                  MS.COD_MAPA_SEPARACAO
                ORDER BY NOM_PESSOA,
                  DTH_INICIO,
                  DTH_FIM";

        $result = $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        $qtdRows = count($result);
        $pesoTotal = 0;
        $volumeTotal = 0;
        $quantidadeTotal = 0;
        $seconds = 0;

        foreach ($result as $key => $value) {
            $tempoFinal = \DateTime::createFromFormat('d/m/Y H:i:s', $value['DTH_FIM']);
            $tempoInicial = \DateTime::createFromFormat('d/m/Y H:i:s', $value['DTH_INICIO']);
            if ($tempoFinal == null) {
                $result[$key]['TEMPO_GASTO'] = utf8_encode('Conferência em Andamento!');
                continue;
            }

            $intervalo = date_diff($tempoInicial,$tempoFinal);
            $result[$key]['TEMPO_GASTO'] = $intervalo->format('%h Hora(s) %i Minuto(s) %s Segundo(s)');
            $pesoTotal = $pesoTotal + $value['NUM_PESO'];
            $volumeTotal = $volumeTotal + $value['VOLUMES'];
            $quantidadeTotal = $quantidadeTotal + $value['QTD_PRODUTOS'];
            list($h,$i,$s) = explode(':',$intervalo->format('%h:%i:%s'));
            $seconds += $h * 3600;
            $seconds += $i * 60;
            $seconds += $s;
        }

        $hours = floor($seconds / 3600);
        $seconds -= $hours * 3600;
        $minutes = floor($seconds / 60);
        $seconds -= $minutes * 60;

        $result[$qtdRows]['NOM_PESSOA'] = 'TOTAIS';
        $result[$qtdRows]['COD_EXPEDICAO'] = '-';
        $result[$qtdRows]['COD_MAPA_SEPARACAO'] = '-';
        $result[$qtdRows]['NUM_PESO'] = $pesoTotal;
        $result[$qtdRows]['VOLUMES'] = $volumeTotal;
        $result[$qtdRows]['QTD_PRODUTOS'] = $quantidadeTotal;
        $result[$qtdRows]['DTH_INICIO'] = '-';
        $result[$qtdRows]['DTH_FIM'] = '-';
        $result[$qtdRows]['TEMPO_GASTO'] = "$hours Hora(s) $minutes Minuto(s) $seconds Segundo(s)";

        return $result;

    }

}