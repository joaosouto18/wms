<?php

namespace Wms\Domain\Entity\Integracao;

use Doctrine\ORM\EntityRepository;
use Wms\Service\Integracao;

class AcaoIntegracaoRepository extends EntityRepository
{
    /** @var \Wms\Domain\Entity\Integracao\AcaoIntegracao $acaoEn */
    public function processaAcao($acaoEn) {

        /** @var \Wms\Domain\Entity\Integracao\ConexaoIntegracaoRepository $conexaoRepo */
        $conexaoRepo = $this->getEntityManager()->getRepository('wms:integracao\ConexaoIntegracao');

        $sucess = "S";
        $observacao = "";

        try {
            $this->getEntityManager()->beginTransaction();

                $conexaoEn = $acaoEn->getConexao();
                $query = $acaoEn->getQuery();

                if ($acaoEn->getDthUltimaExecucao() == null) {
                    $dthExecucao = '01/01/1900 01:01:01';
                } else {
                    $dthExecucao = "TO_DATE('" . $acaoEn->getDthUltimaExecucao()->format("d/m/y H:i:s") . "','DD/MM/YYYY HH24:MI:SS')";
                }

                if (($acaoEn == null) || ($acaoEn->getTipoAcao()->getId() == AcaoIntegracao::INTEGRACAO_PRODUTO)) {
                    $query = str_replace("and p.dtultaltcom >= :dthExecucao", "" ,$query);
                } else {
                    $query = str_replace(":dthExecucao", $dthExecucao ,$query);
                }

                $query = str_replace(":codFilial",$this->getSystemParameterValue("WINTHOR_CODFILIAL_INTEGRACAO"),$query);

                $result = $conexaoRepo->runQuery($query,$conexaoEn);
                $integracaoService = new Integracao($this->getEntityManager(),
                                                    array('acao'=>$acaoEn,
                                                          'dados'=>$result));
                $integracaoService->processaAcao();

            $this->getEntityManager()->flush();
            $this->getEntityManager()->commit();

        } catch (\Exception $e) {
                $observacao = $e->getMessage() . " - QUERY: " . $query;
                $sucess = "N";

            $this->getEntityManager()->rollback();
        }

        try {
            $this->getEntityManager()->beginTransaction();

            if ($acaoEn->getIndUtilizaLog() == 'S') {
                $andamentoEn = new AcaoIntegracaoAndamento();
                $andamentoEn->setAcaoIntegracao($acaoEn);
                $andamentoEn->setIndSucesso($sucess);
                $andamentoEn->setDthAndamento(new \DateTime);
                $andamentoEn->setObservacao($observacao);
                $this->getEntityManager()->persist($andamentoEn);
            }

            if ($sucess=="S") {
                $acaoEn->setDthUltimaExecucao(new \DateTime);
                $this->getEntityManager()->persist($acaoEn);
            }

            $this->getEntityManager()->flush();
            $this->getEntityManager()->commit();

        } catch (\Exception $e) {
            var_dump($e->getMessage());exit;
            $this->getEntityManager()->rollback();
        }

        return true;
    }
}
