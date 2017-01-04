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

                $result = $conexaoRepo->runQuery($query,$conexaoEn);
                $integracaoService = new Integracao($this->getEntityManager(),
                                                    array('acao'=>$acaoEn,
                                                          'dados'=>$result));
                $integracaoService->processaAcao();

            $this->getEntityManager()->flush();
            $this->getEntityManager()->commit();

        } catch (\Exception $e) {
                $observacao = $e->getMessage();
                $sucess = "N";

            $this->getEntityManager()->rollback();
        }

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
        return true;
    }
}
