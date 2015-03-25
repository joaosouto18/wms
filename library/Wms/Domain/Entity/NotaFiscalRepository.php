<?php

namespace Wms\Domain\Entity;

use Doctrine\ORM\EntityRepository,
    Wms\Domain\Entity\NotaFiscal as NotaFiscalEntity,
    Wms\Domain\Entity\Recebimento as RecebimentoEntity,
    Core\Util\Produto as ProdutoUtil;

/**
 * NotaFiscal
 */
class NotaFiscalRepository extends EntityRepository
{

    /**
     *
     * @param array $values
     * @return array Result set
     */
    public function search(array $values = array())
    {
        extract($values);

        $dql = $this->getEntityManager()->createQueryBuilder()
                ->select('nf, p.nomeFantasia fornecedor')
                ->addSelect("
                        (
                            SELECT SUM(nfi.quantidade)
                            FROM wms:NotaFiscal nf2
                            INNER JOIN nf2.itens nfi
                            WHERE nf2.id = nf.id
                        )
                        AS qtdProduto
                    ")
                ->from('wms:NotaFiscal', 'nf')
                ->innerJoin('nf.fornecedor', 'f')
                ->innerJoin('f.pessoa', 'p')
                ->where('nf.recebimento IS NULL')
                ->andWhere('nf.status = ?1')
                ->setParameter(1, NotaFiscalEntity::STATUS_INTEGRADA)
                ->orderBy('nf.placa, nf.dataEmissao');

        if ($idFornecedor)
            $dql->andWhere("nf.fornecedor = '" . $idFornecedor . "'");

        if ($numero)
            $dql->andWhere("nf.numero = '" . $numero . "'");

        if ($serie)
            $dql->andWhere("nf.serie = '" . $serie . "'");

        if ($dataEntradaInicial) {
            $dataEntradaInicial = new \DateTime(str_replace("/", "-", $dataEntradaInicial));

            $dql->andWhere("TRUNC(nf.dataEntrada) >= ?2")
                    ->setParameter(2, $dataEntradaInicial);
        }

        if ($dataEntradaFinal) {
            $dataEntradaFinal = new \DateTime(str_replace("/", "-", $dataEntradaFinal));

            $dql->andWhere("TRUNC(nf.dataEntrada) <= ?3")
                    ->setParameter(3, $dataEntradaFinal);
        }

        return $dql->getQuery()->getResult();
    }

    /**
     * Busca relação de itens para conferencia. 
     * Traz apenas os itens que devem constar na conferencia
     * 
     * @param int $idRecebimento
     * @return array Result set
     */
    public function getItemConferencia($idRecebimento)
    {
        $sql = "
            SELECT nfi.cod_produto codigo, nfi.dsc_grade grade, p.dsc_produto descricao, SUM(nfi.qtd_item) quantidade 
            FROM nota_fiscal nf
            INNER JOIN nota_fiscal_item nfi ON (nf.cod_nota_fiscal = nfi.cod_nota_fiscal)
            INNER JOIN produto p ON (p.cod_produto = nfi.cod_produto AND p.dsc_grade = nfi.dsc_grade)
            WHERE nf.cod_recebimento = " . (int) $idRecebimento . " 
                AND nf.cod_status = " . NotaFiscalEntity::STATUS_EM_RECEBIMENTO . "
                AND NOT EXISTS (
                    SELECT 'x'
                    FROM ordem_servico os
                    INNER JOIN recebimento_conferencia rc ON (rc.cod_os = os.cod_os)
                    WHERE os.cod_recebimento = nf.cod_recebimento 
                    AND rc.cod_produto = nfi.cod_produto 
                    AND rc.dsc_grade = nfi.dsc_grade
                    AND rc.qtd_divergencia = 0
                )
           GROUP BY nfi.cod_produto, nfi.dsc_grade, p.dsc_produto
           ORDER BY nfi.cod_produto, nfi.dsc_grade";

        $array = $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);


        $multiArrayChangeKeyCase = function (&$array) use ( &$multiArrayChangeKeyCase ) {
                    $array = array_change_key_case($array);

                    foreach ($array as $key => $row)
                        if (is_array($row))
                            $multiArrayChangeKeyCase($array[$key]);

                    return $array;
                };

        return $multiArrayChangeKeyCase($array);
    }

    /**
     * Retorna uma nota para reintegrada checando se consta no recebimento
     * 
     * @param int $idNotaFiscal
     * @param int $observacao 
     * @return boolean
     * @throws \Exception 
     */
    public function descartar($idNotaFiscal, $observacao)
    {
        $em = $this->getEntityManager();

        // trata a nota fiscal
        $notaFiscalEntity = $this->find($idNotaFiscal);

        if ($notaFiscalEntity == null)
            throw new \Exception('Nota Fiscal não encontrada');

        if ($notaFiscalEntity->getStatus()->getId() == NotaFiscalEntity::STATUS_CANCELADA)
            throw new \Exception('Nota Fiscal se encontra cancelada');

        if ($notaFiscalEntity->getStatus()->getId() == NotaFiscalEntity::STATUS_INTEGRADA)
            throw new \Exception('Nota Fiscal já se encontra no status integrada');

        $recebimentoEntity = $notaFiscalEntity->getRecebimento();

        $statusEntity = $em->getReference('wms:Util\Sigla', NotaFiscalEntity::STATUS_INTEGRADA);

        $notaFiscalEntity->setRecebimento(null)
                ->setStatus($statusEntity);

        $em->persist($notaFiscalEntity);
        $em->flush();

        // trata o recebimento se houver
        if ($recebimentoEntity) {

            $recebimentoEntity = $em->getRepository('wms:Recebimento')->find($recebimentoEntity->getId());

            // observacao do cancelamento da nf
            $observacao .= '<br /> Descartada Nota Fiscal No. ' . $notaFiscalEntity->getNumero() . ' - Serie: ' . $notaFiscalEntity->getSerie() . ' - Data Emissao: ' . $notaFiscalEntity->getDataEmissao()->format('d/m/Y');
            $recebimentoEntity->addAndamento($recebimentoEntity->getStatus()->getId(), false, $observacao);

            // verifico se ainda há notas no recebimento
            if (count($recebimentoEntity->getNotasFiscais()) == 0) {

                foreach ($recebimentoEntity->getOrdensServicos() as $ordemServicoEntity) {
                    if ($ordemServicoEntity->getDataFinal())
                        continue;

                    $ordemServicoEntity->setDataFinal(new \DateTime)
                            ->setDscObservacao('Todas as notas fiscais foram desfeitas');
                    $em->persist($ordemServicoEntity);
                }

                $statusEntity = $em->getReference('wms:Util\Sigla', RecebimentoEntity::STATUS_DESFEITO);

                $recebimentoEntity->setDataFinal(new \DateTime)
                        ->setStatus($statusEntity)
                        ->addAndamento(RecebimentoEntity::STATUS_DESFEITO, false, 'Todas as notas deste Recebimento foram desfeitas.');
            }

            $em->persist($recebimentoEntity);
            $em->flush();
        }

        return true;
    }

