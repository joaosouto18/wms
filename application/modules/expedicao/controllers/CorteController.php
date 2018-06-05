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
                $this->_redirect('/expedicao');
            } catch (\Exception $e) {
                $this->getEntityManager()->rollback();
                $this->addFlashMessage('error', $e->getMessage());
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
