<?php

use Wms\Module\Web\Controller\Action,
    Wms\Module\Expedicao\Grid\OsRessuprimento as OsGrid,
    Wms\Module\Web\Form\Relatorio\Ressuprimento\FiltroDadosOnda,
    Wms\Module\Web\Form\Subform\FiltroExpedicaoMercadoria;

class Expedicao_OndaRessuprimentoController extends Action
{

    public function indexAction()
    {
        $em = $this->getEntityManager();
        $parametroPedidosTelaExpedicao = $this->getSystemParameterValue('COD_INTEGRACAO_PEDIDOS_TELA_EXP');
        //INTEGRAR CARGAS NO MOMENTO Q ENTRAR NA TELA DE EXPEDICAO
        if (isset($parametroPedidosTelaExpedicao) && !empty($parametroPedidosTelaExpedicao)) {
            $explodeIntegracoes = explode(',', $parametroPedidosTelaExpedicao);

            /** @var \Wms\Domain\Entity\Integracao\AcaoIntegracaoRepository $acaoIntegracaoRepository */
            $acaoIntegracaoRepository = $em->getRepository('wms:Integracao\AcaoIntegracao');
            foreach ($explodeIntegracoes as $codIntegracao) {
                $acaoIntegracaoEntity = $acaoIntegracaoRepository->find($codIntegracao);
                $acaoIntegracaoRepository->processaAcao($acaoIntegracaoEntity,null,'E','P',null, \Wms\Domain\Entity\Integracao\AcaoIntegracaoFiltro::DATA_ESPECIFICA);
            }
        }

        $form = new FiltroExpedicaoMercadoria;
        $form->init("/expedicao/onda-ressuprimento");
        $this->view->form = $form;

        $params = $form->getParams();
        if (!$params) {
            $dataI1 = new \DateTime;
            $params = array(
                'dataInicial1' => $dataI1->format('d/m/Y'),
                'dataInicial2' => $dataI1->format('d/m/Y')
            );
            $form->populate($params);
        }
        /** @var \Wms\Domain\Entity\ExpedicaoRepository $expedicaoRepo */
        $expedicaoRepo = $this->getEntityManager()->getRepository("wms:Expedicao");
        $expedicoes = $expedicaoRepo->getExpedicaoSemOndaByParams($params);
        $this->view->expedicoes = $expedicoes;
    }

    public function semDadosAction()
    {
        $strExpedicao = $this->_getParam("expedicoes");

        /** @var \Wms\Domain\Entity\ProdutoRepository $produtoRepo */
        $produtoRepo = $this->getEntityManager()->getRepository("wms:Produto");
        $produtosSemPicking = $produtoRepo->getProdutosSemPickingByExpedicoes($strExpedicao);
        $this->exportPDF($produtosSemPicking, 'Produtos-sem-picking', 'Produtos Sem Picking - Expedições: ' . $strExpedicao, 'P');
    }

    public function relatorioSemEstoqueAjaxAction()
    {
        /** @var \Wms\Domain\Entity\ExpedicaoRepository $expedicaoRepo */
        $expedicaoRepo = $this->getEntityManager()->getRepository("wms:Expedicao");
        $expedicoes = $this->_getParam("expedicoes");

        $verificaDisponibilidadeEstoquePedido = $expedicaoRepo->verificaDisponibilidadeEstoquePedido($expedicoes);
        $this->exportPDF($verificaDisponibilidadeEstoquePedido, 'sem-estoque', 'Produtos sem estoque', 'L');

    }

    public function produtosDescasadosAjaxAction()
    {
        /** @var \Wms\Domain\Entity\ExpedicaoRepository $expedicaoRepo */
        $expedicaoRepo = $this->getEntityManager()->getRepository("wms:Expedicao");
        $expedicoes = $this->_getParam("expedicoes");

        $produtosDescasados = $expedicaoRepo->getProdutosDescasadosExpedicao($expedicoes);
        $this->exportPDF($produtosDescasados, 'produtos-descasados', 'Produtos Descasados', 'L');

    }

