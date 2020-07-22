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
use Wms\Domain\Entity\Enderecamento\ReservaEstoqueProprietario;
use Wms\Domain\Entity\Enderecamento\ReservaEstoqueProprietarioRepository;
use Wms\Domain\Entity\Integracao\AcaoIntegracao;
use Wms\Math;
use Wms\Service\Integracao;

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

            $idRecebimentoErp = null;
            if ($this->getSystemParameterValue('UTILIZA_RECEBIMENTO_ERP') == 'S') {
                /** @var \Wms\Domain\Entity\Integracao\AcaoIntegracaoRepository $acaoIntRepo */
                $acaoIntRepo = $this->getEntityManager()->getRepository('wms:Integracao\AcaoIntegracao');


                $parametroRecebimentoERP = $this->getSystemParameterValue('ID_INTEGRACAO_RECEBIMENTO_ERP');

                $acaoEn = $acaoIntRepo->find($parametroRecebimentoERP);
                $notaFiscal = $em->getReference('wms:NotaFiscal', $notasFiscais[0]);
                $options = array(
                    0 => $notaFiscal->getFornecedor()->getIdExterno(),
                    1 => $notaFiscal->getSerie(),
                    2 => $notaFiscal->getNumero(),
                    3 => $notaFiscal->getFornecedor()->getPessoa()->getCnpj()
                );
                $notasFiscaisErp = $acaoIntRepo->processaAcao($acaoEn, $options, "E", "P", null, 611);
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

    public function conferenciaColetor($idRecebimento, $idOrdemServico, $idConferente = null)
    {
        /** @var \Wms\Domain\Entity\NotaFiscalRepository $notaFiscalRepo */
        $notaFiscalRepo = $this->_em->getRepository('wms:NotaFiscal');
        $produtoVolumeRepo = $this->_em->getRepository('wms:Produto\Volume');
        $produtoEmbalagemRepo = $this->_em->getRepository('wms:Produto\Embalagem');

        $qtdBloqueada = $this->getQuantidadeConferidaBloqueada($idRecebimento);
        if (count($qtdBloqueada))
            return array(
                'message' => 'Existem itens bloqueados por validade! Não é possível finalizar',
                'exception' => null,
                'concluido' => false);

        // buscar todos os itens das nfs do recebimento
        $itens = $notaFiscalRepo->buscarItensPorRecebimento($idRecebimento);

        $qtdConferidas = [];

        foreach ($itens as $item) {
            // checando qtdes nf
            $qtdNFs[$item['produto']][$item['grade']][$item['lote']] = $item['quantidade'];

            // checando qtdes avarias
            $qtdAvarias[$item['produto']][$item['grade']][$item['lote']] = 0;

            // checando qtdes conferidas
            switch ($item['idTipoComercializacao']) {
                case ProdutoEntity::TIPO_COMPOSTO:

                    $volumes = $produtoVolumeRepo->findBy(array('codProduto' => $item['produto'], 'grade' => $item['grade']));

                    if (empty($volumes)) {
                        return array('message' => null,
                            'exception' => new \Exception("Verifique o tipo de comercialização do produto " . $item['produto'] . ' ' . $item['grade']),
                            'concluido' => false);
                    }

                    $qtdConferidasVolumes = [];

                    foreach ($volumes as $volume) {
                        //verifica se o volume foi conferido.
                        $qtdConferida = $this->buscarConferenciaPorVolume($volume->getId(), $idOrdemServico);

                        //Caso não tenha sido conferido, grava uma conferẽncia com quantidade 0;

                        foreach ($qtdConferida as $lote => $value) {
                            if ($value == 0) {
                                $this->gravarConferenciaItemVolume($idRecebimento, $idOrdemServico, $volume->getId(), $value, null, null, null, null, $volume);
                            }
                            $qtdConferidasVolumes[$lote][$volume->getId()] = $value;
                        }
                    }

                    $qtdConferidas[$item['produto']][$item['grade']][$lote] = $this->buscarVolumeMinimoConferidoPorProduto($qtdConferidasVolumes, $item['quantidade']);

                    break;
                case ProdutoEntity::TIPO_UNITARIO:
                    $gravarConfZerada = function ($idRecebimento, $idOs, $lote) use ($produtoEmbalagemRepo, $item) {
                        $idProdutoEmbalagem = null;
                        $produtoEmbalagemEn = $produtoEmbalagemRepo->findOneBy(array('codProduto'=> $item['produto'], 'grade' => $item['grade'], 'dataInativacao' => null));
                        if ($produtoEmbalagemEn != null) {
                            $idProdutoEmbalagem = $produtoEmbalagemEn->getId();
                        }
                        $this->gravarConferenciaItemEmbalagem($idRecebimento, $idOs, $idProdutoEmbalagem, 0);
                    };

                    $qtdConferida = $this->buscarConferenciaPorEmbalagem($item['produto'], $item['grade'], $idOrdemServico);

                    //Caso não tenha sido conferido, grava uma conferẽncia com quantidade 0;
                    if (!empty($qtdConferida)) {
                        foreach ($qtdConferida as $lote => $value) {
                            if ($value == 0) {
                                $gravarConfZerada($idRecebimento, $idOrdemServico, $lote);
                            }
                            $qtdConferidas[$item['produto']][$item['grade']][$lote] = $value;
                        }
                    } else {
                        $gravarConfZerada($idRecebimento, $idOrdemServico, $item['lote']);
                        $qtdConferidas[$item['produto']][$item['grade']][$item['lote']] = 0;
                    }

                    break;
                default:
                    break;
            }

            if ((!empty($item['lote']) && !isset($qtdConferidas[$item['produto']][$item['grade']][$item['lote']]))
                || !isset($qtdConferidas[$item['produto']][$item['grade']])) {
                $qtdConferidas[$item['produto']][$item['grade']][$item['lote']] = 0;
            }
        }
        // executa os dados da conferencia
        return $this->executarConferencia($idOrdemServico, $qtdNFs, $qtdAvarias, $qtdConferidas, null, null, null, $idConferente);
    }

    public function getDivergenciaByProduto($qtdConferidas, $qtdAvarias, $qtdNFs)
    {

        $arrayQtdByProdNF = array();

        foreach ($qtdConferidas as $idProduto => $grades) {
            foreach ($grades as $grade => $lotes) {
                foreach ($lotes as $lote => $qtd) {
                    $qtdAvaria = (isset($qtdAvarias[$idProduto][$grade][$lote])) ? $qtdAvarias[$idProduto][$grade][$lote] : 0;
                    if (!isset($arrayQtdByProd[$idProduto][$grade])) {
                        $arrayQtdByProd[$idProduto][$grade] = ($qtd + $qtdAvaria);
                    } else {
                        $arrayQtdByProd[$idProduto][$grade] += ($qtd + $qtdAvaria);;
                    }
                }
            }
        }

        foreach ($qtdNFs as $idProduto => $grades) {
            foreach ($grades as $grade => $lotes) {
                foreach ($lotes as $lote => $qtd) {
                    if (!isset($arrayQtdByProdNF[$idProduto][$grade])) {
                        $arrayQtdByProdNF[$idProduto][$grade] = $qtd;
                    } else {
                        $arrayQtdByProdNF[$idProduto][$grade] += $qtd;
                    }
                }
            }
        }

        $arrayDiv = array();
        foreach ($arrayQtdByProd as $idProduto => $grades) {
            foreach ($grades as $grade => $qtd) {
                $qtdDivergencia = Math::subtrair($qtd, $arrayQtdByProdNF[$idProduto][$grade]);
                $arrayDiv[$idProduto][$grade] = ['qtd' => $qtdDivergencia, 'temDivergencia' => ($qtdDivergencia != 0)];
            }
        }

        return $arrayDiv;
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
    public function executarConferencia($idOrdemServico, $qtdNFs, $qtdAvarias, $qtdConferidas, $normas = null, $qtdUnidFracionavel = null, $embalagem = null, $idConferente = false, $unMedida = false, $dataValidade = null, $numPeso = null)
    {
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

        $check = $this->checkPaletesProcessados($idRecebimento, $idOrdemServico);
        if (!empty($check))
            return array('message' => $check,
                'exception' => null,
                'concluido' => false);

        // checo se recebimento ja n tem uma conferencia em andamento
        if ($this->checarConferenciaComDivergencia($idRecebimento))
            return array('message' => "Este recebimento ja possui uma conferencia em andamento",
                'exception' => null,
                'concluido' => false);

        $divergencia = false;
        $produtoEmbalagemRepo = $this->_em->getRepository('wms:Produto\Embalagem');
        $arrayDivergencia = $this->getDivergenciaByProduto($qtdConferidas, $qtdAvarias, $qtdNFs);

        foreach ($qtdConferidas as $idProduto => $grades) {
            foreach ($grades as $grade => $lotes) {
                /** @var Produto $produtoEn */
                $produtoEn = $produtoRepo->findOneBy(array('id' => $idProduto, 'grade' => $grade));
                foreach ($lotes as $lote => $qtdConferida) {
                    if (isset($numPeso[$idProduto][$grade][$lote]) && !empty($numPeso[$idProduto][$grade][$lote]))
                        $numPeso = (float)str_replace(',', '.', $numPeso[$idProduto][$grade][$lote]);

                    if (isset($qtdNFs[$idProduto][$grade][$lote])) {
                        $qtdNF = (float)$qtdNFs[$idProduto][$grade][$lote];
                    } else {
                        $qtdNF = (float)$qtdNFs[$idProduto][$grade][0];
                    }

                    $qtdConferida = (float)$qtdConferida;
                    $qtdAvaria = (float)0;//$qtdAvarias[$idProduto][$grade][$lote];

                    $numPecas = 0;
                    if ($produtoEn->getIndFracionavel() == "S"
                        && isset($qtdUnidFracionavel[$idProduto][$grade][$lote])
                        && !empty($qtdUnidFracionavel[$idProduto][$grade][$lote])) {
                        $numPecas = (int)$qtdConferida;
                        $qtdSemMilhar = str_replace(".", "", $qtdUnidFracionavel[$idProduto][$grade][$lote]);
                        $qtdConferida = (float)str_replace(',', '.', $qtdSemMilhar);
                    }

                    if (isset($dataValidade[$idProduto][$grade]) && !empty($dataValidade[$idProduto][$grade][$lote])) {
                        $dataValidade['dataValidade'] = $dataValidade[$idProduto][$grade][$lote];
                        $dataValidade['dataValidade'] = new \Zend_Date($dataValidade['dataValidade']);
                        $dataValidade['dataValidade'] = $dataValidade['dataValidade']->toString('Y-MM-dd');
                    } else {
                        $dataValidade['dataValidade'] = null;
                    }

                    $idEmbalagem = null;
                    $quantidade = 1;

                    if (isset($unMedida) && !empty($unMedida)) {
                        if (isset($unMedida[$idProduto][$grade][$lote])) {
                            $idEmbalagem = $unMedida[$idProduto][$grade][$lote];
                            $produtoEmbalagemEntity = $produtoEmbalagemRepo->find($idEmbalagem);
                            $quantidade = $produtoEmbalagemEntity->getQuantidade();
                        }
                    } elseif (isset($embalagem) && !empty($embalagem)) {
                        if (isset($embalagem[$idProduto][$grade])) {
                            $idEmbalagem = $embalagem[$idProduto][$grade][$lote];
                            $produtoEmbalagemEntity = $produtoEmbalagemRepo->find($idEmbalagem);
                            $quantidade = $produtoEmbalagemEntity->getQuantidade();
                        }
                    }
                    $qtdConferidaItem = $qtdConferida * $quantidade;
                    $qtdDivergente = $arrayDivergencia[$idProduto][$grade];

                    $divergenciaPesoVariavel = $this->getDivergenciaPesoVariavel($idRecebimento, $produtoEn, $repositorios);
                    $qtdDivergencia = $this->gravarConferenciaItem($idOrdemServico, $idProduto, $grade, $produtoEn, $qtdNF, $qtdConferidaItem, $numPecas, $qtdAvaria, $divergenciaPesoVariavel, $lote, $qtdDivergente);
                    if ($qtdDivergencia != 0) {
                        $divergencia = true;
                    }

                    $arrayDivergencia[$idProduto][$grade]['qtd'] -= $qtdDivergencia;
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
        $result = $this->finalizar($idRecebimento, false, $ordemServicoEntity);

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

        //DISPARA ALERTA AO ERP LIBERANDO FATURAMENTO DE NF VIA INTEGRAÇÃO VIA WS
        if ($result['exception'] == null && $this->getSystemParameterValue('IND_LIBERA_FATURAMENTO_NF_RECEBIMENTO_ERP') == 'S') {

            $checkRecebimento = true;
            if ($this->getSystemParameterValue('STATUS_RECEBIMENTO_ENDERECADO') == 'S') {
                $enderecado = $this->checkRecebimentoEnderecado($idRecebimento);
                $checkRecebimento = (empty($enderecado));
            }

            if ($checkRecebimento) {
                /** @var \Wms\Domain\Entity\NotaFiscal[] $arrNotasEn */
                $arrNotasEn = $this->_em->getRepository("wms:NotaFiscal")->findBy(['recebimento' => $idRecebimento]);
                $this->liberaFaturamentoNotaErp($arrNotasEn);
            }
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

    public function getDivergenciaPesoVariavelByOs($idOS, $idRecebimento, $produtoEn, $repositorios)
    {

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

    public function getDivergenciaPesoVariavel($idRecebimento, $produtoEn, $repositorios)
    {

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
     * @param integer $idRecebimento
     * @param bool $divergencia
     * @param OrdemServico|null $ordemServicoEn
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Exception
     */
    public function finalizar($idRecebimento, $divergencia = false, $ordemServicoEn = null)
    {
        $em = $this->getEntityManager();
        $em->beginTransaction();
        ini_set('max_execution_time', 300);
        $recebimentoEntity = $this->find($idRecebimento);

        if (!$this->checarConferenciaComDivergencia($idRecebimento)) {
            try {

                $msg = 'Recebimento finalizado pelo WMS.';
                if ($divergencia) {
                    $msg = 'Recebimento finalizado e aceito com divergência.';
                }

                /** @var RecebimentoEntity\ConferenciaRepository $conferenciaRepo */
                $conferenciaRepo = $this->_em->getRepository("wms:Recebimento\Conferencia");

                $confLoteInterno = $conferenciaRepo->getProdutosConferidosLoteInterno($idRecebimento);
                $confLoteNaoRegistrado = $conferenciaRepo->getProdutosConferidosLoteNaoRegistrado($idRecebimento);

                $arrConfLotes = array_merge($confLoteInterno, $confLoteNaoRegistrado);

                /** @var ProdutoEntity\LoteRepository $loteRepo */
                $loteRepo = $this->_em->getRepository("wms:Produto\Lote");

                if (!empty($arrConfLotes)) {
                    if (empty($ordemServicoEn)) {
                        /** @var OrdemServicoEntity $ordemServicoEn */
                        $ordemServicoEn = $this->_em->createQueryBuilder()
                            ->select('os')
                            ->from(OrdemServicoEntity::class, 'os')
                            ->where("os.recebimento = $idRecebimento AND os.dataFinal IS NULL")->getQuery()->getResult();
                    }
                    $loteRepo->reorderNFItensLoteByRecebimento($idRecebimento, $arrConfLotes, $ordemServicoEn->getPessoa());
                    $em->flush();
                }

                /** @var \Wms\Domain\Entity\Ressuprimento\ReservaEstoqueRepository $reservaEstoqueRepo */
                $reservaEstoqueRepo = $this->getEntityManager()->getRepository("wms:Ressuprimento\ReservaEstoque");
                /** @var OrdemServicoRepository $osRepo */
                $osRepo = $this->getEntityManager()->getRepository("wms:OrdemServico");

                $paletes = $em->getRepository("wms:Enderecamento\Palete")->findBy(array('recebimento' => $recebimentoEntity->getId(), 'codStatus' => PaleteEntity::STATUS_ENDERECADO));
                /** @var \Wms\Domain\Entity\NotaFiscalRepository $notaFiscalRepo */
                $notaFiscalRepo = $em->getRepository('wms:NotaFiscal');

                $paletesFlush = array();

                /** @var \Wms\Domain\Entity\Enderecamento\Palete $palete */
                foreach ($paletes as $key => $palete) {
                    /** @var \Wms\Domain\Entity\OrdemServico $osEn */
                    $osEn = $osRepo->findOneBy(array('idEnderecamento' => $palete->getId()));
                    //checando Validade
                    $getProduto = $palete->getProdutosArray();
                    $codProduto = $getProduto[0]['codProduto'];
                    $grade = $getProduto[0]['grade'];
                    $dataValidade = $notaFiscalRepo->buscaRecebimentoProduto($idRecebimento, null, $codProduto, $grade);

                    if (isset($paletesFlush[$codProduto][$grade])) {
                        $paletesFlush = array();
                        $this->getEntityManager()->flush();
                    }
                    $paletesFlush[$codProduto][$grade] = 1;

                    $reservaEstoqueRepo->efetivaReservaEstoque($palete->getDepositoEndereco()->getId(), $palete->getProdutosArray(), "E", "U", $palete->getId(), $osEn->getPessoa()->getId(), $osEn->getId(), $palete->getUnitizador()->getId(), false, $dataValidade);
                }

                if ($this->getSystemParameterValue('CONTROLE_PROPRIETARIO') == 'S') {
                    /** @var ReservaEstoqueProprietarioRepository $reservaPropRepo */
                    $reservaPropRepo = $em->getRepository(ReservaEstoqueProprietario::class);
                    $reservaPropRepo->criarReservas($recebimentoEntity->getId(), true);
                    $reservaPropRepo->checkLiberacaoReservas($recebimentoEntity->getId(), true);
                }

                $statusEntity = $em->getReference('wms:Util\Sigla', RecebimentoEntity::STATUS_FINALIZADO);

                $recebimentoEntity->setDataFinal(new \DateTime)
                    ->setStatus($statusEntity)
                    ->addAndamento(RecebimentoEntity::STATUS_FINALIZADO, false, $msg);

                $statusEntity = $em->getReference('wms:Util\Sigla', NotaFiscal::STATUS_RECEBIDA);

                foreach ($recebimentoEntity->getNotasFiscais() as $notaFiscalEntity) {
                    if ($notaFiscalEntity->getStatus()->getId() != NotaFiscalEntity::STATUS_EM_RECEBIMENTO)
                        continue;

                    $notaFiscalEntity->setStatus($statusEntity);
                    $em->persist($notaFiscalEntity);
                }

                $em->persist($recebimentoEntity);

                $em->flush();
                $em->commit();

                //$this->atualizaRecebimentoBenner($idRecebimento);

                if ($this->getSystemParameterValue('UTILIZA_INTEGRACAO_RECEBIMENTO_ERP') == 'S') {
                    $this->executaIntegracaoBDFinalizacaoConferencia($idRecebimento);
                }

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
        /** @var Usuario $user */
        $user = $this->_em->find("wms:Usuario", \Zend_Auth::getInstance()->getIdentity()->getId());
        $idsDepositos = implode(', ', $user->getIdsDepositos()) ;

        $query = '
            SELECT r
            FROM wms:Recebimento r
            INNER JOIN r.deposito dep
            WHERE r.status = ' . RecebimentoEntity::STATUS_INICIADO . ' AND dep.id in ('. $idsDepositos .')
                AND NOT EXISTS (
                    SELECT \'x\'
                    FROM wms:OrdemServico os
                    WHERE os.recebimento = r.id
                        AND os.atividade = ' . AtividadeEntity::CONFERIR_PRODUTO . '
                )
            ORDER BY r.id ASC';

        return $this->getEntityManager()->createQuery($query)
            ->getResult();
    }

    public function getFornecedorbyRecebimento($idRecebimento)
    {
        $notaFiscalRepo = $this->getEntityManager()->getRepository('wms:NotaFiscal');
        $nf = $notaFiscalRepo->findOneBy(array('recebimento' => $idRecebimento));
        if (empty($nf)) return false;
        $fornecedor = $nf->getFornecedor()->getPessoa()->getNome();
        return $fornecedor;
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
    public function gravarConferenciaItem($idOrdemServico, $idProduto, $grade, $produtoEntity = null, $qtdNF, $qtdConferida, $numPecas, $qtdAvaria, $divergenciaPesoVariavel, $lote = null, $arrPreConferencia = [])
    {
        $em = $this->getEntityManager();

        if (empty($produtoEntity)) {
            /** @var Produto $produtoEntity */
            $produtoEntity = $em->getRepository('wms:Produto')->findOneBy(array('id' => $idProduto, 'grade' => $grade));
        }
        /** @var \Wms\Domain\Entity\NotaFiscal\NotaFiscalItemLoteRepository $notaFiscalItemLoteRepo */
        $notaFiscalItemLoteRepo = $this->_em->getRepository('wms:NotaFiscal\NotaFiscalItemLote');

        $ordemServicoEntity = $em->find('wms:OrdemServico', $idOrdemServico);
        $recebimentoEntity = $ordemServicoEntity->getRecebimento();

        $temVolumesDivergentes = false;
        if ($produtoEntity->getTipoComercializacao()->getId() == Produto::TIPO_COMPOSTO)
            $temVolumesDivergentes = $this->checkVolumesDivergentes($recebimentoEntity->getId(), $idOrdemServico, $idProduto, $grade);

        $indDivergenciaLote = 'N';

        $qtdConferidaTotal = ($qtdConferida + $qtdAvaria);

        if ($produtoEntity->getIndControlaLote() == 'S') {
            $qtdNfPorLote = $notaFiscalItemLoteRepo->getQtdLoteByProdutoAndRecebimento($idProduto, $grade, $recebimentoEntity->getId());
            if (!empty($qtdNfPorLote)) {
                $qtdLoteNota = 0;
                foreach ($qtdNfPorLote as $qtdLote) {
                    if ($qtdLote['DSC_LOTE'] == $lote) {
                        $qtdLoteNota = Math::adicionar($qtdLoteNota, $qtdLote['QTD']);
                    }
                }
                $qtdDivergencia = Math::subtrair($qtdConferidaTotal, $qtdLoteNota);

                if (!empty($qtdDivergencia)) {
                    $indDivergenciaLote = 'S';
                }
            } else {
                $qtdDivergente = $arrPreConferencia['qtd'];
                $qtdDivergencia = ($qtdDivergente > $qtdConferidaTotal) ? $qtdConferidaTotal : $qtdDivergente;
                $indDivergenciaLote = ($arrPreConferencia['temDivergencia']) ? 'S' : 'N';
            }
        } elseif ($divergenciaPesoVariavel == 'S' || $produtoEntity->getPossuiPesoVariavel() == 'S') {
            $qtdDivergencia = 0;
        } else {
            $qtdDivergencia = Math::subtrair($qtdConferidaTotal, $qtdNF);
        }

        $dataValidade = null;
        if ($produtoEntity->getValidade() == 'S') {
            /**
             * @ToDo Verificar regra para identificação da data de validade caso o recebimento seja feito em mais de uma embalagem com datas de validades diferentes
             */
            $produtoEmbalagemEntity = $em->getRepository('wms:Produto\Embalagem')->findOneBy(array('codProduto' => $idProduto, 'grade' => $grade));
            if (!empty($produtoEmbalagemEntity)) {
                $recebimentoEmbalagemEntity = $em->getRepository('wms:Recebimento\Embalagem')->findOneBy(array('recebimento' => $recebimentoEntity, 'embalagem' => $produtoEmbalagemEntity));
                if (!empty($recebimentoEmbalagemEntity)) {
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
        }

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
        $conferenciaEntity->setLote((!empty($lote)) ? $lote : null);
        $conferenciaEntity->setIndDivergLote($indDivergenciaLote);

        if ($temVolumesDivergentes) {
            $conferenciaEntity->setIndDivergVolumes("S");
            $qtdDivergencia = 1;
        } else {
            $conferenciaEntity->setIndDivergVolumes("N");
        }

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
    public function gravarConferenciaItemEmbalagem($idRecebimento, $idOrdemServico, $idProdutoEmbalagem, $qtdConferida, $numPecas, $idNormaPaletizacao = null, $params = [], $numPeso = null, $qtdBloqueada = null, $produtoEmbalagemEntity = null, $lote = null)
    {
        $em = $this->getEntityManager();

        $recebimentoEmbalagemEntity = new RecebimentoEmbalagemEntity;

        $recebimentoEntity = $this->find($idRecebimento);
        $ordemServicoEntity = $this->getEntityManager()->getReference('wms:OrdemServico', $idOrdemServico);
        $qtdEmbalagem = 0;
        if ($produtoEmbalagemEntity == null && !empty($idProdutoEmbalagem)) {
            $produtoEmbalagemEntity = $this->getEntityManager()->find('wms:Produto\Embalagem', $idProdutoEmbalagem);
            $qtdEmbalagem = $produtoEmbalagemEntity->getQuantidade();
        } elseif (!empty($produtoEmbalagemEntity)) {
            $qtdEmbalagem = $produtoEmbalagemEntity->getQuantidade();
        }

        if (isset($params['dataValidade']) && !empty($params['dataValidade'])) {
            $validade = new \DateTime($params['dataValidade']);
        } else {
            $validade = null;
        }

        $recebimentoEmbalagemEntity->setRecebimento($recebimentoEntity);
        $recebimentoEmbalagemEntity->setOrdemServico($ordemServicoEntity);
        $recebimentoEmbalagemEntity->setEmbalagem($produtoEmbalagemEntity);
        $recebimentoEmbalagemEntity->setQtdEmbalagem($qtdEmbalagem);
        $recebimentoEmbalagemEntity->setQtdConferida($qtdConferida);
        $recebimentoEmbalagemEntity->setDataConferencia(new \DateTime);
        $recebimentoEmbalagemEntity->setDataValidade($validade);
        $recebimentoEmbalagemEntity->setNumPecas($numPecas);
        $recebimentoEmbalagemEntity->setQtdBloqueada($qtdBloqueada);

        if (!empty($lote)) {
            $recebimentoEmbalagemEntity->setLote($lote);
        }

        $recebimentoEmbalagemEntity->setNumPeso($numPeso);
        if ($idNormaPaletizacao != null) {
            /** @var ProdutoEntity\NormaPaletizacao $normaPaletizacaoEntity */
            $normaPaletizacaoEntity = $this->getEntityManager()->getReference('wms:Produto\NormaPaletizacao', $idNormaPaletizacao);
            $recebimentoEmbalagemEntity->setNormaPaletizacao($normaPaletizacaoEntity);
        }

        $em->persist($recebimentoEmbalagemEntity);
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
    public function gravarConferenciaItemVolume($idRecebimento, $idOrdemServico, $idProdutoVolume, $qtdConferida, $idNormaPaletizacao = null, $params = null, $numPeso = null, $qtdBloqueada = null, $produtoVolumeEntity = null, $lote = null)
    {
        $em = $this->getEntityManager();

        $recebimentoVolumeEntity = new RecebimentoVolumeEntity;

        $recebimentoEntity = $this->find($idRecebimento);
        $ordemServicoEntity = $this->getEntityManager()->getReference('wms:OrdemServico', $idOrdemServico);
        if (empty($produtoVolumeEntity)) {
            $produtoVolumeEntity = $this->getEntityManager()->getReference('wms:Produto\Volume', $idProdutoVolume);
        }
        if (isset($params['dataValidade']) && !empty($params['dataValidade'])) {
            $validade = new \DateTime($params['dataValidade']);
        } else {
            $validade = null;
        }

        if ($idNormaPaletizacao == null) {
            $idNormaPaletizacao = $produtoVolumeEntity->getNormaPaletizacao()->getId();
        }

        $recebimentoVolumeEntity->setRecebimento($recebimentoEntity)
            ->setOrdemServico($ordemServicoEntity)
            ->setVolume($produtoVolumeEntity)
            ->setQtdConferida($qtdConferida)
            ->setDataConferencia(new \DateTime)
            ->setDataValidade($validade);
        $recebimentoVolumeEntity->setNumPeso($numPeso);
        $recebimentoVolumeEntity->setQtdBloqueada($qtdBloqueada);

        if (!empty($lote)) {
            $recebimentoVolumeEntity->setLote($lote);
        }

        if ($idNormaPaletizacao != null) {
            $normaPaletizacaoEntity = $this->getEntityManager()->getReference('wms:Produto\NormaPaletizacao', $idNormaPaletizacao);
            $recebimentoVolumeEntity->setNormaPaletizacao($normaPaletizacaoEntity);
        }

        $em->persist($recebimentoVolumeEntity);
        $em->flush($recebimentoVolumeEntity);
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
        $loteND = ProdutoEntity\Lote::LND;

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
                  LEFT JOIN (SELECT SUM(QTD) / NVL(PV.QTD_NORMAS,1) as QTD,
                                    V.COD_PRODUTO,
                                    V.DSC_GRADE 
                               FROM (SELECT DISTINCT P.UMA, 
                                                     CASE WHEN PROD.IND_POSSUI_PESO_VARIAVEL = 'S' THEN P.PESO
                                                          ELSE PP.QTD 
                                                     END AS QTD, 
                                                     PP.COD_PRODUTO, 
                                                     PP.DSC_GRADE, 
                                                     P.COD_RECEBIMENTO, 
                                                     P.COD_STATUS,
                                                     PP.COD_NORMA_PALETIZACAO,
                                                     NVL(PP.DSC_LOTE, '$loteND') DSC_LOTE
                                       FROM PALETE P
                                      INNER JOIN PALETE_PRODUTO PP ON PP.UMA = P.UMA
                                       LEFT JOIN PRODUTO PROD ON PROD.COD_PRODUTO = PP.COD_PRODUTO AND PROD.DSC_GRADE = PROD.DSC_GRADE
                                      WHERE P.COD_RECEBIMENTO = $idRecebimento
                                        AND P.COD_STATUS = 534) V
                                       LEFT JOIN (SELECT COUNT(DISTINCT COD_NORMA_PALETIZACAO) QTD_NORMAS, COD_PRODUTO, DSC_GRADE FROM PRODUTO_VOLUME PV GROUP BY COD_PRODUTO, DSC_GRADE) PV ON PV.COD_PRODUTO = V.COD_PRODUTO AND PV.DSC_GRADE = V.DSC_GRADE
                                      GROUP BY V.COD_PRODUTO, V.DSC_GRADE, PV.QTD_NORMAS) RECEBIDO
                    ON RECEBIDO.COD_PRODUTO = V.COD_PRODUTO
                   AND RECEBIDO.DSC_GRADE = V.DSC_GRADE
                  LEFT JOIN (SELECT SUM(QTD) / NVL(PV.QTD_NORMAS,1) as QTD,
                                    V.COD_PRODUTO,
                                    V.DSC_GRADE 
                               FROM (SELECT DISTINCT P.UMA, 
                                                     CASE WHEN PROD.IND_POSSUI_PESO_VARIAVEL = 'S' THEN P.PESO
                                                          ELSE PP.QTD 
                                                     END AS QTD, 
                                                     PP.COD_PRODUTO, 
                                                     PP.DSC_GRADE, 
                                                     P.COD_RECEBIMENTO, 
                                                     P.COD_STATUS,
                                                     PP.COD_NORMA_PALETIZACAO,
                                                     NVL(PP.DSC_LOTE, '$loteND') DSC_LOTE
                                       FROM PALETE P
                                      INNER JOIN PALETE_PRODUTO PP ON PP.UMA = P.UMA
                                       LEFT JOIN PRODUTO PROD ON PROD.COD_PRODUTO = PP.COD_PRODUTO AND PROD.DSC_GRADE = PROD.DSC_GRADE
                                      WHERE P.COD_RECEBIMENTO = $idRecebimento
                                        AND P.COD_STATUS = 536) V
                                       LEFT JOIN (SELECT COUNT(DISTINCT COD_NORMA_PALETIZACAO) QTD_NORMAS, COD_PRODUTO, DSC_GRADE FROM PRODUTO_VOLUME PV GROUP BY COD_PRODUTO, DSC_GRADE) PV ON PV.COD_PRODUTO = V.COD_PRODUTO AND PV.DSC_GRADE = V.DSC_GRADE
                                      GROUP BY V.COD_PRODUTO, V.DSC_GRADE, PV.QTD_NORMAS) ENDERECADO
                    ON ENDERECADO.COD_PRODUTO = V.COD_PRODUTO
                   AND ENDERECADO.DSC_GRADE = V.DSC_GRADE
                  LEFT JOIN (SELECT SUM(QTD) / NVL(PV.QTD_NORMAS,1) as QTD,
                                    V.COD_PRODUTO,
                                    V.DSC_GRADE 
                               FROM (SELECT DISTINCT P.UMA, 
                                                     CASE WHEN PROD.IND_POSSUI_PESO_VARIAVEL = 'S' THEN P.PESO
                                                          ELSE PP.QTD 
                                                     END AS QTD, 
                                                     PP.COD_PRODUTO, 
                                                     PP.DSC_GRADE, 
                                                     P.COD_RECEBIMENTO, 
                                                     P.COD_STATUS,
                                                     PP.COD_NORMA_PALETIZACAO,
                                                     NVL(PP.DSC_LOTE, '$loteND') DSC_LOTE
                                       FROM PALETE P
                                      INNER JOIN PALETE_PRODUTO PP ON PP.UMA = P.UMA
                                       LEFT JOIN PRODUTO PROD ON PROD.COD_PRODUTO = PP.COD_PRODUTO AND PROD.DSC_GRADE = PROD.DSC_GRADE
                                      WHERE P.COD_RECEBIMENTO = $idRecebimento
                                        AND P.COD_STATUS = 535) V
                                       LEFT JOIN (SELECT COUNT(DISTINCT COD_NORMA_PALETIZACAO) QTD_NORMAS, COD_PRODUTO, DSC_GRADE FROM PRODUTO_VOLUME PV GROUP BY COD_PRODUTO, DSC_GRADE) PV ON PV.COD_PRODUTO = V.COD_PRODUTO AND PV.DSC_GRADE = V.DSC_GRADE
                                      GROUP BY V.COD_PRODUTO, V.DSC_GRADE, PV.QTD_NORMAS) ENDERECAMENTO
                    ON ENDERECAMENTO.COD_PRODUTO = V.COD_PRODUTO
                   AND ENDERECAMENTO.DSC_GRADE = V.DSC_GRADE   
                  LEFT JOIN (SELECT SUM(QTD) / NVL(QTD_NORMAS,1) as QTD,
                                    V.COD_PRODUTO,
                                    V.DSC_GRADE 
                               FROM (SELECT DISTINCT P.UMA, 
                                                     CASE WHEN PROD.IND_POSSUI_PESO_VARIAVEL = 'S' THEN P.PESO
                                                          ELSE PP.QTD 
                                                     END AS QTD, 
                                                     PP.COD_PRODUTO, 
                                                     PP.DSC_GRADE, 
                                                     P.COD_RECEBIMENTO, 
                                                     P.COD_STATUS,
                                                     PP.COD_NORMA_PALETIZACAO,
                                                     NVL(PP.DSC_LOTE, '$loteND') DSC_LOTE
                                       FROM PALETE P
                                      INNER JOIN PALETE_PRODUTO PP ON PP.UMA = P.UMA
                                       LEFT JOIN PRODUTO PROD ON PROD.COD_PRODUTO = PP.COD_PRODUTO AND PROD.DSC_GRADE = PROD.DSC_GRADE
                                      WHERE P.COD_RECEBIMENTO = $idRecebimento
                                        AND P.COD_STATUS <> 537) V
                                       LEFT JOIN (SELECT COUNT(DISTINCT COD_NORMA_PALETIZACAO) QTD_NORMAS, COD_PRODUTO, DSC_GRADE FROM PRODUTO_VOLUME PV GROUP BY COD_PRODUTO, DSC_GRADE) PV ON PV.COD_PRODUTO = V.COD_PRODUTO AND PV.DSC_GRADE = V.DSC_GRADE
                                      GROUP BY V.COD_PRODUTO, V.DSC_GRADE, PV.QTD_NORMAS) GERADO
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
                  LEFT JOIN PRODUTO P ON P.COD_PRODUTO = V.COD_PRODUTO AND P.DSC_GRADE = V.DSC_GRADE
                  ORDER BY V.COD_PRODUTO, V.DSC_GRADE";
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
                'produto' => $row['DSC_PRODUTO'],
                'grade' => $row['DSC_GRADE'],
                'picking' => $picking,
                'qtdItensNf' => round($row['QTD_NOTA_FISCAL'], 5),
                'qtdRecebimento' => round($row['QTD_RECEBIMENTO'], 5),
                'qtdRecebida' => round($row['QTD_RECEBIDA'], 5),
                'qtdEnderecamento' => round($row['QTD_ENDERECAMENTO'], 5),
                'qtdEnderecada' => round($row['QTD_ENDERECADA'], 5),
                'qtdTotal' => round($row['QTD_TOTAL'], 5)
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
     * @param int $idOrdemServico
     * @param int $produtoVolume
     * @return array Quantidade de volumes conferidos por lote
     */

    public function buscarConferenciaPorVolume($produtoVolume, $idOrdemServico) {
        // busca volumes
        $dql = $this->getEntityManager()->createQueryBuilder()
                ->select('rv.qtdConferida, NVL(rv.lote, 0) lote')
                ->from('wms:Recebimento\Volume', 'rv')
                ->where('rv.ordemServico = ?1 AND rv.volume = ?2')
                ->setParameters([ 1 => $idOrdemServico, 2 => $produtoVolume ] );

        $volumes = $dql->getQuery()->getArrayResult();
        $qtdTotal = array();
        foreach ($volumes as $volume) {
            $lote = $volume['lote'];
            if (!isset($qtdTotal[$lote])) {
                $qtdTotal[$lote] = 0;
            }
            $qtdTotal[$lote] = Math::adicionar($qtdTotal[$lote], $volume['qtdConferida']);
        }

        if (empty($qtdTotal)) $qtdTotal[0] = 0;

        return $qtdTotal;
    }


    public function buscarVolumeMinimoConferidoPorProduto(array $volumesConferidos, $qtdNf) {
        //Garantia de que vai retornar a menor quantidade conferida
        $minimo = 9999999999;
        $maximo = 0;

        foreach ($volumesConferidos as $lote => $vols) {
            foreach ($vols as $qtd) {
                if ($minimo > $qtd) {
                    $minimo = $qtd;
                }

                if ($maximo < $qtd) {
                    $maximo = $qtd;
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
     * @return array Quantidade encontrada de embalagens
     */
    public function buscarConferenciaPorEmbalagem($produto, $grade, $idOrdemServico)
    {
        // busca embalagens
        $dql = $this->getEntityManager()->createQueryBuilder()
            ->select("CASE WHEN p.indFracionavel != 'S' THEN pe.quantidade ELSE 1 END AS qtdEmbalagem, re.qtdConferida, NVL(re.lote, 0) lote")
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

        $qtdTotal = array();
        $norma = null;
        foreach ($embalagens as $embalagem) {
            if (!isset($qtdTotal[$embalagem['lote']])) {
                $qtdTotal[$embalagem['lote']] = 0;
            }
            $qtdTotal[$embalagem['lote']] = Math::adicionar($qtdTotal[$embalagem['lote']], Math::multiplicar($embalagem['qtdEmbalagem'], $embalagem['qtdConferida']));
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

        $ordensServico = $dql->getQuery()->getFirstResult();

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
        $this->executaIntegracaoBDEmRecebimentoERP($recebimentoEntity);

        $recebimentoEntity = $this->find($idRecebimento);
        $recebimentoEntity->addAndamento(RecebimentoEntity::STATUS_CONFERENCIA_COLETOR, false, 'Conferência iniciada pelo usuário.');
        $this->updateStatus($recebimentoEntity, RecebimentoEntity::STATUS_CONFERENCIA_COLETOR);

        return array(
            'criado' => true,
            'id' => $idOrdemServico,
            'mensagem' => 'Ordem de Serviço Nº ' . $idOrdemServico . ' criada com sucesso.',
        );
    }

    /*
     * @param RecebimentoEntity $recebimentoEntity
     */
    public function executaIntegracaoBDEmRecebimentoERP (RecebimentoEntity $recebimentoEntity) {
        $idsIntegracao = $this->getSystemParameterValue('ID_INTEGRACAO_INICIO_RECEBIMENTO_ERP');
        if ($idsIntegracao == "") return true;

        if ($recebimentoEntity == null)  throw new \Exception("Recebimento não informado para integração");

        if (($recebimentoEntity->getStatus()->getId() != RecebimentoEntity::STATUS_CRIADO) &&
            ($recebimentoEntity->getStatus()->getId() != RecebimentoEntity::STATUS_INICIADO)) return false;

        /** @var \Wms\Domain\Entity\Integracao\AcaoIntegracaoRepository $acaoIntRepo */
        $acaoIntRepo = $this->getEntityManager()->getRepository('wms:Integracao\AcaoIntegracao');
        /** @var \Wms\Domain\Entity\NotaFiscalRepository $notaFiscalRepository */
        $notaFiscalRepository = $this->getEntityManager()->getRepository('wms:NotaFiscal');

        /** @var Usuario $usuario */
        $usuario = $this->_em->find('wms:Usuario', \Zend_Auth::getInstance()->getIdentity()->getId());

        $idRecebimento = $recebimentoEntity->getId();
        $ids = explode(',', $idsIntegracao);
        sort($ids);

        foreach ($ids as $idIntegracao) {
            $acaoEn = $acaoIntRepo->find($idIntegracao);

            /*
             * Devolve o Retorno a integração a nível de nota fiscal
             * ?1 - Numero da Nota Fiscal
             * ?2 - Série da Nota Fiscal
             * ?3 - Código do Fornecedor
             * ?4 - CNPJ do Fornecedor
             * ?5 - Data de Emissão da Nota Fiscal
             * ?6 - Código do Recebimento no ERP da Nota Fiscal
             * ?7 - Código do Recebimento interno do WMS
             * ?8 - Código do usuário no ERP
             */

            $nfsEntity = $notaFiscalRepository->findBy(array('recebimento' => $idRecebimento));
            /** @var \Wms\Domain\Entity\NotaFiscal $notaFiscalEntity */
            foreach ($nfsEntity as $notaFiscalEntity) {
                $options = array(
                    0 => $notaFiscalEntity->getNumero(),
                    1 => $notaFiscalEntity->getSerie(),
                    2 => $notaFiscalEntity->getFornecedor()->getIdExterno(),
                    3 => $notaFiscalEntity->getFornecedor()->getPessoa()->getCnpj(),
                    4 => $notaFiscalEntity->getDataEmissao()->format('Y-m-d H:i:s'),
                    5 => $notaFiscalEntity->getCodRecebimentoErp(),
                    6 => $notaFiscalEntity->getRecebimento()->getId(),
                    7 => $usuario->getCodErp()
                );
                $resultAcao = $acaoIntRepo->processaAcao($acaoEn, $options, 'R', "P", null, 612);
                if (!$resultAcao === true) {
                    throw new \Exception($resultAcao);
                }
                unset($options);
            }
        }

        $recebimentoEntity = $this->find($idRecebimento);
        $recebimentoEntity->addAndamento($recebimentoEntity->getStatus()->getId(), false, 'Inicio do recebimento comunicado ao ERP');
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

    public function gravarRecebimentoEmbalagemVolume($idProduto, $grade, $produtoEntity = null, $qtd, $numPecas, $idRecebimento, $idOs, $norma = null, $idEmbalagem = null, $dataValidade = null, $numPeso = null, $lote = null)
    {
        if (empty($produtoEntity))
            $produtoEntity = $this->getEntityManager()->getRepository('wms:Produto')->findOneBy(array('id' => $idProduto, 'grade' => $grade));

        if (!empty($idEmbalagem)) {
            $embalagem = null;
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
            $this->gravarConferenciaItemEmbalagem($idRecebimento, $idOs, $idEmbalagem, $qtd, $numPecas, $norma, $dataValidade, $numPeso, null, $embalagem, $lote);
        } else {
            $volumes = $produtoEntity->getVolumes();
            /** @var \Wms\Domain\Entity\Produto\Volume $volume */
            foreach ($volumes as $volume) {
                $norma = $volume->getNormaPaletizacao()->getId();
                $this->gravarConferenciaItemVolume($idRecebimento, $idOs, $volume->getId(), $qtd, $norma, $dataValidade, $numPeso, null, $volume, $lote);
            }
        }
    }

    public function saveConferenciaCega($idRecebimento, $idOrdemServico, $qtdConferidas, $normas = null, $qtdUnidFracionavel = null, $embalagem = null, $unMedida = false, $dataValidade = null, $numPeso = null)
    {
        /** @var \Wms\Domain\Entity\ProdutoRepository $produtoRepo */
        $produtoRepo = $this->_em->getRepository('wms:Produto');

        foreach ($qtdConferidas as $idProduto => $grades) {
            foreach ($grades as $grade => $lotes) {
                /** @var Produto $produtoEn */
                $produtoEn = $produtoRepo->findOneBy(array('id' => $idProduto, 'grade' => $grade));
                foreach ($lotes as $lote => $qtdConferida) {
                    $qtdConferida = (float)$qtdConferida;

                    $numPecas = 0;
                    if ($produtoEn->getIndFracionavel() == "S"
                        && isset($qtdUnidFracionavel[$idProduto][$grade][$lote])
                        && !empty($qtdUnidFracionavel[$idProduto][$grade][$lote])) {
                        $numPecas = (int)$qtdConferida;
                        $qtdSemMilhar = str_replace(".", "", $qtdUnidFracionavel[$idProduto][$grade][$lote]);
                        $qtdConferida = (float)str_replace(',', '.', $qtdSemMilhar);
                    }

                    if (isset($dataValidade[$idProduto][$grade]) && !empty($dataValidade[$idProduto][$grade][$lote])) {
                        $dataValidade['dataValidade'] = $dataValidade[$idProduto][$grade][$lote];
                        $dataValidade['dataValidade'] = new \Zend_Date($dataValidade['dataValidade']);
                        $dataValidade['dataValidade'] = $dataValidade['dataValidade']->toString('Y-MM-dd');
                    } else {
                        $dataValidade['dataValidade'] = null;
                    }

                    $norma = null;
                    if (!empty($normas)) {
                        $norma = $normas[$idProduto][$grade][$lote];
                    }

                    $idEmbalagem = null;
                    if (isset($embalagem[$idProduto][$grade])) {
                        $idEmbalagem = $embalagem[$idProduto][$grade][$lote];
                    } elseif (isset($unMedida[$idProduto][$grade][$lote])) {
                        $idEmbalagem = $unMedida[$idProduto][$grade][$lote];
                    }

                    $this->gravarRecebimentoEmbalagemVolume($idProduto, $grade, $produtoEn, $qtdConferida, $numPecas, $idRecebimento, $idOrdemServico, $norma, $idEmbalagem, $dataValidade, $numPeso, $lote);
                }
            }
        }
        $this->_em->flush();
    }

    public function alteraNormaPaletizacaoRecebimento($codRecebimento, $codProduto, $grade, $codOs, $idNorma)
    {

        $dql = $this->getEntityManager()->createQueryBuilder()
            ->select("re")
            ->from("wms:Recebimento\Embalagem", "re")
            ->leftJoin("re.embalagem", "pe")
            ->where("re.ordemServico = '$codOs'")
            ->andWhere("pe.codProduto = '$codProduto'")
            ->andWhere("pe.grade = '$grade'")
            ->andWhere("re.recebimento = '$codRecebimento'");
        $embalagens = $dql->getQuery()->getResult();

        if (!empty($embalagens)) {
            /** @var ProdutoEntity\NormaPaletizacao $normaEn */
            $normaEn = $this->getEntityManager()->getRepository("wms:Produto\NormaPaletizacao")->findOneBy(array('id' => $idNorma));

            /** @var \Wms\Domain\Entity\Recebimento\Embalagem $embalagem */
            foreach ($embalagens as $embalagem) {
                $embalagem->setNormaPaletizacao($normaEn);
                $this->getEntityManager()->persist($embalagem);
            }
        } else {

            $dql = $this->getEntityManager()->createQueryBuilder()
                ->select("rv")
                ->from("wms:Recebimento\Volume", "rv")
                ->leftJoin("rv.volume", "pv")
                ->where("rv.ordemServico = '$codOs'")
                ->andWhere("pv.codProduto = '$codProduto'")
                ->andWhere("pv.grade = '$grade'")
                ->andWhere("rv.recebimento = '$codRecebimento'");
            $volumes = $dql->getQuery()->getResult();

            if (!empty($volumes)) {
                /** @var \Wms\Domain\Entity\Recebimento\Volume $volume */
                foreach ($volumes as $volume) {
                    $volume->setNormaPaletizacao($volume->getVolume()->getNormaPaletizacao());
                    $this->getEntityManager()->persist($volume);
                }
            } else {
                $conferenciaRepo = $this->getEntityManager()->getRepository("wms:Recebimento\Conferencia");
                $conferenciaEn = $conferenciaRepo->findOneBy(array('recebimento' => $codRecebimento, 'codProduto' => $codProduto, 'grade' => $grade, 'ordemServico' => $codOs));
                $qtd = $conferenciaEn->getQtdConferida();
                $numPcs = $conferenciaEn->getNumPecas();
                $this->gravarRecebimentoEmbalagemVolume($codProduto, $grade, $conferenciaEn->getProduto(), $qtd, $numPcs, $codRecebimento, $codOs);
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

    public function checkRecebimentoEnderecado($idRecebimento)
    {
        $sql = "SELECT DISTINCT
                    R.COD_RECEBIMENTO
                FROM RECEBIMENTO R
                LEFT JOIN (
                          SELECT RC.COD_RECEBIMENTO, COD_PRODUTO, DSC_GRADE, QTD_CONFERIDA as QTD
                          FROM RECEBIMENTO_CONFERENCIA RC
                          INNER JOIN RECEBIMENTO R ON RC.COD_RECEBIMENTO = R.COD_RECEBIMENTO
                          WHERE R.COD_STATUS = 457 AND R.COD_RECEBIMENTO = $idRecebimento 
                                  AND ((QTD_DIVERGENCIA = 0 AND IND_DIVERGENCIA_PESO = 'N' AND IND_DIVERG_VOLUMES = 'N' AND IND_DIVERG_LOTE = 'N') 
                                    OR ((QTD_DIVERGENCIA != 0 OR IND_DIVERGENCIA_PESO != 'N' AND IND_DIVERG_VOLUMES != 'N' AND IND_DIVERG_LOTE != 'N') AND COD_NOTA_FISCAL IS NOT NULL))
                             ) V ON V.COD_RECEBIMENTO = R.COD_RECEBIMENTO
                LEFT JOIN (SELECT COD_RECEBIMENTO, COD_PRODUTO, DSC_GRADE, SUM(QTD) as QTD
                            FROM (SELECT DISTINCT P.UMA, P.COD_RECEBIMENTO, PP.COD_PRODUTO, PP.DSC_GRADE, PP.QTD, PP.DSC_LOTE
                                  FROM PALETE P
                                  INNER JOIN PALETE_PRODUTO PP ON P.UMA = PP.UMA
                                  WHERE P.COD_RECEBIMENTO = $idRecebimento AND P.COD_STATUS = 536)
                            GROUP BY COD_RECEBIMENTO, COD_PRODUTO, DSC_GRADE
                          ) P ON P.COD_RECEBIMENTO = V.COD_RECEBIMENTO AND P.COD_PRODUTO = V.COD_PRODUTO AND P.DSC_GRADE = V.DSC_GRADE
                WHERE (NVL(V.QTD,0) - NVL(P.QTD,0) >0) AND R.COD_STATUS NOT IN (458,460)";

        return $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function naoEnderecadosByStatus($status = null)
    {

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
                SELECT V.COD_RECEBIMENTO, V.COD_PRODUTO, V.DSC_GRADE, SUM(V.QTD) as QTD, NVL(V.DSC_LOTE,'NCL') DSC_LOTE
                  FROM V_QTD_RECEBIMENTO V
                 WHERE V.COD_RECEBIMENTO IN ($ids)
                 GROUP BY V.COD_RECEBIMENTO, V.COD_PRODUTO, V.DSC_GRADE, V.DSC_LOTE
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
           INNER JOIN DEPOSITO D ON D.COD_DEPOSITO = R.COD_DEPOSITO
           LEFT JOIN ($sqlRecebimentosConferencia
                      SELECT RC.COD_RECEBIMENTO, COD_PRODUTO, DSC_GRADE, QTD_CONFERIDA as QTD, NVL(RC.DSC_LOTE,'NCL') DSC_LOTE
                        FROM RECEBIMENTO_CONFERENCIA RC
                        LEFT JOIN RECEBIMENTO R ON RC.COD_RECEBIMENTO = R.COD_RECEBIMENTO
                       WHERE R.COD_STATUS = 457
                         AND ((QTD_DIVERGENCIA = 0 AND 'S' NOT IN (IND_DIVERG_LOTE,IND_DIVERG_VOLUMES,IND_DIVERGENCIA_PESO)) OR COD_NOTA_FISCAL IS NOT NULL)) V
                  ON V.COD_RECEBIMENTO = R.COD_RECEBIMENTO
           LEFT JOIN (SELECT COD_RECEBIMENTO, COD_PRODUTO, DSC_GRADE, SUM(QTD) / QTD_NORMAS as QTD, NVL(DSC_LOTE,'NCL') DSC_LOTE
                        FROM (SELECT DISTINCT P.UMA, P.COD_RECEBIMENTO, PP.COD_PRODUTO, PP.DSC_GRADE, PP.QTD, NVL(QTD_NORMAS,1) as QTD_NORMAS, PP.DSC_LOTE
                                FROM PALETE P
                                LEFT JOIN PALETE_PRODUTO PP ON P.UMA = PP.UMA
                                LEFT JOIN (SELECT COUNT(DISTINCT COD_NORMA_PALETIZACAO) QTD_NORMAS, COD_PRODUTO, DSC_GRADE FROM PRODUTO_VOLUME PV GROUP BY COD_PRODUTO, DSC_GRADE) PV ON PV.COD_PRODUTO = PP.COD_PRODUTO AND PV.DSC_GRADE = PP.DSC_GRADE
                               WHERE P.COD_STATUS IN (" . Palete::STATUS_ENDERECADO . "," . Palete::STATUS_EM_ENDERECAMENTO . ") OR P.IND_IMPRESSO = 'S')
                       GROUP BY COD_RECEBIMENTO, COD_PRODUTO, DSC_GRADE, QTD_NORMAS, DSC_LOTE) P
                  ON P.COD_RECEBIMENTO = V.COD_RECEBIMENTO
                 AND P.COD_PRODUTO = V.COD_PRODUTO
                 AND P.DSC_GRADE = V.DSC_GRADE
                 AND P.DSC_LOTE = V.DSC_LOTE
           LEFT JOIN BOX B ON R.COD_BOX = B.COD_BOX
           LEFT JOIN (SELECT COD_RECEBIMENTO, MAX(FORNECEDOR) as FORNECEDOR
                        FROM (SELECT DISTINCT
                                     NF.COD_RECEBIMENTO,
                                     NVL(PJ.NOM_FANTASIA, PES.NOM_PESSOA) as FORNECEDOR
                                FROM NOTA_FISCAL NF
                                LEFT JOIN PESSOA_JURIDICA PJ ON PJ.COD_PESSOA = NF.COD_FORNECEDOR
                                LEFT JOIN PESSOA PES ON PES.COD_PESSOA = NF.COD_FORNECEDOR)
                       GROUP BY COD_RECEBIMENTO) F ON F.COD_RECEBIMENTO = R.COD_RECEBIMENTO
          WHERE NVL(D.IND_USA_ENDERECAMENTO, 'S') = 'S' AND (NVL(V.QTD,0) - NVL(P.QTD,0) >0)
            AND R.COD_STATUS NOT IN (" . Recebimento::STATUS_DESFEITO . "," . Recebimento::STATUS_CANCELADO . ")
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
    public function searchNew(array $params = array())
    {
        extract($params);

        $where = " ";
        if (!empty($dataInicial1)) {
            $where .= " AND (R.DTH_INICIO_RECEB >= TO_DATE('$dataInicial1 00:00', 'DD-MM-YYYY HH24:MI'))";
        }
        if (!empty($dataInicial2)) {
            $where .= " AND (R.DTH_INICIO_RECEB <= TO_DATE('$dataInicial2 23:59', 'DD-MM-YYYY HH24:MI'))";
        }
        if (!empty($dataFinal1)) {
            $where .= " AND (R.DTH_FINAL_RECEB >= TO_DATE('$dataFinal1 00:00', 'DD-MM-YYYY HH24:MI'))";
        }
        if (!empty($dataFinal2)) {
            $where .= " AND (R.DTH_FINAL_RECEB <= TO_DATE('$dataFinal2 00:00', 'DD-MM-YYYY HH24:MI'))";
        }
        if (isset($status) && (!empty($status))) {
            $where .= " AND R.COD_STATUS = " . $status;
        }
        if (isset($idRecebimento) && !empty($idRecebimento)) {
            $where .= " AND R.COD_RECEBIMENTO = " . $idRecebimento;
        } elseif (isset($uma) && !empty($uma)) {
            $where .= " AND R.COD_RECEBIMENTO IN (SELECT DISTINCT COD_RECEBIMENTO FROM PALETE WHERE UMA = $uma)";
        }
        if (isset($idFornecedor) && !empty($idFornecedor)) {
            $where .= " AND NF.COD_FORNECEDOR =  $idFornecedor ";

        }

        $sessao = new \Zend_Session_Namespace('deposito');
        $idDeposito = $sessao->idDepositoLogado;

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
                   ST.DSC_SIGLA AS siglaTipoNota,
                   REPLACE(REPLACE(NVL(RA.DSC_OBSERVACAO,''),'Recebimento iniciado pelo Usuário. ',''),'<br />','') as DSC_OBSERVACAO,
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
                       PRODUTO_EMBALAGEM PE ON (NFI.COD_PRODUTO = PE.COD_PRODUTO AND PE.DTH_INATIVACAO IS NULL)
                     WHERE 
                       NF2.COD_RECEBIMENTO = R.COD_RECEBIMENTO
                     GROUP BY 
                       NFI.COD_NOTA_FISCAL,
                       NFI.QTD_ITEM, 
                       NFI.COD_PRODUTO
                     ) AS qtdMenor,
                    NVL(DE.IND_USA_ENDERECAMENTO, 'S') ENDERECA
                 FROM NOTA_FISCAL NF
           RIGHT JOIN RECEBIMENTO R ON (NF.COD_RECEBIMENTO = R.COD_RECEBIMENTO)
           LEFT JOIN RECEBIMENTO_ANDAMENTO RA ON (RA.COD_RECEBIMENTO = R.COD_RECEBIMENTO AND RA.COD_TIPO_ANDAMENTO = 456) 
           INNER JOIN SIGLA S ON (R.COD_STATUS = S.COD_SIGLA)
            LEFT JOIN FILIAL FL ON FL.COD_FILIAL = NF.COD_FILIAL
            LEFT JOIN DEPOSITO DE ON DE.COD_FILIAL = FL.COD_FILIAL
            LEFT JOIN BOX B ON (R.COD_BOX = B.COD_BOX)
            LEFT JOIN SIGLA ST ON ST.COD_SIGLA = NF.COD_TIPO_NOTA_FISCAL
            LEFT JOIN ORDEM_SERVICO OS ON (NF.COD_RECEBIMENTO = OS.COD_RECEBIMENTO AND OS.COD_FORMA_CONFERENCIA = 'M' AND OS.DTH_FINAL_ATIVIDADE IS NULL)
            LEFT JOIN ORDEM_SERVICO OS2 ON (NF.COD_RECEBIMENTO = OS2.COD_RECEBIMENTO AND OS2.COD_FORMA_CONFERENCIA = 'C' AND OS2.DTH_FINAL_ATIVIDADE IS NULL)
                WHERE CASE WHEN FL.COD_FILIAL IS NOT NULL AND FL.IND_ATIVO = 'S' THEN CASE WHEN DE.COD_DEPOSITO = $idDeposito THEN 1 ELSE 0 END ELSE 1 END = 1 " . $where . " ORDER BY TO_NUMBER(R.COD_RECEBIMENTO) DESC";
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


    /**
     * @param $idRecebimento
     * @param $idOrdemServico
     * @return null|string
     * @throws \Doctrine\DBAL\DBALException
     */
    private function checkPaletesProcessados($idRecebimento, $idOrdemServico)
    {

        $statusEnderecado = Palete::STATUS_ENDERECADO;
        $statusEmEnderecamento = Palete::STATUS_EM_ENDERECAMENTO;

        $sql = "SELECT DISTINCT PLT.COD_PRODUTO, 
                       PLT.DSC_GRADE,  
                       PLT.DSC_LOTE,
                       RC.QTD AS QTD_RECEBIDA, 
                       PLT.QTD_TOTAL AS QTD_PALETIZADA
                  FROM (SELECT DISTINCT (SUM(PP.QTD) / COUNT(DISTINCT NVL(PP.COD_PRODUTO_VOLUME, 1))) QTD_TOTAL, 
                               PP.COD_PRODUTO, 
                               PP.DSC_GRADE, 
                               P.COD_RECEBIMENTO, 
                               NVL(PP.DSC_LOTE,0) DSC_LOTE
                          FROM PALETE_PRODUTO PP
                         INNER JOIN PALETE P ON P.UMA = PP.UMA
                         WHERE P.COD_RECEBIMENTO = $idRecebimento 
                           AND (P.IND_IMPRESSO = 'S' OR P.COD_STATUS IN ($statusEmEnderecamento, $statusEnderecado))
                         GROUP BY PP.COD_PRODUTO, PP.DSC_GRADE, P.COD_RECEBIMENTO, NVL(PP.DSC_LOTE,0)) PLT
                 INNER JOIN (SELECT COD_PRODUTO, 
                                    DSC_GRADE, 
                                    QTD, 
                                    NVL(DSC_LOTE,0) DSC_LOTE
                               FROM V_QTD_RECEBIMENTO 
                              WHERE COD_OS = $idOrdemServico
                                AND COD_RECEBIMENTO = $idRecebimento)RC 
                    ON RC.COD_PRODUTO = PLT.COD_PRODUTO AND RC.DSC_GRADE = PLT.DSC_GRADE AND RC.DSC_LOTE = PLT.DSC_LOTE
                 WHERE PLT.QTD_TOTAL > RC.QTD ";

        $result = $this->_em->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);

        $return = null;
        if (!empty($result)) {
            $str = "";
            foreach ($result as $item) {
                $strLote = (!empty($item['DSC_LOTE'])) ? " Lote: $item[DSC_LOTE]" : "";
                $str[] = "Produto: $item[COD_PRODUTO] Grade: $item[DSC_GRADE]$strLote";
            }
            $return = "Existe itens em U.M.A.'s impressas ou já endereçadas com quantidade superior ao recebido, desfaça estas U.M.A.'s antes de finalizar o recebimento: " . implode(", ", $str);
        }
        return $return;
    }

    private function checkVolumesDivergentes($idRecebimento, $idOrdemServico, $idProduto, $dscGrade)
    {
        $sql = "SELECT DISTINCT PV.COD_PRODUTO, PV.DSC_GRADE
                FROM (SELECT PV1.COD_PRODUTO_VOLUME, 
                             RV.COD_OS, 
                             NVL(SUM(RV.QTD_CONFERIDA), 0) QTD_CONFERIDA, 
                             RV.COD_RECEBIMENTO
                        FROM PRODUTO_VOLUME PV1
                        LEFT JOIN RECEBIMENTO_VOLUME RV ON PV1.COD_PRODUTO_VOLUME = RV.COD_PRODUTO_VOLUME
                          AND RV.COD_RECEBIMENTO = $idRecebimento AND RV.COD_OS = $idOrdemServico AND PV1.DTH_INATIVACAO IS NULL
                        WHERE (PV1.COD_PRODUTO = '$idProduto' AND PV1.DSC_GRADE = '$dscGrade') 
                       GROUP BY  PV1.COD_PRODUTO_VOLUME, RV.COD_RECEBIMENTO, RV.COD_OS) RV 
                LEFT JOIN PRODUTO_VOLUME PV ON PV.COD_PRODUTO_VOLUME = RV.COD_PRODUTO_VOLUME
                GROUP BY PV.COD_PRODUTO, PV.DSC_GRADE HAVING COUNT(DISTINCT RV.QTD_CONFERIDA) > 1";

        $result = $this->_em->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);

        return (!empty($result));
    }

    public function getProdutosRecebidosComSenha($values)
    {

        $sql = $this->getEntityManager()->createQueryBuilder()
            ->select("r.id COD_RECEBIMENTO, p.id COD_PRODUTO, p.descricao DESCRICAO_PRODUTO, p.grade DSC_GRADE,
                             TO_CHAR(ra.dataValidade,'DD/MM/YYYY') DATA_VALIDADE_DIGITADA, ra.diasShelflife DIAS_SHELF_LIFE,
                             (ra.dataAndamento + ra.diasShelflife) - ra.dataValidade DIAS_DIFERENCA, 
                             (((ra.dataAndamento + ra.diasShelflife) - ra.dataValidade) * 100) / ra.diasShelflife PORCENTAGEM,
                              ra.qtdConferida QTD_CONFERIDA, conferente.nome USUARIO_CONFERENCIA, 
                              pessoa.nome USUARIO_LIBERACAO, ra.dscObservacao OBSERVACAO")
            ->from('wms:Recebimento', 'r')
            ->innerJoin('wms:Recebimento\Andamento', 'ra', 'WITH', 'r.id = ra.recebimento')
            ->innerJoin('wms:Produto', 'p', 'WITH', 'p.id = ra.codProduto AND p.grade = ra.dscGrade')
            ->innerJoin('wms:Pessoa', 'pessoa', 'WITH', 'pessoa.id = ra.usuario')
            ->innerJoin('wms:OrdemServico', 'os', 'WITH', 'os.recebimento = r.id')
            ->innerJoin('os.pessoa', 'conferente');

        if (isset($values['idRecebimento']) && ($values['idRecebimento'] != null)) {
            $sql->andWhere("r.id = '$values[idRecebimento]'");
        }

        if (isset($values['codProduto']) && ($values['codProduto'] != null)) {
            $sql->andWhere("p.id = '$values[codProduto]'");
        }

        if (isset($values['grade']) && ($values['grade'] != null)) {
            $sql->andWhere("p.grade = '$values[grade]'");
        }

        if (isset($values['dataInicial1']) && ($values['dataInicial1'] != null)) {
            $data = new \DateTime(str_replace("/", "-", $values['dataInicial1']));
            $sql->andWhere("TRUNC(r.dataInicial) >= ?2")
                ->setParameter(2, $data);

        }

        if (isset($values['dataInicial2']) && ($values['dataInicial2'] != null)) {
            $data = new \DateTime(str_replace("/", "-", $values['dataInicial2']));
            $sql->andWhere("TRUNC(r.dataInicial) <= ?3")
                ->setParameter(3, $data);
        }

        if (isset($values['dataFinal1']) && ($values['dataFinal1'] != null)) {
            $data = new \DateTime(str_replace("/", "-", $values['dataFinal1']));
            $sql->andWhere("TRUNC(r.dataFinal) >= ?3")
                ->setParameter(3, $data);
        }

        if (isset($values['dataFinal2']) && ($values['dataFinal2'] != null)) {
            $data = new \DateTime(str_replace("/", "-", $values['dataFinal2']));
            $sql->andWhere("TRUNC(r.dataFinal) <= ?4")
                ->setParameter(4, $data);
        }

        $result = $sql->getQuery()->getResult();

        return $result;
    }

    public function getQuantidadeConferidaBloqueada($idRecebimento = null)
    {
        $sql = $this->getEntityManager()->createQueryBuilder()
            ->select("(NVL(SUM(re.qtdBloqueada),0) + NVL(SUM(rv.qtdBloqueada),0)) qtdBloqueada, r.id codRecebimento,
                        re.id codRecebEmbalagem, rv.id codRecebVolume, p.descricao, p.id codProduto, p.grade, 
                        TO_CHAR(NVL(re.dataValidade, rv.dataValidade),'DD/MM/YYYY') dataValidade, p.diasVidaUtil,
                        TO_CHAR(NVL(((re.dataValidade) - (re.dataConferencia)), ((rv.dataValidade) - (rv.dataConferencia))), '999999') diasValidos,
                        FLOOR(((TO_CHAR( NVL( ((re.dataValidade) - (re.dataConferencia)), ((rv.dataValidade) - (rv.dataConferencia)) ), '999999') / p.diasVidaUtilMax) * 100)) percentualVidaUtil
                        ")
            ->from('wms:Recebimento', 'r')
            ->leftJoin('wms:Recebimento\Embalagem', 're', 'WITH', 're.recebimento = r.id AND re.dataValidade IS NOT NULL')
            ->leftJoin('wms:Recebimento\Volume', 'rv', 'WITH', 'rv.recebimento = r.id AND rv.dataValidade IS NOT NULL')
            ->leftJoin('re.embalagem', 'pe')
            ->leftJoin('rv.volume', 'pv')
            ->innerJoin('wms:Produto', 'p', 'WITH', '(p.id = pe.codProduto AND p.grade = pe.grade) OR (p.id = pv.codProduto AND p.grade = pv.grade)')
            ->where("p.validade = 'S'")
            ->groupBy('r.id, re.id, rv.id, p.descricao, p.id, p.grade, re.dataValidade, rv.dataValidade, p.diasVidaUtil, p.diasVidaUtilMax, re.dataConferencia, rv.dataConferencia')
            ->having('(NVL(SUM(re.qtdBloqueada),0) + NVL(SUM(rv.qtdBloqueada),0) > 0)');

        if ($idRecebimento)
            $sql->andWhere("r.id = $idRecebimento");

        return $sql->getQuery()->getResult();
    }

    /**
     * @param NotaFiscalEntity[] $arrNotas
     * @throws \Exception
     */
    public function liberaFaturamentoNotaErp($arrNotas)
    {
        $idIntegracao = $this->getSystemParameterValue('ID_INTEGRACAO_LIBERA_FATURAMENTO_NF_RECEBIMENTO_ERP');
        $formatoData = $this->getSystemParameterValue('FORMATO_DATA_ERP');

        /** @var \Wms\Domain\Entity\Integracao\AcaoIntegracaoRepository $acaoIntRepo */
        $acaoIntRepo = $this->getEntityManager()->getRepository('wms:Integracao\AcaoIntegracao');

        /** @var Usuario $usuario */
        $usuario = $this->_em->find('wms:Usuario', \Zend_Auth::getInstance()->getIdentity()->getId());

        /** @var AcaoIntegracao $acaoEn */
        $acaoEn = $acaoIntRepo->find($idIntegracao);
        if (!empty($acaoEn)) {
            foreach ($arrNotas as $nota) {
                $options = [];
                $options[] = $nota->getSerie();
                $options[] = $nota->getNumero();
                $options[] = $nota->getFornecedor()->getIdExterno();
                $options[] = date_format($nota->getDataEmissao(), $formatoData);
                $options[] = $usuario->getCodErp();

                $acaoIntRepo->processaAcao($acaoEn, $options, 'R', "P", null, 612);
            }
        }
    }

    public function getProdutosImprimirByRecebimento($idRecebimento)
    {
        $sql = "SELECT P.COD_PRODUTO,
                       P.DSC_GRADE,
                       P.DSC_PRODUTO,
                       SUM(NFI.QTD_ITEM) as QTD_ITEM,
                       P.COD_TIPO_COMERCIALIZACAO TIPO,
                       CASE WHEN I.COD_PRODUTO IS NOT NULL THEN 'S' ELSE 'N' END as IMPRIMIR
                  FROM NOTA_FISCAL NF
                  LEFT JOIN NOTA_FISCAL_ITEM NFI ON NF.COD_NOTA_FISCAL = NFI.COD_NOTA_FISCAL
                  LEFT JOIN PRODUTO P ON P.COD_PRODUTO = NFI.COD_PRODUTO 
                                     AND P.DSC_GRADE = NFI.DSC_GRADE
                  LEFT JOIN (SELECT DISTINCT COD_PRODUTO, DSC_GRADE FROM PRODUTO_EMBALAGEM WHERE IND_IMPRIMIR_CB = 'S'
                              UNION 
                             SELECT DISTINCT COD_PRODUTO, DSC_GRADE FROM PRODUTO_VOLUME WHERE IND_IMPRIMIR_CB = 'S') I
                    ON I.COD_PRODUTO = P.COD_PRODUTO
                   AND I.DSC_GRADE = P.DSC_GRADE
                 WHERE NF.COD_RECEBIMENTO = $idRecebimento
                 GROUP BY P.COD_PRODUTO,
                       P.DSC_GRADE,
                       P.DSC_PRODUTO,
                       I.COD_PRODUTO, 
                       P.COD_TIPO_COMERCIALIZACAO
                 ORDER BY P.COD_PRODUTO, P.DSC_GRADE";
        $produtos = $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        $embalagemRepo = $this->getEntityManager()->getRepository("wms:Produto\Embalagem");

        foreach ($produtos as $key => $produto) {
            if ($produto['TIPO'] == Produto::TIPO_UNITARIO) {
                $elements = [];
                /** @var ProdutoEntity\Embalagem[] $embs */
                $embs = $embalagemRepo->findBy(['codProduto' => $produto['COD_PRODUTO'], 'grade' => $produto['DSC_GRADE'], 'dataInativacao' => null, 'imprimirCB' => 'S']);

                foreach ($embs as $embalagem) {
                    if (!empty($embalagem->getCodigoBarras()))
                        $elements[$embalagem->getId()] = [
                            'dsc' => $embalagem->getDescricao() . "(" . $embalagem->getQuantidade() . ")",
                            'isDef' => ($embalagem->getIsPadrao() == 'S')
                        ];
                }

                if (empty($elements)) {
                    $produtos[$key]['error'] = 'Sem embalagem válida';
                } else {
                    $produtos[$key]['elements'] = $elements;
                }
            }
        }

        return $produtos;
    }

    public function atualizaRecebimentoBenner($idRecebimento)
    {
        $sql = "SELECT DISTINCT TR.RECEBIMENTOFISICOBENNER
                  FROM NOTA_FISCAL NF
                  LEFT JOIN FORNECEDOR F ON F.COD_FORNECEDOR = NF.COD_FORNECEDOR
                 INNER JOIN TR_NOTA_FISCAL_ENTRADA TR 
                    ON NF.NUM_NOTA_FISCAL = TR.NUM_NOTA_FISCAL
                   AND NF.COD_SERIE_NOTA_FISCAL = TR.COD_SERIE_NOTA_FISCAL
                   AND F.COD_EXTERNO = TR.COD_FORNECEDOR
                   AND TR.RECEBIMENTOFISICOBENNER IS NOT NULL
                 WHERE NF.COD_RECEBIMENTO = " . $idRecebimento;
        $idsBenner = $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);

        $idsArray = array();
        foreach ($idsBenner as $id) {
            $idsArray[] = $id['RECEBIMENTOFISICOBENNER'];
        }

        if (count($idsArray) > 0) {
            $ids = implode(",", $idsArray);

            /** @var \Wms\Domain\Entity\Integracao\ConexaoIntegracaoRepository $conexaoRepo */
            $conexaoRepo = $this->_em->getRepository('wms:Integracao\ConexaoIntegracao');
            $conexaoEn = $conexaoRepo->find(10);

            $UPDATE01 = "UPDATE CP_RECEBIMENTOFISICO SET STATUS = 6 WHERE STATUS = 5 AND HANDLE IN ($ids)";

            $UPDATE02 = "UPDATE CP_RECEBIMENTOFISICOPAI SET STATUS = 6 WHERE STATUS = 5 AND HANDLE IN (
                        SELECT RECEBIMENTOFISICOPAI FROM CP_RECEBIMENTOFISICO WHERE HANDLE IN ($ids))";

            $conexaoRepo->runQuery($UPDATE01, $conexaoEn, true);

            $conexaoRepo->runQuery($UPDATE02, $conexaoEn, true);
        }
    }

    public function executaIntegracaoBDFinalizacaoConferencia($idRecebimento)
    {

        $idsIntegracao = $this->getSystemParameterValue('ID_INTEGRACAO_FINALIZA_RECEBIMENTO_ERP');

        /** @var \Wms\Domain\Entity\Integracao\AcaoIntegracaoRepository $acaoIntRepo */
        $acaoIntRepo = $this->getEntityManager()->getRepository('wms:Integracao\AcaoIntegracao');
        /** @var \Wms\Domain\Entity\NotaFiscalRepository $notaFiscalRepository */
        $notaFiscalRepository = $this->getEntityManager()->getRepository('wms:NotaFiscal');

        $ids = explode(',', $idsIntegracao);
        sort($ids);

        foreach ($ids as $idIntegracao) {
            $acaoEn = $acaoIntRepo->find($idIntegracao);
            $options = array();

            $idTipoAcao = $acaoEn->getTipoAcao()->getId();
            if ($idTipoAcao == \Wms\Domain\Entity\Integracao\AcaoIntegracao::INTEGRACAO_FINALIZACAO_RECEBIMENTO_RETORNO_RECEBIMENTO_ERP) {

                /*
                 * Devolve o Retorno a integração a nível de recebimento do ERP
                 * ?1 - Código do Recebimento
                 */

                /** @var \Wms\Domain\Entity\NotaFiscal $notaFiscalEntity */
                $notaFiscalEntity = $notaFiscalRepository->findOneBy(array('recebimento' => $idRecebimento));
                $options = array(
                    0 => $notaFiscalEntity->getCodRecebimentoErp()
                );
                $resultAcao = $acaoIntRepo->processaAcao($acaoEn, $options, 'R', "P", null, 612);
                if (!$resultAcao === true) {
                    throw new \Exception($resultAcao);
                }

            }
            else if ($idTipoAcao == \Wms\Domain\Entity\Integracao\AcaoIntegracao::INTEGRACAO_FINALIZACAO_RECEBIMENTO_RETORNO_NOTA_FISCAL) {

                /*
                 * Devolve o Retorno a integração a nível de recebimento do ERP
                 * ?1 - Numero da Nota Fiscal
                 * ?2 - Série da Nota Fiscal
                 * ?3 - Código do Fornecedor
                 * ?4 - CNPJ do Fornecedor
                 * ?5 - Data de Emissão da Nota Fiscal
                 * ?6 - Código do Recebimento no ERP da Nota Fiscal
                 */

                $nfsEntity = $notaFiscalRepository->findBy(array('recebimento' => $idRecebimento));
                /** @var \Wms\Domain\Entity\NotaFiscal $notaFiscalEntity */
                foreach ($nfsEntity as $notaFiscalEntity) {
                    $options = array(
                        0 => $notaFiscalEntity->getNumero(),
                        1 => $notaFiscalEntity->getSerie(),
                        2 => $notaFiscalEntity->getFornecedor()->getIdExterno(),
                        3 => $notaFiscalEntity->getFornecedor()->getPessoa()->getCnpj(),
                        4 => $notaFiscalEntity->getDataEmissao()->format('Y-m-d H:i:s'),
                        5 => $notaFiscalEntity->getCodRecebimentoErp(),
                        6 => $notaFiscalEntity->getDivergencia(),
                        7 => $notaFiscalEntity->getRecebimento()->getId()
                    );
                    $resultAcao = $acaoIntRepo->processaAcao($acaoEn, $options, 'R', "P", null, 612);
                    if (!$resultAcao === true) {
                        throw new \Exception($resultAcao);
                    }
                    unset($options);
                }
            }
            else if ($idTipoAcao == \Wms\Domain\Entity\Integracao\AcaoIntegracao::INTEGRACAO_FINALIZACAO_RECEBIMENTO_RETORNO_ITEM_RECEBIMENTO) {

                /*
                 * Devolve o Retorno a integração a nível de recebimento do ERP
                 * ?1 - Código do Recebimento do ERP
                 * ?2 - Série da Nota Fiscal
                 * ?3 - Código do Fornecedor
                 * ?4 - CNPJ do Fornecedor
                 * ?5 - Data de Emissão da Nota Fiscal
                 * ?6 - Código do Recebimento no ERP da Nota Fiscal
                 */

                /** @var \Wms\Domain\Entity\Recebimento\ConferenciaRepository $conferenciaRepository */
                $conferenciaRepository = $this->getEntityManager()->getRepository('wms:Recebimento\Conferencia');
                $produtosConferidos = $conferenciaRepository->getProdutosByRecebimento($idRecebimento);

                foreach ($produtosConferidos as $produtoConferido) {
                    $dataValidade = null;
                    $dataConferencia = null;
                    if (isset($produtoConferido['dataValidade']) && !empty($produtoConferido['dataValidade'])) {
                        $dataValidade = $produtoConferido['dataValidade']->format('d/m/Y');
                    }
                    if (isset($produtoConferido['dataConferencia']) && !empty($produtoConferido['dataConferencia'])) {
                        $dataConferencia = $produtoConferido['dataConferencia']->format('d/m/Y');
                    }
                    $options = array(
                        0 => $codRecebimentoErp,
                        1 => $produtoConferido['codProduto'],
                        2 => $produtoConferido['quantidade'],
                        3 => $produtoConferido['qtdDivergencia'],
                        4 => $dataValidade,
                        5 => $dataConferencia,
                        6 => $produtoConferido['codigoBarras']
                    );
                    $resultAcao = $acaoIntRepo->processaAcao($acaoEn, $options, 'R', "P", null, 612);
                    if (!$resultAcao === true) {
                        throw new \Exception($resultAcao);
                    }
                    unset($options);
                }
            }
            else if ($idTipoAcao == \Wms\Domain\Entity\Integracao\AcaoIntegracao::INTEGRACAO_FINALIZACAO_RECEBIMENTO_RETORNO_ITEM_NOTA_FISCAL) {
                $nfsEntity = $notaFiscalRepository->findBy(array('recebimento' => $idRecebimento));
                /** @var \Wms\Domain\Entity\NotaFiscal $notaFiscalEntity */
                $wsNotaFiscal = new \Wms_WebService_NotaFiscal();

                foreach ($nfsEntity as $notaFiscalEntity) {
                    $nfResult = $wsNotaFiscal->buscarNf(
                            $notaFiscalEntity->getFornecedor()->getIdExterno(),
                            $notaFiscalEntity->getNumero(),
                            $notaFiscalEntity->getSerie(),
                            $notaFiscalEntity->getDataEmissao()
                    );
                    foreach ($nfResult->itens as $itemNf) {
                        $options = array(
                            0 => $notaFiscalEntity->getCodRecebimentoErp(),
                            1 => $itemNf->idProduto,
                            2 => $itemNf->grade,
                            3 => $itemNf->lote,
                            4 => $itemNf->quantidadeConferida,
                            5 => $itemNf->qtd,
                            6 => $itemNf->motivoDivergencia,
                            7 => $notaFiscalEntity->getNumero(),
                            8 => $notaFiscalEntity->getSerie(),
                            9 => $notaFiscalEntity->getDataEmissao(),
                            10 => $notaFiscalEntity->getFornecedor()->getIdExterno(),
                            11 => $notaFiscalEntity->getFornecedor()->getPessoa()->getCnpj()
                        );
                        $resultAcao = $acaoIntRepo->processaAcao($acaoEn, $options, 'R', "P", null, 612);
                        if (!$resultAcao === true) {
                            throw new \Exception($resultAcao);
                        }
                        unset($options);
                    }
                }
            }
        }

        return $resultAcao;

    }
}