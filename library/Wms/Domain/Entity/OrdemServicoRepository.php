<?php

namespace Wms\Domain\Entity;

use Doctrine\ORM\EntityRepository,
    Wms\Domain\Entity\OrdemServico as OrdemServicoEntity,
    Wms\Domain\Entity\Atividade as AtividadeEntity;

/**
 * Deposito
 */
class OrdemServicoRepository extends EntityRepository
{

    /**
     *
     * @param OrdemServicoEntity $ordemServicoEntity
     * @param array $values
     * @return int Id da entidade
     */
    public function save(OrdemServicoEntity $ordemServicoEntity, array $values, $runFlush = true , $returnType = "Id")
    {
        extract($values['identificacao']);
        $em = $this->getEntityManager();

        if (!isset($tipoOrdem)) {
            $tipoOrdem = null;
        }

        switch($tipoOrdem) {
            case 'expedicao' :
                $expedicaoEntity = $em->getReference('wms:Recebimento', $idExpedicao);
                $ordemServicoEntity->setExpedicao($expedicaoEntity);
                break;

            case 'enderecamento' :
                $ordemServicoEntity->setIdEnderecamento($idEnderecamento);
                break;
            case 'ressuprimento':
            case 'inventario':
                break;
            default:
                $recebimentoEntity = $em->getReference('wms:Recebimento', $idRecebimento);
                $ordemServicoEntity->osConferencia($recebimentoEntity);
                break;
        }

        $atividadeEntity = $em->getReference('wms:Atividade', $idAtividade);
        // conferente
        $idPessoa = (isset($idPessoa)) ? $idPessoa : \Zend_Auth::getInstance()->getIdentity()->getId();
        $pessoaEntity = $em->getReference('wms:Pessoa', $idPessoa);

        $ordemServicoEntity->setDataInicial(new \DateTime)
            ->setAtividade($atividadeEntity)
            ->setPessoa($pessoaEntity)
            ->setFormaConferencia($formaConferencia);

        $em->persist($ordemServicoEntity);

        if ($runFlush == true) $em->flush();

        if ($returnType == "Id") {
            return $ordemServicoEntity->getId();
        } else {
            return $ordemServicoEntity;
        }
    }


    /**
     * Grava conferente para ordem de servico
     *
     * @param integer $idOrdemServico
     * @param integer $idConferente
     * @return boolean
     */
    public function atualizarConferente($idOrdemServico, $idConferente)
    {
        $ordemServicoEntity = $this->find($idOrdemServico);
        $pessoaEntity = $this->getEntityManager()->getReference('wms:Pessoa', $idConferente);

        $ordemServicoEntity->setPessoa($pessoaEntity);

        $this->getEntityManager()->persist($ordemServicoEntity);
        $this->getEntityManager()->flush();

        return true;
    }

    /**
     * Atualiza observacao da ordem de servico
     *
     * @param integer $idOrdemServico
     * @param string $observacao
     * @return boolean
     */
    public function atualizarObservacao($idOrdemServico, $observacao)
    {
        $ordemServicoEntity = $this->find($idOrdemServico);

        $ordemServicoEntity->setDscObservacao($observacao);

        $this->getEntityManager()->persist($ordemServicoEntity);
        $this->getEntityManager()->flush();

        return true;
    }

    /**
     * Finaliza uma ordem de serviço setando os parametros
     *
     * @param integer $idOrdemServico
     * @return boolean
     */
    public function finalizar($idOrdemServico)
    {
        $ordemServicoEntity = $this->find($idOrdemServico);

        $ordemServicoEntity->setDscObservacao('Recebimento Finalizado.')
            ->setDataFinal(new \DateTime());

        $this->getEntityManager()->persist($ordemServicoEntity);
        $this->getEntityManager()->flush();

        return true;
    }