    public function gerarAction()
    {
        /** @var \Wms\Domain\Entity\ExpedicaoRepository $expedicaoRepo */
        $expedicaoRepo = $this->getEntityManager()->getRepository("wms:Expedicao");
        $repoMotivos = $this->getEntityManager()->getRepository('wms:Expedicao\MotivoCorte');

        $idsExpedicoes = $this->_getParam("expedicao");
        $expedicoes = null;
        $return = [];
        try {
            if (empty($idsExpedicoes))
                throw new \Exception("Nenhuma expedição selecionada");

            $expedicoes = implode(',', $idsExpedicoes);
            $expedicoesSelecionadas = $expedicoes;
            $result = $expedicaoRepo->verificaDisponibilidadeEstoquePedido($expedicoesSelecionadas, true);

            $expedicaoRepo->changeStatusExpedicao($expedicoes, 'S');

            $this->em->beginTransaction();
            if (count($result) > 0) {
                $cortarAutomatico = $this->getSystemParameterValue("PERMISSAO_CORTE_AUTOMATICO");

                if ($cortarAutomatico == 'S') {

                    $idMotivoCorte = $this->getSystemParameterValue('COD_MOTIVO_CORTE_AUTOMATICO');
                    if ($idMotivoCorte == null) throw new \Exception("Parametro COD_MOTIVO_CORTE_AUTOMATICO Não encontrado");

                    $motivoEn = $repoMotivos->find($idMotivoCorte);
                    if ($motivoEn == null) throw new \Exception("Código do Motivo de Corte para Cortes automáticos não encontrado");

                    $motivo = $motivoEn->getDscMotivo();
                    $itensPCortar = $expedicaoRepo->diluirCorte($expedicoes, $result);
                    $expedicaoRepo->executaCortePedido($itensPCortar, $motivo, $cortarAutomatico, $idMotivoCorte);
                    $link = '<a href="' . $this->view->url(array('controller' => 'corte', 'action' => 'relatorio-corte-ajax', 'id' => $expedicoes)) . '" target="_blank" ><img style="vertical-align: middle" src="' . $this->view->baseUrl('img/icons/page_white_acrobat.png') . '" alt="#" /> Relatório de cortes automaticos da onda de ressuprimento</a>';
                    $msgCorte = "Nessa onda de ressuprimento e reserva alguns itens foram cortados automaticamente por falta de estoque.";

                    $return['response'][] = [
                        'msg' => $msgCorte,
                        'link' => $link
                    ];
                } else {

                    $expedicoesComCorte = array();
                    foreach ($result as $expedcao) {
                        if (in_array($expedcao['COD_EXPEDICAO'], $idsExpedicoes)) {

                            $idArray = array_keys($idsExpedicoes, $expedcao['COD_EXPEDICAO']);
                            unset($idsExpedicoes[$idArray[0]]);

                            $expedicoesComCorte[] = $expedcao['COD_EXPEDICAO'];
                        }
                    }
                    $expedicoesComCorte = implode(',', $expedicoesComCorte);

                    $link = '<a href="' . $this->view->url(array('controller' => 'onda-ressuprimento', 'action' => 'relatorio-sem-estoque-ajax', 'expedicoes' => $expedicoesComCorte)) . '" target="_blank" ><img style="vertical-align: middle" src="' . $this->view->baseUrl('img/icons/page_white_acrobat.png') . '" alt="#" /> Relatório de Produtos sem Estoque</a>';
                    $link .= '<br><br> <a href="/expedicao/corte/corte-produto/id/' . $expedicoesComCorte  .'"  target="_self" class="dialogAjax btn"  >Cortar Produtos</a> ';

                    $mensagem = 'Existem Produtos sem Estoque nas Expedições Selecionadas.';

                    $return['response'][] = [
                        'msg' => $mensagem,
                        'link' => $link
                    ];
                }
            }

            $produtosDescasados = $expedicaoRepo->getProdutosDescasadosExpedicao($expedicoes);

            if (count($produtosDescasados) > 99) {

                $expedicaoDescasada = array();
                foreach ($produtosDescasados as $expedicao) {
                    if (!in_array($expedicao['COD_EXPEDICAO'], $expedicaoDescasada)) {
                        $expedicaoDescasada[] = $expedicao['COD_EXPEDICAO'];
                    }
                }
                $expedicoes = implode(',', $expedicaoDescasada);

                $link = '<a href="' . $this->view->url(array('controller' => 'onda-ressuprimento', 'action' => 'produtos-descasados-ajax', 'expedicoes' => $expedicoes)) . '" target="_blank" ><img style="vertical-align: middle" src="' . $this->view->baseUrl('img/icons/page_white_acrobat.png') . '" alt="#" /> Relatório de Produtos Descasados</a>';
                $mensagem = 'Existem Produtos descasados nas Expedições Selecionadas.';

                $return['response'][] = [
                    'msg' => $mensagem,
                    'link' => $link
                ];
            }

            ini_set('max_execution_time', 900);
            ini_set('memory_limit', '-1');

            if (count($idsExpedicoes) > 0) {
                $expedicoes = implode(',', $idsExpedicoes);
                $result = $expedicaoRepo->gerarOnda($expedicoes);
            } else {
                throw new Exception("Todas as expedições selecionadas estão com saldo insuficiente em ao menos 1 item!");
            }

            ini_set('max_execution_time', 30);

            if ($result['resultado'] == false) {
                throw new Exception($result['observacao']);
            } else {
                $return['status'] = 'Ok';
                $return['response'][] = ['msg' => $result['observacao'], 'link' => null];
                $return['expedicoes'] = $idsExpedicoes;
            }
            $this->em->flush();
            $this->em->commit();
        } catch (\Exception $e) {
            $this->em->rollback();
            $return['status'] = 'Error';
            $return['response'][] = ['msg' => "Falha gerando ressuprimento. " . $e->getMessage(), 'link' => null];
            $return['expedicoes'] = null;
        }
        $expedicaoRepo->changeStatusExpedicao($expedicoesSelecionadas, 'N');
        $this->_helper->json($return);
    }

