<?php

use Wms\Module\Web\Controller\Action,
    Wms\Util\Coletor as ColetorUtil;

class Expedicao_CorteController extends Action {

    public function indexAction() {
        $id = $this->_getParam('id');
        $repoMotivos = $this->getEntityManager()->getRepository('wms:Expedicao\MotivoCorte');

        $this->view->motivos = $repoMotivos->getMotivos();
        /** @var \Wms\Domain\Entity\Expedicao\EtiquetaSeparacaoRepository $etiquetaRepo */
        $etiquetaRepo = $this->_em->getRepository('wms:Expedicao\EtiquetaSeparacao');
        $codEtiqueta = $etiquetaRepo->getEtiquetasByExpedicao($id, \Wms\Domain\Entity\Expedicao\EtiquetaSeparacao::STATUS_PENDENTE_CORTE, null);

        if (isset($codEtiqueta) && !empty($codEtiqueta)) {
            $this->view->codBarras = $codEtiqueta[0]['codBarras'];
        }
        //$this->view->codBarras = $codEtiqueta[0]['codBarras'];
    }

    public function salvarAction() {
        $request = $this->getRequest();
        $idExpedicao = $this->getRequest()->getParam('id');
        /** @var \Wms\Domain\Entity\Expedicao\EtiquetaSeparacaoRepository $EtiquetaRepo */
        $EtiquetaRepo = $this->_em->getRepository('wms:Expedicao\EtiquetaSeparacao');
        /** @var \Wms\Domain\Entity\Expedicao\EtiquetaSeparacaoReentregaRepository $EtiquetaReentregaRepo */
        $EtiquetaReentregaRepo = $this->_em->getRepository('wms:Expedicao\EtiquetaSeparacaoReentrega');
        $repoMotivos = $this->getEntityManager()->getRepository('wms:Expedicao\MotivoCorte');

        if ($request->isPost()) {
            $senhaDigitada = $request->getParam('senhaConfirmacao');

            if ($EtiquetaRepo->checkAutorizacao($senhaDigitada)) {
                $codBarra = $request->getParam('codBarra');

                $motivo = $this->_getParam('motivoCorte');
                $motivoEn = $repoMotivos->find($motivo);

                if (!$codBarra) {
                    $this->addFlashMessage('error', 'É necessário preencher todos os campos');
                    $this->_redirect('/expedicao');
                }
                $codBarraFormatado = ColetorUtil::retiraDigitoIdentificador($codBarra);
                $etiquetaEntity = $EtiquetaRepo->findOneBy(array('id' => $codBarraFormatado));
                if ($etiquetaEntity == null) {
                    $this->addFlashMessage('error', 'Etiqueta não encontrada');
                    $this->_redirect('/expedicao');
                }

                $encontrouEtiqueta = true;
                if ($etiquetaEntity->getPedido()->getCarga()->getExpedicao()->getId() != $idExpedicao) {
                    $encontrouEtiqueta = false;
                    $etiquetasReentrega = $EtiquetaReentregaRepo->findBy(array('codEtiquetaSeparacao' => $etiquetaEntity->getId()));
                    foreach ($etiquetasReentrega as $etiquetaReentregaEn) {
                        $idExpedicaoEtqReentrega = $etiquetaReentregaEn->getReentrega()->getCarga()->getExpedicao()->getId();
                        if ($idExpedicao == $idExpedicaoEtqReentrega) {
                            $encontrouEtiqueta = true;
                            continue;
                        }
                    }
                }

                if ($encontrouEtiqueta == false) {
                    $this->addFlashMessage('error', "A Etiqueta código $codBarraFormatado não pertence a expedição $idExpedicao");
                    $this->_redirect('/expedicao');
                }
                if ($etiquetaEntity->getCodStatus() == \Wms\Domain\Entity\Expedicao\EtiquetaSeparacao::STATUS_CORTADO) {
                    $this->addFlashMessage('error', "Etiqueta já cortada!");
                    $this->_redirect('/expedicao');
                }

                $EtiquetaRepo->cortar($etiquetaEntity, null, $motivoEn);

                if ($etiquetaEntity->getProdutoEmbalagem() != NULL) {
                    $codBarrasProdutos = $etiquetaEntity->getProdutoEmbalagem()->getCodigoBarras();
                } else {
                    $codBarrasProdutos = $etiquetaEntity->getProdutoVolume()->getCodigoBarras();
                }

                /** @var \Wms\Domain\Entity\Expedicao\AndamentoRepository $andamentoRepo */
                $andamentoRepo = $this->_em->getRepository('wms:Expedicao\Andamento');
                $andamentoRepo->save("Etiqueta $codBarraFormatado cortada - Motivo: " . $motivoEn->getDscMotivo(), $idExpedicao, false, true, $codBarraFormatado, $codBarrasProdutos);
                $this->addFlashMessage('success', 'Etiqueta cortada com sucesso');
            } else {
                $this->addFlashMessage('error', 'Senha informada não é válida');
            }
        }

        $this->_redirect('/expedicao/os/index/id/' . $idExpedicao);
    }

