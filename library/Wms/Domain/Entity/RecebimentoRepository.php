<?php

namespace Wms\Domain\Entity;

use Doctrine\ORM\EntityRepository,
    Wms\Domain\Entity\NotaFiscal as NotaFiscalEntity,
    Wms\Domain\Entity\Recebimento as RecebimentoEntity,
    Wms\Domain\Entity\Recebimento\Embalagem as RecebimentoEmbalagemEntity,
    Wms\Domain\Entity\Recebimento\Volume as RecebimentoVolumeEntity,
    Wms\Domain\Entity\Recebimento\Conferencia as ConferenciaEntity,
    Wms\Domain\Entity\OrdemServico as OrdemServicoEntity,
    Wms\Domain\Entity\Produto as ProdutoEntity,
    Wms\Domain\Entity\Atividade as AtividadeEntity;
use Wms\Domain\Entity\Enderecamento\Palete as PaleteEntity;
use Wms\Domain\Entity\Enderecamento\Palete;

/**
 * Deposito
 */
class RecebimentoRepository extends EntityRepository
{

    /**
     *
     * @param RecebimentoEntity $recebimentoEntity
     * @param array $values
     */
    public function save(RecebimentoEntity $recebimentoEntity, array $values)
    {
        extract($values);

        $em = $this->getEntityManager();

        $box = $em->getReference('wms:Deposito\Box', $idBox);
        $statusEntity = $em->getReference('wms:Util\Sigla', RecebimentoEntity::STATUS_INICIADO);

        if ($observacao != "" && $observacao != null) {
            $observacao = '<br />' . $observacao;
        }
        $recebimentoEntity->setId($id)
            ->setBox($box)
            ->setStatus($statusEntity)
            ->addAndamento(RecebimentoEntity::STATUS_INICIADO, false, 'Recebimento iniciado pelo Usuário. ' . $observacao);

        $em->persist($recebimentoEntity);

        $em->flush();
    }

    /**
     * Cancela um recebimento com todas as notas envolvidas juntas.
     *
     * @param RecebimentoEntity $recebimentoEntity
     * @param string $observacao
     */
    public function cancelar(RecebimentoEntity $recebimentoEntity, $observacao = '')
    {
        $em = $this->getEntityManager();
        $em->beginTransaction();

        try {
            $statusEntity = $em->getReference('wms:Util\Sigla', RecebimentoEntity::STATUS_CANCELADO);

            $recebimentoEntity->setDataFinal(new \DateTime)
                ->setStatus($statusEntity)
                ->addAndamento(RecebimentoEntity::STATUS_CANCELADO, false, $observacao);

            $statusEntity = $em->getReference('wms:Util\Sigla', NotaFiscalEntity::STATUS_CANCELADA);

            // notas fiscais
            foreach ($recebimentoEntity->getNotasFiscais() as $notaFiscalEntity) {
                $notaFiscalEntity->setStatus($statusEntity);
                $em->persist($notaFiscalEntity);
            }

            $em->persist($recebimentoEntity);
            $em->flush();

            $em->commit();
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
            $em->rollback();
        }
    }

    /**
     * Desfaz um recebimento, removendo a nota do mesmo e liberando ela para ser utilizada novamente.
     *
     * @param RecebimentoEntity $recebimentoEntity
     * @param string $observacao
     * @return boolean
     * @throws Exception
     */
    public function desfazer(RecebimentoEntity $recebimentoEntity, $observacao = '')
    {
        $em = $this->getEntityManager();
        $em->beginTransaction();

        if ($observacao != '')
            $observacao = 'Observação: ' . $observacao;

        $observacao .= '<br/>Cancelada Nota(s) Fiscal(is) Nº: ';

        try {
            $statusEntity = $em->getReference('wms:Util\Sigla', NotaFiscalEntity::STATUS_INTEGRADA);

            // notas fiscais
            foreach ($recebimentoEntity->getNotasFiscais() as $notaFiscalEntity) {

                if ($notaFiscalEntity->getStatus()->getId() != NotaFiscalEntity::STATUS_CANCELADA) {
                    $notaFiscalEntity->setStatus($statusEntity);

                }
                $notaFiscalEntity->setRecebimento(null);
                $em->persist($notaFiscalEntity);


                // observacao do cancelamento da nf
                $observacao .= ' - ' . $notaFiscalEntity->getNumero();
            }

            // ordens de servico
            foreach ($recebimentoEntity->getOrdensServicos() as $ordemServicoEntity) {
                if ($ordemServicoEntity->getDataFinal())
                    continue;

                $ordemServicoEntity->setDataFinal(new \DateTime)
                    ->setDscObservacao('Todas as notas fiscais foram canceladas');

                $em->persist($ordemServicoEntity);
            }

            // finaliza recebimento
            $statusEntity = $em->getReference('wms:Util\Sigla', RecebimentoEntity::STATUS_DESFEITO);

            $recebimentoEntity->setDataFinal(new \DateTime)
                ->setStatus($statusEntity)
                ->addAndamento(RecebimentoEntity::STATUS_DESFEITO, false, $observacao);

            $em->persist($recebimentoEntity);
            $em->flush();

            $em->commit();
        } catch (\Exception $e) {
            $em->rollback();
            throw $e;
        }

        return true;
    }

    /**
     *
     * @param array $notasFiscais
     */
    public function gerar(array $notasFiscais)
    {
        $em = $this->getEntityManager();
        $em->beginTransaction();

        try {
            $sessao = new \Zend_Session_Namespace('deposito');
            $deposito = $em->getReference('wms:Deposito', $sessao->idDepositoLogado);

            $recebEntity = new RecebimentoEntity;

            $statusEntity = $em->getReference('wms:Util\Sigla', RecebimentoEntity::STATUS_CRIADO);

            $recebEntity->setStatus($statusEntity)
                ->setDeposito($deposito)
                ->setDataInicial(new \DateTime)
                ->setFilial($deposito->getFilial())
                ->addAndamento(RecebimentoEntity::STATUS_CRIADO, false, 'Recebimento gerado pelo WMS.');

            $em->persist($recebEntity);

            $statusEntity = $em->getReference('wms:Util\Sigla', NotaFiscalEntity::STATUS_EM_RECEBIMENTO);

            //itera nas notas fiscais enviadas
            foreach ($notasFiscais as $nota) {

                $notaFiscal = $em->getReference('wms:NotaFiscal', $nota);
                $notaFiscal->setRecebimento($recebEntity)
                    ->setStatus($statusEntity);

                $em->persist($notaFiscal);
            }
            $em->flush();

            $em->commit();

            return $recebEntity->getId();
        } catch (\Exception $e) {
            $em->rollback();
            throw $e;
        }
    }

    public function conferenciaColetor($idRecebimento, $idOrdemServico)
    {
        /** @var \Wms\Domain\Entity\NotaFiscalRepository $notaFiscalRepo */
        $notaFiscalRepo = $this->_em->getRepository('wms:NotaFiscal');
        $produtoVolumeRepo = $this->_em->getRepository('wms:Produto\Volume');

        // buscar todos os itens das nfs do recebimento
        $itens = $notaFiscalRepo->buscarItensPorRecebimento($idRecebimento);


        foreach ($itens as $item) {
            // checando qtdes nf
            $qtdNFs[$item['produto']][$item['grade']] = $item['quantidade'];

            // checando qtdes avarias
            $qtdAvarias[$item['produto']][$item['grade']] = 0;

            // checando qtdes conferidas
            switch ($item['idTipoComercializacao']) {
                case ProdutoEntity::TIPO_COMPOSTO:

                    $volumes = $produtoVolumeRepo->findBy(array('codProduto' => $item['produto'], 'grade' => $item['grade']));

                    foreach ($volumes as $volume) {
                        //verifica se o volume foi conferido.
                        $qtdConferida = $this->buscarConferenciaPorVolume($item['produto'], $item['grade'], $volume->getId(), $idOrdemServico);

                        //Caso não tenha sido conferido, grava uma conferẽncia com quantidade 0;
                        if ($qtdConferida == 0) {
                            $this->gravarConferenciaItemVolume($idRecebimento, $idOrdemServico, $volume->getId(), $qtdConferida);
                        }
                        $qtdConferidas[$item['produto']][$item['grade']][$volume->getId()] = $qtdConferida;
                    }

                    if (!isset($qtdConferidas)) {
                        return array('message' => null,
                            'exception' => new \Exception("Verifique o tipo de comercialização do produto " . $item['produto'] . ' ' . $item['grade']),
                            'concluido' => false);
                    }

                    //Pega a menor quantidade de produtos completos
                    $qtdConferidas[$item['produto']][$item['grade']] = $this->buscarVolumeMinimoConferidoPorProduto($qtdConferidas, $item['quantidade']);

                    break;
                case ProdutoEntity::TIPO_UNITARIO:

                    $qtdConferida = $this->buscarConferenciaPorEmbalagem($item['produto'], $item['grade'], $idOrdemServico);

                    $qtdConferidas[$item['produto']][$item['grade']] = $qtdConferida;

                    break;
                default:
                    break;
            }
        }

        // executa os dados da conferencia
        return $this->executarConferencia($idOrdemServico, $qtdNFs, $qtdAvarias, $qtdConferidas);

    }