    public function verificarExpedicoesProcessandoAjaxAction()
    {

        $expedicoes = $this->getRequest()->getParam('expedicao');
        $expedicaoRepo = $this->em->getRepository("wms:Expedicao");
        $result = $expedicaoRepo->findBy(["id" => $expedicoes, "indProcessando" => "S"]);
        $str = [];
        $status = "Ok";
        $msg = null;

        if (!empty($result)) {
            /** @var \Wms\Domain\Entity\Expedicao $expedicao */
            foreach ($result as $expedicao) {
                $str[] = $expedicao->getId();
            }
            $status = "Error";
            $msg = "As expedições a seguir já estão sendo processadas: " . implode(", ", $str);
        }

        $this->_helper->json(["status" => $status, "msg" => $msg]);
    }

    public function gerenciarOsAction()
    {
        $form = new FiltroDadosOnda;
        $values = $form->getParams();

        $filtravel = false;
        if (empty($values)) {
            $values['dataInicial'] = date('d/m/Y');
            $values['dataFinal'] = date('d/m/Y');
            $filtravel = true;
        } else {
            foreach ($values as $key => $arg) {
                if (empty($arg)) unset($values[$key]);
            }
            foreach ($values as $param => $value) {
                $filtravel = ($param != 'submit' && !empty($value)) ;
            }
        }

        if (!$filtravel) {
            ini_set('max_execution_time', -1);
            ini_set('memory_limit', '-1');
        }

        $dataInicial = (isset($values['dataInicial'])) ? $values['dataInicial'] : null;
        $dataFinal =   (isset($values['dataFinal'])) ? $values['dataFinal'] : null;
        $status =      (isset($values['status'])) ? $values['status'] : null;
        $idExpedicao = (isset($values['expedicao'])) ? $values['expedicao'] : null;
        $operador =    (isset($values['operador'])) ? $values['operador'] : null;
        $idProduto =   (isset($values['idProduto'])) ? $values['idProduto'] : null;
        $grade =       (isset($values['grade'])) ? $values['grade'] : null;

        /** @var \Wms\Domain\Entity\Ressuprimento\OndaRessuprimentoRepository $ondaRessuprimentoRepo */
        $ondaRessuprimentoRepo = $this->em->getRepository("wms:Ressuprimento\OndaRessuprimento");
        $result = $ondaRessuprimentoRepo->getOndasEmAbertoCompleto($dataInicial, $dataFinal, $status, true, $idProduto, $idExpedicao, $operador, true, $grade);

        $utilizaGrade = $this->getSystemParameterValue('UTILIZA_GRADE');
        $Grid = new OsGrid();
        $Grid->init($result, $values, $utilizaGrade)->render();

        $pager = $Grid->getPager();
        $pager->setMaxPerPage(30000);
        $Grid->setPager($pager);

        $form->setDefaults($values);
        $this->view->grid = $Grid->render();

        $this->view->form = $form;
    }