    public function confirmaCorteTotalAjaxAction () {

        /** @var \Wms\Domain\Entity\ExpedicaoRepository $expedicaoRepo */
        $expedicaoRepo = $this->getEntityManager()->getRepository("wms:Expedicao");
        $repoMotivos = $this->getEntityManager()->getRepository('wms:Expedicao\MotivoCorte');

        $this->view->motivos = $repoMotivos->getMotivos();
        $this->view->idProduto = $idProduto = $this->_getParam('COD_PRODUTO');
        $this->view->id = $idExpedicao = $this->_getParam('id');
        $this->view->grade     = $grade = $this->_getParam('DSC_GRADE');

        $produtoEn = $this->getEntityManager()->getRepository('wms:Produto')->findOneBy(array('id'=> $idProduto, 'grade' => $grade));

        $this->view->produto   = $produtoEn->getDescricao();
        $params = $this->_getAllParams();

        if (isset($params['submit'])) {
            try {
                $this->getEntityManager()->beginTransaction();

                $senha = $this->_getParam('senha');
                $motivo = $this->_getParam('motivoCorte');

                $motivoEn = $repoMotivos->find($motivo);

                $motivo = $motivoEn->getDscMotivo();
                $idMotivo = $motivoEn->getId();

                $senhaSistema = $this->getSystemParameterValue('SENHA_AUTORIZAR_DIVERGENCIA');
                if ($senha != $senhaSistema)
                    throw new \Exception("Senha Informada Inválida");

                $expedicaoRepo->cortarItemExpedicao($idProduto,$grade,$idExpedicao, $motivo, $idMotivo);

                $this->getEntityManager()->flush();
                $this->getEntityManager()->commit();

                $this->addFlashMessage('success','Cortes efetivados com sucesso');
            } catch (\Exception $e) {
                $this->getEntityManager()->rollback();

                $this->addFlashMessage('error',$e->getMessage());
            }

            $this->redirect('corte-total','corte','expedicao',array('id'=> $idExpedicao));
        }

        $pedidos = $expedicaoRepo->getPedidosByProdutoAndExpedicao($idExpedicao, $idProduto, $grade);

        $produtoCortado = true;
        foreach ($pedidos as $pedido) {
            if ($pedido['QTD'] > 0) {
                $produtoCortado = false;
            }
        }
        $this->view->produtoCortado = $produtoCortado;

        $grid = new \Wms\Module\Expedicao\Grid\PedidosCorteTotal();
        $this->view->grid = $grid->init($pedidos);

    }

