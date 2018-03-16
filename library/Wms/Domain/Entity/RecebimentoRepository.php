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
use Wms\Math;
use Wms\Service\Integracao;

/**
 * Deposito
 */
class RecebimentoRepository extends EntityRepository {

    /**
     *
     * @param RecebimentoEntity $recebimentoEntity
     * @param array $values
     */
    public function save(RecebimentoEntity $recebimentoEntity, array $values) {
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
    public function cancelar(RecebimentoEntity $recebimentoEntity, $observacao = '') {
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
    public function desfazer(RecebimentoEntity $recebimentoEntity, $observacao = '') {
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
    public function gerar(array $notasFiscais) {
        $em = $this->getEntityManager();
        $em->beginTransaction();

        try {

            $idRecebimentoErp = null;
            if ($this->getSystemParameterValue('UTILIZA_RECEBIMENTO_ERP') == 'S') {
                /** @var \Wms\Domain\Entity\Integracao\AcaoIntegracaoRepository $acaoIntRepo */
                $acaoIntRepo = $this->getEntityManager()->getRepository('wms:Integracao\AcaoIntegracao');


                $acaoEn = $acaoIntRepo->find(9);
                $notaFiscal = $em->getReference('wms:NotaFiscal', $notasFiscais[0]);
                $options = array(
                    0 => $notaFiscal->getFornecedor()->getIdExterno(),
                    1 => $notaFiscal->getSerie(),
                    2 => $notaFiscal->getNumero(),
                );
                $notasFiscaisErp = $acaoIntRepo->processaAcao($acaoEn, $options, "E","P",null,611);
                $serviceIntegracao = new Integracao($em, array('acao' => $acaoEn,
                    'options' => null,
                    'tipoExecucao' => 'E'));
                $serviceIntegracao->comparaNotasFiscais($notasFiscais, $notasFiscaisErp);
                $idRecebimentoErp = $notasFiscaisErp[0]['COD_RECEBIMENTO_ERP'];
            }

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
                $notaFiscal
                        ->setRecebimento($recebEntity)
                        ->setStatus($statusEntity)
                        ->setCodRecebimentoErp($idRecebimentoErp);

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

    public function conferenciaColetor($idRecebimento, $idOrdemServico) {
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
    public function executarConferencia($idOrdemServico, $qtdNFs, $qtdAvarias, $qtdConferidas, $normas = null, $qtdUnidFracionavel = null, $embalagem = null, $idConferente = false, $gravaRecebimentoVolumeEmbalagem = false, $unMedida = false, $dataValidade = null, $numPeso = null) {
        $em = $this->_em;
        $ordemServicoRepo = $em->getRepository('wms:OrdemServico');
        $vQtdRecebimentoRepo = $em->getRepository('wms:Recebimento\VQtdRecebimento');
        $notafiscalRepo = $em->getRepository('wms:NotaFiscal');

        $repositorios = array(
            'notaFiscalRepo' => $notafiscalRepo,
            'vQtdRecebimentoRepo' => $vQtdRecebimentoRepo
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
        $produtoEmbalagemRepo = $this->_em->getRepository('wms:Produto\Embalagem');

        foreach ($qtdConferidas as $idProduto => $grades) {
            foreach ($grades as $grade => $qtdConferida) {
                /** @var Produto $produtoEn */
                $produtoEn = $produtoRepo->findOneBy(array('id' => $idProduto, 'grade' => $grade));

                if (isset($numPeso[$idProduto][$grade]) && !empty($numPeso[$idProduto][$grade]))
                    $numPeso = (float) str_replace(',', '.', $numPeso[$idProduto][$grade]);

                $qtdNF = (float) $qtdNFs[$idProduto][$grade];
                $qtdConferida = (float) $qtdConferida;
                $qtdAvaria = (float) $qtdAvarias[$idProduto][$grade];

                $numPecas = null;
                if ($produtoEn->getIndFracionavel() == "S"
                    && isset($qtdUnidFracionavel[$idProduto][$grade])
                    && !empty($qtdUnidFracionavel[$idProduto][$grade])) {
                    $numPecas = (int) $qtdConferida;
                    $qtdSemMilhar = str_replace(".", "", $qtdUnidFracionavel[$idProduto][$grade]);
                    $qtdConferida = (float) str_replace(',', '.', $qtdSemMilhar);
                }

                if (isset($dataValidade[$idProduto][$grade]) && !empty($dataValidade[$idProduto][$grade])) {
                    $dataValidade['dataValidade'] = $dataValidade[$idProduto][$grade];
                    $dataValidade['dataValidade'] = new \Zend_Date($dataValidade['dataValidade']);
                    $dataValidade['dataValidade'] = $dataValidade['dataValidade']->toString('Y-MM-dd');
                } else {
                    $dataValidade['dataValidade'] = null;
                }

                $norma = null;
                if (!empty($normas)) {
                    $norma = $normas[$idProduto][$grade];
                }

                $idEmbalagem = null;
                $quantidade = 1;

                if (isset($unMedida) && !empty($unMedida)) {
                    if (isset($unMedida[$idProduto][$grade])) {
                        $idEmbalagem = $unMedida[$idProduto][$grade];
                        $produtoEmbalagemEntity = $produtoEmbalagemRepo->find($idEmbalagem);
                        $quantidade = $produtoEmbalagemEntity->getQuantidade();
                    }
//                    $qtdConferida = $qtdConferida * $quantidade;
                } elseif (isset($embalagem) && !empty($embalagem)) {
                    if (isset($embalagem[$idProduto][$grade])) {
                        $idEmbalagem = $embalagem[$idProduto][$grade];
                        $produtoEmbalagemEntity = $produtoEmbalagemRepo->find($idEmbalagem);
                        $quantidade = $produtoEmbalagemEntity->getQuantidade();
                    }
//                    $qtdConferida = $qtdConferida * $quantidade;
                }
                $qtdConferidaItem = $qtdConferida * $quantidade;
                $divergenciaPesoVariavel = $this->getDivergenciaPesoVariavel($idRecebimento, $produtoEn, $repositorios);
                $qtdDivergencia = $this->gravarConferenciaItem($idOrdemServico, $idProduto, $grade, $qtdNF, $qtdConferidaItem, $numPecas, $qtdAvaria, $divergenciaPesoVariavel);
                if ($qtdDivergencia != 0) {
                    $divergencia = true;
                }

                if ($gravaRecebimentoVolumeEmbalagem == true) {
                    $this->gravarRecebimentoEmbalagemVolume($idProduto, $grade, $qtdConferida, $numPecas, $idRecebimento, $idOrdemServico, $norma, $idEmbalagem, $dataValidade, $numPeso);
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

        $notasFiscaisEntities = $ordemServicoEntity->getRecebimento()->getNotasFiscais();
        $recebimentoErp = false;
        foreach ($notasFiscaisEntities as $notaFiscalEntity) {
            if (!is_null($notaFiscalEntity->getCodRecebimentoErp())) {
                $recebimentoErp = true;
                break;
            }
        }

        //ATUALIZA O RECEBIMENTO NO ERP CASO O PARAMETRO SEJA 'S'
        if ($this->getSystemParameterValue('UTILIZA_RECEBIMENTO_ERP') == 'S' && $recebimentoErp == true) {
            $serviceIntegracao = new Integracao($em, array('acao' => null,
                'options' => null,
                'tipoExecucao' => 'E'
            ));
            $serviceIntegracao->atualizaRecebimentoERP($idRecebimento);
        }

        //ATUALIZA O ESTOQUE DO ERP CASO O PARAMETRO SEJA 'S'
        if ($this->getSystemParameterValue('LIBERA_ESTOQUE_ERP') == 'S') {
            $serviceIntegracao = new Integracao($em, array
            (
                'acao' => null,
                'options' => null,
                'tipoExecucao' => 'E'
            ));
            $serviceIntegracao->atualizaEstoqueErp($idRecebimento, $this->getSystemParameterValue('WINTHOR_CODFILIAL_INTEGRACAO'));
        }

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
        if (($pesoRecebimento > ($pesoNf + $toleranciaNominal)) || ($pesoRecebimento < ($pesoNf - $toleranciaNominal))) {
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

    public function getDivergenciaPesoVariavel($idRecebimento, $produtoEn, $repositorios) {

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

            if (($pesoRecebimento > ($pesoNota + $toleranciaNominal)) || ($pesoRecebimento < ($pesoNota - $toleranciaNominal))) {
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
    public function finalizar($idRecebimento, $divergencia = false) {
        $em = $this->getEntityManager();
        $em->beginTransaction();
        ini_set('max_execution_time', 300);
        $recebimentoEntity = $this->find($idRecebimento);

        if (!$this->checarConferenciaComDivergencia($idRecebimento)) {

            try {
                $statusEntity = $em->getReference('wms:Util\Sigla', RecebimentoEntity::STATUS_FINALIZADO);

                $msg = 'Recebimento finalizado pelo WMS.';
                if ($divergencia) {
                    $msg = 'Recebimento finalizado e aceito com divergência.';
                }

                $recebimentoEntity->setDataFinal(new \DateTime)
                        ->setStatus($statusEntity)
                        ->addAndamento(RecebimentoEntity::STATUS_FINALIZADO, false, $msg);

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
    public function updateStatus(RecebimentoEntity $recebimentoEntity, $status) {
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
    public function buscarStatusIniciado() {

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

    public function getFornecedorbyRecebimento($idRecebimento) {
        $notaFiscalRepo = $this->getEntityManager()->getRepository('wms:NotaFiscal');
        $nf = $notaFiscalRepo->findOneBy(array('recebimento' => $idRecebimento));
        $fornecedor = $nf->getFornecedor()->getPessoa()->getNome();
        return $fornecedor;
    }

    /**
     *
     * @param array $criteria
     * @return array
     */
    public function buscarStatusEmConferenciaColetor(array $criteria = array()) {
        $usuarioSession = \Zend_Auth::getInstance()->getIdentity();

        $query = '
            SELECT r
            FROM wms:Recebimento r
            WHERE r.status IN (' . RecebimentoEntity::STATUS_CONFERENCIA_COLETOR . ',' . RecebimentoEntity::STATUS_CONFERENCIA_CEGA . ')
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
     * @param float $qtdConferida Quantidade conferida do produto
     * @param integer $numPecas
     * @param integer $qtdAvaria Quantidade avariada do produto
     * @return integer Quantidade de divergencias
     */
    public function gravarConferenciaItem($idOrdemServico, $idProduto, $grade, $qtdNF, $qtdConferida, $numPecas, $qtdAvaria, $divergenciaPesoVariavel) {
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
        $conferenciaEntity->setQtdConferida(str_replace(',', '.', $qtdConferida));
        $conferenciaEntity->setProduto($produtoEntity);
        $conferenciaEntity->setGrade($grade);
        $conferenciaEntity->setQtdAvaria($qtdAvaria);
        $conferenciaEntity->setQtdDivergencia($qtdDivergencia);
        $conferenciaEntity->setDivergenciaPeso($divergenciaPesoVariavel);
        $conferenciaEntity->setDataValidade($dataValidade);
        $conferenciaEntity->setNumPecas($numPecas);

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
    public function gravarConferenciaItemEmbalagem($idRecebimento, $idOrdemServico, $idProdutoEmbalagem, $qtdConferida, $numPecas, $idNormaPaletizacao = NULL, $params, $numPeso = null) {
        $em = $this->getEntityManager();

        $recebimentoEmbalagemEntity = new RecebimentoEmbalagemEntity;

        $recebimentoEntity = $this->find($idRecebimento);
        $ordemServicoEntity = $this->getEntityManager()->getReference('wms:OrdemServico', $idOrdemServico);
        $produtoEmbalagemEntity = $this->getEntityManager()->getReference('wms:Recebimento\Embalagem', $idProdutoEmbalagem);
        $peEntity = $this->getEntityManager()->getReference('wms:Produto\Embalagem', $idProdutoEmbalagem);
        if (isset($params['dataValidade']) && !empty($params['dataValidade'])) {
            $validade = new \DateTime($params['dataValidade']);
        } else {
            $validade = null;
        }

        $qtdEmbalagem = $peEntity->getQuantidade();

        $recebimentoEmbalagemEntity->setRecebimento($recebimentoEntity);
        $recebimentoEmbalagemEntity->setOrdemServico($ordemServicoEntity);
        $recebimentoEmbalagemEntity->setEmbalagem($produtoEmbalagemEntity);
        $recebimentoEmbalagemEntity->setQtdEmbalagem($qtdEmbalagem);
        $recebimentoEmbalagemEntity->setQtdConferida($qtdConferida);
        $recebimentoEmbalagemEntity->setDataConferencia(new \DateTime);
        $recebimentoEmbalagemEntity->setDataValidade($validade);
        $recebimentoEmbalagemEntity->setNumPecas($numPecas);

        $recebimentoEmbalagemEntity->setNumPeso($numPeso);
        if ($idNormaPaletizacao != null) {
            /** @var ProdutoEntity\NormaPaletizacao $normaPaletizacaoEntity */
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
    public function gravarConferenciaItemVolume($idRecebimento, $idOrdemServico, $idProdutoVolume, $qtdConferida, $idNormaPaletizacao = null, $params = null, $numPeso = null) {
        $em = $this->getEntityManager();

        $recebimentoVolumeEntity = new RecebimentoVolumeEntity;

        $recebimentoEntity = $this->find($idRecebimento);
        $ordemServicoEntity = $this->getEntityManager()->getReference('wms:OrdemServico', $idOrdemServico);
        $produtoVolumeEntity = $this->getEntityManager()->getReference('wms:Produto\Volume', $idProdutoVolume);
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
    public function gravarAndamento($idRecebimento, $observacao) {
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
    public function checarConferenciaComDivergencia($idRecebimento, $returBool = true) {
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
            return ($ordensServico && ((int) $ordensServico['qtdConferencia'] > 0));
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
    public function checarOrdemServicoAberta($idRecebimento) {
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
    public function checarOsAnteriores($idRecebimento) {
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
    public function listarProdutosPorOS($idRecebimento) {

        $em = $this->getEntityManager();

        $notaFiscalRepo = $em->getRepository('wms:NotaFiscal');

        return $notaFiscalRepo->getItemConferencia($idRecebimento);
    }

    public function getProdutosConferiodos($idRecebimento) {
        $source = $this->getEntityManager()->createQueryBuilder()
                ->select("c.codProduto as codigo, c.grade as grade,p.descricao as produto,c.qtdConferida as qtdRecebida")
                ->from("wms:Recebimento\Conferencia", "c")
                ->innerJoin('wms:Produto', 'p', 'WITH', 'c.codProduto = p.id AND c.grade = p.grade')
                ->where("c.recebimento = $idRecebimento")
                ->andWhere("(c.qtdDivergencia = 0 OR (c.qtdDivergencia != 0 AND NOT(c.notaFiscal IS NULL)))");

        $result = $source->getQuery()->getResult(\Doctrine\ORM\Query::HYDRATE_ARRAY);

        return $result;
    }

    public function getProdutosByRecebimento($idRecebimento) {

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
                  LEFT JOIN (SELECT SUM(QTD) / COUNT(DISTINCT COD_NORMA_PALETIZACAO) as QTD,
                                    COD_PRODUTO,
                                    DSC_GRADE 
                               FROM (SELECT DISTINCT P.UMA, 
                                                     CASE WHEN PROD.IND_POSSUI_PESO_VARIAVEL = 'S' THEN P.PESO
                                                          ELSE PP.QTD 
                                                     END AS QTD, 
                                                     PP.COD_PRODUTO, 
                                                     PP.DSC_GRADE, 
                                                     P.COD_RECEBIMENTO, 
                                                     P.COD_STATUS,
                                                     PP.COD_NORMA_PALETIZACAO
                                       FROM PALETE P
                                      INNER JOIN PALETE_PRODUTO PP ON PP.UMA = P.UMA
                                       LEFT JOIN PRODUTO PROD ON PROD.COD_PRODUTO = PP.COD_PRODUTO AND PROD.DSC_GRADE = PROD.DSC_GRADE
                                      WHERE P.COD_RECEBIMENTO = $idRecebimento
                                        AND P.COD_STATUS = 534)
                                      GROUP BY COD_PRODUTO, DSC_GRADE) RECEBIDO
                    ON RECEBIDO.COD_PRODUTO = V.COD_PRODUTO
                   AND RECEBIDO.DSC_GRADE = V.DSC_GRADE
                  LEFT JOIN (SELECT SUM(QTD) / COUNT(DISTINCT COD_NORMA_PALETIZACAO) as QTD,
                                    COD_PRODUTO,
                                    DSC_GRADE 
                               FROM (SELECT DISTINCT P.UMA, 
                                                     CASE WHEN PROD.IND_POSSUI_PESO_VARIAVEL = 'S' THEN P.PESO
                                                          ELSE PP.QTD 
                                                     END AS QTD, 
                                                     PP.COD_PRODUTO, 
                                                     PP.DSC_GRADE, 
                                                     P.COD_RECEBIMENTO, 
                                                     P.COD_STATUS,
                                                     PP.COD_NORMA_PALETIZACAO
                                       FROM PALETE P
                                      INNER JOIN PALETE_PRODUTO PP ON PP.UMA = P.UMA
                                       LEFT JOIN PRODUTO PROD ON PROD.COD_PRODUTO = PP.COD_PRODUTO AND PROD.DSC_GRADE = PROD.DSC_GRADE
                                      WHERE P.COD_RECEBIMENTO = $idRecebimento
                                        AND P.COD_STATUS = 536)
                                      GROUP BY COD_PRODUTO, DSC_GRADE) ENDERECADO
                    ON ENDERECADO.COD_PRODUTO = V.COD_PRODUTO
                   AND ENDERECADO.DSC_GRADE = V.DSC_GRADE
                  LEFT JOIN (SELECT SUM(QTD) / COUNT(DISTINCT COD_NORMA_PALETIZACAO) as QTD,
                                    COD_PRODUTO,
                                    DSC_GRADE 
                               FROM (SELECT DISTINCT P.UMA, 
                                                     CASE WHEN PROD.IND_POSSUI_PESO_VARIAVEL = 'S' THEN P.PESO
                                                          ELSE PP.QTD 
                                                     END AS QTD, 
                                                     PP.COD_PRODUTO, 
                                                     PP.DSC_GRADE, 
                                                     P.COD_RECEBIMENTO, 
                                                     P.COD_STATUS,
                                                     PP.COD_NORMA_PALETIZACAO
                                       FROM PALETE P
                                      INNER JOIN PALETE_PRODUTO PP ON PP.UMA = P.UMA
                                       LEFT JOIN PRODUTO PROD ON PROD.COD_PRODUTO = PP.COD_PRODUTO AND PROD.DSC_GRADE = PROD.DSC_GRADE
                                      WHERE P.COD_RECEBIMENTO = $idRecebimento
                                        AND P.COD_STATUS = 535)
                                      GROUP BY COD_PRODUTO, DSC_GRADE) ENDERECAMENTO
                    ON ENDERECAMENTO.COD_PRODUTO = V.COD_PRODUTO
                   AND ENDERECAMENTO.DSC_GRADE = V.DSC_GRADE   
                  LEFT JOIN (SELECT SUM(QTD) / COUNT(DISTINCT COD_NORMA_PALETIZACAO) as QTD,
                                    COD_PRODUTO,
                                    DSC_GRADE 
                               FROM (SELECT DISTINCT P.UMA, 
                                                     CASE WHEN PROD.IND_POSSUI_PESO_VARIAVEL = 'S' THEN P.PESO
                                                          ELSE PP.QTD 
                                                     END AS QTD, 
                                                     PP.COD_PRODUTO, 
                                                     PP.DSC_GRADE, 
                                                     P.COD_RECEBIMENTO, 
                                                     P.COD_STATUS,
                                                     PP.COD_NORMA_PALETIZACAO
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
        $produtoRepo = $this->getEntityManager()->getRepository('wms:Produto');

        $result = array();
        foreach ($resultado as $row) {
            $produtoEn = $produtoRepo->findOneBy(array('id' => $row['COD_PRODUTO'], 'grade' => $row['DSC_GRADE']));
            $picking = $produtoRepo->getEnderecoPicking($produtoEn);
            if (!empty($picking)) {
                $picking = reset($picking);
            } else {
                $picking = null;
            }
            $result[] = array(
                'id' => $row['COD_PRODUTO'],
                'codigo' => $row['COD_PRODUTO'],
                'id' => $row['COD_PRODUTO'],
                'produto' => $row['DSC_PRODUTO'],
                'grade' => $row['DSC_GRADE'],
                'picking' => $picking,
                'qtdItensNf' => $row['QTD_NOTA_FISCAL'],
                'qtdRecebimento' => $row['QTD_RECEBIMENTO'],
                'qtdRecebida' => $row['QTD_RECEBIDA'],
                'qtdEnderecamento' => $row['QTD_ENDERECAMENTO'],
                'qtdEnderecada' => $row['QTD_ENDERECADA'],
                'qtdTotal' => $row['QTD_TOTAL']
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
    public function buscar(array $params = array()) {
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
    public function buscarConferenciaPorVolume($produto, $grade, $produtoVolume, $idOrdemServico) {
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

        return ($qtdConferida) ? (int) $qtdConferida : 0;
    }

    public function buscarVolumeMinimoConferidoPorProduto(array $volumesConferidos, $qtdNf) {
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
    public function buscarConferenciaPorEmbalagem($produto, $grade, $idOrdemServico) {
        // busca embalagens
        $dql = $this->getEntityManager()->createQueryBuilder()
                ->select("CASE WHEN p.indFracionavel != 'S' THEN pe.quantidade ELSE 1 END AS qtdEmbalagem, re.qtdConferida")
                ->from('wms:Produto\Embalagem', 'pe')
                ->innerJoin('pe.recebimentoEmbalagens', 're')
                ->innerJoin("pe.produto", "p")
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
        $norma = null;

        foreach ($embalagens as $embalagem) {
            $qtdTotal = Math::adicionar($qtdTotal, Math::multiplicar($embalagem['qtdEmbalagem'], $embalagem['qtdConferida']));
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
    public function checarStatusFinalizado($idRecebimento) {

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
    private function criarOrdemServico($idRecebimento) {

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
    public function buscarStatusSteps(RecebimentoEntity $recebimentoEntity) {
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

    public function gravarRecebimentoEmbalagemVolume($idProduto, $grade, $qtd, $numPecas, $idRecebimento, $idOs, $norma = null, $idEmbalagem = null, $dataValidade = null, $numPeso = null) {
        $produtoEntity = $this->getEntityManager()->getRepository('wms:Produto')->findOneBy(array('id' => $idProduto, 'grade' => $grade));

        if (!empty($idEmbalagem)) {
            if (empty($norma)) {
                $produtoEmbalagemRepo = $this->_em->getRepository('wms:Produto\Embalagem');
                $embalagem = $produtoEmbalagemRepo->find($idEmbalagem);
                $dadosLogisticos = $embalagem->getDadosLogisticos();
                if (count($dadosLogisticos) > 0) {
                    $normaEntity = $dadosLogisticos[0]->getNormaPaletizacao();
                    if (!empty($normaEntity)) {
                        $norma = $normaEntity->getId();
                    }
                }
            }
            $this->gravarConferenciaItemEmbalagem($idRecebimento, $idOs, $idEmbalagem, $qtd, $numPecas, $norma, $dataValidade, $numPeso);
        } else {
            $volumes = $produtoEntity->getVolumes();
            /** @var \Wms\Domain\Entity\Produto\Volume $volume */
            foreach ($volumes as $volume) {
                $norma = $volume->getNormaPaletizacao()->getId();
                $this->gravarConferenciaItemVolume($idRecebimento, $idOs, $volume->getId(), $qtd, $norma, $dataValidade, $numPeso);
            }
        }
    }

    public function alteraNormaPaletizacaoRecebimento($codRecebimento, $codProduto, $grade, $codOs, $idNorma) {

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
            $numPcs = $conferenciaEn->getNumPecas();

            $this->gravarRecebimentoEmbalagemVolume($codProduto, $grade, $qtd, $numPcs, $codRecebimento, $codOs);
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

    public function getDadosRecebimento($params) {
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
                        AND (REC.DTH_FINAL_RECEB <= TO_DATE('$dataFim 23:59', 'DD-MM-YYYY HH24:MI')))
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

    public function getUsuarioByRecebimento($id) {
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

    public function insertModeloInRecebimento($params) {
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

    public function checkRecebimentoEnderecado($idRecebimento)
    {
        $sql = "SELECT DISTINCT
                    R.COD_RECEBIMENTO
                FROM RECEBIMENTO R
                LEFT JOIN (SELECT V.COD_RECEBIMENTO, V.COD_PRODUTO, V.DSC_GRADE, SUM(V.QTD) as QTD
                          FROM V_QTD_RECEBIMENTO V
                          WHERE V.COD_RECEBIMENTO = $idRecebimento
                          GROUP BY V.COD_RECEBIMENTO, V.COD_PRODUTO, V.DSC_GRADE
                          UNION
                          SELECT RC.COD_RECEBIMENTO, COD_PRODUTO, DSC_GRADE, QTD_CONFERIDA as QTD
                          FROM RECEBIMENTO_CONFERENCIA RC
                          LEFT JOIN RECEBIMENTO R ON RC.COD_RECEBIMENTO = R.COD_RECEBIMENTO
                          WHERE R.COD_STATUS = 457 AND R.COD_RECEBIMENTO = $idRecebimento 
                                  AND (QTD_DIVERGENCIA = 0 OR COD_NOTA_FISCAL IS NOT NULL)
                             ) V ON V.COD_RECEBIMENTO = R.COD_RECEBIMENTO
                LEFT JOIN (SELECT COD_RECEBIMENTO, COD_PRODUTO, DSC_GRADE, SUM(QTD) as QTD
                            FROM (SELECT DISTINCT P.UMA, P.COD_RECEBIMENTO, PP.COD_PRODUTO, PP.DSC_GRADE, PP.QTD
                                  FROM PALETE P
                                  LEFT JOIN PALETE_PRODUTO PP ON P.UMA = PP.UMA
                                  WHERE P.COD_RECEBIMENTO = $idRecebimento AND P.COD_STATUS = 536)
                            GROUP BY COD_RECEBIMENTO, COD_PRODUTO, DSC_GRADE
                          ) P ON P.COD_RECEBIMENTO = V.COD_RECEBIMENTO AND P.COD_PRODUTO = V.COD_PRODUTO AND P.DSC_GRADE = V.DSC_GRADE
                WHERE (NVL(V.QTD,0) - NVL(P.QTD,0) >0) AND R.COD_STATUS NOT IN (458,460)";

        return $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function naoEnderecadosByStatus($status = null) {

        $whereStatus = "";
        if ($status != null) {
            $whereStatus = " AND R.COD_STATUS = " . $status;
        }

        $SQL = " SELECT COD_RECEBIMENTO
                   FROM RECEBIMENTO
                  WHERE COD_STATUS IN (459,461)";
        $dados = $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);

        $sqlRecebimentosConferencia = '';
        if (count($dados) > 0) {
            $ids = "";
            foreach ($dados as $idRecebimento) {
                if (end($dados) == $idRecebimento) {
                    $ids .= $idRecebimento['COD_RECEBIMENTO'];
                } else {
                    $ids .= "$idRecebimento[COD_RECEBIMENTO],";
                }
            }
            $sqlRecebimentosConferencia = "
                SELECT V.COD_RECEBIMENTO, V.COD_PRODUTO, V.DSC_GRADE, SUM(V.QTD) as QTD
                  FROM V_QTD_RECEBIMENTO V
                 WHERE V.COD_RECEBIMENTO IN ($ids)
                 GROUP BY V.COD_RECEBIMENTO, V.COD_PRODUTO, V.DSC_GRADE
                 UNION
            ";
        }

        $SQL = "
         SELECT DISTINCT
                R.COD_RECEBIMENTO,
                R.DTH_INICIO_RECEB,
                B.DSC_BOX AS BOX,
                F.FORNECEDOR as NOM_FANTASIA
           FROM RECEBIMENTO R
           LEFT JOIN ($sqlRecebimentosConferencia
                      SELECT RC.COD_RECEBIMENTO, COD_PRODUTO, DSC_GRADE, QTD_CONFERIDA as QTD
                        FROM RECEBIMENTO_CONFERENCIA RC
                        LEFT JOIN RECEBIMENTO R ON RC.COD_RECEBIMENTO = R.COD_RECEBIMENTO
                       WHERE R.COD_STATUS = 457
                         AND (QTD_DIVERGENCIA = 0 OR COD_NOTA_FISCAL IS NOT NULL)) V
                  ON V.COD_RECEBIMENTO = R.COD_RECEBIMENTO
           LEFT JOIN (SELECT COD_RECEBIMENTO, COD_PRODUTO, DSC_GRADE, SUM(QTD) as QTD
                        FROM (SELECT DISTINCT P.UMA, P.COD_RECEBIMENTO, PP.COD_PRODUTO, PP.DSC_GRADE, PP.QTD
                                FROM PALETE P
                                LEFT JOIN PALETE_PRODUTO PP ON P.UMA = PP.UMA
                               WHERE P.COD_STATUS IN (".Palete::STATUS_ENDERECADO.",".Palete::STATUS_EM_ENDERECAMENTO.",".Palete::STATUS_RECEBIDO.") OR P.IND_IMPRESSO = 'S')
                       GROUP BY COD_RECEBIMENTO, COD_PRODUTO, DSC_GRADE) P
                  ON P.COD_RECEBIMENTO = V.COD_RECEBIMENTO
                 AND P.COD_PRODUTO = V.COD_PRODUTO
                    AND P.DSC_GRADE = V.DSC_GRADE
           LEFT JOIN BOX B ON R.COD_BOX = B.COD_BOX
           LEFT JOIN (SELECT COD_RECEBIMENTO, MAX(FORNECEDOR) as FORNECEDOR
                        FROM (SELECT DISTINCT
                                     NF.COD_RECEBIMENTO,
                                     NVL(PJ.NOM_FANTASIA, PES.NOM_PESSOA) as FORNECEDOR
                                FROM NOTA_FISCAL NF
                                LEFT JOIN PESSOA_JURIDICA PJ ON PJ.COD_PESSOA = NF.COD_FORNECEDOR
                                LEFT JOIN PESSOA PES ON PES.COD_PESSOA = NF.COD_FORNECEDOR)
                       GROUP BY COD_RECEBIMENTO) F ON F.COD_RECEBIMENTO = R.COD_RECEBIMENTO
          WHERE (NVL(V.QTD,0) - NVL(P.QTD,0) >0)
            AND R.COD_STATUS NOT IN (".Recebimento::STATUS_DESFEITO.",".Recebimento::STATUS_CANCELADO.")
            $whereStatus
          ORDER BY R.DTH_INICIO_RECEB DESC
 ";

        return $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Busca recebimento com dados completos
     *
     * @param array $params
     * @return type
     */
    public function searchNew(array $params = array()) {
        extract($params);

        $where = " ";
        if (isset($dataInicial1) && (!empty($dataInicial1)) && (!empty($dataInicial2))) {
            $where .= " AND ((R.DTH_INICIO_RECEB >= TO_DATE('$dataInicial1 00:00', 'DD-MM-YYYY HH24:MI'))
                        AND (R.DTH_INICIO_RECEB <= TO_DATE('$dataInicial2 23:59', 'DD-MM-YYYY HH24:MI') OR R.DTH_INICIO_RECEB IS NULL))";
        }
        if (isset($dataFinal1) && (!empty($dataFinal1)) && (!empty($dataFinal2))) {
            $where .= " AND ((R.DTH_FINAL_RECEB >= TO_DATE('$DataFinal1 00:00', 'DD-MM-YYYY HH24:MI'))
                        AND (R.DTH_FINAL_RECEB <= TO_DATE('$DataFinal2 23:59', 'DD-MM-YYYY HH24:MI') OR R.DTH_FINAL_RECEB IS NULL))";
        }
        if (isset($status) && (!empty($status))) {
            $where .= " AND R.COD_STATUS = " . $status;
        }
        if (isset($idRecebimento) && !empty($idRecebimento)) {
            $where .= " AND R.COD_RECEBIMENTO = " . $idRecebimento;
        } elseif (isset($uma) && !empty($uma)) {
            $where .= " AND R.COD_RECEBIMENTO IN (SELECT DISTINCT COD_RECEBIMENTO FROM PALETE WHERE UMA = $idRecebimento)";
        }

        $sql = "  
                SELECT DISTINCT
                   R.COD_RECEBIMENTO AS id,
                   TO_CHAR(R.DTH_INICIO_RECEB,'DD/MM/YYYY HH24:MI:SS') AS dataInicial,
                   TO_CHAR(R.DTH_FINAL_RECEB,'DD/MM/YYYY HH24:MI:SS') AS dataFinal,
                   B.DSC_BOX AS dscBox,
                   B.COD_BOX AS idBox,
                   S.DSC_SIGLA AS status,
                   S.COD_SIGLA AS idStatus,
                   OS.COD_OS AS idOrdemServicoManual,
                   OS2.COD_OS AS idOrdemServicoColetor,
                   'S' AS indImprimirCB,
                   (
                        SELECT 
                        LISTAGG(P.NOM_PESSOA, ', ') WITHIN GROUP (ORDER BY NF4.COD_FORNECEDOR) AS fornecedor
                        FROM (SELECT DISTINCT COD_FORNECEDOR, COD_RECEBIMENTO FROM NOTA_FISCAL)  NF4
                        INNER JOIN PESSOA P ON (NF4.COD_FORNECEDOR = P.COD_PESSOA)
                        WHERE NF4.COD_RECEBIMENTO = R.COD_RECEBIMENTO
                    ) AS fornecedor,
                    (
                        SELECT 
                        COUNT(NF2.COD_NOTA_FISCAL)
                        FROM NOTA_FISCAL NF2
                        WHERE NF2.COD_RECEBIMENTO = R.COD_RECEBIMENTO
                    ) AS qtdNotaFiscal,
                   (
                     SELECT 
                       TRUNC(SUM(NFI.QTD_ITEM / MAX(PE.QTD_EMBALAGEM))) AS MAIOR
                     FROM 
                       NOTA_FISCAL NF2 INNER JOIN 
                       NOTA_FISCAL_ITEM NFI on (NF2.COD_NOTA_FISCAL = NFI.COD_NOTA_FISCAL) LEFT JOIN
                       PRODUTO_EMBALAGEM PE ON (NFI.COD_PRODUTO = PE.COD_PRODUTO)
                     WHERE 
                       NF2.COD_RECEBIMENTO = R.COD_RECEBIMENTO
                     GROUP BY
                       NFI.COD_NOTA_FISCAL,
                       NFI.QTD_ITEM,  
                       NFI.COD_PRODUTO
                   ) AS qtdMaior,
                   (
                     SELECT
                       TRUNC(SUM((NFI.QTD_ITEM - (TRUNC(NFI.QTD_ITEM / MAX(PE.QTD_EMBALAGEM)) * MAX(PE.QTD_EMBALAGEM))) / MIN(PE.QTD_EMBALAGEM))) as ESTQ_MENOR_EMBALAGEM
                     FROM 
                       NOTA_FISCAL NF2 INNER JOIN 
                       NOTA_FISCAL_ITEM NFI on (NF2.COD_NOTA_FISCAL = NFI.COD_NOTA_FISCAL) LEFT JOIN
                       PRODUTO_EMBALAGEM PE ON (NFI.COD_PRODUTO = PE.COD_PRODUTO)
                     WHERE 
                       NF2.COD_RECEBIMENTO = R.COD_RECEBIMENTO
                     GROUP BY 
                       NFI.COD_NOTA_FISCAL,
                       NFI.QTD_ITEM, 
                       NFI.COD_PRODUTO
                     ) AS qtdMenor
                 FROM 
                   NOTA_FISCAL NF
                   RIGHT JOIN RECEBIMENTO R ON (NF.COD_RECEBIMENTO = R.COD_RECEBIMENTO)
                   LEFT JOIN BOX B ON (R.COD_BOX = B.COD_BOX)
                   INNER JOIN SIGLA S ON (R.COD_STATUS = S.COD_SIGLA)
                   LEFT JOIN ORDEM_SERVICO OS ON (NF.COD_RECEBIMENTO = OS.COD_RECEBIMENTO AND OS.COD_FORMA_CONFERENCIA = 'M' AND OS.DTH_FINAL_ATIVIDADE IS NULL)
                   LEFT JOIN ORDEM_SERVICO OS2 ON (NF.COD_RECEBIMENTO = OS2.COD_RECEBIMENTO AND OS2.COD_FORMA_CONFERENCIA = 'C' AND OS2.DTH_FINAL_ATIVIDADE IS NULL)
                 WHERE 
                1 = 1 ".$where." ORDER BY R.COD_RECEBIMENTO DESC" ;
        $result = $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($result as $key1 => $vet) {
            foreach ($vet as $key => $value) {
                if ($key == 'IDORDEMSERVICOMANUAL' || $key == 'IDORDEMSERVICOCOLETOR') {
                    $result[$key1]['idOrdemServico'] = $vet['IDORDEMSERVICOCOLETOR'];
                    if ($result[$key1]['IDORDEMSERVICOMANUAL'] != null) {
                        $result[$key1]['idOrdemServico'] = $vet['IDORDEMSERVICOMANUAL'];
                    }
                }
                switch ($key) {
                    case 'ID':
                        $result[$key1]['id'] = $vet[$key] = $value;
                        break;
                    default:
                        $result[$key1][$key] = $vet[$key] = $value;
                        break;
                }
            }
        }
        return $result;
    }

    public function getProdutosRecebidosComSenha($values) {

        $andWhere = "";
        if (isset($values['dataInicial1']) && ($values['dataInicial1'] != null)) {
            $dataInicial1 = str_replace("/", "-", $values['dataInicial1']);
            $andWhere .= $andWhere . " AND R.DTH_INICIO_RECEB >= TO_DATE('" . $dataInicial1 . " 00:00', 'DD-MM-YYYY HH24:MI')";
        }
        if (isset($values['dataInicial2']) && ($values['dataInicial2'] != null)) {
            $dataInicial2 = str_replace("/", "-", $values['dataInicial2']);
            $andWhere .= $andWhere . " AND R.DTH_INICIO_RECEB <= TO_DATE('" . $dataInicial2 . " 00:00', 'DD-MM-YYYY HH24:MI')";
        }
        if (isset($values['dataFinal1']) && ($values['dataFinal1'] != null)) {
            $dataFinal1 = str_replace("/", "-", $values['dataFinal1']);
            $andWhere .= $andWhere . " AND R.DTH_FINAL_RECEB >= TO_DATE('" . $dataFinal1 . " 00:00', 'DD-MM-YYYY HH24:MI')";
        }
        if (isset($values['dataFinal2']) && ($values['dataFinal2'] != null)) {
            $dataFinal2 = str_replace("/", "-", $values['dataFinal2']);
            $andWhere .= $andWhere . " AND R.DTH_FINAL_RECEB <= TO_DATE('" . $dataFinal2 . " 00:00', 'DD-MM-YYYY HH24:MI')";
        }

        if (isset($values['idRecebimento']) && ($values['idRecebimento'] != null)) {
            $idRecebimento = $values['idRecebimento'];
            $andWhere .= $andWhere . " AND R.COD_RECEBIMENTO = '" & $idRecebimento & "'";
        }

        $sql = "SELECT V.COD_RECEBIMENTO,
                       F.COD_EXTERNO,
                       PJ.PJ.NOM_FANTASIA as FORNECEDOR,     
                       NF.NUM_NOTA_FISCAL as NF,
                       NF.COD_SERIE_NOTA_FISCAL as SERIE,
                       V.COD_PRODUTO, 
                       V.DSC_GRADE,
                       PROD.DSC_PRODUTO,
                       V.DIAS_VIDA_UTIL AS SHELFLIFE_MIN,
                       V.DIAS_VIDA_UTIL_MAX as SHELFLIFE_MAX,
                       V.COD_OS,
                       V.QTD as QTD_CONFERIDA,
                       V.DTH_VALIDADE,
                       V.SHELFLIFE,
                       TO_CHAR(V.DTH_CONFERENCIA,'DD/MM/YYYY HH24:MI:SS') as DTH_CONFERENCIA,
                       CONF.NOM_PESSOA as CONFERENTE,
                       TO_CHAR(RA.DTH_ANDAMENTO,'DD/MM/YYYY HH24:MI:SS') AS DTH_FINALIZACAO,
                       U.NOM_PESSOA as USUARIO_FINALIZACAO,
                       RA.DSC_OBSERVACAO as OBSERVACAO_RECEBIMENTO
                  FROM (SELECT SUM(RE.QTD_CONFERIDA * RE.QTD_EMBALAGEM) AS QTD,
                               RE.COD_RECEBIMENTO,
                               PE.COD_PRODUTO,
                               PE.DSC_GRADE,
                               OS.COD_OS,
                               RE.COD_NORMA_PALETIZACAO,
                               MAX(RE.DTH_VALIDADE) as DTH_VALIDADE,
                               MAX(RE.DTH_CONFERENCIA) as DTH_CONFERENCIA,
                               TRUNC(MAX(RE.DTH_VALIDADE)- MAX(RE.DTH_CONFERENCIA)) as SHELFLIFE,
                               P.DIAS_VIDA_UTIL,
                               P.DIAS_VIDA_UTIL_MAX,
                               SUM(RE.NUM_PESO) AS NUM_PESO
                          FROM RECEBIMENTO_EMBALAGEM RE
                         INNER JOIN PRODUTO_EMBALAGEM PE ON PE.COD_PRODUTO_EMBALAGEM = RE.COD_PRODUTO_EMBALAGEM
                          LEFT JOIN PRODUTO P ON P.COD_PRODUTO = PE.COD_PRODUTO AND P.DSC_GRADE = PE.DSC_GRADE
                         INNER JOIN (SELECT DISTINCT DTH_FINAL_ATIVIDADE,
                                            COD_OS,
                                            COD_RECEBIMENTO,
                                            COD_PRODUTO,
                                            DSC_GRADE,
                                            RANK() OVER(PARTITION BY COD_RECEBIMENTO, COD_PRODUTO, DSC_GRADE ORDER BY DTH_FINAL_ATIVIDADE DESC) RANK
                                        FROM (SELECT DISTINCT
                                                     NVL(OS.DTH_FINAL_ATIVIDADE, TO_DATE('31/12/9999', 'dd/mm/yyyy')) AS DTH_FINAL_ATIVIDADE,
                                                     MAX(OS.COD_OS) COD_OS,
                                                     OS.COD_RECEBIMENTO,
                                                     PE.COD_PRODUTO,
                                                     PE.DSC_GRADE
                                                FROM RECEBIMENTO_EMBALAGEM RE
                                               INNER JOIN PRODUTO_EMBALAGEM PE ON PE.COD_PRODUTO_EMBALAGEM = RE.COD_PRODUTO_EMBALAGEM
                                                LEFT JOIN ORDEM_SERVICO OS ON OS.COD_OS = RE.COD_OS
                                               GROUP BY OS.COD_RECEBIMENTO, PE.COD_PRODUTO, PE.DSC_GRADE, NVL(OS.DTH_FINAL_ATIVIDADE, TO_DATE('31/12/9999', 'dd/mm/yyyy')))) OS
                            ON OS.COD_OS = RE.COD_OS
                           AND OS.RANK <= 1
                           AND OS.COD_RECEBIMENTO = RE.COD_RECEBIMENTO
                           AND OS.COD_PRODUTO = PE.COD_PRODUTO
                           AND OS.DSC_GRADE = PE.DSC_GRADE
                         WHERE RE.DTH_VALIDADE IS NOT NULL
                           AND P.POSSUI_VALIDADE = 'S'
                         GROUP BY RE.COD_RECEBIMENTO, PE.COD_PRODUTO, PE.DSC_GRADE, OS.COD_OS,  RE.COD_NORMA_PALETIZACAO,       P.DIAS_VIDA_UTIL,
                               P.DIAS_VIDA_UTIL_MAX) V
                 INNER JOIN RECEBIMENTO_ANDAMENTO RA ON RA.COD_RECEBIMENTO = V.COD_RECEBIMENTO AND RA.COD_TIPO_ANDAMENTO = 457
                  LEFT JOIN ORDEM_SERVICO OS ON OS.COD_OS = V.COD_OS
                  LEFT JOIN PESSOA CONF ON CONF.COD_PESSOA = OS.COD_PESSOA
                  LEFT JOIN PESSOA U ON U.COD_PESSOA = RA.COD_USUARIO
                  LEFT JOIN PRODUTO PROD ON PROD.COD_PRODUTO = V.COD_PRODUTO AND PROD.DSC_GRADE = V.DSC_GRADE
                  LEFT JOIN RECEBIMENTO R ON R.COD_RECEBIMENTO = V.COD_RECEBIMENTO
                  LEFT JOIN (SELECT NF.COD_RECEBIMENTO, NFI.COD_PRODUTO, NFI.DSC_GRADE, NF.COD_FORNECEDOR, NF.NUM_NOTA_FISCAL, NF.COD_SERIE_NOTA_FISCAL
                               FROM NOTA_FISCAL NF
                               LEFT JOIN NOTA_FISCAL_ITEM NFI ON NFI.COD_NOTA_FISCAL = NF.COD_NOTA_FISCAL) NF
                    ON NF.COD_RECEBIMENTO = V.COD_RECEBIMENTO
                   AND NF.COD_PRODUTO = V.COD_PRODUTO
                   AND NF.DSC_GRADE = V.DSC_GRADE
                  LEFT JOIN PESSOA_JURIDICA PJ ON NF.COD_FORNECEDOR = PJ.COD_PESSOA
                  LEFT JOIN FORNECEDOR F ON F.COD_FORNECEDOR = NF.COD_FORNECEDOR
                 WHERE NOT( V.SHELFLIFE > V.DIAS_VIDA_UTIL AND V.SHELFLIFE < V.DIAS_VIDA_UTIL_MAX) AND R.COD_STATUS = 457
                       $andWhere          
                 ORDER BY RA.DTH_ANDAMENTO DESC";

        $result = $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);

        return $result;
    }
    
}
