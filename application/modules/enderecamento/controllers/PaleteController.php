<?php
use Wms\Module\Web\Controller\Action,
    Wms\Module\Web\Page;

class Enderecamento_PaleteController extends Action
{

    /**
     * Ele ira gerar as u.m.a.s de acordo com a norma de paletização do produto informado e o recebimento
     */
    public function indexAction()
    {
        ini_set('max_execution_time', 3000);
        $idRecebimento  = $this->getRequest()->getParam('id');
        $codProduto     = $this->getRequest()->getParam('codigo');
        $grade          = $this->getRequest()->getParam('grade');
        $produtos       = $this->getRequest()->getParam('produtos');
        $grade          = urldecode($grade);

        /** @var \Wms\Domain\Entity\Enderecamento\PaleteRepository $paleteRepo */
        $paleteRepo = $this->em->getRepository('wms:Enderecamento\Palete');

        /** @var \Wms\Domain\Entity\ProdutoRepository $ProdutoRepository */
        $ProdutoRepository = $this->em->getRepository('wms:Produto');
        $produtoEspecifico = false;
        $quebraPorLote = ($this->getSystemParameterValue("QUEBRA_UMA_POR_LOTE") == 'S');

        if (!empty($codProduto) && !empty($grade)) {
            /** @var \Wms\Domain\Entity\Produto $produtoEn */
            $produtoEn = $ProdutoRepository->findOneBy(array('id' => $codProduto, 'grade' => $grade));
            $this->view->endPicking = $ProdutoRepository->getEnderecoPicking($produtoEn);
            $produtoEspecifico = true;

            try {
                $completaPicking = ($produtos) ? true : false;
                $paletes = $paleteRepo->getPaletes($quebraPorLote, $idRecebimento, $codProduto, $grade, true, $tipoEnderecamento = 'M');

                $idPaletes = array();
                $existeLote = false;
                foreach ($paletes as $palete) {
                    $idPaletes[] = $palete['UMA'];
                    if($palete['LOTE'] != null){
                        $existeLote = true;
                    }
                }
                if ($completaPicking) {
                    $paleteRepo->enderecaPicking($idPaletes, $completaPicking);
                    $paletes = $paleteRepo->getPaletes($quebraPorLote, $idRecebimento, $codProduto, $grade, true, $tipoEnderecamento = 'M');
                }

            } catch (Exception $e) {
                $this->addFlashMessage('error', $e->getMessage());
                $this->_redirect('/enderecamento/produto/index/id/' . $idRecebimento);
            }
            $this->view->isIndividual = true;
            $this->view->produto = $produtoEn->getDescricao();
            $this->view->codProduto = $codProduto;
            $this->view->grade = $grade;
            $this->view->paletes = $paletes;
            $this->view->existeLote = $existeLote;
        } else {
            /** @var \Wms\Domain\Entity\NotaFiscalRepository $notaFiscalRepo */
            $notaFiscalRepo = $this->em->getRepository('wms:NotaFiscal');
            $itens = isset($produtos) && !empty($produtos) ? $produtos : $notaFiscalRepo->getItensNotaByRecebimento($idRecebimento);

            try {
                $result = array();
                /** @var \Wms\Domain\Entity\NotaFiscal\Item $item */
                foreach ($itens as $item) {
                    $piece = null;
                    if ($produtos) {
                        $piece = explode('-', $item);
                    }
                    if (isset($piece) && !empty($piece)) {
                        $codProduto = $piece[0];
                        $grade = $piece[1];
                        $completaPicking = true;
                    } else {
                        $codProduto = $item['codProduto'];
                        $grade = $item['grade'];
                        $completaPicking = false;
                    }

                    /** @var \Wms\Domain\Entity\Produto $produtoEn */
                    $produtoEn = $ProdutoRepository->findOneBy(array('id' => $codProduto, 'grade' => $grade));
                    $arr = array();
                    $arr['codProduto'] = $codProduto;
                    $arr['grade'] = $grade;
                    $arr['descricao'] = $produtoEn->getDescricao();
                    $arr['endPicking'] = $ProdutoRepository->getEnderecoPicking($produtoEn);

                    $arr['paletes'] = $paleteRepo->getPaletes($quebraPorLote, $idRecebimento, $codProduto, $grade, true, $tipoEnderecamento = 'M');
                    $paletes = array();
                    $existeLote = false;
                    foreach ($arr['paletes'] as $palete) {
                        $paletes[] = $palete['UMA'];
                        if($palete['LOTE'] != null){
                            $existeLote = true;
                        }
                    }
                    if ($completaPicking) {
                        $paleteRepo->enderecaPicking($paletes, $completaPicking);
                        $arr['paletes'] = $paleteRepo->getPaletes($quebraPorLote, $idRecebimento, $codProduto, $grade, true, $tipoEnderecamento = 'M');
                    }
                    $result[] = $arr;
                }
            } catch (Exception $e) {
                $this->addFlashMessage('error', $e->getMessage());
                $this->_redirect('/enderecamento/produto/index/id/' . $idRecebimento);
            }

            $this->view->isIndivudal = false;
            $this->view->utilizaGrade = $this->getSystemParameterValue("UTILIZA_GRADE");
            $this->view->itens = $result;
            $this->view->existeLote = $existeLote;
        }

        $this->view->idRecebimento = $idRecebimento;
        $this->configurePage($idRecebimento, $produtoEspecifico);
    }