    public function corteTotalAction() {

        $idExpedicao = $this->_getParam('id');

        /** @var \Wms\Domain\Entity\ExpedicaoRepository $expedicaoRepo */
        $expedicaoRepo = $this->getEntityManager()->getRepository("wms:Expedicao");

        if ($this->getSystemParameterValue('MOVIMENTA_EMBALAGEM_VENDA_PEDIDO') == 'S') {
            $produtos = $expedicaoRepo->getProdutosPorExpedicaoEmbVend($idExpedicao);
        } else {
            $produtos = $expedicaoRepo->getProdutosPorExpedicao($idExpedicao);
        }

        $grid = new \Wms\Module\Expedicao\Grid\CorteTotal();
        $this->view->grid = $grid->init($produtos);

    }

    public function confirmaCorteProdutoAjaxAction(){
        $idProduto = $this->_getParam('codProduto');
        $grade = $this->_getParam('grade');
        $idMotivo = $this->_getParam('motivo');
        $cortes = $this->_getParam('cortes');
        $embVendaDefault = $this->_getParam('embVendaDefault');

        try {
            $this->getEntityManager()->beginTransaction();

            /** @var \Wms\Domain\Entity\ExpedicaoRepository $expedicaoRepo */
            $expedicaoRepo = $this->getEntityManager()->getRepository("wms:Expedicao");
            /** @var \Wms\Domain\Entity\Expedicao\PedidoProdutoRepository $pedidoProdutoRepo */
            $pedidoProdutoRepo = $this->getEntityManager()->getRepository("wms:Expedicao\PedidoProduto");
            /** @var \Wms\Domain\Entity\Produto\EmbalagemRepository $embalagemRepoRepo */
            $embalagemRepo = $this->getEntityManager()->getRepository("wms:Produto\Embalagem");
            /** @var \Wms\Domain\Entity\Expedicao\MotivoCorteRepository $motivoRepo */
            $motivoRepo = $this->getEntityManager()->getRepository("wms:Expedicao\MotivoCorte");

            if (count($cortes) == 0) {
                throw new \Exception("Nenhum pedido informado para cortar");
            }

            $motivoEn = $motivoRepo->find($idMotivo);

            foreach ($cortes as $corte) {
                $codPedido = $corte[0];
                $idEmbalagem = $corte[1];
                $quantidadeCortada = $corte[2];
                $idMapa = $corte[3];

                $pedidoProdutoEn = $pedidoProdutoRepo->findOneBy(array(
                    'codPedido' => $codPedido,
                    'codProduto' => $idProduto,
                    'grade' => $grade

                ));

                if ($idEmbalagem >0 ) {
                    $embalagemEn = $embalagemRepo->find($idEmbalagem);
                    if ($embalagemEn == null) {
                        throw new \Exception("Embalagem id $idEmbalagem não encontrada");
                    }

                    $qtdCortar = $quantidadeCortada * $embalagemEn->getQuantidade();

                } else {
                    $qtdCortar = $quantidadeCortada;
                }

                if ($motivoEn == null) {
                    throw new \Exception("Motivo de corte id $idMotivo não encontrado");
                }

                if ($pedidoProdutoEn == null) {
                    throw new \Exception("PedidoProduto não encontrado para o produto $idProduto, $grade referente ao pedido interno $codPedido");
                }


                $motivo = $motivoEn->getDscMotivo();

                $retornoCorte = $expedicaoRepo->cortaPedido($codPedido, $pedidoProdutoEn, $idProduto, $grade, $qtdCortar, $motivo, NULL,$idMotivo, $idMapa, $idEmbalagem, $embVendaDefault);
                if (is_string($retornoCorte))
                    throw new \Exception($retornoCorte);

            }

            $this->getEntityManager()->flush();
            $this->getEntityManager()->commit();

        } catch (\Exception $e) {
            $this->getEntityManager()->rollback();

            /** @var \Wms\Domain\Entity\Expedicao\AndamentoRepository $andamentoRepo */
            $andamentoRepo = $this->_em->getRepository('wms:Expedicao\Andamento');
            $andamentoEntity = $andamentoRepo->findOneBy(array('expedicao' => $pedidoProdutoEn->getPedido()->getCarga()->getExpedicao()->getId(), 'erroProcessado' => 'N'));

            $query = "UPDATE EXPEDICAO_ANDAMENTO SET IND_ERRO_PROCESSADO = 'S' WHERE COD_EXPEDICAO = " . $pedidoProdutoEn->getPedido()->getCarga()->getExpedicao()->getId();
            $this->_em->getConnection()->query($query)->execute();

            $this->_helper->json(array(
                'error' => $e->getMessage()
            ));
            return;
        }

        $this->_helper->json(array(
            'result' => true,
        ));

    }