    /**
     * Executa todos os calculos de uma conferencia e redireciona conforme o
     * tipo de fechamento
     *
     * @param int $idOrdemServico
     * @param array $qtdNFs = array (
     *      'COD_PRODUTO' => array (
     *         'GRADE' => '',
     *      ),
     *  )
     * @param array $qtdAvarias = array (
     *      'COD_PRODUTO' => array (
     *         'GRADE' => '',
     *      ),
     *  )
     * @param array $qtdConferidas = array (
     *      'COD_PRODUTO' => array (
     *         'GRADE' => '',
     *      ),
     *  )
     * @param int $idConferente
     */
    public function executarConferencia($idOrdemServico, $qtdNFs, $qtdAvarias, $qtdConferidas, $idConferente = false, $gravaRecebimentoVolumeEmbalagem = false, $unMedida = false, $dataValidade = null, $numPeso = null)
    {
        $ordemServicoRepo = $this->_em->getRepository('wms:OrdemServico');
        $vQtdRecebimentoRepo = $this->_em->getRepository('wms:Recebimento\VQtdRecebimento');
        $notafiscalRepo = $this->_em->getRepository('wms:NotaFiscal');
        /** @var \Wms\Domain\Entity\Recebimento\ConferenciaRepository $conferenciaRepo */
        $conferenciaRepo = $this->_em->getRepository('wms:Recebimento\Conferencia');
        /** @var \Wms\Domain\Entity\Produto\PesoRepository $pesoRepo */
        $pesoProdutoRepo = $this->_em->getRepository('wms:Produto\Peso');


        $repositorios = array(
            'notaFiscalRepo' => $notafiscalRepo,
            'vQtdRecebimentoRepo' =>$vQtdRecebimentoRepo
        );

        /** @var \Wms\Domain\Entity\ProdutoRepository $produtoRepo */
        $produtoRepo = $this->_em->getRepository('wms:Produto');

        // ordem servico
        $ordemServicoEntity = $ordemServicoRepo->find($idOrdemServico);
        // recebimento
        $idRecebimento = $ordemServicoEntity->getRecebimento()->getId();

        // checo se recebimento ja n tem uma conferencia em andamento
        if ($this->checarConferenciaComDivergencia($idRecebimento))
            return array('message' => "Este recebimento ja possui uma conferencia em andamento",
                'exception' => null,
                'concluido' => false);

        $divergencia = false;

        foreach ($qtdConferidas as $idProduto => $grades) {
            foreach ($grades as $grade => $qtdConferida) {
                $produtoEn = $produtoRepo->findOneBy(array('id'=>$idProduto, 'grade'=>$grade));

                $params['COD_PRODUTO'] = $idProduto;
                $params['DSC_GRADE'] = $grade;


                if (isset($numPeso[$idProduto][$grade]) && !empty($numPeso[$idProduto][$grade]))
                    $numPeso = (float)str_replace(',','.',$numPeso[$idProduto][$grade]);

                if (isset($unMedida) && !empty($unMedida)) {
                    $quantidade = 1;
                    $idEmbalagem = null;
                    if (isset($unMedida[$idProduto][$grade])) {
                        $produtoEmbalagemRepo = $this->_em->getRepository('wms:Produto\Embalagem');
                        $produtoEmbalagemEntity = $produtoEmbalagemRepo->find($unMedida[$idProduto][$grade]);
                        $quantidade = $produtoEmbalagemEntity->getQuantidade();
                        $idEmbalagem = $unMedida[$idProduto][$grade];
                    }

                    if (isset($dataValidade[$idProduto][$grade]) && !empty($dataValidade[$idProduto][$grade])) {
                        $dataValidade['dataValidade'] = $dataValidade[$idProduto][$grade];
                        $dataValidade['dataValidade'] = new \Zend_Date($dataValidade['dataValidade']);
                        $dataValidade['dataValidade'] = $dataValidade['dataValidade']->toString('Y-MM-dd');
                    } else {
                        $dataValidade['dataValidade'] = null;
                    }

                    $qtdNF = (float)$qtdNFs[$idProduto][$grade];
                    $qtdConferida = (float)$qtdConferida;
                    $qtdAvaria = (float)$qtdAvarias[$idProduto][$grade];

                    $qtdConferidaCalculada = $qtdConferida * $quantidade;

                    $divergenciaPesoVariavel = $this->getDivergenciaPesoVariavel($idRecebimento,$produtoEn,$repositorios);
                    $qtdDivergencia = $this->gravarConferenciaItem($idOrdemServico, $idProduto, $grade, $qtdNF, $qtdConferidaCalculada, $qtdAvaria, $divergenciaPesoVariavel);
                    if ($qtdDivergencia != 0) {
                        $divergencia = true;
                    }

                    if ($gravaRecebimentoVolumeEmbalagem == true) {
                        $this->gravarRecebimentoEmbalagemVolume($idProduto, $grade, $qtdConferida, $idRecebimento, $idOrdemServico, $idEmbalagem, $dataValidade, $numPeso);
                    }
                } else {

                    $qtdNF = (float)$qtdNFs[$idProduto][$grade];
                    $qtdConferida = (float)$qtdConferida;
                    $qtdAvaria = (float)$qtdAvarias[$idProduto][$grade];

                    if (isset($dataValidade[$idProduto][$grade]) && !empty($dataValidade[$idProduto][$grade])) {
                        $dataValidade['dataValidade'] = $dataValidade[$idProduto][$grade];
                        $dataValidade['dataValidade'] = new \Zend_Date($dataValidade['dataValidade']);
                        $dataValidade['dataValidade'] = $dataValidade['dataValidade']->toString('Y-MM-dd');
                    } else {
                        $dataValidade['dataValidade'] = null;
                    }


                    $divergenciaPesoVariavel = $this->getDivergenciaPesoVariavel($idRecebimento,$produtoEn,$repositorios);
                    $qtdDivergencia = $this->gravarConferenciaItem($idOrdemServico, $idProduto, $grade, $qtdNF, $qtdConferida, $qtdAvaria, $divergenciaPesoVariavel);
                    if ($qtdDivergencia != 0) {
                        $divergencia = true;
                    }

                    if ($gravaRecebimentoVolumeEmbalagem == true) {
                        $this->gravarRecebimentoEmbalagemVolume($idProduto, $grade, $qtdConferida, $idRecebimento, $idOrdemServico, null, $dataValidade, $numPeso);
                    }
                }
            }
        }

        if (isset($idConferente) && is_numeric($idConferente) && $idConferente != 0)
            $ordemServicoRepo->atualizarConferente($idOrdemServico, $idConferente);

        if ($divergencia) {
            // atualiza observacao da ordem de servico
            $ordemServicoRepo->atualizarObservacao($idOrdemServico, 'Conferencia com Divergencias');

            // recebimento
            $this->gravarAndamento($idRecebimento, 'Conferencia realizada mas com divergencia.');

            return array('message' => 'Quantidades atualizadas, mas com divergencia(s).',
                'exception' => null,
                'concluido' => false);
        }

        // finaliza ordem de servico
        $ordemServicoRepo->finalizar($idOrdemServico);

        //altera recebimento para o status finalizado
        $result = $this->finalizar($idRecebimento);

        if ($result['exception'] == null) {
            return array('message' => 'Recebimento Nº. ' . $idRecebimento . ' finalizado com sucesso.',
                'exception' => null,
                'concluido' => true);
        } else {
            return array('message' => null,
                'exception' => $result['exception'],
                'concluido' => false);
        }
    }

    public function getDivergenciaPesoVariavelByOs($idOS, $idRecebimento, $produtoEn, $repositorios) {

        $notaFiscalRepo = $repositorios['notaFiscalRepo'];

        $qtdDivergencia = 0;
        
        $codProduto = $produtoEn->getId();
        $grade = $produtoEn->getGrade();
        
        $pesoNf = $notaFiscalRepo->getPesoByProdutoAndRecebimento($idRecebimento, $produtoEn->getId(), $produtoEn->getGrade());
    
        $SQL = "SELECT NVL(SUM(NUM_PESO),0) as PESO
                  FROM RECEBIMENTO_EMBALAGEM RE
             LEFT JOIN PRODUTO_EMBALAGEM PE ON PE.COD_PRODUTO_EMBALAGEM = RE.COD_PRODUTO_EMBALAGEM
                 WHERE RE.COD_OS = $idOS
                   AND PE.COD_PRODUTO = '$codProduto'
                   AND PE.DSC_GRADE = '$grade'
                   AND RE.COD_RECEBIMENTO = $idRecebimento";
    
        $pesoRecebimento = $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);
        $toleranciaNominal = $produtoEn->getToleranciaNominal();
        $pesoRecebimento = $pesoRecebimento[0]['PESO'];
        if (($pesoRecebimento > ($pesoNf+$toleranciaNominal)) || ($pesoRecebimento < ($pesoNf- $toleranciaNominal))) {
            if ($pesoRecebimento > $pesoNf - $toleranciaNominal) {
                $qtdDivergencia = $pesoRecebimento - $pesoNf - $toleranciaNominal;
            } else {
                $qtdDivergencia = $pesoRecebimento - $pesoNf + $toleranciaNominal;
            }
        }

