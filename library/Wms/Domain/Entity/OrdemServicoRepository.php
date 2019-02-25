<?php

namespace Wms\Domain\Entity;

use Doctrine\ORM\EntityRepository,
    Wms\Domain\Entity\OrdemServico as OrdemServicoEntity,
    Wms\Domain\Entity\Atividade as AtividadeEntity;
use Wms\Domain\Configurator;

/**
 * Deposito
 */
class OrdemServicoRepository extends EntityRepository
{

    /**
     * @param $params
     * @param bool $executeFlush
     * @return OrdemServico
     * @throws \Exception
     */
    public function addNewOs($params, $executeFlush = true)
    {
        try {
            /** @var OrdemServico $entity */
            $entity = Configurator::configure(new $this->_entityName, $params);

            $this->_em->persist($entity);
            if ($executeFlush) $this->_em->flush();

            return $entity;

        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

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
     * @param $idOrdemServico
     * @param string $observacao
     * @param null $ordemServicoEntity
     * @return bool
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function finalizar($idOrdemServico, $observacao='Recebimento Finalizado.', $ordemServicoEntity = null, $flush = true)
    {
        if (empty($ordemServicoEntity))
            $ordemServicoEntity = $this->find($idOrdemServico);

        $ordemServicoEntity->setDscObservacao($observacao)
            ->setDataFinal(new \DateTime());

        $this->getEntityManager()->persist($ordemServicoEntity);
        if ($flush) $this->getEntityManager()->flush();

        return true;
    }

    public function getOsByExpedicao ($idExpedicao)
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder()
            ->select('os.id,
                      os.dataInicial,
                      os.dataFinal,
                      atv.descricao atividade,
                      p.nome pessoa')
            ->from('wms:OrdemServico', 'os')
            ->innerJoin("os.pessoa", "p")
            ->innerJoin("os.atividade", "atv")
            ->addSelect("( SELECT NVL(SUM(msc.qtdConferida), COUNT(es.id))
                             FROM wms:OrdemServico os1
                             LEFT JOIN wms:Expedicao\EtiquetaSeparacao es WITH es.codOS = os1.id
                             LEFT JOIN wms:Expedicao\MapaSeparacaoConferencia msc WITH msc.codOS = os1.id
                            WHERE os1.id = os.id
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

    public function getConferenciaByOs ($idOS, $transbordo = false, $tipoConferencia = null) {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder()
            ->from('wms:Expedicao\EtiquetaSeparacao', 'es')
            ->innerJoin("es.produto","prod")
            ->leftJoin('es.produtoEmbalagem','emb')
            ->leftJoin('es.produtoVolume','vol')
            ->setParameter('idOS', $idOS);

        if ($transbordo == false) {
            if ($tipoConferencia != null && $tipoConferencia == 'Conferencia') {
                $queryBuilder
                    ->select('es.id,
                      prod.descricao as produto,
                      prod.id as codProduto,
                      prod.grade,
                      CASE WHEN emb.descricao IS NULL THEN vol.descricao ELSE emb.descricao END as embalagem,
                      es.dataConferenciaTransbordo,
                      es.dataConferencia
                      ')
                    ->leftJoin('wms:Expedicao\EtiquetaConferencia', 'ec', 'WITH', 'ec.codEtiquetaSeparacao = es.id')
                    ->andWhere('ec.codOsPrimeiraConferencia = :idOS');
            } elseif ($tipoConferencia != null && $tipoConferencia == 'Reconferencia') {
                $queryBuilder
                    ->select('es.id,
                      prod.descricao as produto,
                      prod.id as codProduto,
                      prod.grade,
                      CASE WHEN emb.descricao IS NULL THEN vol.descricao ELSE emb.descricao END as embalagem,
                      es.dataConferenciaTransbordo,
                      ec.dataReconferencia as dataConferencia
                      ')
                    ->leftJoin('wms:Expedicao\EtiquetaConferencia', 'ec', 'WITH', 'ec.codEtiquetaSeparacao = es.id')
                    ->andWhere('ec.codOsSegundaConferencia = :idOS');
            } else {
                $queryBuilder->select('es.id,
                      prod.descricao as produto,
                      prod.id as codProduto,
                      prod.grade,
                      CASE WHEN emb.descricao IS NULL THEN vol.descricao ELSE emb.descricao END as embalagem,
                      es.dataConferencia,
                      es.dataConferenciaTransbordo
                      ');
                $queryBuilder->andWhere('es.codOS = :idOS');
            }
        } else {
            $queryBuilder->select('es.id,
                      prod.descricao as produto,
                      prod.id as codProduto,
                      prod.grade,
                      CASE WHEN emb.descricao IS NULL THEN vol.descricao ELSE emb.descricao END as embalagem,
                      es.dataConferencia,
                      es.dataConferenciaTransbordo
                      ');
            $queryBuilder->andWhere('es.codOSTransbordo = :idOS');
        }

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

    public function getSeparacaoByOs($idOs){
        $sql = "SELECT 
                    SMS.COD_MAPA_SEPARACAO,
                    SMS.COD_PRODUTO,
                    SMS.DSC_GRADE,
                    SUM(SMS.QTD_SEPARADA * SMS.QTD_EMBALAGEM)  AS QTD_SEPARADA,
                    P.DSC_PRODUTO,
                    MAX(TO_CHAR(SMS.DTH_SEPARACAO, 'DD/MM/YYYY')) as DTH_SEPARACAO
                FROM
                    SEPARACAO_MAPA_SEPARACAO SMS
                    INNER JOIN PRODUTO P ON SMS.COD_PRODUTO = P.COD_PRODUTO AND SMS.DSC_GRADE = P.DSC_GRADE
                WHERE SMS.COD_OS = $idOs
                GROUP BY 
                    SMS.COD_MAPA_SEPARACAO,
                    SMS.COD_PRODUTO,
                    SMS.DSC_GRADE,
                    P.DSC_PRODUTO";

        return $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function criarOs($params)
    {
        if (!$params['atividade']) {
            throw new \Exception('Atividade não informada');
        }
        if (!$params['observacao']) {
            throw new \Exception('Observação não informada');
        }

        $em = $this->getEntityManager();
        $atividadeEntity = $em->getReference('wms:Atividade', $params['atividade']);

        $idPessoa = (isset($idPessoa)) ? $idPessoa : \Zend_Auth::getInstance()->getIdentity()->getId();
        $pessoaEntity = $em->getReference('wms:Pessoa', $idPessoa);

        $ordemServicoEn = new OrdemServico();
        $ordemServicoEn->setDataInicial(new \DateTime);
        $ordemServicoEn->setAtividade($atividadeEntity);
        $ordemServicoEn->setDscObservacao($params['observacao']);
        $ordemServicoEn->setPessoa($pessoaEntity);

        $this->_em->persist($ordemServicoEn);
        $this->_em->flush();
        return $ordemServicoEn;
    }

    public function criarOsByReentrega($recebimentoReentregaEn)
    {
        $em = $this->getEntityManager();
        $atividadeEntity = $em->getReference('wms:Atividade', 15);

        $idPessoa = (isset($idPessoa)) ? $idPessoa : \Zend_Auth::getInstance()->getIdentity()->getId();
        $pessoaEntity = $em->getReference('wms:Pessoa', $idPessoa);

        $ordemServicoEn = new OrdemServico();
        $ordemServicoEn->setDataInicial(new \DateTime);
        $ordemServicoEn->setAtividade($atividadeEntity);
        $ordemServicoEn->setRecebimentoReentrega($recebimentoReentregaEn);
        $ordemServicoEn->setDscObservacao('Recebimento de Reentrega');
        $ordemServicoEn->setPessoa($pessoaEntity);

        $this->_em->persist($ordemServicoEn);
        $this->_em->flush();
    }

    public function saveByInventarioManual()
    {
        $em = $this->getEntityManager();

        $idPessoa = (isset($idPessoa)) ? $idPessoa : \Zend_Auth::getInstance()->getIdentity()->getId();
        $pessoaEntity = $em->getReference('wms:Pessoa', $idPessoa);
        $atividadeEntity = $em->getReference('wms:Atividade', AtividadeEntity::INVENTARIO);

        $ordemServicoEn = new OrdemServico();
        $ordemServicoEn->setDataInicial(new \DateTime());
        $ordemServicoEn->setAtividade($atividadeEntity);
        $ordemServicoEn->setDscObservacao('Inventário Manual');
        $ordemServicoEn->setPessoa($pessoaEntity);
        $ordemServicoEn->setFormaConferencia('M');

        $this->_em->persist($ordemServicoEn);
        $this->_em->flush();

        return $ordemServicoEn;
    }

    public function buscaOsProdutoExcluidoDoInventario($idEndereco, $idProduto, $grade){
        $sql = "select 
                      os.cod_os codOs
                      from ordem_servico os            
                        inner join inventario_cont_end_os iceo on iceo.cod_os = os.cod_os      
                        inner join inventario_cont_end ice on ice.cod_inv_cont_end = iceo.cod_inv_cont_end
                        inner join inventario_cont_end_prod icep on icep.cod_inv_cont_end = ice.cod_inv_cont_end  
                        inner join inventario_endereco_novo ien on ien.cod_inventario_endereco = ice.cod_inventario_endereco                      
                      where ien.cod_inventario_endereco = $idEndereco                        
                        and icep.cod_produto = $idProduto                        
                        and icep.dsc_grade = $grade                        
                        and os.dth_final_atividade is null";

        $idOs = $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);

        if(!empty($idOs))
            $this->excluiOs($idOs);
    }

    public function buscaOsEnderecoExcluidoDoInventario($idEndereco){
        $sql = "select os.cod_os 
                    from ordem_servico os            
                      inner join inventario_cont_end_os iceo on iceo.cod_os = os.cod_os
                      inner join inventario_cont_end ice on ice.COD_INV_CONT_END = iceo.cod_inv_cont_end                                        
                    where ice.cod_inventario_endereco = $idEndereco                      
                      and os.dth_final_atividade is null";

        $idOs = $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);

        if(!empty($idOs))
            $this->excluiOs($idOs);
    }

    public function excluiOsInventarioCancelado($idInventario){
        $sql = "select os.cod_os codOs
                    from ordem_servico os            
                      inner join inventario_cont_end_os iceo on iceo.cod_os = os.cod_os
                      inner join inventario_cont_end ice on ice.COD_INV_CONT_END = iceo.cod_inv_cont_end 
                      inner join inventario_endereco_novo ien on ien.cod_inventario_endereco = ice.cod_inventario_endereco                                      
                    where ien.cod_inventario = $idInventario
                      and os.dth_final_atividade is null";

        $idOs = $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);

        if(!empty($idOs))
            $this->excluiOs($idOs);
    }

    public function excluiOs($idOs){
        $codigos = array();
        foreach ($idOs as $item) {
            $codigos[] = $item['CODOS'];
        }

        $sql = "delete from inventario_cont_end_os where cod_os in (" . implode(",", $codigos).")";
        $this->getEntityManager()->getConnection()->query($sql)->execute();

        $sql = "delete from ordem_servico where cod_os in (" . implode(",", $codigos).")";
        $this->getEntityManager()->getConnection()->query($sql)->execute();
    }
}

