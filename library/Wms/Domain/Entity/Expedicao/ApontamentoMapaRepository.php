<?php

namespace Wms\Domain\Entity\Expedicao;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Wms\Domain\Entity\Atividade;
use Wms\Domain\Entity\OrdemServico;
use Wms\Math;

class ApontamentoMapaRepository extends EntityRepository {

    public function save($mapaSeparacao, $codUsuario) {

        $apontamentos = $this->findBy(array('codMapaSeparacao'=> $mapaSeparacao->getId()));
        if (count($apontamentos) >0) {
            $find = false;
            foreach ($apontamentos as $apontamento) {
                if ($apontamento->getDataFimConferencia() == null) {
                    $find = true;
                    break;
                }
            }

            if ($find == false) {
                throw new \Exception("Mapa de separação " . $mapaSeparacao->getId() . " ja se encontra com o apontamento de separação finalizado");
            }

        }

        $apontar = false;
        $em = $this->getEntityManager();
        $usuarioEn = $em->getReference('wms:Usuario',$codUsuario);

        /** @var \Wms\Domain\Entity\OrdemServicoRepository $ordemServicoRepository */
        $ordemServicoRepository = $this->getEntityManager()->getRepository('wms:OrdemServico');


        $apontamentoEn = new ApontamentoMapa();
        $apontamentoEn->setDataConferencia(new \DateTime());
        $apontamentoEn->setCodMapaSeparacao($mapaSeparacao->getId());
        $apontamentoEn->setCodUsuario($codUsuario);
        $apontamentoEn->setUsuario($usuarioEn);
        $apontamentoEn->setMapaSeparacao($mapaSeparacao);

        $apontamentosByUsuario = $this->findBy(array('codUsuario' => $codUsuario, 'dataFimConferencia' => null), array('id' => 'DESC'));
        if (count($apontamentosByUsuario) > 0) {
            $ultimoApontamentoByUsuario = $apontamentosByUsuario[0];
            $ultimoApontamentoByUsuario->setDataFimConferencia(new \DateTime());
            $em->persist($ultimoApontamentoByUsuario);

            $apontar = true;
        }

        $em->persist($apontamentoEn);

        /*
         * Cria a Ordem de Serviço de Separação
         */
        $ordemEntity = $ordemServicoRepository->findOneBy(array(
            'idExpedicao' => $mapaSeparacao->getCodExpedicao(),
            'atividade' => Atividade::SEPARACAO,
            'pessoa' => $codUsuario,
            'formaConferencia' => 'D'));

        if (!isset($ordemEntity)) {
            $array = array();
            $array['identificacao']['idExpedicao'] = $mapaSeparacao->getCodExpedicao();
            $array['identificacao']['tipoOrdem'] = 'expedicao';
            $array['identificacao']['idAtividade'] = Atividade::SEPARACAO;
            $array['identificacao']['idPessoa'] = $codUsuario;
            $array['identificacao']['formaConferencia'] = 'D';
            $ordemServicoRepository->save(new OrdemServico(), $array, true, 'entity');
        }

        $em->flush();

        if ($apontar) {
            /** @var \Wms\Domain\Entity\Expedicao\ApontamentoMapaRepository $apontamentoMapaRepo */
            $apontamentoMapaRepo = $this->getEntityManager()->getRepository('wms:Expedicao\ApontamentoMapa');
            $apontamentoMapaRepo->geraAtividadeSeparacao($ultimoApontamentoByUsuario->getMapaSeparacao(), $usuarioEn->getId());
        }

        return $apontamentoEn;
    }