    /**
     * Se já estiver endereço deve mudar o status para STATUS_EM_ENDERECAMENTO
     */
    public function imprimirAction()
    {
        $embalagemRepo = $this->_em->getRepository("wms:Produto\Embalagem");
        $volumeRepo = $this->_em->getRepository("wms:Produto\Volume");
        $produtoRepo = $this->_em->getRepository('wms:Produto');

        $params = $this->_getAllParams();
        $paletes = $params['palete'];

        $PaleteRepository = $this->getEntityManager()->getRepository("wms:Enderecamento\Palete");

        $param = array();
        $paletesArray = array();

        $produtoEn = null;
        foreach ($paletes as $paleteId) {
            /** @var \Wms\Domain\Entity\Enderecamento\Palete $paleteEn */
            $paleteEn = $PaleteRepository->find($paleteId);
            $dadosPalete = array();
            $dadosPalete['idUma'] = $paleteId;
            $dadosPalete['lotes'] = [];
            $dadosPalete['qtd'] = 0;
            if ($paleteEn->getDepositoEndereco() != null) {
                $dadosPalete['endereco'] = $paleteEn->getDepositoEndereco()->getDescricao();
            } else {
                $dadosPalete['endereco'] = "";
            }

            if (empty($produtoEn)) {
                /** @var \Wms\Domain\Entity\Produto $produtoEn */
                $produtoEn = $paleteEn->getProdutos()[0]->getProduto();
            }
            //$produtoEn = $produtoRepo->findOneBy(array('id' => $params['codigo'], 'grade' => $params['grade']));

            //SE O PRODUTO TIVER PESO VARIAVEL CONSIDERA O PESO DO PALETE
            if ($produtoEn->getPossuiPesoVariavel() == 'S') {
                $dadosPalete['qtd'] = str_replace('.',',',$paleteEn->getPeso(). ' kg');
                $paletesEn = $paleteEn->getProdutos()->toArray();
            } else {
                $paletesEn = $paleteEn->getProdutos()->toArray();
                if ($produtoEn->getIndControlaLote() == 'S') {
                    /** @var \Wms\Domain\Entity\Enderecamento\PaleteProduto $umaProd */
                    foreach ($paletesEn as $umaProd){
                        $dadosPalete['qtd'] += $umaProd->getQtd();
                        $dadosPalete['lotes'][] = $umaProd->getLote();
                    }
                } else {
                    $dadosPalete['qtd'] = $paletesEn[0]->getQtd();
                }
            }

            if (($paletesEn[0]->getCodProdutoEmbalagem() == NULL)) {
                $embalagemEn = $volumeRepo->findOneBy(array('id'=> $paletesEn[0]->getCodProdutoVolume()));
            } else {
                $embalagemEn = $embalagemRepo->findOneBy(array('id'=> $paletesEn[0]->getCodProdutoEmbalagem()));
                $dadosPalete['unMedida'] = $embalagemEn->getDescricao();
                $dadosPalete['qtdEmbalagem'] = $embalagemEn->getQuantidade();
            }
            if ($embalagemEn->getEndereco() != null) {
                $dadosPalete['picking'] = $embalagemEn->getEndereco()->getDescricao();
            }
            $paletesArray[] = $dadosPalete;
        }

        $param['idRecebimento'] = $params['id'];
        $param['codProduto']    = $produtoEn->getId();
        $param['grade']         = $produtoEn->getGrade();
        $param['paletes']       = $paletesArray;

        /** @var \Wms\Domain\Entity\NotaFiscalRepository $notaFiscalRepo */
        $notaFiscalRepo = $this->em->getRepository('wms:NotaFiscal');
        $param['dataValidade'] = $notaFiscalRepo->buscaRecebimentoProduto($param['idRecebimento'], null, $param['codProduto'], $param['grade']);
        $param['notaFiscal'] = $notaFiscalRepo->findOneBy(array('recebimento' => $param['idRecebimento']));

        $Uma = new \Wms\Module\Enderecamento\Printer\UMA('L');
        $Uma->imprimir($param, $this->getSystemParameterValue("MODELO_RELATORIOS"));
    }