    public function liberarAction()
    {
        $idOndaOs = $this->_getParam("ID");
        $params = $this->_getAllParams();

        /** @var \Wms\Domain\Entity\Ressuprimento\OndaRessuprimentoOs $ondaOsEn */
        $ondaOsEn = $this->getEntityManager()->getReference("wms:Ressuprimento\OndaRessuprimentoOs", $idOndaOs);
        $statusEn = $this->getEntityManager()->getRepository("wms:Util\Sigla")->findOneBy(array('id' => \Wms\Domain\Entity\Ressuprimento\OndaRessuprimentoOs::STATUS_ONDA_GERADA));
        $ondaOsEn->setStatus($statusEn);
        $this->getEntityManager()->persist($ondaOsEn);

        /** @var \Wms\Domain\Entity\Ressuprimento\AndamentoRepository $andamentoRepo */
        $andamentoRepo = $this->getEntityManager()->getRepository("wms:Ressuprimento\Andamento");
        $andamentoRepo->save($idOndaOs, \Wms\Domain\Entity\Ressuprimento\Andamento::STATUS_LIBERADO);

        $this->getEntityManager()->flush();

        $formParams = array('status' => $params['status'],
            'dataInicial' => $params['dataInicial'],
            'actionParams' => true,
            'dataFinal' => $params['dataFinal']);
        $this->addFlashMessage("success", "OS  $idOndaOs liberada para ressuprimento");
        $this->redirect("gerenciar-os", "onda-ressuprimento", "expedicao", $formParams);
    }

    public function cancelarAction()
    {
        $idOndaOs = $this->_getParam("ID");
        $params = $this->_getAllParams();

        ini_set('max_execution_time', -1);
        ini_set('memory_limit', '-1');

        $ondaOsEn = $this->getEntityManager()->getReference("wms:Ressuprimento\OndaRessuprimentoOs", $idOndaOs);
        $reservasOnda = $this->getEntityManager()->getRepository("wms:Ressuprimento\ReservaEstoqueOnda")->findBy(array('ondaRessuprimentoOs' => $ondaOsEn));
        foreach ($reservasOnda as $reservaOnda) {
            /** @var \Wms\Domain\Entity\Ressuprimento\ReservaEstoque $reservaEstoque */
            $reservaEstoque = $reservaOnda->getReservaEstoque();
            $reservaEstoque->setAtendida("C");
            $this->getEntityManager()->persist($reservaEstoque);
        }
        $statusEn = $this->getEntityManager()->getRepository("wms:Util\Sigla")->findOneBy(array('id' => \Wms\Domain\Entity\Ressuprimento\OndaRessuprimentoOs::STATUS_CANCELADO));
        $ondaOsEn->setStatus($statusEn);
        $this->getEntityManager()->persist($ondaOsEn);

        /** @var \Wms\Domain\Entity\Ressuprimento\AndamentoRepository $andamentoRepo */
        $andamentoRepo = $this->getEntityManager()->getRepository("wms:Ressuprimento\Andamento");
        $andamentoRepo->save($idOndaOs, \Wms\Domain\Entity\Ressuprimento\Andamento::STATUS_CANCELADO);

        $this->getEntityManager()->flush();

        unset($params['module']);
        unset($params['controller']);
        unset($params['action']);
        unset($params['ID']);
        unset($params['submit']);

        $this->addFlashMessage("success", "OS  $idOndaOs cancelada com sucesso");
        $this->redirect("gerenciar-os", "onda-ressuprimento", "expedicao", $params);
    }

    public function listAction()
    {
        $idOndaOs = $this->_getParam("ID");

        /** @var \Wms\Domain\Entity\Ressuprimento\AndamentoRepository $andamentoRepo */
        $andamentoRepo = $this->getEntityManager()->getRepository("wms:Ressuprimento\Andamento");
        $result = $andamentoRepo->getAndamentoRessuprimento($idOndaOs);

        $this->view->andamentos = $result;
    }

    public function modeloSeparacaoExpedicaoAjaxAction()
    {
        /** @var \Wms\Domain\Entity\Expedicao\ModeloSeparacaoRepository $modeloSeparacaoRepository */
        $modeloSeparacaoRepository = $this->getEntityManager()->getRepository('wms:Expedicao\ModeloSeparacao');
        /** @var \Wms\Domain\Entity\ExpedicaoRepository $expedicaoRepository */
        $expedicaoRepository = $this->getEntityManager()->getRepository('wms:Expedicao');

        $modeloSeparacaoEntities = $modeloSeparacaoRepository->getModelos();
        $modelos = array();
        foreach ($modeloSeparacaoEntities as $modeloSeparacaoEntity) {
            $modelos[$modeloSeparacaoEntity['id']] = $modeloSeparacaoEntity['descricao'];
        }
        $this->view->modeloSeparacao = $modelos;
        $codExpedicoes = $this->_getParam('expedicao');
        $expedicaoEntities = array();
        foreach ($codExpedicoes as $codExpedicao) {
            $expedicaoEntities[] = $expedicaoRepository->find($codExpedicao);
        }
        $this->view->expedicoes = $expedicaoEntities;
        $parametroModeloSeparacao = $this->getSystemParameterValue('MODELO_SEPARACAO_PADRAO');
        $this->view->modeloSeparacaoPadrao = $modeloSeparacaoRepository->find($parametroModeloSeparacao);

    }

