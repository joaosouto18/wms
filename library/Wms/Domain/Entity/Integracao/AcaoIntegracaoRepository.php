<?php

namespace Wms\Domain\Entity\Integracao;

use Composer\DependencyResolver\Transaction;
use Doctrine\ORM\EntityRepository;
use Wms\Service\Integracao;

class AcaoIntegracaoRepository extends EntityRepository
{

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

    private function getDadosTemporarios($tipoAcao, $dados = null) {
        $SQL = null;
        $where = 'WHERE 1 = 1';
        switch ($tipoAcao) {
            case AcaoIntegracao::INTEGRACAO_NOTAS_FISCAIS:
                if (isset($dados) && !empty($dados)) {
                    $where .= " AND NUM_NOTA_FISCAL IN ($dados)";
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
                         TO_CHAR(DTH,'DD/MM/YYYY HH24:MI:SS') as DTH
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
                                QTD,
                                VLR_VENDA,
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
        $dados = $this->getDadosTemporarios($acaoEn->getTipoAcao()->getId(), $dados);

        /* Executo uma unica ação com todos os dados retornados */
        $result = $this->processaAcao($acaoEn,null,"E","P", $dados,$IdFiltro);

        /* Limpo os dados da tabela temporaria */
        $this->limpaDadosTemporarios($acaoEn->getTipoAcao()->getId());

        return $result;
    }

    public function listaTemporaria($acoes, $options = null, $idFiltro) {

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
        $dados = $this->getDadosTemporarios($acaoEn->getTipoAcao()->getId());

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
    public function processaAcao($acaoEn, $options = null, $tipoExecucao = "E", $destino = "P", $dados = null, $filtro = AcaoIntegracaoFiltro::DATA_ESPECIFICA) {
        /** @var \Wms\Domain\Entity\Integracao\AcaoIntegracao $acaoEn */
        /** @var \Wms\Domain\Entity\Integracao\ConexaoIntegracaoRepository $conexaoRepo */
        $conexaoRepo = $this->_em->getRepository('wms:Integracao\ConexaoIntegracao');
        /** @var \Wms\Domain\Entity\Integracao\AcaoIntegracaoFiltroRepository $acaoFiltroRepo */
        $acaoFiltroRepo = $this->_em->getRepository('wms:Integracao\AcaoIntegracaoFiltro');
        $idAcao = $acaoEn->getId();

        $this->_em->clear();
        $acaoEn = $this->findOneBy(array('id'=>$idAcao));

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
                    } else if ($conexaoEn->getProvedor() == ConexaoIntegracao::PROVEDOR_ORACLE) {
                        $options[] = $data->format("d/m/Y H:i:s");
                    }else if ($conexaoEn->getProvedor() == ConexaoIntegracao::PROVEDOR_MSSQL) {
                        $options[] = $data->format("Y-m-d H:i:s");
                    }
                }
            }

            //STRING DA QUERY DE INTEGRAÇÃO
            $query = $acaoFiltroRepo->getQuery($acaoEn, $options, $filtro, $data);
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
            var_dump($result);exit;
            if ($acaoEn->getidAcaoRelacionada() != null) {
                if (count($result) >0) {

                    $acaoRelacionadaEn = $this->find($acaoEn->getidAcaoRelacionada());

                    $dadosFiltrar = array();
                    foreach ($result as $row) {
                        $dadosFiltrar[] = $row['ID'];
                    }
                    $options = array();
                    $options[] = implode(",", $dadosFiltrar);
                    $result = $this->processaAcao($acaoRelacionadaEn,$options,"E","P",null,AcaoIntegracaoFiltro::CONJUNTO_CODIGO);
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
                    if (count($result) && !is_null($acaoEn->getTabelaReferencia())) {
                        $idTabelaTemp = $result;
                    }

                    $integracaoService = new Integracao($this->getEntityManager(),
                        array('acao'=>$acaoEn,
                            'options'=>$options,
                            'tipoExecucao' => $tipoExecucao,
                            'dados'=>$result));
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
                if ($sucess == 'S') {
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