    public function relatorioAction()
    {
        $paletes = $this->_getParam('palete');
        $idRecebimento = $this->_getParam('id');
        $relatorio = new \Wms\Module\Enderecamento\Printer\RelatorioPaletes('L');

        if ($paletes == null) {
            $this->addFlashMessage('error','Nenhum palete selecionado para imprimir');
            $this->_redirect('/enderecamento/produto/index/id/'.$idRecebimento);
        }

        $relatorio->imprimir($paletes, $idRecebimento);
    }

    public function enderecarAction()
    {
        $this->view->id = $id = $this->_getParam('id');
        $this->view->codigo = $codigo = $this->_getParam('codigo');
        $this->view->grade = $grade = urldecode($this->_getParam('grade'));

        try {
            $this->em->beginTransaction();
            $usuarioRepo = $this->em->getRepository('wms:Usuario');
            $perfil = $this->getSystemParameterValue('COD_PERFIL_OPERADOR_EMPILHADEIRA');

            $this->view->conferentes = $usuarioRepo->getIdValueByPerfil($perfil);

            $paletes = $this->_getParam('palete', null);
            if ($paletes != null) {
                /**@var \Wms\Domain\Entity\Enderecamento\PaleteRepository $paleteRepo */
                $paleteRepo = $this->em->getRepository('wms:Enderecamento\Palete');

                $result = $paleteRepo->finalizar($paletes, $this->_getParam('idPessoa'));
                if ($result && !is_string($result)) {
                    $this->em->commit();
                    $this->addFlashMessage('success', 'Endereçamento finalizado com sucesso');

                    if ($this->getSystemParameterValue('IND_LIBERA_FATURAMENTO_NF_RECEBIMENTO_ERP') == 'S') {
                        if ($this->getSystemParameterValue('STATUS_RECEBIMENTO_ENDERECADO') == 'S') {
                            /** @var \Wms\Domain\Entity\RecebimentoRepository $recebimentoRepo */
                            $recebimentoRepo = $this->getEntityManager()->getRepository("wms:Recebimento");

                            if (empty($recebimentoRepo->checkRecebimentoEnderecado($id))) {
                                /** @var \Wms\Domain\Entity\NotaFiscal[] $arrNotasEn */
                                $arrNotasEn = $this->_em->getRepository("wms:NotaFiscal")->findBy(['recebimento' => $id]);
                                $recebimentoRepo->liberaFaturamentoNotaErp($arrNotasEn);
                            }
                        }
                    }

                    if (!empty($codigo) && !empty($grade)) {
                        $this->_redirect('enderecamento/palete/index/id/' . $id . '/codigo/' . $codigo . '/grade/' . urlencode($grade));
                    } else {
                        $this->_redirect('enderecamento/palete/index/id/' . $id);
                    }
                } else {
                    throw new Exception($result);
                }
            }
        } catch (Exception $e) {
            $this->addFlashMessage('info', 'Não foram feitos endereçamentos.' . $e->getMessage());
            $this->em->rollback();
            if (!empty($codigo) && !empty($grade)) {
                $this->_redirect('enderecamento/palete/index/id/' . $id . '/codigo/' . $codigo . '/grade/' . urlencode($grade));
            } else {
                $this->_redirect('enderecamento/palete/index/id/' . $id);
            }
        }
    }