    public function alterarModeloSeparacaoAjaxAction()
    {
        $params = $this->_getAllParams();
        /** @var \Wms\Domain\Entity\ExpedicaoRepository $expedicaoRepository */
        $expedicaoRepository = $this->getEntityManager()->getRepository('wms:Expedicao');

        try {
            foreach ($params['expedicoes'] as $codExpedicao) {
                $expedicaoRepository->defineModeloSeparacao($codExpedicao, $params['id-modelo']);
            }

            $this->_helper->json(["status" => "Ok"]);

        } catch (Exception $e) {

            $this->_helper->json(["status" => "Error", "msg" => $e->getMessage()]);

        }
    }

    public function finalizarAction() {
        $ids = $this->_getParam("ID");
        $arrayIds = explode(",", $ids);

        $params = $this->_getAllParams();

        $formParams = array();
        if (!isset($params['resetaFiltros'])) {
            $formParams = array('status' => $params['status'],
                'dataInicial' => $params['dataInicial'],
                'actionParams' => true,
                'dataFinal' => $params['dataFinal']);
        }

        try {
            $this->getEntityManager()->beginTransaction();

            if (count($ids) == 0) {
                throw new \Exception("Nenhum ressuprimento selecionado");
            }

            $sql = "SELECT * FROM ONDA_RESSUPRIMENTO_OS WHERE COD_ONDA_RESSUPRIMENTO_OS IN ($ids) AND COD_STATUS <> 540";
            $result = $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);

            if (count($result) >0) {
                throw new \Exception("Apenas ressuprimentos com o status 'ONDA GERADA' devem ser selecionados");
            }

            foreach ($arrayIds as $idOndaOs) {
                /** @var \Wms\Domain\Entity\Ressuprimento\OndaRessuprimentoRepository $ondaRepo */
                $ondaRepo = $this->getEntityManager()->getRepository("wms:Ressuprimento\OndaRessuprimento");

                /** @var \Wms\Domain\Entity\Ressuprimento\OndaRessuprimentoOs $ondaOsEn */
                $ondaOsEn = $this->getEntityManager()->getReference("wms:Ressuprimento\OndaRessuprimentoOs", $idOndaOs);

                if ($ondaOsEn == null)
                    throw new \Exception("Onda de ressuprimento $idOndaOs não encontrada");

                if ($ondaOsEn->getStatus()->getId() == \Wms\Domain\Entity\Ressuprimento\OndaRessuprimentoOs::STATUS_FINALIZADO)
                    throw new \Exception("Onda de ressuprimento $idOndaOs já atendida");

                if ($ondaOsEn->getStatus()->getId() == \Wms\Domain\Entity\Ressuprimento\OndaRessuprimentoOs::STATUS_CANCELADO)
                    throw new \Exception("Onda de ressuprimento $idOndaOs cancelada");

                if ($ondaOsEn->getStatus()->getId() == \Wms\Domain\Entity\Ressuprimento\OndaRessuprimentoOs::STATUS_DIVERGENTE)
                    throw new \Exception("Onda de ressuprimento $idOndaOs marcada como divergente");

                $ondaRepo->finalizaOnda($ondaOsEn, "M");

            }

            $this->getEntityManager()->commit();
            if (count($arrayIds) == 1) {
                $this->addFlashMessage("success", "Onda de ressuprimento $idOndaOs finalizada com sucesso");
            } else {
                $this->addFlashMessage("success", "Ondas de ressuprimento finalizadas com sucesso");
            }

        } catch (Exception $e) {
            $this->addFlashMessage("error", $e->getMessage());
            $this->getEntityManager()->rollback();
        }

        $this->redirect("gerenciar-os", "onda-ressuprimento", "expedicao", $formParams);
    }

}
