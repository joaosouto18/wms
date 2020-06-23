<?php

namespace Wms\Domain\Entity\Integracao;

use Composer\DependencyResolver\Transaction;
use Doctrine\ORM\EntityRepository;
use Wms\Domain\Configurator;
use Wms\Domain\Entity\Util\Sigla;
use Wms\Service\Integracao;

class AcaoIntegracaoRepository extends EntityRepository
{

    /**
     * @param array $params
     * @return AcaoIntegracao
     * @throws \Exception
     */
    public function save(array $params)
    {
        try {
            $this->_em->beginTransaction();

            $entity = null;
            if (!empty($params['id'])) {
                /** @var AcaoIntegracao $entity */
                $entity = $this->find($params['id']);
                if (empty($entity)) throw new \Exception("Nenhuma integração foi encontrada com o ID $params[id]!");
            } else {
                $entity = new AcaoIntegracao();
            }

            $entity = Configurator::configure($entity, $params);

            $entity->setConexao($this->_em->getReference(ConexaoIntegracao::class, $params['conexao']));
            $entity->setTipoAcao($this->_em->getReference(Sigla::class, $params['tipoAcao']));

            $this->_em->persist($entity);
            $this->_em->flush($entity);
            $this->_em->commit();

            return $entity;
        } catch (\Exception $e) {
            $this->_em->rollback();
            throw $e;
        }
    }

    public function toggleLog($id, $status)
    {
        /** @var AcaoIntegracao $entity */
        $entity = $this->find($id);
        if (empty($entity)) throw new \Exception("Nenhuma integração foi encontrada com o ID $id!");

        $entity->setIndUtilizaLog($status);
        $this->_em->persist($entity);
        $this->_em->flush($entity);

        return $entity;
    }

    public function getExisteIntegracao() {
        $SQL = "SELECT * FROM ACAO_INTEGRACAO";
        $result = $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);