        return array(
            'pesoConferido' => $pesoRecebimento . ' Kg',
            'pesoDivergencia' => $qtdDivergencia . ' Kg',
            'pesoNf' => $pesoNf . ' Kg'
        );
    }

    public function getDivergenciaPesoVariavel ($idRecebimento, $produtoEn, $repositorios){

        $notaFiscalRepo = $repositorios['notaFiscalRepo'];
        $vQtdRecebimentoRepo = $repositorios['vQtdRecebimentoRepo'];

        if (($produtoEn->getPossuiPesoVariavel() == 'S')) {
            $pesoNota = $notaFiscalRepo->getPesoByProdutoAndRecebimento($idRecebimento, $produtoEn->getId(), $produtoEn->getGrade());
            $vRecebimentoEn = $vQtdRecebimentoRepo->findOneBy(array('codRecebimento' => $idRecebimento,
                'codProduto' => $produtoEn->getId(),
                'grade' => $produtoEn->getGrade()));

            $pesoRecebimento = 0;
            if ($vRecebimentoEn != null) {
                $pesoRecebimento = $vRecebimentoEn->getPeso();
            }
            $toleranciaNominal = $produtoEn->getToleranciaNominal();

            if (($pesoRecebimento > ($pesoNota+$toleranciaNominal)) || ($pesoRecebimento < ($pesoNota- $toleranciaNominal))) {
                return "S";
            }
        }

        return "N";

    }

    /**
     * Finaliza o recebimento, alterando status e lançando observações
     *
     * @param integer $idRecebimento
     * @throws Exception
     */
    public function finalizar($idRecebimento)
    {
        $em = $this->getEntityManager();
        $em->beginTransaction();
        ini_set('max_execution_time', 300);
        $recebimentoEntity = $this->find($idRecebimento);

        if (!$this->checarConferenciaComDivergencia($idRecebimento)) {

            try {
                $statusEntity = $em->getReference('wms:Util\Sigla', RecebimentoEntity::STATUS_FINALIZADO);

                $recebimentoEntity->setDataFinal(new \DateTime)
                    ->setStatus($statusEntity)
                    ->addAndamento(RecebimentoEntity::STATUS_FINALIZADO, false, 'Recebimento finalizado pelo WMS.');

                $statusEntity = $em->getReference('wms:Util\Sigla', NotaFiscalEntity::STATUS_RECEBIDA);

                foreach ($recebimentoEntity->getNotasFiscais() as $notaFiscalEntity) {
                    if ($notaFiscalEntity->getStatus()->getId() != NotaFiscalEntity::STATUS_EM_RECEBIMENTO)
                        continue;

                    $notaFiscalEntity->setStatus($statusEntity);
                    $em->persist($notaFiscalEntity);
                }

                $em->persist($recebimentoEntity);

                /** @var \Wms\Domain\Entity\Ressuprimento\ReservaEstoqueRepository $reservaEstoqueRepo */
                $reservaEstoqueRepo = $this->getEntityManager()->getRepository("wms:Ressuprimento\ReservaEstoque");
                $osRepo = $this->getEntityManager()->getRepository("wms:OrdemServico");

                /** @var \Wms\Domain\Entity\Enderecamento\Palete $palete */
                $paletes = $em->getRepository("wms:Enderecamento\Palete")->findBy(array('recebimento' => $recebimentoEntity->getId(), 'codStatus' => PaleteEntity::STATUS_ENDERECADO));
                /** @var \Wms\Domain\Entity\NotaFiscalRepository $notaFiscalRepo */
                $notaFiscalRepo = $em->getRepository('wms:NotaFiscal');

                foreach ($paletes as $key => $palete) {
                    /** @var \Wms\Domain\Entity\OrdemServico $osEn */
                    $osEn = $osRepo->findOneBy(array('idEnderecamento' => $palete->getId()));
                    //checando Validade
                    $getProduto = $palete->getProdutosArray();
                    $dataValidade = $notaFiscalRepo->buscaRecebimentoProduto($idRecebimento, null, $getProduto[0]['codProduto'], $getProduto[0]['grade']);

                    $reservaEstoqueRepo->efetivaReservaEstoque($palete->getDepositoEndereco()->getId(), $palete->getProdutosArray(), "E", "U", $palete->getId(), $osEn->getPessoa()->getId(), $osEn->getId(), $palete->getUnitizador()->getId(), false, $dataValidade);
                    $em->flush();
                }
                $em->flush();
                $em->commit();
                return array('exception' => null);

            } catch (\Exception $e) {
                $em->rollback();
                return array('exception' => $e);
            }
        } else {
            return array('exception' => new \Exception('Conferência com divergência. Não pode ser finalizada.'));
        }
    }

    /**
     *
     * @param RecebimentoEntity $recebimentoEntity
     * @param int $status
     */
    public function updateStatus(RecebimentoEntity $recebimentoEntity, $status)
    {
        $em = $this->getEntityManager();

        $statusEntity = $em->getReference('wms:Util\Sigla', $status);
        $recebimentoEntity->setStatus($statusEntity);

        $em->persist($recebimentoEntity);
        $em->flush();
    }

    /**
     * Busca todos os Recebimentos iniciados e sem ordem de servico vinculada
     *
     * @return type
     */
    public function buscarStatusIniciado()
    {

        $query = '
            SELECT r
            FROM wms:Recebimento r
            WHERE r.status = ' . RecebimentoEntity::STATUS_INICIADO . '
                AND NOT EXISTS (
                    SELECT \'x\'
                    FROM wms:OrdemServico os
                    WHERE os.recebimento = r.id
                        AND os.atividade = ' . AtividadeEntity::CONFERIR_PRODUTO . '
                )';

        return $this->getEntityManager()->createQuery($query)
            ->getResult();
    }

    /**
     *
     * @param array $criteria
     * @return array
     */
    public function buscarStatusEmConferenciaColetor(array $criteria = array())
    {
        $usuarioSession = \Zend_Auth::getInstance()->getIdentity();

        $query = '
            SELECT r
            FROM wms:Recebimento r
            WHERE r.status = ' . RecebimentoEntity::STATUS_CONFERENCIA_COLETOR . '
                AND EXISTS (
                    SELECT \'x\'
                    FROM wms:OrdemServico os
                    WHERE os.recebimento = r.id
                        AND os.atividade = ' . AtividadeEntity::CONFERIR_PRODUTO . '
                        AND os.pessoa = ' . $usuarioSession->getId() . '
                )';

        return $this->getEntityManager()->createQuery($query)
            ->getResult();
    }

    /**
     * Grava uma conferencia de um produto especifico a partir da ordem de servico.
     *
     * @param integer $idOrdemServico
     * @param integer $idProduto
     * @param integer $grade
     * @param integer $qtdNF Quantidade de nota fiscal do produto
     * @param integer $qtdConferida Quantidade conferida do produto
     * @param integer $qtdAvaria Quantidade avariada do produto
     * @return integer Quantidade de divergencias
     */
    public function gravarConferenciaItem($idOrdemServico, $idProduto, $grade, $qtdNF, $qtdConferida, $qtdAvaria, $divergenciaPesoVariavel)
    {
        $em = $this->getEntityManager();

        $produtoEntity = $em->getRepository('wms:Produto')->findOneBy(array('id' => $idProduto, 'grade' => $grade));

        $ordemServicoEntity = $em->find('wms:OrdemServico', $idOrdemServico);
        $recebimentoEntity = $ordemServicoEntity->getRecebimento();

        $produtoEmbalagemEntity = $em->getRepository('wms:Produto\Embalagem')->findOneBy(array('codProduto' => $idProduto, 'grade' => $grade));

        $dataValidade = null;
        if (isset($produtoEmbalagemEntity) && !empty($produtoEmbalagemEntity)) {
            $recebimentoEmbalagemEntity = $em->getRepository('wms:Recebimento\Embalagem')->findOneBy(array('recebimento' => $recebimentoEntity, 'embalagem' => $produtoEmbalagemEntity));
            if (isset($recebimentoEmbalagemEntity) && !empty($recebimentoEmbalagemEntity)) {
                $dataValidade = $recebimentoEmbalagemEntity->getDataValidade();
            }
        } else {
            /** @var \Wms\Domain\Entity\NotaFiscalRepository $notaFiscalRepo */
            $notaFiscalRepo = $this->getEntityManager()->getRepository('wms:NotaFiscal');
            $buscaDataProdutos = $notaFiscalRepo->buscaRecebimentoProduto($recebimentoEntity->getId(), null, $idProduto, $grade);

            if (count($buscaDataProdutos) > 0) {
                $dataValidade = new \DateTime($buscaDataProdutos['dataValidade']);
            }
        }

        $qtdDivergencia = (($qtdConferida + $qtdAvaria) - $qtdNF);
        if ($divergenciaPesoVariavel == 'S' || $produtoEntity->getPossuiPesoVariavel() == 'S')
            $qtdDivergencia = 0;

        $conferenciaEntity = new ConferenciaEntity;
        $conferenciaEntity->setRecebimento($recebimentoEntity);
        $conferenciaEntity->setOrdemServico($ordemServicoEntity);
        $conferenciaEntity->setDataConferencia(new \DateTime);
        $conferenciaEntity->setQtdConferida(str_replace(',','.',$qtdConferida));
        $conferenciaEntity->setProduto($produtoEntity);
        $conferenciaEntity->setGrade($grade);
        $conferenciaEntity->setQtdAvaria($qtdAvaria);
        $conferenciaEntity->setQtdDivergencia($qtdDivergencia);
        $conferenciaEntity->setDivergenciaPeso($divergenciaPesoVariavel);
        $conferenciaEntity->setDataValidade($dataValidade);

        $em->persist($conferenciaEntity);
        $em->flush();

        if ($divergenciaPesoVariavel == 'S' && $produtoEntity->getPossuiPesoVariavel() == 'S')
            $qtdDivergencia = 1;

        return $qtdDivergencia;
    }

    /**
     * Grava uma conferencia de uma embalagem produto especifico a partir da ordem de servico.
     * nesse caso a quantidade conferida é de embalagens e deve ser convertida em produtos.
     *
     * @param integer $idRecebimento
     * @param integer $idOrdemServico
     * @param integer $idProdutoEmbalagem Codigo do Produto Embalagem
     * @param integer $qtdConferida Quantidade conferida do produto
     */
    public function gravarConferenciaItemEmbalagem($idRecebimento, $idOrdemServico, $idProdutoEmbalagem, $qtdConferida, $idNormaPaletizacao = NULL, $params, $numPeso = null)
    {
        $em = $this->getEntityManager();

        $recebimentoEmbalagemEntity = new RecebimentoEmbalagemEntity;

        $recebimentoEntity = $this->find($idRecebimento);
        $ordemServicoEntity = $this->getEntityManager()->getReference('wms:OrdemServico', $idOrdemServico);
        $produtoEmbalagemEntity = $this->getEntityManager()->getReference('wms:Recebimento\Embalagem', $idProdutoEmbalagem);
        if (isset($params['dataValidade']) && !empty($params['dataValidade'])) {
            $validade = new \DateTime($params['dataValidade']);
        } else {
            $validade = null;
        }

        $recebimentoEmbalagemEntity
            ->setRecebimento($recebimentoEntity)
            ->setOrdemServico($ordemServicoEntity)
            ->setEmbalagem($produtoEmbalagemEntity)
            ->setQtdConferida($qtdConferida)
            ->setDataConferencia(new \DateTime)
            ->setDataValidade($validade);

        $recebimentoEmbalagemEntity->setNumPeso($numPeso);
        if ($idNormaPaletizacao != null) {
            $normaPaletizacaoEntity = $this->getEntityManager()->getReference('wms:Produto\NormaPaletizacao', $idNormaPaletizacao);
            $recebimentoEmbalagemEntity->setNormaPaletizacao($normaPaletizacaoEntity);
        }

        $em->persist($recebimentoEmbalagemEntity);
        $em->flush();
    }

    /**
     * Grava uma conferencia de um volume do produto especifico a partir da ordem de servico.
     * nesse caso a quantidade conferida baseada em cada volume cadastrado
     *
     * @param integer $idRecebimento
     * @param integer $idOrdemServico
     * @param integer $idProdutoVolume Codigo do Produto Volume
     * @param integer $qtdConferida Quantidade conferida do produto
     */
    public function gravarConferenciaItemVolume($idRecebimento, $idOrdemServico, $idProdutoVolume, $qtdConferida, $idNormaPaletizacao = null, $params = null, $numPeso = null)
    {
        $em = $this->getEntityManager();

        $recebimentoVolumeEntity = new RecebimentoVolumeEntity;

        $recebimentoEntity = $this->find($idRecebimento);
        $ordemServicoEntity = $this->getEntityManager()->getReference('wms:OrdemServico', $idOrdemServico);
        $produtoVolumeEntity = $this->getEntityManager()->getReference('wms:Recebimento\Volume', $idProdutoVolume);
        if (isset($params['dataValidade']) && !empty($params['dataValidade'])) {
            $validade = new \DateTime($params['dataValidade']);
        } else {
            $validade = null;
        }

        $recebimentoVolumeEntity->setRecebimento($recebimentoEntity)
            ->setOrdemServico($ordemServicoEntity)
            ->setVolume($produtoVolumeEntity)
            ->setQtdConferida($qtdConferida)
            ->setDataConferencia(new \DateTime)
            ->setDataValidade($validade);

        $recebimentoVolumeEntity->setNumPeso($numPeso);
        if ($idNormaPaletizacao != null) {
            $normaPaletizacaoEntity = $this->getEntityManager()->getReference('wms:Produto\NormaPaletizacao', $idNormaPaletizacao);
            $recebimentoVolumeEntity->setNormaPaletizacao($normaPaletizacaoEntity);
        }

        $em->persist($recebimentoVolumeEntity);
        $em->flush();
    }

    /**
     *
     * @param integer $idRecebimento
     * @param string $observacao
     */
    public function gravarAndamento($idRecebimento, $observacao)
    {
        $em = $this->getEntityManager();
        $recebimentoEntity = $this->find($idRecebimento);

        $recebimentoEntity->addAndamento(false, false, $observacao);
        $em->persist($recebimentoEntity);
        $em->flush();
    }

    /**
     * Verifica se há conferencia do Recebimento já em processo de finalização
     * e com divergencia. Utilizada para verificar se reconta ou nao produtos
     *
     * @param int $idRecebimento
     * @return boolean Caso ja esteja em
     */
    public function checarConferenciaComDivergencia($idRecebimento, $returBool = true)
    {
        $em = $this->getEntityManager();

        $dql = $em->createQueryBuilder()
            ->select('os.id')
            ->addSelect('
                    (SELECT COUNT(rc)
                    FROM wms:Recebimento\Conferencia rc
                    WHERE rc.ordemServico = os.id
                    ) qtdConferencia')
            ->from('wms:OrdemServico', 'os')
            ->where('os.recebimento = ?1')
            ->andWhere('os.dataFinal is NULL ')
            ->setParameter(1, $idRecebimento);

        $ordensServico = $dql->getQuery()->getOneOrNullResult();
        if ($returBool)
            return ($ordensServico && ((int)$ordensServico['qtdConferencia'] > 0));
        else
            return $ordensServico;
    }

    /**
     * Verifica se o recebimento em questão tem uma ordem de serviço aberta,
     * caso não, cria uma ordem automaticamente
     *
     * @param integer $idRecebimento
     * @return array Matriz com as opções:
     *   criado (boolean)   se foi criado ou nao,
     *   id (integer)       da Os,
     *   mensagem (string)  infomando o ocorrido
     */
    public function checarOrdemServicoAberta($idRecebimento)
    {
        $em = $this->getEntityManager();

        $dql = $em->createQueryBuilder()
            ->select('os')
            ->from('wms:OrdemServico', 'os')
            ->where('os.recebimento = ?1')
            ->andWhere('os.dataFinal is NULL ')
            ->setParameter(1, $idRecebimento);

        $ordensServico = $dql->getQuery()->getOneOrNullResult();

        if ($ordensServico) {

            if ($this->checarConferenciaComDivergencia($idRecebimento))
                throw new \Exception('Recebimento Nº. ' . $idRecebimento . ' já está em processo de finalização na mesa de Operação.');

            return array(
                'criado' => false,
                'id' => $ordensServico->getId(),
                'mensagem' => 'A Ordem de Serviço Nº ' . $ordensServico->getId() . ' já está aberta para este recebimento',
            );
        }


        // Se não não há ordem de serviço e o recebimento não está concluído, retorna uma nova ordem de serviço
        $chkStatusRecebimento = $this->checarStatusFinalizado($idRecebimento);
        return $chkStatusRecebimento ? $chkStatusRecebimento : $this->criarOrdemServico($idRecebimento);
    }

    /**
     * Verifica se há mais de uma OS para o recebimento, caracterizando assim uma recontagem.
     * @param int $idRecebimento
     * @return boolean
     */
    public function checarOsAnteriores($idRecebimento)
    {
        $em = $this->getEntityManager();

        $dql = $em->createQueryBuilder()
            ->select('os')
            ->from('wms:OrdemServico', 'os')
            ->where('os.recebimento = ?1')
            ->andWhere('os.dataFinal is NOT NULL ')
            ->setParameter(1, $idRecebimento);


        $ordensServico = $dql->getQuery()->getResult();

        if ($ordensServico) {

            return true;
        }
        return false;
    }

    /**
     * busca os itens do recebimento que estão em conferência
     * @param int $idRecebimento
     * @return Array
     */
    public function listarProdutosPorOS($idRecebimento)
    {

        $em = $this->getEntityManager();

        $notaFiscalRepo = $em->getRepository('wms:NotaFiscal');

        return $notaFiscalRepo->getItemConferencia($idRecebimento);
    }

    public function getProdutosConferiodos($idRecebimento)
    {
        $source = $this->getEntityManager()->createQueryBuilder()
            ->select("c.codProduto as codigo, c.grade as grade,p.descricao as produto,c.qtdConferida as qtdRecebida")
            ->from("wms:Recebimento\Conferencia", "c")
            ->innerJoin('wms:Produto', 'p', 'WITH', 'c.codProduto = p.id AND c.grade = p.grade')
            ->where("c.recebimento = $idRecebimento")
            ->andWhere("(c.qtdDivergencia = 0 OR (c.qtdDivergencia != 0 AND NOT(c.notaFiscal IS NULL)))");

        $result = $source->getQuery()->getResult(\Doctrine\ORM\Query::HYDRATE_ARRAY);

        return $result;
    }

    public function getProdutosByRecebimento($idRecebimento)
    {

        $SQL = "SELECT V.COD_PRODUTO,
                       V.DSC_GRADE,
                       P.DSC_PRODUTO,
                       CASE WHEN IND_POSSUI_PESO_VARIAVEL = 'N' THEN TO_CHAR(NVL(NOTAFISCAL.QTD,0)) ELSE NVL(NOTAFISCAL.QTD,0) || ' Kg' END as QTD_NOTA_FISCAL,
                       CASE WHEN IND_POSSUI_PESO_VARIAVEL = 'N' THEN TO_CHAR(NVL(CONFERIDO.QTD,0) - NVL(GERADO.QTD,0)) ELSE NVL(CONFERIDO.QTD,0) - NVL(GERADO.QTD,0) || ' Kg' END as qtd_Recebimento,
                       CASE WHEN IND_POSSUI_PESO_VARIAVEL = 'N' THEN TO_CHAR(NVL(RECEBIDO.QTD,0)) ELSE NVL(RECEBIDO.QTD,0) || ' Kg' END as qtd_Recebida,
                       CASE WHEN IND_POSSUI_PESO_VARIAVEL = 'N' THEN TO_CHAR(NVL(ENDERECADO.QTD,0)) ELSE NVL(ENDERECADO.QTD,0) || ' Kg' END as qtd_Enderecada, 
                       CASE WHEN IND_POSSUI_PESO_VARIAVEL = 'N' THEN TO_CHAR(NVL(ENDERECAMENTO.QTD,0)) ELSE NVL(ENDERECAMENTO.QTD,0) || ' Kg' END as qtd_Enderecamento,
                       CASE WHEN IND_POSSUI_PESO_VARIAVEL = 'N' THEN TO_CHAR((NVL(CONFERIDO.QTD,0) - NVL(GERADO.QTD,0)) + NVL(RECEBIDO.QTD,0) + NVL(ENDERECADO.QTD,0) + NVL(ENDERECAMENTO.QTD,0))
                                                                ELSE ((NVL(CONFERIDO.QTD,0) - NVL(GERADO.QTD,0)) + NVL(RECEBIDO.QTD,0) + NVL(ENDERECADO.QTD,0) + NVL(ENDERECAMENTO.QTD,0)) || ' Kg' END as qtd_Total
                  FROM (SELECT COD_PRODUTO, DSC_GRADE
                          FROM V_QTD_RECEBIMENTO
                         WHERE COD_RECEBIMENTO = $idRecebimento
                           AND COD_PRODUTO IS NOT NULL
                         UNION 
                        SELECT COD_PRODUTO, DSC_GRADE
                          FROM NOTA_FISCAL_ITEM NFI 
                         INNER JOIN NOTA_FISCAL NF ON NFI.COD_NOTA_FISCAL = NF.COD_NOTA_FISCAL
                         WHERE NF.COD_RECEBIMENTO = $idRecebimento) V
                  LEFT JOIN (SELECT CASE WHEN P.IND_POSSUI_PESO_VARIAVEL = 'S' THEN SUM(NVL(R.NUM_PESO,0))
                                         ELSE SUM(QTD) 
                                    END as QTD,
                                    R.COD_PRODUTO,
                                    R.DSC_GRADE
                               FROM V_QTD_RECEBIMENTO R
                               LEFT JOIN PRODUTO P ON P.COD_PRODUTO = R.COD_PRODUTO AND P.DSC_GRADE = R.DSC_GRADE 
                              WHERE R.COD_RECEBIMENTO = $idRecebimento
                              GROUP BY R.COD_PRODUTO, R.DSC_GRADE, P.IND_POSSUI_PESO_VARIAVEL) CONFERIDO
                    ON CONFERIDO.COD_PRODUTO = V.COD_PRODUTO
                   AND CONFERIDO.DSC_GRADE = V.DSC_GRADE
                  LEFT JOIN (SELECT SUM(QTD) as QTD,
                                    COD_PRODUTO,
                                    DSC_GRADE 
                               FROM (SELECT DISTINCT P.UMA, 
                                                     CASE WHEN PROD.IND_POSSUI_PESO_VARIAVEL = 'S' THEN P.PESO
                                                          ELSE PP.QTD 
                                                     END AS QTD, 
                                                     PP.COD_PRODUTO, 
                                                     PP.DSC_GRADE, 
                                                     P.COD_RECEBIMENTO, 
                                                     P.COD_STATUS
                                       FROM PALETE P
                                      INNER JOIN PALETE_PRODUTO PP ON PP.UMA = P.UMA
                                       LEFT JOIN PRODUTO PROD ON PROD.COD_PRODUTO = PP.COD_PRODUTO AND PROD.DSC_GRADE = PROD.DSC_GRADE
                                      WHERE P.COD_RECEBIMENTO = $idRecebimento
                                        AND P.COD_STATUS = 534)
                                      GROUP BY COD_PRODUTO, DSC_GRADE) RECEBIDO
                    ON RECEBIDO.COD_PRODUTO = V.COD_PRODUTO
                   AND RECEBIDO.DSC_GRADE = V.DSC_GRADE
                  LEFT JOIN (SELECT SUM(QTD) as QTD,
                                    COD_PRODUTO,
                                    DSC_GRADE 
                               FROM (SELECT DISTINCT P.UMA, 
                                                     CASE WHEN PROD.IND_POSSUI_PESO_VARIAVEL = 'S' THEN P.PESO
                                                          ELSE PP.QTD 
                                                     END AS QTD, 
                                                     PP.COD_PRODUTO, 
                                                     PP.DSC_GRADE, 
                                                     P.COD_RECEBIMENTO, 
                                                     P.COD_STATUS
                                       FROM PALETE P
                                      INNER JOIN PALETE_PRODUTO PP ON PP.UMA = P.UMA
                                       LEFT JOIN PRODUTO PROD ON PROD.COD_PRODUTO = PP.COD_PRODUTO AND PROD.DSC_GRADE = PROD.DSC_GRADE
                                      WHERE P.COD_RECEBIMENTO = $idRecebimento
                                        AND P.COD_STATUS = 536)
                                      GROUP BY COD_PRODUTO, DSC_GRADE) ENDERECADO
                    ON ENDERECADO.COD_PRODUTO = V.COD_PRODUTO
                   AND ENDERECADO.DSC_GRADE = V.DSC_GRADE
                  LEFT JOIN (SELECT SUM(QTD) as QTD,
                                    COD_PRODUTO,
                                    DSC_GRADE 
                               FROM (SELECT DISTINCT P.UMA, 
                                                     CASE WHEN PROD.IND_POSSUI_PESO_VARIAVEL = 'S' THEN P.PESO
                                                          ELSE PP.QTD 
                                                     END AS QTD, 
                                                     PP.COD_PRODUTO, 
                                                     PP.DSC_GRADE, 
                                                     P.COD_RECEBIMENTO, 
                                                     P.COD_STATUS
                                       FROM PALETE P
                                      INNER JOIN PALETE_PRODUTO PP ON PP.UMA = P.UMA
                                       LEFT JOIN PRODUTO PROD ON PROD.COD_PRODUTO = PP.COD_PRODUTO AND PROD.DSC_GRADE = PROD.DSC_GRADE
                                      WHERE P.COD_RECEBIMENTO = $idRecebimento
                                        AND P.COD_STATUS = 535)
                                      GROUP BY COD_PRODUTO, DSC_GRADE) ENDERECAMENTO
                    ON ENDERECAMENTO.COD_PRODUTO = V.COD_PRODUTO
                   AND ENDERECAMENTO.DSC_GRADE = V.DSC_GRADE   
                  LEFT JOIN (SELECT SUM(QTD) as QTD,
                                    COD_PRODUTO,
                                    DSC_GRADE 
                               FROM (SELECT DISTINCT P.UMA, 
                                                     CASE WHEN PROD.IND_POSSUI_PESO_VARIAVEL = 'S' THEN P.PESO
                                                          ELSE PP.QTD 
                                                     END AS QTD, 
                                                     PP.COD_PRODUTO, 
                                                     PP.DSC_GRADE, 
                                                     P.COD_RECEBIMENTO, 
                                                     P.COD_STATUS
                                       FROM PALETE P
                                      INNER JOIN PALETE_PRODUTO PP ON PP.UMA = P.UMA
                                       LEFT JOIN PRODUTO PROD ON PROD.COD_PRODUTO = PP.COD_PRODUTO AND PROD.DSC_GRADE = PROD.DSC_GRADE
                                      WHERE P.COD_RECEBIMENTO = $idRecebimento
                                        AND P.COD_STATUS <> 537)
                                      GROUP BY COD_PRODUTO, DSC_GRADE) GERADO
                    ON GERADO.COD_PRODUTO = V.COD_PRODUTO
                   AND GERADO.DSC_GRADE = V.DSC_GRADE   
                  LEFT JOIN (SELECT CASE WHEN P.IND_POSSUI_PESO_VARIAVEL = 'S' THEN SUM(NVL(NFI.NUM_PESO,0))
                                         ELSE SUM(NFI.QTD_ITEM)
                                    END as QTD,
                                    NFI.COD_PRODUTO,
                                    NFI.DSC_GRADE
                               FROM NOTA_FISCAL NF
                              INNER JOIN NOTA_FISCAL_ITEM NFI ON NFI.COD_NOTA_FISCAL = NF.COD_NOTA_FISCAL
                               LEFT JOIN PRODUTO P ON P.COD_PRODUTO = NFI.COD_PRODUTO AND P.DSC_GRADE = NFI.DSC_GRADE
                              WHERE NF.COD_RECEBIMENTO = $idRecebimento
                              GROUP BY NFI.COD_PRODUTO, NFI.DSC_GRADE,P.IND_POSSUI_PESO_VARIAVEL) NOTAFISCAL
                    ON NOTAFISCAL.COD_PRODUTO = V.COD_PRODUTO
                   AND NOTAFISCAL.DSC_GRADE = V.DSC_GRADE
                  LEFT JOIN PRODUTO P ON P.COD_PRODUTO = V.COD_PRODUTO AND P.DSC_GRADE = V.DSC_GRADE";
        $resultado = $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);

        $result = array();
        foreach ($resultado as $row){
            $result[] = array(
                'codigo'=>$row['COD_PRODUTO'],
                'produto'=>$row['DSC_PRODUTO'],
                'grade'=>$row['DSC_GRADE'],
                'qtdItensNf'=>$row['QTD_NOTA_FISCAL'],
                'qtdRecebimento'=>$row['QTD_RECEBIMENTO'],
                'qtdRecebida'=>$row['QTD_RECEBIDA'],
                'qtdEnderecamento'=>$row['QTD_ENDERECAMENTO'],
                'qtdEnderecada'=>$row['QTD_ENDERECADA'],
                'qtdTotal'=>$row['QTD_TOTAL']
            );
        }

        return $result;
    }

    /**
     * Busca recebimento com dados completos
     *
     * @param array $params
     * @return type
     */
    public function buscar(array $params = array())
    {
        extract($params);

        $source = $this->getEntityManager()->createQueryBuilder()
            ->select('r, b.descricao as dscBox, b, s.sigla as status, s.id as idStatus, p.nome as fornecedor, os.id idOrdemServicoManual,
                    os2.id idOrdemServicoColetor, NVL(os.id, os2.id) idOrdemServico, \'S\' AS indImprimirCB')
            ->addSelect("
                    (
                        SELECT COUNT(nf.id)
                        FROM wms:NotaFiscal nf
                        WHERE nf.recebimento = r.id
                    )
                    AS qtdNotaFiscal
                    ")
            ->addSelect("
                    (
                        SELECT SUM(nfi.quantidade)
                        FROM wms:NotaFiscal nf2
                        JOIN nf2.itens nfi
                        WHERE nf2.recebimento = r.id
                    )
                    AS qtdProduto
                    ")
            ->from('wms:Recebimento', 'r')
            ->innerJoin('r.status', 's')
            ->leftJoin('r.box', 'b')
            ->leftJoin('r.notasFiscais', 'nf3')
            ->leftJoin('nf3.fornecedor', 'f')
            ->leftJoin('f.pessoa', 'p')
            ->leftJoin('r.ordensServicos', 'os', 'WITH', 'os.formaConferencia = :manual AND os.dataFinal IS NULL')
            ->leftJoin('r.ordensServicos', 'os2', 'WITH', 'os2.formaConferencia = :coletor AND os2.dataFinal IS NULL')
            ->orderBy('r.id')
            ->setParameters(array(
                'manual' => OrdemServicoEntity::MANUAL,
                'coletor' => OrdemServicoEntity::COLETOR,
            ));

        if (isset($dataInicial1) && (!empty($dataInicial1)) && (!empty($dataInicial2))) {
            $dataInicial1 = str_replace("/", "-", $dataInicial1);
            $dataI1 = new \DateTime($dataInicial1);

            $dataInicial2 = str_replace("/", "-", $dataInicial2);
            $dataI2 = new \DateTime($dataInicial2);

            $source->andWhere("((TRUNC(r.dataInicial) >= ?1 AND TRUNC(r.dataInicial) <= ?2) OR r.dataInicial IS NULL)")
                ->setParameter(1, $dataI1)
                ->setParameter(2, $dataI2);
        }

        if (isset($dataFinal1) && (!empty($dataFinal1)) && (!empty($dataFinal2))) {
            $DataFinal1 = str_replace("/", "-", $dataFinal1);
            $dataF1 = new \DateTime($DataFinal1);

            $DataFinal2 = str_replace("/", "-", $dataFinal2);
            $dataF2 = new \DateTime($DataFinal2);

            $source->andWhere("((TRUNC(r.dataFinal) >= ?3 AND TRUNC(r.dataFinal) <= ?4) OR r.dataFinal IS NULL")
                ->setParameter(3, $dataF1)
                ->setParameter(4, $dataF2);
        }

        if (isset($status) && (!empty($status))) {
            $source->andWhere("r.status = ?5")
                ->setParameter(5, $status);
        }

        if (isset($idRecebimento) && (!empty($idRecebimento))) {
            $source->andWhere("r.id = ?6")
                ->setParameter(6, $idRecebimento);
        }


        if (isset($uma) && (!empty($uma))) {
            $source->andWhere("r.id = ?7")
                ->setParameter(7, $uma);
        }

        return $source->getQuery()->getResult(\Doctrine\ORM\Query::HYDRATE_ARRAY);
    }

    /**
     *
     * @param int $produto
     * @param int $grade
     * @param int $idOrdemServico
     * @param int $produtoVolume
     * @return int Quantidade de volumes conferidos
     */
    public function buscarConferenciaPorVolume($produto, $grade, $produtoVolume, $idOrdemServico)
    {
        // busca volumes
        $dql = $this->getEntityManager()->createQueryBuilder()
            ->select('sum(rv.qtdConferida) qtdConferida')
            ->from('wms:Produto\Volume', 'pv')
            ->innerJoin('pv.recebimentoVolumes', 'rv')
            ->where('pv.codProduto = :produto AND pv.grade = :grade')
            ->andWhere('rv.ordemServico = ?1')
            ->andWhere('pv.id = ?2')
            ->setParameters(array(
                    1 => $idOrdemServico,
                    2 => $produtoVolume,
                    'produto' => $produto,
                    'grade' => $grade,
                )
            );
        $qtdConferida = $dql->getQuery()->getSingleScalarResult();

        return ($qtdConferida) ? (int)$qtdConferida : 0;
    }

    public function buscarVolumeMinimoConferidoPorProduto(array $volumesConferidos, $qtdNf)
    {
        //Garantia de que vai retornar a menor quantidade conferida
        $minimo = 9999999999;
        $maximo = 0;

        foreach ($volumesConferidos as $idProduto => $grades) {
            foreach ($grades as $grade => $qtdConferidas) {
                foreach ($qtdConferidas as $volume => $qtd) {

                    if ($minimo > $qtd) {
                        $minimo = $qtd;
                    }

                    if ($maximo < $qtd) {
                        $maximo = $qtd;
                    }
                }
            }
        }

        //Verifica se o valor da divergência está maior que a quantidade informada na nf
        if ($maximo > $qtdNf && $minimo == $qtdNf)
            return $maximo;

        return $minimo;
    }

    /**
     *
     * @param int $produto
     * @param int $grade
     * @param int $idOrdemServico
     * @return int Quantidade encontrada de embalagens
     */
    public function buscarConferenciaPorEmbalagem($produto, $grade, $idOrdemServico)
    {
        // busca embalagens
        $dql = $this->getEntityManager()->createQueryBuilder()
            ->select('pe.quantidade qtdEmbalagem, re.qtdConferida')
            ->from('wms:Produto\Embalagem', 'pe')
            ->innerJoin('pe.recebimentoEmbalagens', 're')
            ->where('pe.codProduto = :produto AND pe.grade = :grade')
            ->andWhere('re.ordemServico = ?1')
            ->setParameters(
                array(
                    1 => $idOrdemServico,
                    'produto' => $produto,
                    'grade' => $grade,
                )
            );
        $embalagens = $dql->getQuery()->getResult();

        $qtdTotal = 0;

        foreach ($embalagens as $embalagem) {
            $qtdTotal += ($embalagem['qtdEmbalagem'] * $embalagem['qtdConferida']);
        }

        return $qtdTotal;
    }

    /**
     * Verifica se o status do recebimento está finalizado e retorna um array
     * contendo uma mensagem se verdadeiro
     * @author Derlandy Belchior
     * @see checarOrdemServicoAberta();
     * @since 2012-11-22
     * @param int $idRecebimento
     * @return array | null
     */
    public function checarStatusFinalizado($idRecebimento)
    {

        $em = $this->getEntityManager();

        $dql = $em->createQueryBuilder()
            ->select('os')
            ->from('wms:OrdemServico', 'os')
            ->where('os.recebimento = ?1')
            ->andWhere('os.dataFinal is not NULL ')
            ->setParameter(1, $idRecebimento);

        $ordensServico = $dql->getQuery()->getOneOrNullResult();

        if ($ordensServico) {
            return array(
                'finalizado' => true,
                'id' => $ordensServico->getId(),
                'mensagem' => 'Ordem de Serviço Nº ' . $ordensServico->getId() . ' concluída.',
            );
        }

        return null;
    }

    /**
     * Cria uma nova ordem de serviço para o recebimento passado
     * @author Derlandy Belchior
     * @see checarOrdemServicoAberta();
     * @since 2012-11-22
     * @param int $idRecebimento
     * @return array
     */
    private function criarOrdemServico($idRecebimento)
    {

        $em = $this->getEntityManager();
        $ordemServicoRepo = $em->getRepository('wms:OrdemServico');

        // cria ordem de servico
        $idOrdemServico = $ordemServicoRepo->save(new OrdemServicoEntity, array(
            'identificacao' => array(
                'idRecebimento' => $idRecebimento,
                'idAtividade' => AtividadeEntity::CONFERIR_PRODUTO,
                'formaConferencia' => OrdemServicoEntity::COLETOR,
            ),
        ));

        // altero status e andamento do recebimento
        $recebimentoEntity = $this->find($idRecebimento);
        $recebimentoEntity->addAndamento(RecebimentoEntity::STATUS_CONFERENCIA_COLETOR, false, 'Conferência iniciada pelo usuário.');
        $this->updateStatus($recebimentoEntity, RecebimentoEntity::STATUS_CONFERENCIA_COLETOR);

        return array(
            'criado' => true,
            'id' => $idOrdemServico,
            'mensagem' => 'Ordem de Serviço Nº ' . $idOrdemServico . ' criada com sucesso.',
        );
    }

    /**
     * Busca os status do recebimento e retorna um array de status que sera utilizado para vizualizacao.
     *
     * @param RecebimentoEntity $recebimentoEntity
     * @return array $recebimentoStatus
     */
    public function buscarStatusSteps(RecebimentoEntity $recebimentoEntity)
    {
        $em = $this->getEntityManager();
        $recebimentoStatus = $em->getRepository('wms:Util\Sigla')->getReferenciaValor(array('tipo' => 50), array('referencia' => 'ASC'));

        if ($recebimentoEntity->getStatus()->getId() == RecebimentoEntity::STATUS_CONFERENCIA_CEGA) {
            unset($recebimentoStatus[5]);
        } else if ($recebimentoEntity->getStatus()->getId() == RecebimentoEntity::STATUS_CONFERENCIA_COLETOR) {
            unset($recebimentoStatus[4]);
        } else {
            unset($recebimentoStatus[4]);
            $recebimentoStatus[5] = 'CONFERENCIA';
        }

        return $recebimentoStatus;
    }

    public function gravarRecebimentoEmbalagemVolume($idProduto, $grade, $qtd, $idRecebimento, $idOs, $idEmbalagem, $dataValidade = null, $numPeso = null)
    {
        $produtoEntity = $this->getEntityManager()->getRepository('wms:Produto')->findOneBy(array('id' => $idProduto, 'grade' => $grade));

        if (isset($idEmbalagem)) {

            $produtoEmbalagemRepo = $this->_em->getRepository('wms:Produto\Embalagem');
            $embalagem = $produtoEmbalagemRepo->find($idEmbalagem);

            $dadosLogisticos = $embalagem->getDadosLogisticos();
            if (count($dadosLogisticos) > 0) {
                $norma = $dadosLogisticos[0]->getNormaPaletizacao()->getId();
            } else {
                $norma = null;
            }
            $this->gravarConferenciaItemEmbalagem($idRecebimento, $idOs, $idEmbalagem, $qtd, $norma, $dataValidade, $numPeso);
        } else {
            $volumes = $produtoEntity->getVolumes();
            /** @var \Wms\Domain\Entity\Produto\Volume $volume */
            foreach ($volumes as $volume) {
                $norma = $volume->getNormaPaletizacao()->getId();
                $this->gravarConferenciaItemVolume($idRecebimento, $idOs, $volume->getId(), $qtd, $norma, $dataValidade, $numPeso);
            }
        }
    }

    public function alteraNormaPaletizacaoRecebimento($codRecebimento, $codProduto, $grade, $codOs, $idNorma)
    {

        $normaEn = $this->getEntityManager()->getRepository("wms:Produto\NormaPaletizacao")->findOneBy(array('id' => $idNorma));

        $dql = $this->getEntityManager()->createQueryBuilder()
            ->select("re")
            ->from("wms:Recebimento\Embalagem", "re")
            ->leftJoin("re.embalagem", "pe")
            ->where("re.ordemServico = '$codOs'")
            ->andWhere("pe.codProduto = '$codProduto'")
            ->andWhere("pe.grade = '$grade'")
            ->andWhere("re.recebimento = '$codRecebimento'");
        $embalagens = $dql->getQuery()->getResult();

        $dql = $this->getEntityManager()->createQueryBuilder()
            ->select("rv")
            ->from("wms:Recebimento\Volume", "rv")
            ->leftJoin("rv.volume", "pv")
            ->where("rv.ordemServico = '$codOs'")
            ->andWhere("pv.codProduto = '$codProduto'")
            ->andWhere("pv.grade = '$grade'")
            ->andWhere("rv.recebimento = '$codRecebimento'");
        $volumes = $dql->getQuery()->getResult();

        if (($embalagens == NULL) && ($volumes == NULL)) {
            $conferenciaRepo = $this->getEntityManager()->getRepository("wms:Recebimento\Conferencia");
            $conferenciaEn = $conferenciaRepo->findOneBy(array('recebimento' => $codRecebimento, 'codProduto' => $codProduto, 'grade' => $grade, 'ordemServico' => $codOs));
            $qtd = $conferenciaEn->getQtdConferida();

            $this->gravarRecebimentoEmbalagemVolume($codProduto, $grade, $qtd, $codRecebimento, $codOs);
        } else {
            /** @var \Wms\Domain\Entity\Recebimento\Embalagem $embalagem */
            foreach ($embalagens as $embalagem) {
                $embalagem->setNormaPaletizacao($normaEn);
                $this->getEntityManager()->persist($embalagem);
            }

            /** @var \Wms\Domain\Entity\Recebimento\Volume $volume */
            foreach ($volumes as $volume) {
                $volume->setNormaPaletizacao($normaEn);
                $this->getEntityManager()->persist($volume);
            }
        }

        $this->getEntityManager()->flush();
        return true;
    }


    public function getDadosRecebimento($params)
    {
        $dataInicial = $params['dataInicial'];
        $dataFim = $params['dataFim'];
        $statusFinalizado = \Wms\Domain\Entity\Recebimento::STATUS_FINALIZADO;

        $sql = "SELECT REC.COD_RECEBIMENTO as \"COD.RECEBIMENTO\",
                       TO_CHAR(REC.DTH_INICIO_RECEB,'DD/MM/YYYY HH24:MI:SS') as \"DTH.INICIO\",
                       TO_CHAR(REC.DTH_FINAL_RECEB,'DD/MM/YYYY HH24:MI:SS') as \"DTH.FINALIZACAO\",
                       SREC.DSC_SIGLA as \"STATUS RECEBIMENTO\",
                       NF.NUM_NOTA_FISCAL as \"NF\",
                       NF.COD_SERIE_NOTA_FISCAL as \"SERIE\",
                       TO_CHAR(NF.DAT_EMISSAO,'DD/MM/YYYY HH24:MI:SS') as \"DTH.EMISSAO\",
                       TO_CHAR(NF.DTH_ENTRADA,'DD/MM/YYYY HH24:MI:SS') as \"DTH.ENTRADA\",
                       NFI.COD_PRODUTO as \"COD.PRODUTO\",
                       NFI.DSC_GRADE as \"GRADE\",
                       PROD.DSC_PRODUTO as \"PRODUTO\",
                       NFI.QTD_ITEM as \"QTD.NF\",
                       OSREC.COD_OS as \"OS\",
                       OSREC.DSC_OBSERVACAO as \"OBSERVACAO OS\",
                       TO_CHAR(OSREC.DTH_INICIO_ATIVIDADE,'DD/MM/YYYY HH24:MI:SS') as \"DTH.INICIO CONFERENCIA\",
                       TO_CHAR(OSREC.DTH_FINAL_ATIVIDADE,'DD/MM/YYYY HH24:MI:SS') as \"DTH.FINAL CONFERENCIA\",
                       CONF.NOM_PESSOA as \"CONFERENTE\",
                       RC.QTD_CONFERIDA as \"QTD.CONFERIDA\",
                       RC.QTD_AVARIA as \"AVARIA\",
                       RC.QTD_DIVERGENCIA as \"DIVERGENCIA\",
                       MOT.DSC_MOTIVO_DIVER_RECEB as \"MOTIVO DIVERGENCIA\",
                       VPES.PESO as \"PESO TOTAL\",
                       VPES.CUBAGEM as \"CUBAGEM\",
                       UMA.UMA as \"UMA\",
                       PP.QTD as \"QTD NA UMA\",
                       NP.NUM_LASTRO as \"LASTRO\",
                       NP.NUM_CAMADAS as \"CAMADAS\",
                       U.DSC_UNITIZADOR as \"UNITIZADOR\",
                       SUMA.DSC_SIGLA as \"STATUS UMA\",
                       DE.DSC_DEPOSITO_ENDERECO as \"ENDERECO ARMAZENAGEM\",
                       OSUMA.COD_OS as \"OS UMA\",
                       TO_CHAR(OSUMA.DTH_INICIO_ATIVIDADE,'DD/MM/YYYY HH24:MI:SS') as \"DTH ARMAZENAGEM\",
                       OPEMP.NOM_PESSOA as \"OPERADOR EMPILHADEIRA\"
                  FROM RECEBIMENTO                   REC
                  LEFT JOIN SIGLA                    SREC  ON REC.COD_STATUS = SREC.COD_SIGLA
                  LEFT JOIN NOTA_FISCAL              NF    ON REC.COD_RECEBIMENTO = NF.COD_RECEBIMENTO
                  LEFT JOIN NOTA_FISCAL_ITEM         NFI   ON NF.COD_NOTA_FISCAL  = NFI.COD_NOTA_FISCAL
                  LEFT JOIN PRODUTO                  PROD  ON NFI.COD_PRODUTO = PROD.COD_PRODUTO AND NFI.DSC_GRADE = PROD.DSC_GRADE
                  LEFT JOIN V_QTD_RECEBIMENTO        VQTD  ON VQTD.COD_RECEBIMENTO = REC.COD_RECEBIMENTO
                                                          AND VQTD.COD_PRODUTO = NFI.COD_PRODUTO
                                                          AND VQTD.DSC_GRADE = NFI.DSC_GRADE
                  LEFT JOIN ORDEM_SERVICO            OSREC ON OSREC.COD_OS = VQTD.COD_OS
                  LEFT JOIN PESSOA                   CONF  ON OSREC.COD_PESSOA = CONF.COD_PESSOA
                  LEFT JOIN RECEBIMENTO_CONFERENCIA  RC    ON RC.COD_RECEBIMENTO = VQTD.COD_RECEBIMENTO
                                                          AND RC.COD_PRODUTO = VQTD.COD_PRODUTO
                                                          AND RC.DSC_GRADE = VQTD.DSC_GRADE
                                                          AND RC.COD_OS = VQTD.COD_OS
                  LEFT JOIN MOTIVO_DIVER_RECEB       MOT   ON RC.COD_MOTIVO_DIVER_RECEB = MOT.COD_MOTIVO_DIVER_RECEB
                  LEFT JOIN V_PESO_RECEBIMENTO       VPES  ON VPES.COD_RECEBIMENTO = REC.COD_RECEBIMENTO
                                                          AND VPES.COD_PRODUTO = PROD.COD_PRODUTO
                                                          AND VPES.DSC_GRADE = PROD.DSC_GRADE
                  LEFT JOIN PALETE                   UMA   ON REC.COD_RECEBIMENTO = UMA.COD_RECEBIMENTO
                  LEFT JOIN PALETE_PRODUTO           PP    ON UMA.UMA = PP.UMA
                                                          AND VQTD.COD_PRODUTO = PP.COD_PRODUTO
                                                          AND VQTD.DSC_GRADE = PP.DSC_GRADE
                  LEFT JOIN NORMA_PALETIZACAO        NP    ON NP.COD_NORMA_PALETIZACAO = PP.COD_NORMA_PALETIZACAO
                  LEFT JOIN UNITIZADOR               U     ON UMA.COD_UNITIZADOR = U.COD_UNITIZADOR
                  LEFT JOIN ORDEM_SERVICO            OSUMA ON UMA.UMA = OSUMA.COD_ENDERECAMENTO
                  LEFT JOIN PESSOA                   OPEMP ON OSUMA.COD_PESSOA = OPEMP.COD_PESSOA
                  LEFT JOIN SIGLA                    SUMA  ON UMA.COD_STATUS = SUMA.COD_SIGLA
                  LEFT JOIN DEPOSITO_ENDERECO           DE ON UMA.COD_DEPOSITO_ENDERECO = DE.COD_DEPOSITO_ENDERECO
                      WHERE ((REC.DTH_INICIO_RECEB >= TO_DATE('$dataInicial 00:00', 'DD-MM-YYYY HH24:MI'))
                        AND (REC.DTH_FINAL_RECEB <= TO_DATE('$dataFim 00:00', 'DD-MM-YYYY HH24:MI')))
                        AND REC.COD_STATUS = $statusFinalizado
                  ORDER BY REC.COD_RECEBIMENTO,
				           NF.NUM_NOTA_FISCAL,
                           OSREC.DTH_FINAL_ATIVIDADE,
                           OSREC.COD_OS,
                           NFI.COD_PRODUTO,
                           NFI.DSC_GRADE,
                           PP.COD_NORMA_PALETIZACAO,
                           UMA.COD_STATUS,
                           UMA.UMA";

        $resultado = $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        return $resultado;
    }

    public function getUsuarioByRecebimento($id)
    {
        $sql = "SELECT R.COD_RECEBIMENTO RECEBIMENTO, P.NOM_PESSOA NOME, (SELECT SUM(PV.NUM_PESO)
                    FROM RECEBIMENTO_VOLUME RV
                    INNER JOIN PRODUTO_VOLUME PV ON PV.COD_PRODUTO_VOLUME = RV.COD_PRODUTO_VOLUME
                    WHERE RV.COD_RECEBIMENTO = $id) PESO_TOTAL, (SELECT SUM(PV.NUM_CUBAGEM)
                    FROM RECEBIMENTO_VOLUME RV
                    INNER JOIN PRODUTO_VOLUME PV ON PV.COD_PRODUTO_VOLUME = RV.COD_PRODUTO_VOLUME
                    WHERE RV.COD_RECEBIMENTO = $id) CUBAGEM_TOTAL, (SELECT (COUNT(DISTINCT PV.COD_PRODUTO) + COUNT(DISTINCT PE.COD_PRODUTO))
                    FROM RECEBIMENTO_VOLUME RV
                    LEFT JOIN RECEBIMENTO_EMBALAGEM RE ON RE.COD_RECEBIMENTO = RV.COD_RECEBIMENTO
                    LEFT JOIN PRODUTO_VOLUME PV ON PV.COD_PRODUTO_VOLUME = RV.COD_PRODUTO_VOLUME
                    LEFT JOIN PRODUTO_EMBALAGEM PE ON PE.COD_PRODUTO_EMBALAGEM = RE.COD_PRODUTO_EMBALAGEM
                    WHERE RV.COD_RECEBIMENTO = $id) SKU, (SELECT SUM(RC.QTD_CONFERIDA)
                    FROM RECEBIMENTO_CONFERENCIA RC
                    WHERE RC.COD_RECEBIMENTO = $id) QTD_TOTAL_PRODUTOS
                    FROM RECEBIMENTO R
                    INNER JOIN RECEBIMENTO_DESCARGA RD ON RD.COD_RECEBIMENTO = R.COD_RECEBIMENTO
                    INNER JOIN USUARIO U ON U.COD_USUARIO = RD.COD_USUARIO
                    INNER JOIN PESSOA P ON P.COD_PESSOA = U.COD_USUARIO
                    WHERE R.COD_RECEBIMENTO = $id";

        return $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function insertModeloInRecebimento($params)
    {
        $idRecebimento = $params['id'];

        /** @var \Wms\Domain\Entity\Enderecamento\ModeloRepository $modeloEnderecamentoRepo */
        $modeloEnderecamentoRepo = $this->getEntityManager()->getRepository('wms:Enderecamento\Modelo');
        $modeloEnderecamentoEn = $modeloEnderecamentoRepo->findAll();

        $entity = $this->getEntityManager()->getReference('wms:Recebimento', $idRecebimento);

        if ($params['recebimento']['recebimento'] == 'S' && isset($modeloEnderecamentoEn)) {
            $entity->setModeloEnderecamento($modeloEnderecamentoEn[0]);
        } else {
            $entity->setModeloEnderecamento(null);
        }
        $this->getEntityManager()->persist($entity);
        $this->getEntityManager()->flush();
        return $entity;
    }


    public function naoEnderecadosByStatus($status = null)
    {

        $whereStatus = "";
        if ($status != null) {
            $whereStatus = " AND R.COD_STATUS = " . $status;
        }

        $SQL = "SELECT DISTINCT R.COD_RECEBIMENTO,
                                R.DTH_INICIO_RECEB,
                                B.DSC_BOX AS BOX,
                                MAX(PJ.NOM_FANTASIA) AS NOM_FANTASIA
                  FROM (SELECT SUM(QTD) QTD, COD_PRODUTO, DSC_GRADE, COD_RECEBIMENTO
                          FROM V_QTD_RECEBIMENTO V
                         GROUP BY COD_RECEBIMENTO, COD_PRODUTO, DSC_GRADE, COD_NORMA_PALETIZACAO) V
                  LEFT JOIN (SELECT SUM(QTD) QTD, COD_PRODUTO, DSC_GRADE, COD_RECEBIMENTO 
                               FROM (SELECT DISTINCT P.UMA, PP.COD_PRODUTO, PP.DSC_GRADE, PP.QTD, P.COD_RECEBIMENTO
                                       FROM PALETE P
                                       LEFT JOIN PALETE_PRODUTO PP ON PP.UMA = P.UMA
                                      WHERE (P.IND_IMPRESSO = 'S' OR P.COD_STATUS <> '".Palete::STATUS_EM_RECEBIMENTO."')) P
                              GROUP BY COD_PRODUTO, DSC_GRADE, COD_RECEBIMENTO) P
                         ON P.COD_PRODUTO = V.COD_PRODUTO
                        AND P.DSC_GRADE = V.DSC_GRADE
                        AND P.COD_RECEBIMENTO = V.COD_RECEBIMENTO
                  LEFT JOIN RECEBIMENTO R ON R.COD_RECEBIMENTO = V.COD_RECEBIMENTO
                  LEFT JOIN BOX B ON B.COD_BOX = R.COD_BOX
                  LEFT JOIN NOTA_FISCAL NF ON R.COD_RECEBIMENTO = NF.COD_RECEBIMENTO
                  INNER JOIN FORNECEDOR F ON NF.COD_FORNECEDOR = F.COD_FORNECEDOR
                  INNER JOIN PESSOA ON PESSOA.COD_PESSOA = F.COD_FORNECEDOR
                  LEFT JOIN PESSOA_JURIDICA PJ ON PJ.COD_PESSOA = PESSOA.COD_PESSOA
                 WHERE V.QTD - NVL(P.QTD,0) > 0
                  AND R.COD_STATUS <> 458
                 $whereStatus
                 GROUP BY R.COD_RECEBIMENTO,
                                R.DTH_INICIO_RECEB,
                                B.DSC_BOX
                 ORDER BY R.DTH_INICIO_RECEB DESC
";

        return $this->getEntityManager()->getConnection()->query($SQL)
            ->fetchAll(\PDO::FETCH_ASSOC);
    }


}