    public function corteProdutoAction() {
        $this->view->id = $id = $this->_getParam('id');
        $grade = $this->_getParam('grade');
        $codProduto = $this->_getParam('codProduto');
        $actionAjax = $this->_getParam('acao');

        try {
            $this->view->permiteCortes = $this->getSystemParameterValue('PERMITE_REALIZAR_CORTES_WMS');
            /** @var \Wms\Domain\Entity\Expedicao\ModeloSeparacao $modeloSeparacaoEn */
            $modeloSeparacaoEn = $this->em->getRepository("wms:Expedicao\ModeloSeparacao")->getModeloSeparacao($id);
            $this->view->forcaEmbVenda = $forcaEmbVenda = $modeloSeparacaoEn->getForcarEmbVenda();

            if (($codProduto != null) && ($grade != null)) {

                $produtoEn = $this->getEntityManager()->getRepository('wms:Produto')->findOneBy(array('id'=> $codProduto, 'grade' => $grade));

                if ($produtoEn == null) {
                    throw new \Exception("Produto não encontrado");
                }

                $formMotivo = new \Wms\Module\Expedicao\Form\CorteProduto();
                $formMotivo->init();
                $formMotivo->setProduto($produtoEn);
                $this->view->formMotivo = $formMotivo;


                /** @var \Wms\Domain\Entity\Expedicao\PedidoRepository $pedidoRepo */
                $pedidoRepo = $this->getEntityManager()->getRepository('wms:Expedicao\Pedido');
                $pedidos = $pedidoRepo->getPedidoByExpedicao($id, $codProduto, $grade, true);

                $grid = new \Wms\Module\Web\Grid\Expedicao\CorteProduto();

                $grid = $grid->init($pedidos, $codProduto, $grade, (!empty($produtoEn->getForcarEmbVenda()) ? $produtoEn->getForcarEmbVenda() : $forcaEmbVenda));

                $this->arrCortes = $pedidos;
                $this->view->grid = $grid;
            }

            $form = new \Wms\Module\Web\Form\CortePedido();
            $this->view->form = $form;

        } catch (\Exception $e) {
            if (!empty($actionAjax)) {
                $this->_helper->json(array(
                    'error' => $e->getMessage()
                ));
            }
            return;
        }

        if (!empty($actionAjax)) {
            $this->_helper->json(array(
                'resultGrid' => $grid->render(),
                'resultForm' => $formMotivo->render(),
                'pedidos' => $this->html_table($pedidos)
            ));
        }
    }

    function html_table($data = array())
    {
        $rows = array();
        foreach ($data as $row) {
            $cells = array();
            foreach ($row as $cell) {
                $cells[] = "<td>{$cell}</td>";
            }
            $rows[] = "<tr class='grid-corte-resumo' style='display:none' >" . implode('', $cells) . "</tr>";

        }
        return "<table class='hci-table'>" . implode('', $rows) . "</table>";
    }