        if (count($result) > 0) {
            return true;
        } else {
            return false;
        }
    }

    public function getProdutosPendentes () {
        $SQL = "SELECT DISTINCT PRODUTO FROM (
                   SELECT PRODUTO FROM TR_PEDIDO WHERE (IND_PROCESSADO = 'N' OR IND_PROCESSADO IS NULL)
                   UNION
                   SELECT COD_PRODUTO FROM TR_NOTA_FISCAL_ENTRADA WHERE (IND_PROCESSADO = 'N' OR IND_PROCESSADO IS NULL))
                WHERE PRODUTO NOT IN (SELECT COD_PRODUTO FROM PRODUTO)";
        $result = $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);
        return $result;
    }

    private function validaAcoesMesmoTipo($acoes) {
        if (count($acoes) == 0) {
            throw new \Exception ("Nenhuma ação foi definida para ser executada");
        }

        $tipoAcao = null;
        foreach ($acoes as $acaoEn) {
            if ($tipoAcao == null) {
                $tipoAcao = $acaoEn->getTipoAcao()->getId();
            } else {
                if ($tipoAcao != $acaoEn->getTipoAcao()->getId()) {
                    throw new \Exception ("Apenas integrações com o mesmo tipo de ação podem ser disparadas em conjunto");
                }
            }
        }
    }

    private function getDadosTemporarios($tipoAcao, $dados = null, $efetivar = false) {
        $SQL = null;
        $where = 'WHERE 1 = 1';
        switch ($tipoAcao) {
            case AcaoIntegracao::INTEGRACAO_NOTAS_FISCAIS:
                if (!$efetivar) {
                    $where .= " AND NUM_NOTA_FISCAL IN ($dados)";
                } else {
                    $detalhesNotas = explode(',', $dados);
                    $where .= " AND ";

                    foreach ($detalhesNotas as $key => $detalhesNota) {
                        $dadosNotasConcatenados = explode('*-*', $detalhesNota);
                        $where .= (" (TRIM(NUM_NOTA_FISCAL) = '$dadosNotasConcatenados[0]' AND TRIM(COD_SERIE_NOTA_FISCAL) = '$dadosNotasConcatenados[1]' AND TRIM(COD_FORNECEDOR) = '$dadosNotasConcatenados[2]') ");

                        if ($detalhesNotas[$key] != end($detalhesNotas))
                            $where .= ' OR ';

                    }
                }

                $SQL = "
                  SELECT COD_INTEGRACAO_NF_ENTRADA,
                         COD_FORNECEDOR,
                         NOM_FORNECEDOR,
                         CPF_CNPJ,
                         DSC_GRADE,
                         INSCRICAO_ESTADUAL,
                         NUM_NOTA_FISCAL,
                         COD_PRODUTO,
                         COD_SERIE_NOTA_FISCAL,
                         TO_CHAR(DAT_EMISSAO,'DD/MM/YYYY') as DAT_EMISSAO,
                         DSC_PLACA_VEICULO,
                         QTD_ITEM,
                         VALOR_TOTAL,
                         TO_CHAR(DTH,'DD/MM/YYYY HH24:MI:SS') as DTH,
                         DSC_LOTE
                        FROM INTEGRACAO_NF_ENTRADA
                        $where
                  ORDER by NUM_NOTA_FISCAL, COD_SERIE_NOTA_FISCAL, COD_FORNECEDOR, TO_DATE(DAT_EMISSAO)
                ";
                break;
            case AcaoIntegracao::INTEGRACAO_PEDIDOS:
                $SQL = "SELECT  COD_INTEGRACAO_PEDIDO,
                                CARGA,
                                PLACA,
                                PEDIDO,
                                COD_PRACA,
                                DSC_PRACA,
                                COD_ROTA,
                                DSC_ROTA,
                                COD_CLIENTE,
                                NOME,
                                CPF_CNPJ,
                                TIPO_PESSOA,
                                LOGRADOURO,
                                NUMERO,
                                BAIRRO,
                                CIDADE,
                                UF,
                                COMPLEMENTO,
                                REFERENCIA,
                                CEP,
                                PRODUTO,
                                GRADE,
                                TIPO_PEDIDO,
                                QTD,
                                VLR_VENDA,
                                NOM_MOTORISTA,
                                TO_CHAR(DTH,'DD/MM/YYYY HH24:MI:SS') as DTH
                FROM INTEGRACAO_PEDIDO 
                ORDER by CARGA, PEDIDO, PRODUTO";
                break;
        }

        if ($SQL == null) {
            return false;
        }


        $result = $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);
        return $result;
    }

    private function limpaDadosTemporarios($tipoAcao) {
        $repo = null;
        switch ($tipoAcao) {
            case AcaoIntegracao::INTEGRACAO_NOTAS_FISCAIS:
                $repo = $this->getEntityManager()->getRepository('wms:Integracao\TabelaTemporaria\NotaFiscalEntrada');
                break;
            case AcaoIntegracao::INTEGRACAO_PEDIDOS:
                $repo = $this->getEntityManager()->getRepository('wms:Integracao\TabelaTemporaria\Pedido');
                break;
        }

        if ($repo != null) {
            $ens = $repo->findAll();
            foreach ($ens as $en) {
                $this->getEntityManager()->remove($en);
            }
        }

        $this->getEntityManager()->flush();
    }

    public function efetivaTemporaria($acoes, $IdFiltro, $dados = null) {

        /* Para efetivar no banco de dados, só vou efetivar uma unica vez mesmo que tenham sido disparados n consultas.
           Porem todas tem que compartilhar a mesma tabela temporaria, ou seja, ser da mesma ação */
        $this->validaAcoesMesmoTipo($acoes);
        $acaoEn = $acoes[0];

        /* Consulto os dados da tabela temporaria referente a ação */
        $dados = $this->getDadosTemporarios($acaoEn->getTipoAcao()->getId(), $dados, true);

        /* Executo uma unica ação com todos os dados retornados */
        $result = $this->processaAcao($acaoEn,null,"E","P", $dados,$IdFiltro);

        /* Limpo os dados da tabela temporaria */
        $this->limpaDadosTemporarios($acaoEn->getTipoAcao()->getId());

        return $result;
    }

    public function listaTemporaria($acoes, $options = null, $idFiltro, $codigo = null) {

        $this->validaAcoesMesmoTipo($acoes);

        $acaoEn = $acoes[0];

        $this->limpaDadosTemporarios($acaoEn->getTipoAcao()->getId());
        /* Executa cada ação salvando os dados na tabela temporaria */
        foreach ($acoes as $acaoEn) {
            $result = $this->processaAcao($acaoEn,$options,"E","T",null,$idFiltro);
            if (!($result === true)) {
                $this->limpaDadosTemporarios($acaoEn->getTipoAcao()->getId());
                return $result;
            }
        }

        /* Faz a consulta na tabela temporaria, para retornar as informações no mesmo modelo do ERP */
        $dados = $this->getDadosTemporarios($acaoEn->getTipoAcao()->getId(), $codigo);

        /* Executa uma integração apenas para pegar o array formatado */
        $integracaoService = new Integracao($this->getEntityManager(),
            array('acao'=>$acaoEn,
                'options'=>null,
                'tipoExecucao' => "R",
                'dados'=>$dados));
        $result = $integracaoService->processaAcao();

        return $result;
    }

    /*
     * TiposRetorno E => Executar
     *              L => Listar o resultado da query
     *              R => Resumo do resultado
     * Destino => (P => Produção, T => Tabela temporária)
     */

    public function processaAcao($acaoEn, $options = null, $tipoExecucao = "E", $destino = "P", $dados = null, $filtro = AcaoIntegracaoFiltro::DATA_ESPECIFICA, $insertAll = false) {
        ini_set('max_execution_time', '-1');
        ini_set('memory_limit', '-1');
        /** @var \Wms\Domain\Entity\Integracao\AcaoIntegracao $acaoEn */
        /** @var \Wms\Domain\Entity\Integracao\ConexaoIntegracaoRepository $conexaoRepo */
        $conexaoRepo = $this->_em->getRepository('wms:Integracao\ConexaoIntegracao');
        /** @var \Wms\Domain\Entity\Integracao\AcaoIntegracaoFiltroRepository $acaoFiltroRepo */
        $acaoFiltroRepo = $this->_em->getRepository('wms:Integracao\AcaoIntegracaoFiltro');
        $idAcao = $acaoEn->getId();

        $this->_em->clear();
        $acaoEn = $this->findOneBy(array('id'=>$idAcao));

        $encontrouRegistro = false;
        $sucess = "S";
        $observacao = "";
        $trace = "";
        $query = "";
        $existeOutraTransacaoAtiva = "N";
        $iniciouTransacaoAtual = 'N';
        $integracaoService = null;

        if ($acaoEn->getIndExecucao() == 'S') {
            $existeOutraTransacaoAtiva = "S";
        } else {
            $iniciouTransacaoAtual = 'S';
            $acaoEn->setIndExecucao("S");
            $this->_em->persist($acaoEn);
            $this->_em->flush();
        }

        try {

            $this->_em->beginTransaction();

            if ($existeOutraTransacaoAtiva == 'S') {
                throw new \Exception("Integração em andamento em outro processo");
            }

            $conexaoEn = $acaoEn->getConexao();

            $data = $acaoEn->getDthUltimaExecucao();

            if (!empty($data)) {
                if ($filtro == AcaoIntegracaoFiltro::DATA_ESPECIFICA) {
                    if ($conexaoEn->getProvedor() == ConexaoIntegracao::PROVEDOR_MYSQL) {
                        $options[] = $data->format("Y-m-d");
                    } else if ($conexaoEn->getProvedor() == ConexaoIntegracao::PROVEDOR_ORACLE
                        || $conexaoEn->getProvedor() == ConexaoIntegracao::PROVEDOR_POSTGRE) {
                        $options[] = $data->format("d/m/Y H:i:s");
                    } else if ($conexaoEn->getProvedor() == ConexaoIntegracao::PROVEDOR_MSSQL
                        || $conexaoEn->getProvedor() == ConexaoIntegracao::PROVEDOR_SQLSRV) {
                        $options[] = $data->format("Y-m-d H:i:s");
                    }
                }
            }

            //STRING DA QUERY DE INTEGRAÇÃO
            if($insertAll === true){
                $insertAll = $conexaoEn->getProvedor();
            }
            $query = $acaoFiltroRepo->getQuery($acaoEn, $options, $filtro, $data, $insertAll);
            if ($dados == null) {
                $words = explode(" ",trim($query));
                $update = true;
                if (strtoupper($words[0]) == "SELECT") {
                    $update = false;
                }
                $result = $conexaoRepo->runQuery($query, $conexaoEn, $update);
            } else {
                $result = $dados;
            }

            $integracaoService = new Integracao($this->getEntityManager(),
                array('acao'=>$acaoEn,
                    'options'=>$options,
                    'tipoExecucao' => $tipoExecucao,
                    'dados'=>$result));

            if (intval($acaoEn->getIdAcaoRelacionada()) != null) {
                if (count($result) > 0) {
                    $acaoRelacionadaEn = $this->find(intval($acaoEn->getIdAcaoRelacionada()));
                    $idsProcessados = $result;

                    $dadosFiltrar = array();
                    foreach ($result as $row) {
                        $row = array_change_key_case($row,CASE_UPPER);
                        if (!in_array($row['COD_PRODUTO'],$dadosFiltrar)) {
                            $dadosFiltrar[] = $row['COD_PRODUTO'];
                        }
                    }

                    foreach ($dadosFiltrar as $value) {
                        $options = array();
                        $options[] = $value;
                        $result = $this->processaAcao($acaoRelacionadaEn,$options,"E","P",null,AcaoIntegracaoFiltro::CONJUNTO_CODIGO);
                    }

                    //CASO TENHA DADO SUCESSO NA INTEGRAÇÃO RELACIONADA, ENTÃO PEGA OS IDS PARA SETAR COMO PROCESSADOS
                    if ($result === true) {
                        $encontrouRegistro = true;
                        if (!is_null($acaoEn->getTabelaReferencia())) {
                            $idTabelaTemp = $idsProcessados;
                        }
                    }

                } else {
                    $result = true;
                }
            } else {
                if (($tipoExecucao == "E") && ($destino == "T")) {
                    $integracaoService = new Integracao($this->getEntityManager(),
                        array('acao'=>$acaoEn,
                            'dados'=>$result));
                    $result = $integracaoService->salvaTemporario();
                } else {

                    //pegar os ID's das tabelas temporárias das triggers
                    if (count($result)) {
                        $encontrouRegistro = true;
                        if (!is_null($acaoEn->getTabelaReferencia())) {
                            $idTabelaTemp = $result;
                        }
                    }
                    $result = $integracaoService->processaAcao();
                }

            }

            $this->_em->flush();
            $this->_em->commit();
            $this->_em->clear();
            $errNumber = "";
            $trace = "";
            $query = "";
        } catch (\Exception $e) {

            $observacao = $e->getMessage();
            $sucess = "N";

            $prev = $e->getPrevious();
            if ( !empty($prev) ) {
                while ($prev != null) {
                    $prev = $prev->getPrevious();
                    if ($prev != null) {
                        $trace = $prev->getTraceAsString();
                    }
                }
            }

            $errNumber = $e->getCode();
            $result = $e->getMessage();

            $this->_em->rollback();
            $this->_em->clear();
        }

        try {

            $iniciouBeginTransaction = false;
            if ($this->_em->isOpen() == false) {
                $this->_em = $this->_em->create($this->_em->getConnection(),$this->_em->getConfiguration());
            }

            $acaoEn = $this->_em->find("wms:Integracao\AcaoIntegracao",$idAcao);

            if ($iniciouTransacaoAtual == "S") {
                $acaoEn->setIndExecucao("N");
                $this->_em->persist($acaoEn);
                $this->_em->flush();
            }

            $this->_em->beginTransaction();
            $iniciouBeginTransaction = true;

            if (($tipoExecucao == "E") || ($dados == null)) {
                /*
                 * Gravo o log apenas se estiver executando uma operação de inserção no banco de dados, seja tabela temporaria ou de produção
                 * Caso esteja inserindo na tabela temporaria, significa que fiz uma consulta no ERP, então gravo o log
                 * Caso esteja inserindo nas tabelas de produção, sinifica que ou estou gravando um dado em tempo real, ou fiz uma consulta no ERP, então preciso gravar log
                 * Ações de listagem de resumo aonde os dados ja são informados, não é necessario gravar log
                 */
                if ($acaoEn->getIndUtilizaLog() == 'S') {
                    $url = $_SERVER['REQUEST_URI'];
                    $andamentoEn = new AcaoIntegracaoAndamento();
                    $andamentoEn->setAcaoIntegracao($acaoEn);
                    $andamentoEn->setIndSucesso($sucess);
                    $andamentoEn->setUrl($url);
                    $andamentoEn->setDestino($destino);
                    $andamentoEn->setDthAndamento(new \DateTime());
                    $andamentoEn->setObservacao($observacao);
                    $andamentoEn->setErrNumber($errNumber);
                    $andamentoEn->setTrace($trace);
                    if ($sucess != "S") {
                        $andamentoEn->setQuery($query);
                    }
                    $this->_em->persist($andamentoEn);
                }
            }


            if (($tipoExecucao == "E") && ($destino == "P") && ($filtro == AcaoIntegracaoFiltro::DATA_ESPECIFICA) && $acaoEn->getTipoControle() == 'D') {
                /*
                 * Se estiver salvando os dados ja nas tabelas de produção, atualizo a data da ultima execução indicando que a operação foi finalizada para aquela data
                 * Caso estja salvando em tabelas temporarias (com o fim de listagem e validação), a data da ultima execução não deve ser alterada dois a operação ainda não foi concluida
                 */
                if ($sucess=="S") {
                    $maxDate = $integracaoService->getMaxDate();
                    if (!empty($maxDate)) {
                        $acaoEn->setDthUltimaExecucao($maxDate);
                        $this->_em->persist($acaoEn);
                    }
                }
            } else if (($tipoExecucao == 'E') && ($destino == 'P') && $acaoEn->getTipoControle() == 'F') {

                if ($encontrouRegistro == true) {
                    $v = "S";
                } else if ($encontrouRegistro == false) {
                    $v = "N";
                } else {
                    $v = "null";
                }

                if ($sucess == 'S') {
                    if ($encontrouRegistro == true) {
                        if(!empty($idTabelaTemp)) {
                            $max = 900;
                            $ids = array();
                            foreach ($idTabelaTemp as $key => $value){
                                $ids[] = $value['ID'];
                                if(count($ids) == $max){
                                    $ids = implode(',',$ids);
                                    $query = "UPDATE " . $acaoEn->getTabelaReferencia() . " SET IND_PROCESSADO = 'S', DTH_PROCESSAMENTO = SYSDATE WHERE ID IN ($ids) AND (IND_PROCESSADO IS NULL OR IND_PROCESSADO = 'N')";
                                    $this->_em->getConnection()->query($query)->execute();
                                    unset($ids);
                                }
                            }
                            if(count($ids) < $max){
                                $ids = implode(',',$ids);
                                $query = "UPDATE " . $acaoEn->getTabelaReferencia() . " SET IND_PROCESSADO = 'S', DTH_PROCESSAMENTO = SYSDATE WHERE ID IN ($ids) AND (IND_PROCESSADO IS NULL OR IND_PROCESSADO = 'N')";
                                $this->_em->getConnection()->query($query)->execute();
                                unset($ids);
                            }
                        }else{
                            $query = "UPDATE ".$acaoEn->getTabelaReferencia()." SET IND_PROCESSADO = 'S', DTH_PROCESSAMENTO = SYSDATE WHERE IND_PROCESSADO IS NULL OR IND_PROCESSADO = 'N'";
                            $this->_em->getConnection()->query($query)->execute();
                        }
                    }
                }
            }

            $this->_em->flush();
            $this->_em->commit();
            $this->_em->clear();

        } catch (\Exception $e) {
            if ($iniciouBeginTransaction == true) {
                $this->_em->rollback();
            }
            throw new \Exception($e->getMessage());

        }

        return $result;
    }

}
