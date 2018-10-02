<?php

use Wms\Module\Web\Controller\Action,
    Wms\Util\Coletor as ColetorUtil;

class Expedicao_CorteController extends Action {

    public function indexAction() {
        $id = $this->_getParam('id');
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

        if ($request->isPost()) {
            $senhaDigitada = $request->getParam('senhaConfirmacao');

            if ($EtiquetaRepo->checkAutorizacao($senhaDigitada)) {
                $codBarra = $request->getParam('codBarra');

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

                $EtiquetaRepo->cortar($etiquetaEntity);

                if ($etiquetaEntity->getProdutoEmbalagem() != NULL) {
                    $codBarrasProdutos = $etiquetaEntity->getProdutoEmbalagem()->getCodigoBarras();
                } else {
                    $codBarrasProdutos = $etiquetaEntity->getProdutoVolume()->getCodigoBarras();
                }

                /** @var \Wms\Domain\Entity\Expedicao\AndamentoRepository $andamentoRepo */
                $andamentoRepo = $this->_em->getRepository('wms:Expedicao\Andamento');
                $andamentoRepo->save("Etiqueta $codBarraFormatado cortada", $idExpedicao, false, true, $codBarraFormatado, $codBarrasProdutos);
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

                $senhaSistema = $this->getSystemParameterValue('SENHA_AUTORIZAR_DIVERGENCIA');
                if ($senha != $senhaSistema)
                    throw new \Exception("Senha Informada Inválida");

                $expedicaoRepo->cortarItemExpedicao($idProduto,$grade,$idExpedicao, $motivo);

                $this->getEntityManager()->flush();
                $this->getEntityManager()->commit();

                $this->addFlashMessage('success','Cortes efetivados com sucesso');
            } catch (\Exception $e) {
                $this->getEntityManager()->rollback();

                $this->addFlashMessage('error',$e->getMessage());
            }

            $this->redirect('corte-total-ajax','corte','expedicao',array('id'=> $idExpedicao));
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

    public function corteTotalAjaxAction() {

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

            foreach ($cortes as $corte) {
                $codPedido = $corte[0];
                $idEmbalagem = $corte[1];
                $quantidadeCortada = $corte[2];

                $motivoEn = $motivoRepo->find($idMotivo);

                $pedidoProdutoEn = $pedidoProdutoRepo->findOneBy(array(
                    'codPedido' => $codPedido,
                    'codProduto' => $idProduto,
                    'grade' => $grade

                ));

                $embalagemEn = null;
                if ($idEmbalagem >0 ) {
                    $embalagemEn = $embalagemRepo->find($idEmbalagem);
                }

                if (($idEmbalagem >0) && ($embalagemEn == null)) {
                    throw new \Exception("Embalagem id $idEmbalagem não encontrada");
                }

                if ($motivoEn == null) {
                    throw new \Exception("Motivo de corte id $idMotivo não encontrado");
                }

                if ($pedidoProdutoEn == null) {
                    throw new \Exception("PedidoProduto não encontrado para o produto $idProduto, $grade referente ao pedido interno $codPedido");
                }

                $qtdCortar = $quantidadeCortada * $embalagemEn->getQuantidade();
                $motivo = $motivoEn->getDscMotivo();

                $expedicaoRepo->cortaPedido($codPedido, $pedidoProdutoEn, $idProduto, $grade, $qtdCortar, $motivo);

            }

            $this->getEntityManager()->flush();
            $this->getEntityManager()->commit();

        } catch (\Exception $e) {
            $this->getEntityManager()->rollback();

            $this->_helper->json(array(
                'error' => $e->getMessage()
            ));
            return;
        }

        $this->_helper->json(array(
            'result' => true,
        ));

    }

    public function corteProdutoAjaxAction() {
        $this->view->id = $id = $this->_getParam('id');
        $grade = $this->_getParam('grade');
        $codProduto = $this->_getParam('codProduto');
        $actionAjax = $this->_getParam('acao');

        try {
            $permiteCortes = $this->getSystemParameterValue('PERMITE_REALIZAR_CORTES_WMS');
            $this->view->permiteCortes = $permiteCortes;

            $corteEmbalagemVenda = $this->getSystemParameterValue('MOVIMENTA_EMBALAGEM_VENDA_PEDIDO');

            /** @var \Wms\Domain\Entity\Expedicao\PedidoRepository $pedidoRepo */
            $pedidoRepo = $this->getEntityManager()->getRepository('wms:Expedicao\Pedido');

            if (($codProduto != null) && ($grade != null)) {
                $pedidos = $pedidoRepo->getPedidoByExpedicao($id, $codProduto, $grade, true);

                $grid = new \Wms\Module\Web\Grid\Expedicao\CorteProduto();
                $grid = $grid->init($pedidos, $id, $codProduto, $grade, $corteEmbalagemVenda);

                $this->arrCortes = $pedidos;
                $this->view->grid = $grid;

                $produtoEn = $this->getEntityManager()->getRepository('wms:Produto')->findOneBy(array('id'=> $codProduto, 'grade' => $grade));

                if ($produtoEn == null) {
                    throw new \Exception("Produto não encontrado");
                }

                $formMotivo = new \Wms\Module\Expedicao\Form\CorteProduto();
                $formMotivo->init();
                $formMotivo->setProduto($produtoEn);
                $this->view->formMotivo = $formMotivo;

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

    public function corteAntecipadoAjaxAction() {

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
        $this->view->pedido = $pedido = $this->_getParam('id', 0);
        $this->view->produto = $produto = $this->_getParam('COD_PRODUTO', 0);
        $this->view->grade = $grade = $this->_getParam('DSC_GRADE', 0);
        $this->view->expedicao = $expedicao = $this->_getParam('expedicao');
        $this->view->origin = $origin = $this->_getParam('origin');

        $quantidade = $this->_getParam('quantidade');
        $motivo = $this->_getParam('motivoCorte', null);

        $senha = $this->_getParam('senha');

        if (isset($senha) && !empty($senha) && isset($quantidade) && !empty($quantidade) && isset($motivo) && !empty($motivo)) {

            try {
                $this->getEntityManager()->beginTransaction();
                $senhaSistema = $this->getSystemParameterValue('SENHA_AUTORIZAR_DIVERGENCIA');
                if ($senha != $senhaSistema)
                    throw new \Exception("Senha Informada Inválida");

                $pedidoRepo = $this->getEntityManager()->getRepository('wms:Expedicao\Pedido');
                $pedido = $pedidoRepo->getMaxCodPedidoByCodExterno($pedido);
                /** @var \Wms\Domain\Entity\ExpedicaoRepository $expedicaoRepo */
                $expedicaoRepo = $this->getEntityManager()->getRepository('wms:Expedicao');
                $pedidoProduto = $this->getEntityManager()->getRepository('wms:Expedicao\PedidoProduto')
                        ->findOneBy(array('codPedido' => $pedido, 'codProduto' => $produto, 'grade' => $grade));

                if (!isset($pedidoProduto) || empty($pedidoProduto))
                    throw new \Exception("Produto $produto grade $grade não encontrado para o pedido $pedido");

                $expedicaoRepo->cortaPedido($pedido, $pedidoProduto, $pedidoProduto->getCodProduto(), $pedidoProduto->getGrade(), $quantidade, $motivo);

                $this->getEntityManager()->flush();
                $this->getEntityManager()->commit();
                $this->addFlashMessage('success', 'Produto ' . $produto . ' grade ' . $grade . ' pedido ' . $pedido . ' cortado com Sucesso');
            } catch (\Exception $e) {
                $this->getEntityManager()->rollback();
                $this->addFlashMessage('error', $e->getMessage());
            }

            if ($origin == 'ressuprimento') {
                $this->_redirect("/expedicao/corte/corte-antecipado-ajax/id/$expedicao/origin/ressuprimento");
            } else {
                $this->_redirect('/expedicao');
            }

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