    public function cortePedidoAction() {

        $this->view->id = $id = $this->_getParam('id');
        $grade = $this->_getParam('grade');
        $codProduto = $this->_getParam('codProduto');
        $actionAjax = $this->_getParam('acao');

        $permiteCortes = $this->getSystemParameterValue('PERMITE_REALIZAR_CORTES_WMS');
        $this->view->permiteCortes = $permiteCortes;
        $this->view->idMapa = $idMapa = $this->_getParam('COD_MAPA_SEPARACAO', null);

        /** @var \Wms\Domain\Entity\Expedicao\MapaSeparacaoPedidoRepository $mapaSeparacaoRepo */
        $mapaSeparacaoRepo = $this->getEntityManager()->getRepository('wms:Expedicao\MapaSeparacaoPedido');
        /** @var \Wms\Domain\Entity\Expedicao\PedidoRepository $pedidoRepo */
        $pedidoRepo = $this->getEntityManager()->getRepository('wms:Expedicao\Pedido');

        if (isset($idMapa) && !empty($idMapa))
            $pedidos = $mapaSeparacaoRepo->getPedidosByMapa($idMapa, $codProduto, $grade);
        else
            $pedidos = $pedidoRepo->getPedidoByExpedicao($id, $codProduto, $grade);

        $grid = new \Wms\Module\Web\Grid\Expedicao\CortePedido();
        $grid = $grid->init($pedidos, $id);
        $this->view->grid = $grid;

        $form = new \Wms\Module\Web\Form\CortePedido();
        $this->view->form = $form;

        if (!empty($actionAjax)) {
            $this->_helper->json(array('result' => $grid->render()));
        }
    }

    public function listAction() {
        $idExpedicao = $this->_getParam('expedicao');

        /** @var \Wms\Domain\Entity\ExpedicaoRepository $expedicaoRepo */
        $expedicaoRepo = $this->getEntityManager()->getRepository("wms:Expedicao");
        $pedidoRepo = $this->getEntityManager()->getRepository('wms:Expedicao\Pedido');
        $idPedido = $pedidoRepo->getMaxCodPedidoByCodExterno($this->_getParam('id', 0));
        $produtos = $expedicaoRepo->getProdutosExpedicaoCorte($idPedido,null, false);

        $grid = new \Wms\Module\Web\Grid\Expedicao\CorteAntecipado();
        $this->view->grid = $grid->init($produtos, $this->_getParam('id', 0), $idExpedicao);
    }

    public function cortarItemAction() {
        $repoMotivos = $this->getEntityManager()->getRepository('wms:Expedicao\MotivoCorte');

        $this->view->motivos = $repoMotivos->getMotivos();
        $this->view->pedido = $pedido = $this->_getParam('id', 0);
        $this->view->produto = $produto = $this->_getParam('COD_PRODUTO', 0);
        $this->view->grade = $grade = $this->_getParam('DSC_GRADE', 0);
        $this->view->expedicao = $expedicao = $this->_getParam('expedicao');
        $this->view->origin = $origin = $this->_getParam('origin');
        $quantidade = $this->_getParam('quantidade');
        $this->view->mapaPreSelected = $mapaPreSelected = $this->_getParam('COD_MAPA_SEPARACAO', null);
        $motivo = $this->_getParam('motivoCorte', null);
        $mapa = $this->_getParam('mapa', null);
        $pedidoRepo = $this->getEntityManager()->getRepository('wms:Expedicao\Pedido');
        $idPedido = $pedidoRepo->getMaxCodPedidoByCodExterno($pedido);
        $senha = $this->_getParam('senha');

        if (isset($senha) && !empty($senha) && isset($quantidade) && !empty($quantidade) && isset($motivo) && !empty($motivo)) {

            try {
                $motivoEn = $repoMotivos->find($motivo);
                $motivo = $motivoEn->getDscMotivo();
                $idMotivo = $motivoEn->getId();

                $this->getEntityManager()->beginTransaction();
                $senhaSistema = $this->getSystemParameterValue('SENHA_AUTORIZAR_DIVERGENCIA');
                if ($senha != $senhaSistema)
                    throw new \Exception("Senha Informada Inválida");


                /** @var \Wms\Domain\Entity\ExpedicaoRepository $expedicaoRepo */
                $expedicaoRepo = $this->getEntityManager()->getRepository('wms:Expedicao');
                $pedidoProduto = $this->getEntityManager()->getRepository('wms:Expedicao\PedidoProduto')
                        ->findOneBy(array('codPedido' => $idPedido, 'codProduto' => $produto, 'grade' => $grade));

                if (!isset($pedidoProduto) || empty($pedidoProduto))
                    throw new \Exception("Produto $produto grade $grade não encontrado para o pedido $pedido");

                $expedicaoRepo->cortaPedido($idPedido, $pedidoProduto, $pedidoProduto->getCodProduto(), $pedidoProduto->getGrade(), $quantidade, $motivo, null, $idMotivo, (!empty($mapa))? $mapa : $mapaPreSelected);

                $this->getEntityManager()->flush();
                $this->getEntityManager()->commit();
                $this->addFlashMessage('success', 'Produto ' . $produto . ' grade ' . $grade . ' pedido ' . $pedido . ' cortado com Sucesso');
                if (empty($mapaPreSelected)) {
                    $this->redirect("index", "index", "expedicao");
                } else {
                    $this->redirect("index", "os", "expedicao", ["id"=> $expedicao]);
                }
            } catch (\Exception $e) {
                $this->getEntityManager()->rollback();
                $this->addFlashMessage('error', $e->getMessage());
            }

        } else {
            if (empty($mapaPreSelected))
                $this->view->mapas = $this->em->getRepository("wms:Expedicao\MapaSeparacaoPedido")->getMapaByPedidoProduto($idPedido, $produto, $grade);
        }
    }

