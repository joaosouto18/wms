<?php

namespace Wms\Domain\Entity\Integracao;

use Composer\DependencyResolver\Transaction;
use Doctrine\ORM\EntityRepository;
use Wms\Service\Integracao;

class AcaoIntegracaoRepository extends EntityRepository
{
    /** @var \Wms\Domain\Entity\Integracao\AcaoIntegracao $acaoEn */
    public function processaAcao($acaoEn, $options = null) {

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
                        $dthExecucao = "TO_DATE('" . $acaoEn->getDthUltimaExecucao()->format("d/m/y H:i:s") . "','DD/MM/YY HH24:MI:SS')";
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
                    if ($acaoEn->getDthUltimaExecucao() == null) {
                        $dthExecucao = "1900-01-01 01:01:01";
                        if ($acaoEn->getTipoAcao()->getId() == AcaoIntegracao::INTEGRACAO_PRODUTO) {
                            $query = str_replace("and a.es1_dtalteracao > :dthExecucao", "" ,$query);
                        }
                    } else {
                        $dthExecucao = $acaoEn->getDthUltimaExecucao()->format("Y-m-d H:i:s");
                    }

                    $query = str_replace(":dthExecucao", "'$dthExecucao'" ,$query);
                }

                $result = $conexaoRepo->runQuery($query,$conexaoEn);
                $integracaoService = new Integracao($this->getEntityManager(),
                                                    array('acao'=>$acaoEn,
                                                          'options'=>$options,
                                                          'dados'=>$result));
                $result = $integracaoService->processaAcao();

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

            if ($acaoEn->getIndUtilizaLog() == 'S') {
                $andamentoEn = new AcaoIntegracaoAndamento();
                $andamentoEn->setAcaoIntegracao($acaoEn);
                $andamentoEn->setIndSucesso($sucess);
                $andamentoEn->setDthAndamento(new \DateTime());
                $andamentoEn->setObservacao($observacao);
                $andamentoEn->setErrNumber($errNumber);
                $andamentoEn->setTrace($trace);
                if ($sucess != "S") {
                    $andamentoEn->setQuery($query);
                }
                $this->_em->persist($andamentoEn);
            }

            if ($sucess=="S") {
                $maxDate = $integracaoService->getMaxDate();
                if (!empty($maxDate)) {
                    $acaoEn->setDthUltimaExecucao($maxDate);
                    $this->_em->persist($acaoEn);
                }
            }

            $this->_em->flush();
            $this->_em->commit();

        } catch (\Exception $e) {
            $this->_em->rollback();
            //var_dump($e->getMessage());exit;
            throw new \Exception($e->getMessage());

        }

        return $result;
    }
}