    public function update($apontamentoMapaEn) {
        $em = $this->getEntityManager();
        if ($apontamentoMapaEn->getDataFimConferencia() == null) {
            $apontamentoMapaEn->setDataFimConferencia(new \DateTime());
            $em->persist($apontamentoMapaEn);
            $em->flush();
        }

        /** @var \Wms\Domain\Entity\Expedicao\ApontamentoMapaRepository $apontamentoMapaRepo */
        $apontamentoMapaRepo = $this->getEntityManager()->getRepository('wms:Expedicao\ApontamentoMapa');
        $apontamentoMapaRepo->geraAtividadeSeparacao($apontamentoMapaEn->getMapaSeparacao(), $apontamentoMapaEn->getUsuario()->getId());

        return true;
    }

    public function getApontamentoDetalhado($params) {

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
        $dataInicio = str_replace('-', '/', $dataInicio);
        $dataFim = str_replace('-', '/', $dataFim);

        if (isset($idUsuario) && !empty($idUsuario)) {
            $andWhere .= " AND P.COD_PESSOA = $idUsuario";
            $andWhereConf .= " AND P.COD_PESSOA = $idUsuario";
        }

        if (isset($tipoQuebra) && !empty($tipoQuebra)) {
            $quebra = MapaSeparacaoQuebra::QUEBRA_CARRINHO;
            $andWhere .= " AND QUEBRA.IND_TIPO_QUEBRA = '$quebra'";
            $andWhereConf .= " AND QUEBRA.IND_TIPO_QUEBRA = '$quebra'";
        }

        if (isset($idExpedicao) && !empty($idExpedicao)) {
            $andWhere .= " AND E.COD_EXPEDICAO = $idExpedicao";
            $andWhereConf .= " AND E.COD_EXPEDICAO = $idExpedicao";
        }

        if (isset($idMapaSeparacao) && !empty($idMapaSeparacao)) {
            $andWhere .= " AND MS.COD_MAPA_SEPARACAO = $idMapaSeparacao";
            $andWhereConf .= " AND MS.COD_MAPA_SEPARACAO = $idMapaSeparacao";
        }

        if (isset($dataInicio) && !empty($dataInicio)) {
            if (isset($horaInicio) && !empty($horaInicio)) {
                $andWhere .= " AND APONT.DTH_CONFERENCIA >= TO_DATE('$dataInicio $horaInicio', 'DD-MM-YYYY HH24:MI') ";
                $andWhereConf .= " AND CONF.DTH_CONFERENCIA >= TO_DATE('$dataInicio $horaInicio', 'DD-MM-YYYY HH24:MI') ";
            } else {
                $andWhere .= " AND APONT.DTH_CONFERENCIA >= TO_DATE('$dataInicio 00:00', 'DD-MM-YYYY HH24:MI') ";
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
                    SUM((MSP.QTD_SEPARAR - (MSP.QTD_CORTADO / MSP.QTD_EMBALAGEM)) * PDL.NUM_PESO) NUM_PESO,
                    SUM(MSP.QTD_SEPARAR - (MSP.QTD_CORTADO / MSP.QTD_EMBALAGEM)) VOLUMES,
                    COUNT(DISTINCT PROD.COD_PRODUTO) QTD_PRODUTOS,
                    TO_CHAR(APONT.DTH_CONFERENCIA, 'DD/MM/YYYY HH24:MI:SS') DTH_INICIO,
                    TO_CHAR(APONT.DTH_FIM_CONFERENCIA, 'DD/MM/YYYY HH24:MI:SS') DTH_FIM,
                    'SEPARAÇÃO' as ATIVIDADE
                FROM APONTAMENTO_SEPARACAO_MAPA APONT
                  INNER JOIN MAPA_SEPARACAO MS ON MS.COD_MAPA_SEPARACAO = APONT.COD_MAPA_SEPARACAO
                  INNER JOIN MAPA_SEPARACAO_QUEBRA QUEBRA ON QUEBRA.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO
                  INNER JOIN EXPEDICAO E ON MS.COD_EXPEDICAO = E.COD_EXPEDICAO
                  INNER JOIN PESSOA P ON P.COD_PESSOA = APONT.COD_USUARIO
                  INNER JOIN MAPA_SEPARACAO_PRODUTO MSP ON MSP.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO
                  INNER JOIN PRODUTO PROD ON PROD.COD_PRODUTO = MSP.COD_PRODUTO AND PROD.DSC_GRADE = MSP.DSC_GRADE
                  LEFT JOIN PRODUTO_DADO_LOGISTICO PDL ON PDL.COD_PRODUTO_EMBALAGEM = MSP.COD_PRODUTO_EMBALAGEM
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
                    SUM(CONF.QTD_CONFERIDA * PDL.NUM_PESO) NUM_PESO,
                    SUM(CONF.QTD_CONFERIDA) VOLUMES,
                    COUNT(DISTINCT PROD.COD_PRODUTO) QTD_PRODUTOS,
                    TO_CHAR(MIN(CONF.DTH_CONFERENCIA), 'DD/MM/YYYY HH24:MI:SS') DTH_INICIO,
                    TO_CHAR(MAX(CONF.DTH_CONFERENCIA), 'DD/MM/YYYY HH24:MI:SS') DTH_FIM,
                    'CONFERÊNCIA DE SEPARAÇÃO' as ATIVIDADE
                FROM MAPA_SEPARACAO_CONFERENCIA CONF
                  INNER JOIN MAPA_SEPARACAO MS ON MS.COD_MAPA_SEPARACAO = CONF.COD_MAPA_SEPARACAO
                  INNER JOIN MAPA_SEPARACAO_QUEBRA QUEBRA ON QUEBRA.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO
                  INNER JOIN EXPEDICAO E ON MS.COD_EXPEDICAO = E.COD_EXPEDICAO
                  INNER JOIN PRODUTO PROD ON PROD.COD_PRODUTO = CONF.COD_PRODUTO AND PROD.DSC_GRADE = CONF.DSC_GRADE
                  LEFT JOIN PRODUTO_DADO_LOGISTICO PDL ON PDL.COD_PRODUTO_EMBALAGEM = CONF.COD_PRODUTO_EMBALAGEM
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
        $countExpedicao = 0;
        $countMapaSeparacao = 0;
        $idExpedicaoAnterior = null;
        $idMapaSeparacaoAnterior = null;

        foreach ($result as $key => $value) {
            $tempoFinal = \DateTime::createFromFormat('d/m/Y H:i:s', $value['DTH_FIM']);
            $tempoInicial = \DateTime::createFromFormat('d/m/Y H:i:s', $value['DTH_INICIO']);
            if ($tempoFinal == null) {
                $result[$key]['TEMPO_GASTO'] = utf8_encode('Conferência em Andamento!');
                continue;
            }
            if ($value['COD_EXPEDICAO'] != $idExpedicaoAnterior) {
                $countExpedicao = $countExpedicao + 1;
            }
            if ($value['COD_MAPA_SEPARACAO'] != $idMapaSeparacaoAnterior) {
                $countMapaSeparacao = $countMapaSeparacao + 1;
            }
            $idExpedicaoAnterior = $value['COD_EXPEDICAO'];
            $idMapaSeparacaoAnterior = $value['COD_MAPA_SEPARACAO'];

            $intervalo = date_diff($tempoInicial, $tempoFinal);
            $result[$key]['TEMPO_GASTO'] = $intervalo->format('%h Hora(s) %i Minuto(s) %s Segundo(s)');
            $pesoTotal = $pesoTotal + $value['NUM_PESO'];
            $volumeTotal = $volumeTotal + $value['VOLUMES'];
            $quantidadeTotal = $quantidadeTotal + $value['QTD_PRODUTOS'];
            list($h, $i, $s) = explode(':', $intervalo->format('%h:%i:%s'));
            $seconds += $h * 3600;
            $seconds += $i * 60;
            $seconds += $s;
        }

        $hours = floor($seconds / 3600);
        $seconds -= $hours * 3600;
        $minutes = floor($seconds / 60);
        $seconds -= $minutes * 60;

        $result[$qtdRows]['NOM_PESSOA'] = 'TOTAIS';
        $result[$qtdRows]['COD_EXPEDICAO'] = $countExpedicao;
        $result[$qtdRows]['COD_MAPA_SEPARACAO'] = $countMapaSeparacao;
        $result[$qtdRows]['NUM_PESO'] = $pesoTotal;
        $result[$qtdRows]['VOLUMES'] = $volumeTotal;
        $result[$qtdRows]['QTD_PRODUTOS'] = $quantidadeTotal;
        $result[$qtdRows]['DTH_INICIO'] = '-';
        $result[$qtdRows]['DTH_FIM'] = '-';
        $result[$qtdRows]['TEMPO_GASTO'] = "$hours Hora(s) $minutes Minuto(s) $seconds Segundo(s)";
        $result[$qtdRows]['ATIVIDADE'] = '-';

        return $result;
    }

    public function getProdutividadeDetalhe($params) {

        $idUsuario = $params['usuario'];
        $atividade = $params['atividade'];
        $idIdentidade = $params['identidade'];
        $tipoQuebra = $params['tipoQuebra'];
        $dataInicio = $params['dataInicio'];
        $horaInicio = $params['horaInicio'];
        $dataFim = $params['dataFim'];
        $horaFim = $params['horaFim'];
        $ordem = $params['ordem'];
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
        $dataInicio = str_replace('-', '/', $dataInicio);
        $dataFim = str_replace('-', '/', $dataFim);

        if (isset($idUsuario) && !empty($idUsuario)) {
            $andWhere .= " AND PE.COD_PESSOA = $idUsuario";
        }

        if (isset($atividade) && !empty($atividade)) {
            $andWhere .= " AND DSC_ATIVIDADE LIKE '%$atividade%'";
        }
        if (isset($idIdentidade) && !empty($idIdentidade)) {
            $andWhere .= " AND IDENTIDADE = $idIdentidade";
        }

        if (isset($dataInicio) && !empty($dataInicio)) {
            if (isset($horaInicio) && !empty($horaInicio)) {
                $andWhere .= " AND PD.DTH_INICIO >= TO_DATE('$dataInicio $horaInicio', 'DD/MM/YYYY HH24:MI') ";
            } else {
                $andWhere .= " AND PD.DTH_INICIO >= TO_DATE('$dataInicio 00:00', 'DD/MM/YYYY HH24:MI') ";
            }
        }

        if (isset($dataFim) && !empty($dataFim)) {
            if (isset($horaFim) && !empty($horaFim)) {
                $andWhere .= " AND PD.DTH_FIM <= TO_DATE('$dataFim $horaFim', 'DD/MM/YYYY HH24:MI') ";
            } else {
                $andWhere .= " AND PD.DTH_FIM <= TO_DATE('$dataFim 23:59', 'DD/MM/YYYY HH24:MI') ";
            }
        }
        switch ($ordem) {
            case 1:
                $order = "TO_DATE(DTH_INICIO, 'DD/MM/YYYY HH24:MI:SS')";
                break;
            case 2:
                $order = "DSC_ATIVIDADE, PD.COD_PESSOA ASC, TO_DATE(DTH_INICIO, 'DD/MM/YYYY HH24:MI:SS')";
                break;
            default:
                $order = "PD.COD_PESSOA, DSC_ATIVIDADE ASC, TO_DATE(DTH_INICIO, 'DD/MM/YYYY HH24:MI:SS')";
                break;
        }
        $sql = " SELECT 
                    PE.NOM_PESSOA, 
                    IDENTIDADE,
                    MP.COD_EXPEDICAO,
                    E.COD_EXPEDICAO AS ETIQUETA_EX,
                    DSC_ATIVIDADE,
                    TO_CHAR(MIN(PD.DTH_INICIO), 'DD/MM/YYYY HH24:MI:SS') DTH_INICIO,
                    TO_CHAR(MAX(PD.DTH_FIM), 'DD/MM/YYYY HH24:MI:SS') DTH_FIM,
                    SUM(QTD_PESO) AS QTD_PESO,
                    SUM(QTD_VOLUMES) AS QTD_VOLUMES,
                    SUM(QTD_CUBAGEM) AS QTD_CUBAGEM,
                    SUM(QTD_PRODUTOS) AS QTD_PRODUTOS,
                    SUM(QTD_PALETES) AS QTD_PALETES,
                    1 AS QTD_CARGA
                    FROM PRODUTIVIDADE_DETALHE PD
                  INNER JOIN PESSOA PE ON PE.COD_PESSOA = PD.COD_PESSOA
                  LEFT JOIN MAPA_SEPARACAO MP ON (PD.IDENTIDADE = MP.COD_MAPA_SEPARACAO AND (PD.DSC_ATIVIDADE LIKE 'CONF. SEPARACAO%' OR PD.DSC_ATIVIDADE LIKE 'SEPARACAO%'))
                  LEFT JOIN EXPEDICAO E ON (PD.IDENTIDADE = E.COD_EXPEDICAO AND (PD.DSC_ATIVIDADE LIKE 'SEPARACAO%' OR PD.DSC_ATIVIDADE = 'CARREGAMENTO'))
                  WHERE 1 = 1
                  $andWhere 
                  GROUP BY 
                  PE.NOM_PESSOA, 
                    IDENTIDADE,
                    E.COD_EXPEDICAO,
                    DSC_ATIVIDADE,
                    MP.COD_EXPEDICAO,
                    PD.COD_PESSOA
                    ORDER BY $order";
        $result = $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        $qtdRows = count($result);
        $pesoTotal = 0;
        $volumeTotal = 0;
        $quantidadeTotal = 0;
        $cubagemTotal = 0;
        $quantidadePaletes = 0;
        $quantidadeCarga = 0;
        $seconds = 0;
        foreach ($result as $key => $value) {
            $tempoFinal = \DateTime::createFromFormat('d/m/Y H:i:s', $value['DTH_FIM']);
            $tempoInicial = \DateTime::createFromFormat('d/m/Y H:i:s', $value['DTH_INICIO']);
            if ($tempoFinal == null) {
                $result[$key]['TEMPO_GASTO'] = 'Conferência em Andamento!';
                continue;
            }
            if($value['DSC_ATIVIDADE'] == 'CARREGAMENTO'){
                $result[$key]['COD_EXPEDICAO'] = $value['IDENTIDADE'];
            }
            
            if($value['COD_EXPEDICAO'] == null){
                $result[$key]['COD_EXPEDICAO'] = ' - ';
            }

            if($value['ETIQUETA_EX'] != null){
                $result[$key]['COD_EXPEDICAO'] = $value['ETIQUETA_EX'];
            }
            $result[$key]['QTD_VOLUMES'] = number_format($value['QTD_VOLUMES'], 2, ',', '.');
            $result[$key]['QTD_PESO'] = number_format($value['QTD_PESO'], 2, ',', '');
            $result[$key]['QTD_CUBAGEM'] = number_format($value['QTD_CUBAGEM'], 2, ',', '.');
            $intervalo = date_diff($tempoInicial, $tempoFinal);
            $result[$key]['TEMPO_GASTO'] = $intervalo->format('%H:%I:%S');
            $volumeTotal = $volumeTotal + $value['QTD_VOLUMES'];
            $pesoTotal = $pesoTotal + $value['QTD_PESO'];
            $cubagemTotal = $cubagemTotal + $value['QTD_CUBAGEM'];
            $quantidadeTotal = $quantidadeTotal + $value['QTD_PRODUTOS'];
            $quantidadeCarga = $quantidadeCarga + $value['QTD_CARGA'];
            $quantidadePaletes = $quantidadePaletes + $value['QTD_PALETES'];
            list($h, $i, $s) = explode(':', $intervalo->format('%H:%I:%S'));
            $seconds += $h * 3600;
            $seconds += $i * 60;
            $seconds += $s % 60;
        }
        $hoje = date('Y-m-d H:i:s');
        $intervalo = date_diff(\DateTime::createFromFormat('Y-m-d H:i:s', $hoje), \DateTime::createFromFormat('Y-m-d H:i:s', date('Y-m-d H:i:s', strtotime($hoje . '+ ' . $seconds . ' seconds'))));
        $min = $intervalo->i;
        $sec = $intervalo->s;
        if ($intervalo->i < 10) {
            $min = '0' . $intervalo->i;
        }
        if ($intervalo->s < 10) {
            $sec = '0' . $intervalo->s;
        }
        if ($intervalo->d > 0) {
            $hr = ($intervalo->d * 24) + $intervalo->h;
            $tempoTotal = $hr . ":" . $min . ':' . $sec;
        } else {
            $tempoTotal = date('H:i:s', strtotime("$intervalo->h:$min:$sec"));
        }

        $result[$qtdRows]['NOM_PESSOA'] = 'TOTAIS';
        $result[$qtdRows]['IDENTIDADE'] = '-';
        $result[$qtdRows]['QTD_PESO'] = number_format($pesoTotal, 2, ',', '');
        $result[$qtdRows]['QTD_VOLUMES'] = number_format($volumeTotal, 2, ',', '.');
        $result[$qtdRows]['QTD_PRODUTOS'] = number_format($quantidadeTotal, 2, ',', '.');
        $result[$qtdRows]['QTD_CUBAGEM'] = number_format($cubagemTotal, 2, ',', '.');
        $result[$qtdRows]['QTD_CARGA'] = number_format($quantidadeCarga, 0, ',', '.');
        $result[$qtdRows]['QTD_PALETES'] = number_format($quantidadePaletes, 0, ',', '.');
        $result[$qtdRows]['DTH_INICIO'] = '-';
        $result[$qtdRows]['DTH_FIM'] = '-';
        $result[$qtdRows]['TEMPO_GASTO'] = $tempoTotal;
        $result[$qtdRows]['DSC_ATIVIDADE'] = '-';
        $result[$qtdRows]['COD_EXPEDICAO'] = '-';

        return $result;
    }

    public function getMapaAbertoUsuario($codPessoa){
        $sql = "SELECT COD_MAPA_SEPARACAO
                FROM APONTAMENTO_SEPARACAO_MAPA
                WHERE COD_USUARIO = $codPessoa AND DTH_FIM_CONFERENCIA IS NULL";
        return $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getQtdApontamentoMapa($mapa){
        $sql = "SELECT COUNT(COD_MAPA_SEPARACAO) AS QTD
                FROM APONTAMENTO_SEPARACAO_MAPA
                WHERE COD_MAPA_SEPARACAO = $mapa";
        return $this->getEntityManager()->getConnection()->query($sql)->fetch(\PDO::FETCH_ASSOC);
    }

    public function verificaApontamentoMapaUsuarioPendenteFechamento($mapa, $usuario) {
        $sql = "SELECT COUNT(COD_MAPA_SEPARACAO) AS QTD
                FROM APONTAMENTO_SEPARACAO_MAPA
                WHERE COD_MAPA_SEPARACAO = $mapa
                  AND COD_USUARIO = $usuario
                  AND DTH_FIM_CONFERENCIA IS NULL";
        $result =  $this->getEntityManager()->getConnection()->query($sql)->fetch(\PDO::FETCH_ASSOC);

        if ($result['QTD'] > 0) {
            return true;
        }
        return false;
    }

    public function geraAtividadeSeparacao($mapaSeparacaoEn, $codUsuario)
    {
        /** @var \Wms\Domain\Entity\OrdemServicoRepository $ordemServicoRepository */
        $ordemServicoRepository = $this->getEntityManager()->getRepository('wms:OrdemServico');
        /** @var \Wms\Domain\Entity\Expedicao\MapaSeparacaoProdutoRepository $mapaSeparacaoProdutoRepository */
        $mapaSeparacaoProdutoRepository = $this->getEntityManager()->getRepository('wms:Expedicao\MapaSeparacaoProduto');
        /** @var \Wms\Domain\Entity\Expedicao\SeparacaoMapaSeparacao $separacaoMapaSeparacaoRepository */
        $separacaoMapaSeparacaoRepository = $this->getEntityManager()->getRepository('wms:Expedicao\SeparacaoMapaSeparacao');

        $mapaSeparacaoProdutoEntities = $mapaSeparacaoProdutoRepository->findBy(array('mapaSeparacao' => $mapaSeparacaoEn));

        $mapasApontados = $this->findBy(array('mapaSeparacao' => $mapaSeparacaoEn));
        if (count($mapasApontados)) {
            //verifica se existem mapas com a coluna dth_fim_conferencia como null e retorna falso
            foreach ($mapasApontados as $mapaAberto) {
                if (is_null($mapaAberto->getDataFimConferencia()))
                    return false;
            }
            $array = array();
            $ordemServicoEntities = array();
            foreach ($mapasApontados as $mapaFechado) {
                $array['identificacao']['idExpedicao'] = $mapaSeparacaoEn->getCodExpedicao();
                $array['identificacao']['tipoOrdem'] = 'expedicao';
                $array['identificacao']['idAtividade'] = Atividade::SEPARACAO;
                $array['identificacao']['idPessoa'] = $mapaFechado->getCodUsuario();
                $array['identificacao']['formaConferencia'] = 'D';

                $ordemEntity = $ordemServicoRepository->findOneBy(array(
                    'idExpedicao' => $mapaSeparacaoEn->getCodExpedicao(),
                    'atividade' => Atividade::SEPARACAO,
                    'pessoa' => $mapaFechado->getCodUsuario(),
                    'formaConferencia' => 'D'));

                if (isset($ordemEntity)) {
                    $ordemServicoEntities[] = $ordemEntity;
                    continue;
                }

                $ordemServicoEntities[] = $ordemServicoRepository->save(new OrdemServico(), $array, true, 'entity');
            }

            $contador = 0;
            foreach ($ordemServicoEntities as $i => $ordemServicoEntity) {

                if ($ordemServicoEntity->getDataFinal() == null) {
                    $ordemServicoEntity->setDataFinal(new \DateTime());
                    $this->getEntityManager()->persist($ordemServicoEntity);

                    $qtdPorPessoa = (floor(Math::dividir(count($mapaSeparacaoProdutoEntities), count($ordemServicoEntities)))) * ($i + 1);
                    while ($contador < $qtdPorPessoa) {
                        $produtoEn = $mapaSeparacaoProdutoEntities[$contador]->getProduto();
                        $codMapaSeparacao = $mapaSeparacaoEn->getId();
                        $codOs = $ordemServicoEntity->getId();
                        $qtdSeparar = (($mapaSeparacaoProdutoEntities[$contador]->getQtdSeparar() * $mapaSeparacaoProdutoEntities[$contador]->getQtdEmbalagem()) - $mapaSeparacaoProdutoEntities[$contador]->getQtdCortado()) / $mapaSeparacaoProdutoEntities[$contador]->getQtdEmbalagem();
                        $idEmbalagem = $mapaSeparacaoProdutoEntities[$contador]->getProdutoEmbalagem()->getId();
                        $qtdEmb = $mapaSeparacaoProdutoEntities[$contador]->getQtdEmbalagem();
                        $separacaoMapaSeparacaoEntity = $separacaoMapaSeparacaoRepository->save($produtoEn, $codMapaSeparacao, $codOs, $qtdSeparar, $idEmbalagem, $qtdEmb, $idVol = null, $lote = null);

                        $contador = $contador + 1;
                    }
                }
            }

            $this->getEntityManager()->flush();
        }
    }


}