    public function relatorioCorteAjaxAction() {
        /** @var \Wms\Domain\Entity\ExpedicaoRepository $expedicaoRepo */
        $expedicaoRepo = $this->getEntityManager()->getRepository("wms:Expedicao");
        $expedicao = $this->_getParam("id");

        $pedidosCortados = $expedicaoRepo->getCortePedido($expedicao);
        $this->exportPDF($pedidosCortados, 'relatorio-corte', 'Cortes automáticos da onda de ressuprimento', 'P');
    }

    public function relatorioAction() {
        try {
            $form = new \Wms\Module\Expedicao\Form\RelatoriosCorte();
            $this->view->form = $form;

            /** @var \Wms\Domain\Entity\ExpedicaoRepository $expedicaoRepo */
            $expedicaoRepo = $this->getEntityManager()->getRepository("wms:Expedicao");

            $params = $this->_getAllParams();
            unset($params['module']);
            unset($params['controller']);
            unset($params['action']);

            if (!empty($params)) {
                $form->populate($params);

                $valores = $expedicaoRepo->getPedidosCortadosByParams($params);

                if (isset($params['pdf']) && ($params['pdf'] != null)) {
                    $report = array();
                    foreach ($valores as $value) {
                        $report[] = array(
                            'Carga' => $value['COD_CARGA_EXTERNO'],
                            'Pedido' => $value['COD_PEDIDO'],
                            'Cod.Cli.' => $value['COD_CLIENTE'],
                            'Cliente' => $value['CLIENTE'],
                            'Cod.Prod.' => $value['COD_PRODUTO'],
                            'Produto' => $value['DSC_PRODUTO'],
                            'Qtd.Ped.' => $value['QUANTIDADE'],
                            'Qtd.Cort.' => $value['QTD_CORTADA'],
                            'Qtd.At.' => $value['QTD_ATENDIDA']
                        );
                    }

                    $this->exportPDF($report,'cortes','Relatório de Cortes', 'L');
                } else {
                    $grid = new \Wms\Module\Expedicao\Grid\RelatorioCorte();
                    $this->view->grid = $grid->init($valores);
                }


            }


        } catch (\Exception $e) {
            $this->addFlashMessage('error', $e->getMessage());
        }

    }
}