    /**
     * Altera uma nota para cancelada no sistema. 
     * Caso necessário cancela o Recebimento a que a nota pertence.
     * 
     * @param int $idNotaFiscal
     * @param int $observacao 
     * @return boolean
     * @throws \Exception 
     */
    public function desfazer($idNotaFiscal, $observacao)
    {
        $em = $this->getEntityManager();

        // trata a nota fiscal
        $notaFiscalEntity = $this->find($idNotaFiscal);

        if ($notaFiscalEntity == null)
            throw new \Exception('Nota Fiscal não encontrada');

        if ($notaFiscalEntity->getStatus()->getId() == NotaFiscalEntity::STATUS_CANCELADA)
            throw new \Exception('Nota Fiscal se encontra cancelada');

        $recebimentoEntity = $notaFiscalEntity->getRecebimento();

        $statusEntity = $em->getReference('wms:Util\Sigla', NotaFiscalEntity::STATUS_CANCELADA);

        $notaFiscalEntity->setStatus($statusEntity);

        $em->persist($notaFiscalEntity);
        $em->flush();

        $observacao = 'Obs. Usuário: ' . $observacao;

        // trata o recebimento se houver
        if ($recebimentoEntity) {
            $recebimentoEntity = $em->getRepository('wms:Recebimento')->find($recebimentoEntity->getId());

            // observacao do cancelamento da nf
            $observacao .= '<br />Cancelada Nota Fiscal No. ' . $notaFiscalEntity->getNumero() . ' - Serie: ' . $notaFiscalEntity->getSerie() . ' - Data Emissao: ' . $notaFiscalEntity->getDataEmissao()->format('d/m/Y');

            $cancelarRecebimento = true;

            // verifico se ainda há notas no recebimento
            foreach ($recebimentoEntity->getNotasFiscais() as $notaFiscalEntity) {
                if ($notaFiscalEntity->getStatus()->getId() != NotaFiscalEntity::STATUS_CANCELADA)
                    $cancelarRecebimento = false;
            }

            if ($cancelarRecebimento) {
                foreach ($recebimentoEntity->getOrdensServicos() as $ordemServicoEntity) {
                    if ($ordemServicoEntity->getDataFinal())
                        continue;

                    $ordemServicoEntity->setDataFinal(new \DateTime)
                            ->setDscObservacao('Todas as notas fiscais foram canceladas');
                    $em->persist($ordemServicoEntity);
                }

                $statusEntity = $em->getReference('wms:Util\Sigla', RecebimentoEntity::STATUS_CANCELADO);

                $recebimentoEntity->setDataFinal(new \DateTime)
                        ->setStatus($statusEntity);

                $observacao .= '<br />Recebimento Desfeito. Todas as notas deste Recebimento foram canceladas.';
            }

            $recebimentoEntity->addAndamento($recebimentoEntity->getStatus()->getId(), false, $observacao);

            $em->persist($recebimentoEntity);
            $em->flush();
        }

        return true;
    }

