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
        $idRecebimento  = $this->getRequest()->getParam('id');
        $codProduto     = $this->getRequest()->getParam('codigo');
        $grade          = $this->getRequest()->getParam('grade');

        /** @var \Wms\Domain\Entity\Enderecamento\PaleteRepository $paleteRepo */
        $paleteRepo    = $this->em->getRepository('wms:Enderecamento\Palete');
        $produtoEn = $this->em->getRepository("wms:Produto")->findOneBy(array('id'=>$codProduto,'grade'=>$grade));
        /** @var \Wms\Domain\Entity\ProdutoRepository $ProdutoRepository */
        $ProdutoRepository   = $this->em->getRepository('wms:Produto');
        $this->view->endPicking = $ProdutoRepository->getEnderecoPicking($produtoEn);

        try {
            $paletes = $paleteRepo->getPaletes($idRecebimento,$codProduto,$grade);
        } catch(Exception $e) {
                $this->addFlashMessage('error',$e->getMessage());
            $this->_redirect('/enderecamento/produto/index/id/'.$idRecebimento);
        }

        $this->configurePage($idRecebimento);
        $this->view->produto      = $produtoEn->getDescricao();
        $this->view->codProduto    = $codProduto;
        $this->view->grade         = $grade;
        $this->view->paletes       = $paletes;
        $this->view->idRecebimento = $idRecebimento;
    }

    /**
     * Se já estiver endereço deve mudar o status para STATUS_EM_ENDERECAMENTO
     */
    public function imprimirAction()
    {
        $embalagemRepo = $this->_em->getRepository("wms:Produto\Embalagem");
        $volumeRepo = $this->_em->getRepository("wms:Produto\Volume");

        $params = $this->_getAllParams();
        $paletes = $params['palete'];

        $PaleteRepository = $this->getEntityManager()->getRepository("wms:Enderecamento\Palete");

        $param = array();
        $paletesArray = array();
        foreach ($paletes as $paleteId) {
            $paleteEn = $PaleteRepository->find($paleteId);

            $dadosPalete = array();
            $dadosPalete['idUma'] = $paleteId;
            if ($paleteEn->getDepositoEndereco() != null) {
                $dadosPalete['endereco'] = $paleteEn->getDepositoEndereco()->getDescricao();
            } else {
                $dadosPalete['endereco'] = "";
            }

            $paleteEn = $paleteEn->getProdutos();

            $dadosPalete['qtd'] = $paleteEn[0]->getQtd();
            if (($paleteEn[0]->getCodProdutoEmbalagem() == NULL)) {
                $embalagemEn = $volumeRepo->findOneBy(array('id'=> $paleteEn[0]->getCodProdutoVolume()));
            } else {
                $embalagemEn = $embalagemRepo->findOneBy(array('id'=> $paleteEn[0]->getCodProdutoEmbalagem()));
            }
            if ($embalagemEn->getEndereco() != null) {
                $dadosPalete['picking'] = $embalagemEn->getEndereco()->getDescricao();
            }

            $paletesArray[] = $dadosPalete;
        }

        $param['idRecebimento'] = $params['id'];
        $param['codProduto']    = $params['codigo'];
        $param['grade']         = $params['grade'];
        $param['paletes']        = $paletesArray;

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
        $usuarioRepo = $this->em->getRepository('wms:Usuario');
        $perfilParam = $this->_em->getRepository('wms:Sistema\Parametro')->findOneBy(array('constante' => 'COD_PERFIL_OPERADOR_EMPILHADEIRA'));

        $this->view->conferentes = $usuarioRepo->getIdValueByPerfil($perfilParam->getValor());

        $this->view->id      = $id         = $this->_getParam('id');
        $this->view->codigo  = $codigo     = $this->_getParam('codigo');
        $this->view->grade   = $grade      = urldecode($this->_getParam('grade'));

        $paletes = $this->_getParam('palete', null);
        if ($paletes != null) {
            /** @var \Wms\Domain\Entity\Enderecamento\PaleteRepository $paleteRepo */
            $paleteRepo = $this->em->getRepository('wms:Enderecamento\Palete');
            if ($paleteRepo->finalizar($paletes, $this->_getParam('idPessoa'))) {
                $this->addFlashMessage('success', 'Endereçamento finalizado com sucesso');
            } else {
                $this->addFlashMessage('info', 'Não foram feitos endereçamentos');
            }
            $this->_redirect('enderecamento/palete/index/id/'.$id.'/codigo/'.$codigo.'/grade/'. urlencode($grade));
        }
    }

    /**
     * @param $idRecebimento
     * @param $buttons
     */
    public function configurePage($idRecebimento)
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
            $buttons[] = array(
                'label' => 'Trocar U.M.A',
                'cssClass' => 'dialogAjax',
                'urlParams' => array(
                    'module' => 'enderecamento',
                    'controller' => 'palete',
                    'action' => 'trocar',
                ),
                'tag' => 'a'
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

        $paleteRepo = $this->_em->getRepository('wms:Enderecamento\Palete');
        try {
            $result = $paleteRepo->enderecaPicking($paletes);

            if ($result != "") {
                $this->addFlashMessage("info",$result);
            }
        } catch(Exception $e) {
            $this->addFlashMessage('error',$e->getMessage());
        }

        $this->_redirect('enderecamento/palete/index/id/'.$idRecebimento.'/codigo/'.$codProduto.'/grade/'.urlencode($grade));
    }

    public function desfazerAction()
    {
        $idPalete = $this->_getParam('id');

        /** @var \Wms\Domain\Entity\Enderecamento\PaleteRepository $paleteRepo */
        $paleteRepo = $this->getEntityManager()->getRepository("wms:Enderecamento\Palete");

        $paleteEn = $paleteRepo->findOneBy(array('id'=> $idPalete));
        $idRecebimento = $paleteEn->getRecebimento()->getId();
        $produtosEn = $paleteEn->getProdutos();
        $codProduto = $produtosEn[0]->getCodProduto();
        $grade      = $produtosEn[0]->getGrade();

        try{
            $paleteRepo->desfazerPalete($idPalete);
        } catch(Exception $e) {
            $this->addFlashMessage('error',$e->getMessage());
        }

        $this->_redirect('enderecamento/palete/index/id/'.$idRecebimento.'/codigo/'.$codProduto.'/grade/'.urlencode($grade));

    }

    public function trocarAction()
    {
        $trocaUma = $this->_getParam('massaction-select', null);
        $params = $this->_getAllParams();
        $idRecebimento  = $this->getRequest()->getParam('id');
        $codProduto     = $this->getRequest()->getParam('codigo');

        if (!is_null($trocaUma)) {
            /** @var \Wms\Domain\Entity\NotaFiscalRepository $notaFiscalRepo */
            // verifica se novo recebimento possui o produto selecionado
            $notaFiscalRepo = $this->em->getRepository('wms:NotaFiscal');
            $result = $notaFiscalRepo->buscarItensPorNovoRecebimento($params['novo-recebimento-id'], $codProduto);

            if (count($result) == 0) {
                // realizar trocas de U.M.As para novo recebimento
                $result = $this->confirmaTroca();

                if ($result) {
                    $this->addFlashMessage('info', $result);
                    $this->_redirect('/enderecamento/produto/index/id/' . $idRecebimento);
                }
            } else {
                $this->addFlashMessage('info', 'Este produto já consta no novo recebimento!');
                $this->_redirect('/enderecamento/produto/index/id/' . $idRecebimento);
            }
        }

        $grid = new \Wms\Module\Enderecamento\Grid\Trocar();

        if (isset($params['filtro-recebimento'])) {
            $this->view->ajaxFilter = true;
        }

        $this->view->grid = $grid->init($params);
    }

    public function confirmaTroca()
    {
        $params = $this->_getAllParams();
        $novoRecebimento = $params['novo-recebimento-id'];
        /** @var \Wms\Domain\Entity\Enderecamento\PaleteRepository $paleteRepo */
        $paleteRepo = $this->getEntityManager()->getRepository("wms:Enderecamento\Palete");
        /** @var \Wms\Domain\Entity\RecebimentoRepository $recebimentoRepo */
        $recebimentoRepo = $this->getEntityManager()->getRepository("wms:Recebimento");
        $existeRecebimento = $recebimentoRepo->find($novoRecebimento);

        if ($existeRecebimento == null) {
            return 'Recebimento inexistente!';
        }

        if ($paleteRepo->realizaTroca($novoRecebimento, $params['mass-id'])) {
            $this->addFlashMessage('success', 'Troca realizada com sucesso');
        }

        $url = '/enderecamento/produto/index/id/' . $params['id'] . '/codigo/' . $params['codigo'] . '/grade/' . urlencode($params['grade']);
        $this->_redirect($url);
        exit;
    }

} 