    public function getOsByExpedicao ($idExpedicao)
    {
        $_em = $this->getEntityManager();

        $queryBuilder = $this->getEntityManager()->createQueryBuilder()
            ->select('os.id,
                      os.dataInicial,
                      os.dataFinal,
                      atv.descricao atividade,
                      p.nome pessoa')
            ->from('wms:OrdemServico', 'os')
            ->innerJoin("os.pessoa", "p")
            ->innerJoin("os.atividade", "atv")
            ->addSelect("( SELECT COUNT(es) as qtdConferido
                             FROM wms:Expedicao\EtiquetaSeparacao es
                            WHERE es.codOS = os.id
                          ) as qtdConferida")
            ->addSelect("( SELECT COUNT(es2) as qtdConferidoTransbordo
                            FROM wms:Expedicao\EtiquetaSeparacao es2
                            WHERE es2.codOSTransbordo = os.id
                          ) as qtdConferidaTransbordo")
            ->where('os.idExpedicao = :idExpedicao')
            ->setParameter('idExpedicao', $idExpedicao);

        //$result = $queryBuilder->getQuery()->getResult();

        return $queryBuilder;
    }

    public function getResumoOsById ($idOS) {
        $_em = $this->getEntityManager();

        $queryBuilder = $this->getEntityManager()->createQueryBuilder()
            ->select('os.id idOS,
                      os.dataInicial,
                      os.dataFinal,
                      atv.descricao atividade,
                      p.nome pessoa,
                      os.idExpedicao')
            ->from('wms:OrdemServico', 'os')
            ->innerJoin("os.pessoa","p")
            ->innerJoin("os.atividade", "atv")
            ->where('os.id = :idOs')
            ->setParameter('idOs', $idOS);

        $result = $queryBuilder->getQuery()->getResult();
        return $result[0];
    }

    public function getConferenciaByOs ($idOS, $transbordo = false) {
        $_em = $this->getEntityManager();
        $queryBuilder = $this->getEntityManager()->createQueryBuilder()
            ->select('es.id,
                      prod.descricao as produto,
                      prod.id as codProduto,
                      prod.grade,
                      CASE WHEN emb.descricao IS NULL THEN vol.descricao ELSE emb.descricao END as embalagem,
                      es.dataConferencia,
                      es.dataConferenciaTransbordo
                      ')
            ->from('wms:Expedicao\EtiquetaConferencia', 'ec')
            ->leftJoin('wms:Expedicao\EtiquetaSeparacao', 'es', 'WITH', 'ec.codEtiquetaSeparacao = es.id')
            ->innerJoin("es.produto","prod")
            ->leftJoin('es.produtoEmbalagem','emb')
            ->leftJoin('es.produtoVolume','vol');

        if ($transbordo == false) {
            $queryBuilder->where('ec.codOsPrimeiraConferencia = :idOS')
            ->orWhere('ec.codOsSegundaConferencia = :idOS');
        } else {
            $queryBuilder->where('es.codOSTransbordo = :idOS');
        }

        $queryBuilder->setParameter('idOS', $idOS);

        return $queryBuilder;
    }

    public function forcarCorrecao($idRecebimento)
    {

        $entity = $this->getEntityManager()->createQueryBuilder()
            ->select('COUNT(os.id), MIN(os.id)')
            ->from('wms:OrdemServico', 'os')
            ->where("os.dataFinal is null and os.idRecebimento = $idRecebimento");

        return $entity->getQuery()->getResult();
    }

    public function atualizarDataFinal($idOrdemServico, $data)
    {
        $ordemServicoEntity = $this->find($idOrdemServico);

        $ordemServicoEntity->setDataFinal($data);

        $this->getEntityManager()->persist($ordemServicoEntity);
        $this->getEntityManager()->flush();

        return true;
    }

    public function getOsByExpedicaoReconferencia($idExpedicao)
    {
        $sql = "
                SELECT NVL(PRIMEIRA.COD_EXPEDICAO, SEGUNDA.COD_EXPEDICAO) as COD_EXPEDICAO,
                       NVL(PRIMEIRA.COD_OS, SEGUNDA.COD_OS) as os,
                       P.NOM_PESSOA as PESSOA,
                       NVL(PRIMEIRA.QTD_ETIQUETAS,0) as qtdConferida,
                       NVL(SEGUNDA.QTD_ETIQUETAS,0) as qtdSegundaConferencia,
                       NVL(TRANSBORDO.QTD_CONFERIDAS_TRANSBORDO,0) as qtdConferidaTransbordo,
                       TO_CHAR(OS.DTH_INICIO_ATIVIDADE, 'DD/MM/YYYY HH24:MI') as dataInicial,
                       TO_CHAR(OS.DTH_FINAL_ATIVIDADE, 'DD/MM/YYYY HH24:MI') as dataFinal,
                       AT.DSC_ATIVIDADE
                  FROM (SELECT COD_EXPEDICAO,
                               COD_OS_PRIMEIRA_CONFERENCIA as COD_OS,
                               COUNT(COD_ETIQUETA_CONFERENCIA) as QTD_ETIQUETAS
                          FROM ETIQUETA_CONFERENCIA
                            WHERE COD_OS_PRIMEIRA_CONFERENCIA IS NOT NULL
                            GROUP BY COD_EXPEDICAO, COD_OS_PRIMEIRA_CONFERENCIA) PRIMEIRA
                FULL OUTER JOIN (SELECT COD_EXPEDICAO,
                                        COD_OS_SEGUNDA_CONFERENCIA as COD_OS,
                                        COUNT(COD_ETIQUETA_CONFERENCIA) as QTD_ETIQUETAS
                                  FROM ETIQUETA_CONFERENCIA
                                  WHERE COD_OS_SEGUNDA_CONFERENCIA IS NOT NULL
                                  GROUP BY COD_EXPEDICAO, COD_OS_SEGUNDA_CONFERENCIA) SEGUNDA
                                    ON SEGUNDA.COD_EXPEDICAO = PRIMEIRA.COD_EXPEDICAO
                                    AND SEGUNDA.COD_OS = PRIMEIRA.COD_OS
                FULL OUTER JOIN (SELECT ES.COD_OS_TRANSBORDO,
                                        COUNT(COD_ETIQUETA_SEPARACAO) as QTD_CONFERIDAS_TRANSBORDO
                                  FROM ETIQUETA_SEPARACAO ES
                                  WHERE COD_OS_TRANSBORDO IS NOT NULL
                                  GROUP BY ES.COD_OS_TRANSBORDO) TRANSBORDO
                                    ON COD_OS_TRANSBORDO = SEGUNDA.COD_OS
                LEFT JOIN ORDEM_SERVICO OS
                       ON OS.COD_OS = PRIMEIRA.COD_OS
                       OR OS.COD_OS = SEGUNDA.COD_OS
                LEFT JOIN ATIVIDADE AT
                       ON AT.COD_ATIVIDADE = OS.COD_ATIVIDADE
                LEFT JOIN PESSOA P ON OS.COD_PESSOA = P.COD_PESSOA
                    WHERE PRIMEIRA.COD_EXPEDICAO = $idExpedicao
                       OR SEGUNDA.COD_EXPEDICAO = $idExpedicao";

        return $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

}

