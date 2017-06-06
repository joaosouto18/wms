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

    private function getDadosTemporarios($tipoAcao) {
        $SQL = null;
        switch ($tipoAcao) {
            case AcaoIntegracao::INTEGRACAO_NOTAS_FISCAIS:
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
                FROM INTEGRACAO_PEDIDO ORDER by CARGA, PEDIDO, PRODUTO";
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

    public function efetivaTemporaria($acoes) {

        /* Para efetivar no banco de dados, só vou efetivar uma unica vez mesmo que tenham sido disparados n consultas.
           Porem todas tem que compartilhar a mesma tabela temporaria, ou seja, ser da mesma ação */
        $this->validaAcoesMesmoTipo($acoes);
        $acaoEn = $acoes[0];

        /* Consulto os dados da tabela temporaria referente a ação */
        $dados = $this->getDadosTemporarios($acaoEn->getTipoAcao()->getId());

        /* Executo uma unica ação com todos os dados retornados */
        $result = $this->processaAcao($acaoEn,null,"E","P",$dados);

        /* Limpo os dados da tabela temporaria */
        $this->limpaDadosTemporarios($acaoEn->getTipoAcao()->getId());

        return $result;
    }

    public function listaTemporaria($acoes, $options = null) {

        $this->validaAcoesMesmoTipo($acoes);

        $acaoEn = $acoes[0];

        $this->limpaDadosTemporarios($acaoEn->getTipoAcao()->getId());

        /* Executa cada ação salvando os dados na tabela temporaria */
        foreach ($acoes as $acaoEn) {
            $this->processaAcao($acaoEn,$options,"E","T");
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
    public function processaAcao($acaoEn, $options = null, $tipoExecucao = "E", $destino = "P", $dados = null) {
        /** @var \Wms\Domain\Entity\Integracao\AcaoIntegracao $acaoEn */
        /** @var \Wms\Domain\Entity\Integracao\ConexaoIntegracaoRepository $conexaoRepo */
        $conexaoRepo = $this->_em->getRepository('wms:integracao\ConexaoIntegracao');
        $idAcao = $acaoEn->getId();
        $sucess = "S";
        $observacao = "";
        $trace = "";
        $query = "";
        $integracaoService = null;

        try {
            $this->_em->beginTransaction();

            $conexaoEn = $acaoEn->getConexao();
            $query = $acaoEn->getQuery();

            if ($conexaoEn->getProvedor() == ConexaoIntegracao::PROVEDOR_ORACLE){
                //PARAMETRIZA A DATA DE ULTIMA EXECUÇÃO DA QUERY
                if ($acaoEn->getDthUltimaExecucao() == null) {
                    $dthExecucao = "TO_DATE('01/01/1900 01:01:01','DD/MM/YY HH24:MI:SS')";
                    if ($acaoEn->getTipoAcao()->getId() == AcaoIntegracao::INTEGRACAO_PRODUTO) {
                        $query = str_replace("and p.dtcadastro > :dthExecucao", "" ,$query);
                        $query = str_replace("AND (log.datainicio > :dthExecucao OR p.dtultaltcom > :dthExecucao)", "" ,$query);
                    }
                } else {
                    if ($acaoEn->getTipoAcao()->getId() == AcaoIntegracao::INTEGRACAO_PEDIDOS) {
                        $dthExecucao = "TO_DATE('" . $acaoEn->getDthUltimaExecucao()->format("d/m/Y H:i:s") . "','DD/MM/YYYY HH24:MI:SS')";
                    } else {
                        $dthExecucao = "TO_DATE('" . $acaoEn->getDthUltimaExecucao()->format("d/m/y H:i:s") . "','DD/MM/YY HH24:MI:SS')";
                    }
                }

                $query = str_replace(":dthExecucao", $dthExecucao ,$query);

                //PARAMETRIZA O COD_FILIAL PELO CODIGO DA FILIAL DE INTEGRAÇAO PARA INTEGRAÇÕES NO WINTHOR
                $query = str_replace(":codFilial",$this->getSystemParameterValue("WINTHOR_CODFILIAL_INTEGRACAO"),$query);

                //DEFINI OS PARAMETROS PASSADOS EM OPTIONS
                if (!is_null($options)) {
                    foreach ($options as $key => $value) {
                        $query = str_replace(":?" . ($key+1) ,$value ,$query);
                    }
                }
            } else if ($conexaoEn->getProvedor() == ConexaoIntegracao::PROVEDOR_MYSQL) {
                $dthExecucao = null;
                if ($acaoEn->getDthUltimaExecucao() == null) {
                    if ($acaoEn->getTipoAcao()->getId() == AcaoIntegracao::INTEGRACAO_PRODUTO) {
                        $query = str_replace("and a.es1_dtalteracao > :dthExecucao", "" ,$query);
                    } else {
                        $hoje = new \DateTime();
                        $dthExecucao = $hoje->format("Y/m/d") . " 00:00:00";
                    }
                } else {
                    $dthExecucao = $acaoEn->getDthUltimaExecucao()->format("Y/m/d H:i:s");
                }

                $query = str_replace(":dthExecucao", "'$dthExecucao'" ,$query);
            }

            if ($dados == null) {
                $result = $conexaoRepo->runQuery($query,$conexaoEn);
            } else {
                $result = $dados;
            }

            if (($tipoExecucao == "E") && ($destino == "T")) {
                $integracaoService = new Integracao($this->getEntityManager(),
                    array('acao'=>$acaoEn,
                        'dados'=>$result));
                $integracaoService->salvaTemporario();
            } else {
                $integracaoService = new Integracao($this->getEntityManager(),
                    array('acao'=>$acaoEn,
                          'options'=>$options,
                          'tipoExecucao' => $tipoExecucao,
                          'dados'=>$result));
                $result = $integracaoService->processaAcao();
            }

            $this->_em->flush();
            $this->_em->commit();
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
            if ($this->_em->isOpen() == false) {
                $this->_em = $this->_em->create($this->_em->getConnection(),$this->_em->getConfiguration());
            }
            $this->_em->beginTransaction();

            $acaoEn = $this->_em->find("wms:Integracao\AcaoIntegracao",$idAcao);

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

            var_dump($tipoExecucao);
            var_dump($destino);
            var_dump($sucess);
            if (($tipoExecucao == "E") && ($destino == "P")) {
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
            }

            $this->_em->flush();
            $this->_em->commit();

        } catch (\Exception $e) {
            $this->_em->rollback();
            throw new \Exception($e->getMessage());

        }

        return $result;
    }
}
