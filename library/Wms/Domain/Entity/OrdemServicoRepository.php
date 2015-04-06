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

    public function getOsByExpedicao ($idExpedicao) {
        $_em = $this->getEntityManager();

        $queryBuilder = $this->getEntityManager()->createQueryBuilder()
            ->select('os.id,
                      os.dataInicial,
                      os.dataFinal,
                      atv.descricao atividade,
                      p.nome pessoa')
            ->from('wms:OrdemServico', 'os')
            ->innerJoin("os.pessoa","p")
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

        $result = $queryBuilder->getQuery()->getResult();

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
            ->from('wms:Expedicao\EtiquetaSeparacao', 'es')
            ->innerJoin("es.produto","prod")
            ->leftJoin('es.produtoEmbalagem','emb')
            ->leftJoin('es.produtoVolume','vol');

        if ($transbordo == false) {
            $queryBuilder->where('es.codOS = :idOS');
        } else {
            $queryBuilder->where('es.codOSTransbordo = :idOS');
        }

        $queryBuilder->setParameter('idOS', $idOS);

        return $queryBuilder;
    }

}

