<?php

namespace Wms\Domain\Entity\Integracao;

use Doctrine\ORM\EntityRepository;

class AcaoIntegracaoAndamentoRepository extends EntityRepository
{
    public function getStatusAcaoIntegracao()
    {
        $sql = "SELECT TO_CHAR(AIA.DTH_ANDAMENTO,'DD/MM/YYYY HH24:MI:SS') as DTH_ANDAMENTO,
                      AIA.COD_ACAO_INTEGRACAO,
                      AIA.IND_SUCESSO,
                      SUBSTR(AIA.DSC_OBSERVACAO,0,80) DSC_OBSERVACAO,
                      AI.DSC_ACAO_INTEGRACAO
                 FROM ACAO_INTEGRACAO_ANDAMENTO AIA
                 INNER JOIN (SELECT MAX(COD_ACAO_INTEGRACAO_ANDAMENTO) as COD_ACAO_INTEGRACAO_ANDAMENTO,
                                    COD_ACAO_INTEGRACAO
                               FROM ACAO_INTEGRACAO_ANDAMENTO
                              GROUP BY COD_ACAO_INTEGRACAO) MAIA ON MAIA.COD_ACAO_INTEGRACAO_ANDAMENTO = AIA.COD_ACAO_INTEGRACAO_ANDAMENTO
                 INNER JOIN ACAO_INTEGRACAO AI ON AIA.COD_ACAO_INTEGRACAO = AI.COD_ACAO_INTEGRACAO
                 WHERE AIA.IND_SUCESSO = 'N'
                 ORDER BY AIA.COD_ACAO_INTEGRACAO";

        return $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function setAcaoIntegracaoAndamento($idAcao, $erros)
    {

        /*
         * Gravo o log apenas se estiver executando uma operação de inserção no banco de dados, seja tabela temporaria ou de produção
         * Caso esteja inserindo na tabela temporaria, significa que fiz uma consulta no ERP, então gravo o log
         * Caso esteja inserindo nas tabelas de produção, sinifica que ou estou gravando um dado em tempo real, ou fiz uma consulta no ERP, então preciso gravar log
         * Ações de listagem de resumo aonde os dados ja são informados, não é necessario gravar log
         */

        $acaoEn = $this->_em->find("wms:Integracao\AcaoIntegracao", $idAcao);
        foreach ($erros as $erro) {
            if ($acaoEn->getIndUtilizaLog() == 'S') {
                $url = $_SERVER['REQUEST_URI'];
                $andamentoEn = new AcaoIntegracaoAndamento();
                $andamentoEn->setAcaoIntegracao($acaoEn);
                $andamentoEn->setIndSucesso($erro['success']);
                $andamentoEn->setUrl($url);
                $andamentoEn->setDestino($erro['destino']);
                $andamentoEn->setDthAndamento(new \DateTime());
                $andamentoEn->setObservacao($erro['message']);
                $andamentoEn->setErrNumber($erro['errNumber']);
                $andamentoEn->setTrace($erro['trace']);
                if ($sucess != "S") {
                    $andamentoEn->setQuery($erro['query']);
                }
                $this->_em->persist($andamentoEn);
                $this->_em->flush();
            }
        }

    }

}