    /**
     * @param $idRecebimento
     * @param $buttons
     */
    public function configurePage($idRecebimento, $produtoEspecifico)
    {
        $buttons[] = array(
            'label' => 'Voltar',
            'cssClass' => 'btnBack',
            'urlParams' => array(
                'module' => 'enderecamento',
                'controller' => 'produto',
                'action' => 'index',
                'id' => $idRecebimento
            ),
            'tag' => 'a'
        );

        $recebimentoEn = $this->getEntityManager()->getRepository("wms:Recebimento")->findOneBy(array('id'=>$idRecebimento));
        $cancelarPaletesParam = $this->_em->getRepository('wms:Sistema\Parametro')->findOneBy(array('constante' => 'CANCELA_PALETES_DESFAZER_RECEBIMENTO'));

        if ((($recebimentoEn->getStatus()->getId() == \Wms\Domain\Entity\Recebimento::STATUS_DESFEITO) && ($cancelarPaletesParam->getValor() != "S")) || (($recebimentoEn->getStatus()->getId() != \Wms\Domain\Entity\Recebimento::STATUS_DESFEITO) && ($recebimentoEn->getStatus()->getId() != \Wms\Domain\Entity\Recebimento::STATUS_CANCELADO))){
            $buttons[] = array(
                'label' => 'Endereçar no Picking',
                'cssClass' => 'button imprimir',
                'urlParams' => array(
                    'module' => 'enderecamento',
                    'controller' => 'palete',
                    'action' => 'picking',
                ),
                'tag' => 'a'
            );
            $buttons[] = array(
                'label' => 'Imprimir U.M.A.',
                'cssClass' => 'button imprimir',
                'urlParams' => array(
                    'module' => 'enderecamento',
                    'controller' => 'palete',
                    'action' => 'imprimir',
                ),
                'tag' => 'a'
            );
            $buttons[] = array(
                'label' => 'Relatório de Paletes',
                'cssClass' => 'button imprimir',
                'urlParams' => array(
                    'module' => 'enderecamento',
                    'controller' => 'palete',
                    'action' => 'relatorio',
                ),
                'tag' => 'a'
            );
            $buttons[] = array(
                'label' => 'Selecionar Endereço',
                'cssClass' => 'dialogAjax selecionar-endereco',
                'urlParams' => array(
                    'module' => 'enderecamento',
                    'controller' => 'endereco',
                    'action' => 'filtrar',
                    'origin' => 'enderecamentoPalete'
                ),
                'tag' => 'a'
            );
            $buttons[] = array(
                'label' => 'Confirmar Endereçamento',
                'cssClass' => 'dialogAjax',
                'urlParams' => array(
                    'module' => 'enderecamento',
                    'controller' => 'palete',
                    'action' => 'enderecar',
                ),
                'tag' => 'a'
            );
            if ($produtoEspecifico)
                $buttons[] = array(
                    'label' => 'Trocar U.M.A',
                    'urlParams' => array(
                        'module' => 'enderecamento',
                        'controller' => 'palete',
                        'action' => 'trocar'
                    ),
                    'tag' => 'a',
                );
        }


        Page::configure(array('buttons' => $buttons));
    }

    public function pickingAction()
    {
        $paletes       = $this->_getParam('palete');
        $idRecebimento = $this->_getParam('id');
        $codProduto    = $this->_getParam('codigo');
        $grade         = $this->_getParam('grade');

        /** @var \Wms\Domain\Entity\Enderecamento\PaleteRepository $paleteRepo */
        $paleteRepo = $this->_em->getRepository('wms:Enderecamento\Palete');
        try {
            $result = $paleteRepo->enderecaPicking($paletes);

            if ($result != "") {
                $this->addFlashMessage("info",$result);
            }
        } catch(Exception $e) {
            $this->addFlashMessage('error',$e->getMessage());
        }

        if ($codProduto && $grade) {
            $this->_redirect('enderecamento/palete/index/id/' . $idRecebimento . '/codigo/' . $codProduto . '/grade/' . urlencode($grade));
        } else {
            $this->_redirect('enderecamento/palete/index/id/' . $idRecebimento);
        }
    }