    /**
     * Busca relaçao de conferencias de uma Nota fiscal
     * 
     * @param string $idFornecedor
     * @param string $numero
     * @param string $serie
     * @param string $dataEmissao Formato esperado (DD/MM/YYYY)
     * @param integer $idStatus Codigo do status da nota fiscal no wms
     * @return array Matriz de notas fiscais 
     */
    public function getConferencia($idFornecedor, $numero, $serie, $dataEmissao, $idStatus)
    {

        $dataEmissao = \DateTime::createFromFormat('d/m/Y', $dataEmissao);

        $sql = '
            SELECT NFI.COD_PRODUTO, NFI.DSC_GRADE, NFI.QTD_ITEM, NF.DAT_EMISSAO, (NFI.QTD_ITEM + NVL(RC2.QTD_DIVERGENCIA, 0)) AS QTD_CONFERIDA, RC.QTD_AVARIA, MDR.DSC_MOTIVO_DIVER_RECEB
            FROM NOTA_FISCAL NF
            INNER JOIN RECEBIMENTO R ON (R.COD_RECEBIMENTO = NF.COD_RECEBIMENTO)
            INNER JOIN ORDEM_SERVICO OS ON (OS.COD_RECEBIMENTO = R.COD_RECEBIMENTO)
            INNER JOIN NOTA_FISCAL_ITEM NFI ON (NFI.COD_NOTA_FISCAL = NF.COD_NOTA_FISCAL)
            INNER JOIN RECEBIMENTO_CONFERENCIA RC ON (RC.COD_OS = OS.COD_OS AND RC.COD_PRODUTO = NFI.COD_PRODUTO AND RC.DSC_GRADE = NFI.DSC_GRADE) 
            LEFT OUTER JOIN MOTIVO_DIVER_RECEB MDR ON (MDR.COD_MOTIVO_DIVER_RECEB = RC.COD_MOTIVO_DIVER_RECEB)
            LEFT JOIN RECEBIMENTO_CONFERENCIA RC2 ON (RC2.COD_OS = OS.COD_OS AND RC2.COD_PRODUTO = NFI.COD_PRODUTO AND RC2.DSC_GRADE = NFI.DSC_GRADE AND RC2.COD_NOTA_FISCAL = NFI.COD_NOTA_FISCAL) 
            WHERE NF.COD_FORNECEDOR = \'' . $idFornecedor . '\' 
                AND NF.NUM_NOTA_FISCAL = \'' . $numero . '\' 
                AND NF.COD_SERIE_NOTA_FISCAL = \'' . $serie . '\' 
                AND TRUNC(NF.DAT_EMISSAO) = \'' . $dataEmissao->format('Y-m-d') . '\'
                AND NF.COD_STATUS =' . $idStatus . '
                AND NOT EXISTS (SELECT \'X\' 
                                FROM RECEBIMENTO_CONFERENCIA RC2
                                WHERE RC2.COD_OS IN (SELECT OS2.COD_OS 
                                                        FROM ORDEM_SERVICO OS2
                                                    WHERE OS2.COD_RECEBIMENTO = R.COD_RECEBIMENTO)
                                AND RC2.COD_PRODUTO = RC.COD_PRODUTO 
                                AND RC2.DSC_GRADE = RC.DSC_GRADE
                                AND RC2.COD_RECEBIMENTO_CONFERENCIA > RC.COD_RECEBIMENTO_CONFERENCIA
                                )';
        return $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Busco dados da conferencia baseados no Recebimento,
     * essa busca n considera nfs canceladas
     * 
     * @param type $idRecebimento
     * @return type 
     */
    public function getConferenciaPorRecebimento($idRecebimento)
    {
        $sql = '
            SELECT NF.NUM_NOTA_FISCAL, NF.DAT_EMISSAO, NF.COD_SERIE_NOTA_FISCAL, NFI.COD_PRODUTO, P.DSC_PRODUTO, NFI.DSC_GRADE, RC.DTH_CONFERENCIA,                
                CASE WHEN RC.COD_NOTA_FISCAL = NFI.COD_NOTA_FISCAL THEN (NFI.QTD_ITEM + RC.QTD_DIVERGENCIA) ELSE NFI.QTD_ITEM END QTD_CONFERIDA,
                CASE WHEN RC.COD_NOTA_FISCAL = NFI.COD_NOTA_FISCAL THEN 0 ELSE 0 END QTD_AVARIA, -- pending
                CASE WHEN RC.COD_NOTA_FISCAL = NFI.COD_NOTA_FISCAL THEN RC.QTD_DIVERGENCIA ELSE 0 END QTD_DIVERGENCIA,
                CASE WHEN RC.COD_NOTA_FISCAL = NFI.COD_NOTA_FISCAL THEN MDR.DSC_MOTIVO_DIVER_RECEB ELSE \'\' END DSC_MOTIVO_DIVER_RECEB
            FROM RECEBIMENTO R
            INNER JOIN NOTA_FISCAL NF ON (NF.COD_RECEBIMENTO = R.COD_RECEBIMENTO)
            INNER JOIN NOTA_FISCAL_ITEM NFI ON (NFI.COD_NOTA_FISCAL = NF.COD_NOTA_FISCAL)
            INNER JOIN PRODUTO P on (P.COD_PRODUTO = NFI.COD_PRODUTO AND P.DSC_GRADE = NFI.DSC_GRADE)
            INNER JOIN ORDEM_SERVICO OS ON (OS.COD_RECEBIMENTO = R.COD_RECEBIMENTO)
            INNER JOIN RECEBIMENTO_CONFERENCIA RC ON (RC.COD_OS = OS.COD_OS)
            LEFT JOIN MOTIVO_DIVER_RECEB MDR ON (MDR.COD_MOTIVO_DIVER_RECEB = RC.COD_MOTIVO_DIVER_RECEB)
                 WHERE R.COD_RECEBIMENTO = ' . $idRecebimento . '
                   AND NF.COD_STATUS != ' . NotaFiscalEntity::STATUS_CANCELADA . '
                   AND RC.COD_PRODUTO = NFI.COD_PRODUTO
                   AND RC.DSC_GRADE = NFI.DSC_GRADE
              ORDER BY NF.NUM_NOTA_FISCAL, NFI.COD_PRODUTO, RC.DTH_CONFERENCIA DESC';

        return $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Retorna Nota fiscal que esteja nos status Integrada, Em Recebimento ou Recebida pelo WMS.
     * 
     * @param string $idFornecedor Codigo interno do fornecedor
     * @param string $numero Numero da Nota fiscal
     * @param string $serie Serie da nota
     * @param string $dataEmissao Data de emissao da nota fiscal. Formato esperado (d/m/Y) ex:'22/11/2010'
     * @return mixed NotaFiscalEntity ou null
     */
    public function getAtiva($idFornecedor, $numero, $serie, $dataEmissao)
    {
        return $this->getEntityManager()->createQueryBuilder()
                        ->select('nf')
                        ->from('wms:NotaFiscal', 'nf')
                        ->where('nf.fornecedor = ?1')
                        ->setParameter(1, $idFornecedor)
                        ->andWhere('nf.numero = :numero')
                        ->setParameter('numero', $numero)
                        ->andWhere('nf.serie = :serie')
                        ->setParameter('serie', $serie)
                        ->andWhere('TRUNC(nf.dataEmissao) = ?2')
                        ->setParameter(2, \DateTime::createFromFormat('d/m/Y H:i:s', $dataEmissao . ' 00:00:00'))
                        ->andWhere('nf.status IN (:status) ')
                        ->setParameter('status', array(NotaFiscalEntity::STATUS_INTEGRADA, NotaFiscalEntity::STATUS_EM_RECEBIMENTO, NotaFiscalEntity::STATUS_RECEBIDA))
                        ->getQuery()
                        ->getOneOrNullResult();
    }

    /**
     *
     * @param array $params Parametros da busca
     * @return array 
     */
    public function getProdutoRecebido(array $criteria = array())
    {
        extract($criteria);

        $sql = "SELECT R.COD_RECEBIMENTO, R.DTH_FINAL_RECEB, NF.NUM_NOTA_FISCAL, NF.COD_SERIE_NOTA_FISCAL, 
                       NFI.COD_PRODUTO, NFI.DSC_GRADE, NFI.QTD_ITEM, (NFI.QTD_ITEM + NVL(RC2.QTD_DIVERGENCIA, 0)) AS QTD_CONFERIDA, RC2.QTD_DIVERGENCIA,
                       PROD.DSC_PRODUTO
                  FROM NOTA_FISCAL NF
            INNER JOIN RECEBIMENTO R ON (R.COD_RECEBIMENTO = NF.COD_RECEBIMENTO)
            INNER JOIN NOTA_FISCAL_ITEM NFI ON (NFI.COD_NOTA_FISCAL = NF.COD_NOTA_FISCAL)
            INNER JOIN PRODUTO PROD on (PROD.COD_PRODUTO = NFI.COD_PRODUTO AND PROD.DSC_GRADE = NFI.DSC_GRADE)
            INNER JOIN ORDEM_SERVICO OS ON (OS.COD_RECEBIMENTO = R.COD_RECEBIMENTO)
            INNER JOIN RECEBIMENTO_CONFERENCIA RC ON (RC.COD_OS = OS.COD_OS AND RC.COD_PRODUTO = NFI.COD_PRODUTO AND RC.DSC_GRADE = NFI.DSC_GRADE)
            LEFT JOIN RECEBIMENTO_CONFERENCIA RC2 ON (RC2.COD_OS = OS.COD_OS AND RC2.COD_PRODUTO = NFI.COD_PRODUTO AND RC2.DSC_GRADE = NFI.DSC_GRADE AND RC2.COD_NOTA_FISCAL = NFI.COD_NOTA_FISCAL)
                WHERE 1 = 1
                    AND NOT EXISTS (
                        SELECT 'X'
                        FROM RECEBIMENTO_CONFERENCIA RC2
                        WHERE RC2.COD_OS IN (
                            SELECT OS2.COD_OS 
                            FROM ORDEM_SERVICO OS2
                            WHERE OS2.COD_RECEBIMENTO = R.COD_RECEBIMENTO
                        )
                    AND RC2.COD_PRODUTO = RC.COD_PRODUTO 
                    AND RC2.DSC_GRADE = RC.DSC_GRADE
                    AND RC2.COD_RECEBIMENTO_CONFERENCIA > RC.COD_RECEBIMENTO_CONFERENCIA
                   )";

        if (isset($idProduto) && !empty($idProduto))
            $sql .= ' AND nfi.cod_produto = \'' . ProdutoUtil::formatar($idProduto) . '\'';

        if (isset($grade) && !empty($grade))
            $sql .= ' AND nfi.dsc_grade = \'' . $grade . '\'';

        if (isset($descricao) && !empty($descricao))
            $sql .= ' AND prod.dsc_produto LIKE UPPER(\'%' . $descricao . '%\')';

        if (isset($dataFinal1) && !empty($dataFinal1)) {
            $dataFinal1 = \DateTime::createFromFormat('d/m/Y', $dataFinal1);
            $sql .= ' AND TRUNC(r.dth_final_receb) >= \'' . $dataFinal1->format('Y-m-d') . '\'';
        }

        if (isset($dataFinal2) && !empty($dataFinal2)) {
            $dataFinal2 = \DateTime::createFromFormat('d/m/Y', $dataFinal2);
            $sql .= ' AND TRUNC(r.dth_final_receb) <= \'' . $dataFinal2->format('Y-m-d') . '\'';
        }

        $sql .= ' AND r.cod_status = ' . RecebimentoEntity::STATUS_FINALIZADO;
        $sql .= ' ORDER BY r.cod_recebimento DESC';

        return $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Busca relação de produtos para o relatorio de produtos com ou sem dados logisticos
     * Se for informado recebimento a query parte de nota fiscal, senao ja vai direto de produto.
     * Se nao for informado nada, retorna todos os produtos.
     * 
     * @param array $params Parametros da busca
     * @return array 
     */
    public function relatorioProdutoDadosLogisticos(array $criteria = array())
    {
        extract($criteria);

        if (isset($idRecebimento) && !empty($idRecebimento)) {
            $sql = "SELECT P.COD_PRODUTO,
                           P.DSC_GRADE,
                           P.DSC_PRODUTO,
                           NVL(PV.COD_BARRAS, PE.COD_BARRAS) COD_BARRAS,
                           NVL(PV.NUM_ALTURA, PDL.NUM_ALTURA) ALTURA,
                           NVL(PV.NUM_LARGURA, PDL.NUM_LARGURA) LARGURA,
                           NVL(PV.NUM_PESO, PDL.NUM_PESO) PESO,
                           NVL(PV.NUM_PROFUNDIDADE, PDL.NUM_PROFUNDIDADE) PROFUNDIDADE,
                           NVL(PV.COD_NORMA_PALETIZACAO, PDL.COD_NORMA_PALETIZACAO) COD_NORMA,
                           NVL(U1.DSC_UNITIZADOR, U2.DSC_UNITIZADOR) UNITIZADOR,
                           NVL(PE.DSC_EMBALAGEM,PV.DSC_VOLUME) DESCRICAO,
                           NVL(NP1.NUM_CAMADAS, NP2.NUM_CAMADAS) CAMADA,
                           NVL(NP1.NUM_LASTRO, NP2.NUM_LASTRO) LASTRO
                    FROM NOTA_FISCAL NF
                    INNER JOIN NOTA_FISCAL_ITEM NFI ON NFI.COD_NOTA_FISCAL = NF.COD_NOTA_FISCAL
                    INNER JOIN PRODUTO P ON (P.COD_PRODUTO = NFI.COD_PRODUTO AND P.DSC_GRADE = NFI.DSC_GRADE)
                    LEFT JOIN PRODUTO_EMBALAGEM PE ON (P.COD_PRODUTO = PE.COD_PRODUTO AND P.DSC_GRADE = PE.DSC_GRADE)
                    LEFT JOIN PRODUTO_DADO_LOGISTICO PDL ON PE.COD_PRODUTO_EMBALAGEM = PDL.COD_PRODUTO_EMBALAGEM
                    LEFT JOIN PRODUTO_VOLUME PV ON (P.COD_PRODUTO = PV.COD_PRODUTO AND P.DSC_GRADE = PV.DSC_GRADE)
                    LEFT JOIN NORMA_PALETIZACAO NP1 ON PV.COD_NORMA_PALETIZACAO = NP1.COD_NORMA_PALETIZACAO
                    LEFT JOIN NORMA_PALETIZACAO NP2 ON PDL.COD_NORMA_PALETIZACAO = NP2.COD_NORMA_PALETIZACAO
                    LEFT JOIN UNITIZADOR U1 ON NP1.COD_UNITIZADOR = U1.COD_UNITIZADOR
                    LEFT JOIN UNITIZADOR U2 ON NP2.COD_UNITIZADOR = U2.COD_UNITIZADOR
                    WHERE NF.COD_RECEBIMENTO = " . $idRecebimento;
        } else {
            //SE NAO FOR INFORMADO RECEBIMENTO, VAI DIRETO NA TABELA PRODUTO
            $sql = "SELECT P.COD_PRODUTO,
                           P.DSC_GRADE,
                           P.DSC_PRODUTO,
                           NVL(PV.COD_BARRAS, PE.COD_BARRAS) COD_BARRAS,
                           NVL(PV.NUM_ALTURA, PDL.NUM_ALTURA) ALTURA,
                           NVL(PV.NUM_LARGURA, PDL.NUM_LARGURA) LARGURA,
                           NVL(PV.NUM_PESO, PDL.NUM_PESO) PESO,
                           NVL(PV.NUM_PROFUNDIDADE, PDL.NUM_PROFUNDIDADE) PROFUNDIDADE,
                           NVL(PV.COD_NORMA_PALETIZACAO, PDL.COD_NORMA_PALETIZACAO) COD_NORMA,
                           NVL(U1.DSC_UNITIZADOR, U2.DSC_UNITIZADOR) UNITIZADOR,
                           NVL(PE.DSC_EMBALAGEM,PV.DSC_VOLUME) DESCRICAO,
                           NVL(NP1.NUM_CAMADAS, NP2.NUM_CAMADAS) CAMADA,
                           NVL(NP1.NUM_LASTRO, NP2.NUM_LASTRO) LASTRO
                    FROM PRODUTO P
                    LEFT JOIN PRODUTO_EMBALAGEM PE ON (P.COD_PRODUTO = PE.COD_PRODUTO AND P.DSC_GRADE = PE.DSC_GRADE)
                    LEFT JOIN PRODUTO_DADO_LOGISTICO PDL ON PE.COD_PRODUTO_EMBALAGEM = PDL.COD_PRODUTO_EMBALAGEM
                    LEFT JOIN PRODUTO_VOLUME PV ON (P.COD_PRODUTO = PV.COD_PRODUTO AND P.DSC_GRADE = PV.DSC_GRADE)
                    LEFT JOIN NORMA_PALETIZACAO NP1 ON PV.COD_NORMA_PALETIZACAO = NP1.COD_NORMA_PALETIZACAO
                    LEFT JOIN NORMA_PALETIZACAO NP2 ON PDL.COD_NORMA_PALETIZACAO = NP2.COD_NORMA_PALETIZACAO
                    LEFT JOIN UNITIZADOR U1 ON NP1.COD_UNITIZADOR = U1.COD_UNITIZADOR
                    LEFT JOIN UNITIZADOR U2 ON NP2.COD_UNITIZADOR = U2.COD_UNITIZADOR
                     WHERE 1 = 1";
            }

        if (isset($classe) && !empty($classe)) {
            $sql .= ' AND P.COD_PRODUTO_CLASSE = ' . $classe;
        }
        if (isset($idLinhaSeparacao) && !empty($idLinhaSeparacao)) {
            $sql .= ' AND P.COD_LINHA_SEPARACAO = ' . $idLinhaSeparacao;
        }
        if (isset($idTipoComercializacao) && !empty($idTipoComercializacao)) {
            $sql .= ' AND P.COD_TIPO_COMERCIALIZACAO = ' . $idTipoComercializacao;
        }

        if (isset($codigoBarras) && !empty($codigoBarras) && !($codigoBarras == 'T')){
            if ($codigoBarras == 'S') {
                $sql .= ' AND ((PV.COD_BARRAS IS NOT NULL) OR (PE.COD_BARRAS IS NOT NULL))';
            } else if ($codigoBarras == 'N'){
                $sql .= ' AND ((PV.COD_BARRAS IS NULL) AND (PE.COD_BARRAS IS NULL))';
            }
        }

        if (isset($normaPaletizacao) && !empty($normaPaletizacao) && !($normaPaletizacao == 'T')){
            if ($normaPaletizacao == 'S') {
                $sql .= ' AND (NP1.NUM_NORMA > 0 OR NP2.NUM_NORMA > 0)';
            } else if ($normaPaletizacao == 'N'){
                $sql .= ' AND (((PV.COD_NORMA_PALETIZACAO IS NULL) AND (PDL.COD_NORMA_PALETIZACAO IS NULL))
                                OR (NP1.NUM_NORMA = 0 OR NP1.NUM_NORMA = 0))';
            }
        }

        if (isset($enderecoPicking) && !empty($enderecoPicking) && !($enderecoPicking == 'T')){
            if ($enderecoPicking == 'S') {
                $sql .= ' AND ((PV.COD_DEPOSITO_ENDERECO IS NOT NULL) OR (PE.COD_DEPOSITO_ENDERECO IS NOT NULL))';
            } else if ($enderecoPicking == 'N'){
                $sql .= ' AND ((PV.COD_DEPOSITO_ENDERECO IS NULL) AND (PE.COD_DEPOSITO_ENDERECO IS NULL))';
            }
        }

        if (isset($estoquePulmao) && !empty($estoquePulmao) && !($estoquePulmao == 'T')){
            $caracteristicaPicking = $this->getSystemParameterValue('ID_CARACTERISTICA_PICKING');
            if ($estoquePulmao == 'S') {
                $sql .= " AND (EXISTS (SELECT 'X'
                                         FROM ESTOQUE EX
                                    LEFT JOIN DEPOSITO_ENDERECO DEX ON DEX.COD_DEPOSITO_ENDERECO = EX.COD_DEPOSITO_ENDERECO
                                        WHERE DEX.COD_CARACTERISTICA_ENDERECO <> $caracteristicaPicking
                                          AND P.COD_PRODUTO = EX.COD_PRODUTO
                                          AND P.DSC_GRADE = EX.DSC_GRADE))";
            } else if ($estoquePulmao == 'N'){
                $sql .= " AND (NOT EXISTS (SELECT 'X'
                                         FROM ESTOQUE EX
                                    LEFT JOIN DEPOSITO_ENDERECO DEX ON DEX.COD_DEPOSITO_ENDERECO = EX.COD_DEPOSITO_ENDERECO
                                        WHERE DEX.COD_CARACTERISTICA_ENDERECO <> $caracteristicaPicking
                                          AND P.COD_PRODUTO = EX.COD_PRODUTO
                                          AND P.DSC_GRADE = EX.DSC_GRADE))";
            }
        }

        if (isset($indDadosLogisticos) && !empty($indDadosLogisticos)) {
            if ($indDadosLogisticos == 'S') {
                //produtos com dados logisticos - embalagem ou volumes
                $sql .= " AND (EXISTS (SELECT 'X'
                                       FROM PRODUTO_VOLUME PVX
                                                  WHERE PVX.COD_PRODUTO = P.COD_PRODUTO
                                                        AND PVX.DSC_GRADE = P.DSC_GRADE
                                                        AND (PVX.NUM_CUBAGEM IS NOT NULL OR PVX.NUM_PESO IS NOT NULL)
                                                        AND (PVX.NUM_CUBAGEM <> 0 OR PVX.NUM_PESO <> 0))
                                      OR EXISTS (SELECT 'X'
                                                 FROM PRODUTO_DADO_LOGISTICO PDLX
                                                 WHERE PDLX.COD_PRODUTO_EMBALAGEM = PE.COD_PRODUTO_EMBALAGEM
                                                       AND (PDLX.NUM_CUBAGEM IS NOT NULL OR PDLX.NUM_PESO IS NOT NULL)
                                                       AND (PDLX.NUM_CUBAGEM <> 0 OR PDLX.NUM_PESO <> 0)))";
            } else {
                //produtos sem dados logisticos - embalagem e volumes
                $sql .= " AND ((PDL.NUM_CUBAGEM = 0 OR PDL.NUM_PESO = 0) OR (PDL.NUM_CUBAGEM IS NULL OR PDL.NUM_PESO IS NULL))
                          AND ((PV.NUM_CUBAGEM = 0 OR PV.NUM_PESO = 0) OR (PV.NUM_CUBAGEM IS NULL OR PV.NUM_PESO IS NULL))";
            }
        }

        $sql .= ' ORDER BY P.DSC_PRODUTO, P.COD_PRODUTO, P.DSC_GRADE DESC';

        return $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Busca item da nota fiscal baseado em um recebimento e codigo de barras
     * 
     * @param int $idRecebimento
     * @param string $codigoBarras 
     */
    public function buscarItemPorCodigoBarras($idRecebimento, $codigoBarras)
    {
        // busco produto
        $dql = $this->getEntityManager()->createQueryBuilder()
                ->select('nfi.id idItem, nfi.grade, nfi.quantidade, p.id idProduto, p.descricao, 
                        tc.id idTipoComercializacao, tc.descricao tipoComercializacao,
                        pe.id idEmbalagem, pv.id idVolume,
                        NVL(pv.codigoBarras, pe.codigoBarras) codigoBarras,
                        NVL(unitizador_embalagem.id, unitizador_volume.id) idUnitizador,
                        NVL(np_embalagem.numLastro, np_volume.numLastro) numLastro,
                        NVL(np_embalagem.numCamadas, np_volume.numCamadas) numCamadas,
                        NVL(np_embalagem.numPeso, np_volume.numPeso) numPeso,
                        NVL(np_embalagem.numNorma, np_volume.numNorma) numNorma,
                        NVL(np_embalagem.id, np_volume.id) idNorma,
                        NVL(pe.descricao, \'\') descricaoEmbalagem,
                        NVL(pe.quantidade, \'0\') quantidadeEmbalagem,
                        NVL(pv.descricao, \'\') descricaoVolume,
                        NVL(pv.codigoSequencial, \'\') sequenciaVolume')
                ->from('wms:NotaFiscal', 'nf')
                ->innerJoin('nf.itens', 'nfi')
                ->innerJoin('nfi.produto', 'p', 'WITH', 'p.grade = nfi.grade')
                ->innerJoin('p.tipoComercializacao', 'tc')
                ->leftJoin('p.embalagens', 'pe', 'WITH', 'pe.grade = p.grade')
                ->leftJoin('pe.dadosLogisticos', 'dl')
                ->leftJoin('dl.normaPaletizacao', 'np_embalagem')
                ->leftJoin('np_embalagem.unitizador', 'unitizador_embalagem')
                ->leftJoin('p.volumes', 'pv', 'WITH', 'pv.grade = p.grade')
                ->leftJoin('pv.normaPaletizacao', 'np_volume')
                ->leftJoin('np_volume.unitizador', 'unitizador_volume')
                ->where('nf.recebimento = ?1')
                ->andWhere('(pe.codigoBarras = :codigoBarras OR pv.codigoBarras = :codigoBarras)')
                ->andWhere('NOT EXISTS(
                    SELECT \'x\'
                    FROM wms:OrdemServico os
                    INNER JOIN os.conferencias rc 
                    WHERE os.recebimento = nf.recebimento
                        AND rc.produto = p.id
                        AND rc.grade = p.grade
                        AND rc.qtdDivergencia = 0
                )')
                ->setParameters(
                array(
                    1 => $idRecebimento,
                    'codigoBarras' => $codigoBarras,
                )
        );

        return $dql->getQuery()->setMaxResults(1)->getOneOrNullResult();
    }

    /**
     *
     * @param int $idRecebimento 
     * @return array
     */
    public function buscarItensPorRecebimento($idRecebimento)
    {
        $dql = $this->getEntityManager()->createQueryBuilder()
                ->select('SUM(nfi.quantidade) quantidade, p.id produto, nfi.grade, p.descricao, tc.id idTipoComercializacao')
                ->from('wms:NotaFiscal', 'nf')
                ->innerJoin('nf.itens', 'nfi')
                ->innerJoin('nfi.produto', 'p', 'WITH', 'p.grade = nfi.grade')
                ->innerJoin('p.tipoComercializacao', 'tc')
                ->where('nf.recebimento = :idRecebimento')
                ->andWhere('NOT EXISTS(
                    SELECT \'x\'
                    FROM wms:OrdemServico os
                    INNER JOIN os.conferencias rc 
                    WHERE os.recebimento = nf.recebimento
                        AND rc.produto = nfi.produto
                        AND rc.grade = nfi.grade
                        AND rc.qtdDivergencia = 0
                )')
                ->setParameter('idRecebimento', $idRecebimento)
                ->groupBy('p.id, nfi.grade, p.descricao, tc.id');

        return $dql->getQuery()->getResult();
    }

    /**
     * Busca os produtos com impressão automática do código de barras
     * @param int $idRecebimento
     */
    public function buscarProdutosImprimirCodigoBarras($idRecebimento)
    {
        $dql = $this->getEntityManager()->createQueryBuilder()
                ->select('nf.numero as numNota, nf.serie, 
                          nfi.id as idNotaFiscalItem, nfi.quantidade as qtdItem,
                          pj.nomeFantasia as fornecedor,
                          p.id as idProduto, p.grade, p.descricao as dscProduto,
                          ls.descricao as dscLinhaSeparacao,
                          fb.nome as fabricante,
                          tc.descricao as dscTipoComercializacao,
                          pe.id as idEmbalagem, pe.descricao as dscEmbalagem,
                          pv.id as idVolume, pv.codigoSequencial as codSequencialVolume, pv.descricao as dscVolume,
                          NVL(pe.codigoBarras, pv.codigoBarras) codigoBarras')
                ->from('wms:NotaFiscal', 'nf')
                ->innerJoin('nf.itens', 'nfi')
                ->innerJoin('nf.fornecedor', 'f')
                ->innerJoin('f.pessoa', 'pj')
                ->leftJoin('nfi.produto', 'p', 'WITH', 'nfi.grade = p.grade')
                ->innerJoin('p.tipoComercializacao', 'tc')
                ->leftJoin('p.linhaSeparacao', 'ls')
                ->leftJoin('p.fabricante', 'fb')
                ->leftJoin('p.embalagens', 'pe', 'WITH', 'pe.grade = p.grade AND pe.isPadrao = \'S\'')
                ->leftJoin('p.volumes', 'pv', 'WITH', 'pv.grade = p.grade')
                ->where('nf.recebimento = :idRecebimento')
                ->setParameter('idRecebimento', $idRecebimento)
                ->andWhere('(pe.imprimirCB = \'S\' OR pv.imprimirCB = \'S\')');

        return $dql->getQuery()->getResult();
    }
    
    /**
     * Busca relação de itens para conferencia cega. 
     * Traz apenas os itens que devem constar na conferencia
     * 
     * @param int $idRecebimento
     * @return array Result set
     */
    public function buscarItensConferenciaCega($idRecebimento)
    {
        $sql = "SELECT NFI.COD_PRODUTO AS codigo, NFI.DSC_GRADE AS grade, SUM(NFI.QTD_ITEM) AS quantidade,
                       P.DSC_PRODUTO AS descricao,
                       U.DSC_UNITIZADOR, 
                       NP.NUM_LASTRO, NP.NUM_CAMADAS, NP.NUM_NORMA,
                       NVL(PV.COD_SEQUENCIAL_VOLUME, '') AS COD_SEQUENCIA_VOLUME,
                       NVL(PE.DSC_EMBALAGEM, '') AS DSC_EMBALAGEM,
                       NVL(EV.DSC_DEPOSITO_ENDERECO, '') AS ENDERECO_VOLUME,
                       NVL(EE.DSC_DEPOSITO_ENDERECO, '') AS ENDERECO_EMBALAGEM
                FROM NOTA_FISCAL NF
                INNER JOIN NOTA_FISCAL_ITEM NFI ON (NF.COD_NOTA_FISCAL = NFI.COD_NOTA_FISCAL) 
                INNER JOIN PRODUTO P ON (P.COD_PRODUTO = NFI.COD_PRODUTO AND P.DSC_GRADE = NFI.DSC_GRADE)
                LEFT JOIN PRODUTO_EMBALAGEM PE ON (PE.COD_PRODUTO = P.COD_PRODUTO AND PE.DSC_GRADE = P.DSC_GRADE) 
                LEFT JOIN PRODUTO_DADO_LOGISTICO PDL ON (PDL.COD_PRODUTO_EMBALAGEM = PE.COD_PRODUTO_EMBALAGEM) 
                LEFT JOIN PRODUTO_VOLUME PV ON (PV.COD_PRODUTO = P.COD_PRODUTO AND PV.DSC_GRADE = P.DSC_GRADE)
                LEFT JOIN DEPOSITO_ENDERECO EV ON (PV.COD_DEPOSITO_ENDERECO = EV.COD_DEPOSITO_ENDERECO)
                LEFT JOIN DEPOSITO_ENDERECO EE ON (PE.COD_DEPOSITO_ENDERECO = EE.COD_DEPOSITO_ENDERECO)
                LEFT JOIN NORMA_PALETIZACAO NP ON (NP.COD_NORMA_PALETIZACAO = PV.COD_NORMA_PALETIZACAO OR NP.COD_NORMA_PALETIZACAO = PDL.COD_NORMA_PALETIZACAO)
                LEFT JOIN UNITIZADOR U ON(U.COD_UNITIZADOR = NP.COD_UNITIZADOR)        
                WHERE NF.COD_RECEBIMENTO = " . (int) $idRecebimento . "
                AND NF.COD_STATUS = " . NotaFiscalEntity::STATUS_EM_RECEBIMENTO . "
                AND NOT EXISTS ( SELECT 'x' 
                                   FROM ORDEM_SERVICO OS 
                                   INNER JOIN RECEBIMENTO_CONFERENCIA RC ON (RC.COD_OS = OS.COD_OS) 
                                   WHERE OS.COD_RECEBIMENTO = NF.COD_RECEBIMENTO
                                   AND RC.COD_PRODUTO = NFI.COD_PRODUTO 
                                   AND RC.DSC_GRADE = NFI.DSC_GRADE
                                   AND RC.QTD_DIVERGENCIA = 0 )
                GROUP BY NFI.COD_PRODUTO, NFI.DSC_GRADE, P.DSC_PRODUTO, NFI.QTD_ITEM, U.DSC_UNITIZADOR, NP.NUM_LASTRO, NP.NUM_CAMADAS, NP.NUM_NORMA, NVL(PV.COD_SEQUENCIAL_VOLUME, ''),NVL(PE.DSC_EMBALAGEM, ''),NVL(EV.DSC_DEPOSITO_ENDERECO, ''), NVL(EE.DSC_DEPOSITO_ENDERECO, '')
                ORDER BY NFI.COD_PRODUTO, NFI.DSC_GRADE, U.DSC_UNITIZADOR, NVL(PV.COD_SEQUENCIAL_VOLUME, '')";
        
        $result = $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);

        return $result;
    }

    public function getQtdByProduto($idRecebimento, $codProduto, $grade) {
        $dql = $this->getEntityManager()->createQueryBuilder()
            ->select("SUM(nfi.quantidade) as qtd")
            ->from("wms:NotaFiscal","nf")
            ->leftJoin("nf.itens", "nfi")
            ->where("nf.recebimento = '$idRecebimento'")
            ->andWhere("nfi.codProduto = '$codProduto'")
            ->andWhere("nfi.grade = '$grade'")
            ->groupBy("nfi.codProduto");
        $result = $dql->getQuery()->getArrayResult();

        if ($result == NULL) {
            return 0;
        } else {
            return $result[0]['qtd'];
        }

    }

    public function buscarItensPorNovoRecebimento($idRecebimento, $idProduto)
    {
        $dql = $this->getEntityManager()->createQueryBuilder()
            ->select('p.id produto, nfi.grade, nf.id AS notaFiscal, IDENTITY(nf.recebimento) AS recebimento, p.descricao')
            ->from('wms:NotaFiscal', 'nf')
            ->innerJoin('nf.itens', 'nfi')
            ->innerJoin('nfi.produto', 'p', 'WITH', 'p.grade = nfi.grade')
            ->where("nf.recebimento = $idRecebimento")
            ->andWhere("p.id = $idProduto");
        return $dql->getQuery()->getResult();
    }

    public function buscarItensPorRecebimentoDesfeito($idRecebimento, $idProduto)
    {
        $dql = $this->getEntityManager()->createQueryBuilder()
            ->select('p.id produto, nfi.grade, nf.id AS notaFiscal, IDENTITY(nf.recebimento) AS recebimento, p.descricao')
            ->from('wms:NotaFiscal', 'nf')
            ->innerJoin('nf.itens', 'nfi')
            ->innerJoin('nfi.produto', 'p', 'WITH', 'p.grade = nfi.grade')
            ->where("nf.recebimento = $idRecebimento")
            ->andWhere("p.id = $idProduto");
        return $dql->getQuery()->getResult();
    }

    public function atualizaRecebimentoUma($recebimento)
    {
        $entity = $this->em->getReference('wms:NotaFiscal', $recebimento['notaFiscal']);
        $entity->setRecebimento($recebimento['recebimento']);

        $this->em->persist($entity);
        $this->em->flush();

        return $entity;
    }

}