    public function desfazerAction()
    {
        $idPalete = $this->_getParam('id');
        $isIndividual = $this->_getParam('isIndividual');

        /** @var \Wms\Domain\Entity\Enderecamento\PaleteRepository $paleteRepo */
        $paleteRepo = $this->getEntityManager()->getRepository("wms:Enderecamento\Palete");

        $paleteEn = $paleteRepo->findOneBy(array('id'=> $idPalete));
        $idRecebimento = $paleteEn->getRecebimento()->getId();
        $produtosEn = $paleteEn->getProdutos();
        $codProduto = $produtosEn[0]->getCodProduto();
        $grade      = $produtosEn[0]->getGrade();
        $this->em->beginTransaction();
        try{
            $paleteRepo->desfazerPalete($idPalete);
        } catch(Exception $e) {
            $this->em->rollback();
            $this->addFlashMessage('error',$e->getMessage());
        }

        if ($isIndividual) {
            $this->_redirect('enderecamento/palete/index/id/' . $idRecebimento . '/codigo/' . $codProduto . '/grade/' . urlencode($grade));
        } else {
            $this->_redirect('enderecamento/palete/index/id/' . $idRecebimento);
        }

    }

    public function trocarAction()
    {
        $trocaUma = $this->_getParam('massaction-select', null);
        $params = $this->_getAllParams();
        $idRecebimento  = $this->getRequest()->getParam('id');
        $codProduto     = $this->getRequest()->getParam('codigo');
        $grade          = urldecode($this->getRequest()->getParam('grade'));

        if (!is_null($trocaUma)) {
            try {
                /** @var \Wms\Domain\Entity\NotaFiscalRepository $notaFiscalRepo */
                // verifica se novo recebimento possui o produto selecionado
                $notaFiscalRepo = $this->em->getRepository('wms:NotaFiscal');
                $result = $notaFiscalRepo->buscarItensPorNovoRecebimento($params['novo-recebimento-id'], $codProduto, $grade);

                if (count($result) > 0) {
                    // realizar trocas de U.M.As para novo recebimento
                    $this->confirmaTroca();
                    $this->addFlashMessage('success', 'A troca foi realizada com sucesso!');
                } else {
                    $this->addFlashMessage('info', 'Este produto não consta no recebimento: ' . $params['novo-recebimento-id']);
                }
            } catch (Exception $e) {
                $this->addFlashMessage('error', $e->getMessage());
            }

            $this->_redirect("/enderecamento/produto/index/id/$idRecebimento/codigo/$codProduto/grade/$params[grade]");
        }

        $grid = new \Wms\Module\Enderecamento\Grid\Trocar();

        if (isset($params['filtro-recebimento'])) {
            $this->view->ajaxFilter = true;
        }

        $this->view->grid = $grid->init($params);
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function confirmaTroca()
    {
        $params = $this->_getAllParams();
        $novoRecebimento = $params['novo-recebimento-id'];
        /** @var \Wms\Domain\Entity\Enderecamento\PaleteRepository $paleteRepo */
        $paleteRepo = $this->getEntityManager()->getRepository("wms:Enderecamento\Palete");
        /** @var \Wms\Domain\Entity\RecebimentoRepository $recebimentoRepo */
        $recebimentoRepo = $this->getEntityManager()->getRepository("wms:Recebimento");
        $existeRecebimento = $recebimentoRepo->find($novoRecebimento);

        $idRecebimentoAntigo = $params['id'];
        $codProduto          = $params['codigo'];
        $grade               = urldecode($params['grade']);

        if ($existeRecebimento == null) {
            throw new Exception('Recebimento inexistente!');
        }

        $paleteRepo->validaTroca($novoRecebimento,$codProduto,$grade);
        $paleteRepo->realizaTroca($novoRecebimento, $params['mass-id'], $idRecebimentoAntigo, $codProduto,$grade);
        $recebimentoRepo->gravarAndamento($novoRecebimento, "Troca UMA do Recb: $params[id] produto $params[codigo] - $params[grade]");

        return true;
    }

} 