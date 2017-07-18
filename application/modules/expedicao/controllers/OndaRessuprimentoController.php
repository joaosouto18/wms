<?php
use Wms\Module\Web\Controller\Action,
    Wms\Module\Expedicao\Grid\OsRessuprimento as OsGrid,
    Wms\Module\Web\Form\Relatorio\Ressuprimento\FiltroDadosOnda,
    Wms\Module\Web\Form\Subform\FiltroExpedicaoMercadoria;

class Expedicao_OndaRessuprimentoController  extends Action
{
    public function indexAction()
    {
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
        $this->exportPDF($produtosSemPicking,'Produtos-sem-picking','Produtos Sem Picking - Expedições: ' . $strExpedicao,'P');
    }

    public function relatorioSemEstoqueAjaxAction(){
        $expedicaoRepo = $this->getEntityManager()->getRepository("wms:Expedicao");
        $expedicoes = $this->_getParam("expedicoes");
        $expedicoes = explode(",",$expedicoes);

        $verificaDisponibilidadeEstoquePedido = $expedicaoRepo->verificaDisponibilidadeEstoquePedido($expedicoes);
        $this->exportPDF($verificaDisponibilidadeEstoquePedido,'sem-estoque','Produtos sem estoque','P');
    }


    public function gerarAction()
    {
        /** @var \Wms\Domain\Entity\ExpedicaoRepository $expedicaoRepo */
        $expedicaoRepo = $this->getEntityManager()->getRepository("wms:Expedicao");
        $expedicoes = $this->_getParam("expedicao");
        try {
            $this->em->beginTransaction();
            if (empty($expedicoes))
                throw new \Exception("Nenhuma expedição selecionada");

            $result = $expedicaoRepo->verificaDisponibilidadeEstoquePedido($expedicoes);

            if (count($result) > 0){
                $cortarAutomatico = $this->getSystemParameterValue("PERMISSAO_CORTE_AUTOMATICO");

                if ($cortarAutomatico == 'S') {
                    $motivo = "Saldo insuficiente";
                    $itensPCortar = $expedicaoRepo->diluirCorte($expedicoes, $result);
                    $expedicaoRepo->executaCortePedido($itensPCortar, $motivo);
                    $this->addFlashMessage("alert", "Nessa requisição de onda alguns itens foram cortados automaticamente por falta de estoque.");
                } else {
                    $idExp = $expedicoes = implode(',', $expedicoes);

                    $link = '<a href="' . $this->view->url(array('controller' => 'onda-ressuprimento', 'action' => 'relatorio-sem-estoque-ajax', 'expedicoes' => $idExp)) . '" target="_blank" ><img style="vertical-align: middle" src="' . $this->view->baseUrl('img/icons/page_white_acrobat.png') . '" alt="#" /> Relatório de Produtos sem Estoque</a>';
                    $mensagem = 'Existem Produtos sem Estoque nas Expedições Selecionadas. Clique para exibir ' . $link;

                    $this->addFlashMessage("error", $mensagem);
                    $this->redirect("index", "onda-ressuprimento", "expedicao");
                }
            }

            ini_set('max_execution_time', 300);
            ini_set('memory_limit', '-1');

            $result = $expedicaoRepo->gerarOnda($expedicoes);
            ini_set('max_execution_time', 30);

            if ($result['resultado'] == false) {
                throw new Exception($result['observacao']);
            } else {
                $this->addFlashMessage("success",$result['observacao']);
            }

        } catch(\Exception $e) {
            $this->em->rollback();
            $this->addFlashMessage("error","Falha gerando ressuprimento. " . $e->getMessage());
        }
        $this->redirect("index","onda-ressuprimento","expedicao");

    }

    public function gerenciarOsAction()
    {
        $form = new FiltroDadosOnda;
        $actionParams= $this->_getParam('actionParams',false);

        if ($form->getParams() or $actionParams){
            if ($actionParams) {
                $dataInicial    = $this->_getParam('dataInicial',null);
                $dataFinal      = $this->_getParam('dataFinal',null);
                $status         = $this->_getParam('status',null);
                $idExpedicao    = $this->_getParam('expedicao',null);
                $operador       = $this->_getParam('operador',null);
                $idProduto      = $this->_getParam('idProduto',null);
                $values=array('status'=>$status,
                              'dataInicial'=>$dataInicial,
                              'dataFinal'=>$dataFinal);
            }

            if ($form->getParams()){
                $values = $form->getParams();
                $dataInicial    = $values['dataInicial'];
                $dataFinal      = $values['dataFinal'];
                $status         = $values['status'];
                $idExpedicao    = $values['expedicao'];
                $operador       = $values['operador'];
                $idProduto      = $values['idProduto'];
            }
            /** @var \Wms\Domain\Entity\Ressuprimento\OndaRessuprimentoRepository $ondaRessuprimentoRepo */
            $ondaRessuprimentoRepo = $this->em->getRepository("wms:Ressuprimento\OndaRessuprimento");
            $result = $ondaRessuprimentoRepo->getOndasEmAbertoCompleto($dataInicial, $dataFinal, $status, true, $idProduto, $idExpedicao, $operador);
            $Grid = new OsGrid();
            $Grid->init($result,$values)->render();

            $pager = $Grid->getPager();
            $pager->setMaxPerPage(30000);
            $Grid->setPager($pager);

            $this->view->grid = $Grid->render();
        }

        $this->view->form = $form;
    }

    public function liberarAction()
    {
        $idOndaOs = $this->_getParam("ID");
        $params = $this->_getAllParams();

        /** @var \Wms\Domain\Entity\Ressuprimento\OndaRessuprimentoOs $ondaOsEn */
            $ondaOsEn = $this->getEntityManager()->getReference("wms:Ressuprimento\OndaRessuprimentoOs",$idOndaOs);
            $statusEn = $this->getEntityManager()->getRepository("wms:Util\Sigla")->findOneBy(array('id'=>\Wms\Domain\Entity\Ressuprimento\OndaRessuprimentoOs::STATUS_ONDA_GERADA));
            $ondaOsEn->setStatus($statusEn);
        $this->getEntityManager()->persist($ondaOsEn);

        /** @var \Wms\Domain\Entity\Ressuprimento\AndamentoRepository $andamentoRepo */
        $andamentoRepo = $this->getEntityManager()->getRepository("wms:Ressuprimento\Andamento");
        $andamentoRepo->save($idOndaOs, \Wms\Domain\Entity\Ressuprimento\Andamento::STATUS_LIBERADO);

        $this->getEntityManager()->flush();

        $formParams=array('status'=>$params['status'],
                          'dataInicial'=>$params['dataInicial'],
                          'actionParams'=>true,
                          'dataFinal'=>$params['dataFinal']);
        $this->addFlashMessage("success","OS  $idOndaOs liberada para ressuprimento");
        $this->redirect("gerenciar-os","onda-ressuprimento","expedicao",$formParams);

    }

    public function cancelarAction()
    {
        $idOndaOs = $this->_getParam("ID");
        $params = $this->_getAllParams();

        $ondaOsEn = $this->getEntityManager()->getReference("wms:Ressuprimento\OndaRessuprimentoOs",$idOndaOs);
        $reservasOnda = $this->getEntityManager()->getRepository("wms:Ressuprimento\ReservaEstoqueOnda")->findBy(array('ondaRessuprimentoOs'=>$ondaOsEn));
            foreach ($reservasOnda as $reservaOnda){
                /** @var \Wms\Domain\Entity\Ressuprimento\ReservaEstoque $reservaEstoque */
                $reservaEstoque = $reservaOnda->getReservaEstoque();
                $reservaEstoque->setAtendida("C");
                $this->getEntityManager()->persist($reservaEstoque);
            }
            $statusEn = $this->getEntityManager()->getRepository("wms:Util\Sigla")->findOneBy(array('id'=>\Wms\Domain\Entity\Ressuprimento\OndaRessuprimentoOs::STATUS_CANCELADO));
            $ondaOsEn->setStatus($statusEn);
            $this->getEntityManager()->persist($ondaOsEn);

        /** @var \Wms\Domain\Entity\Ressuprimento\AndamentoRepository $andamentoRepo */
        $andamentoRepo = $this->getEntityManager()->getRepository("wms:Ressuprimento\Andamento");
        $andamentoRepo->save($idOndaOs, \Wms\Domain\Entity\Ressuprimento\Andamento::STATUS_CANCELADO);

        $this->getEntityManager()->flush();

        $formParams=array('status'=>$params['status'],
            'dataInicial'=>$params['dataInicial'],
            'actionParams'=>true,
            'dataFinal'=>$params['dataFinal']);

        $this->addFlashMessage("success","OS  $idOndaOs cancelada com sucesso");
        $this->redirect("gerenciar-os","onda-ressuprimento","expedicao",$formParams);
    }

    public function listAction()
    {
        $idOndaOs = $this->_getParam("ID");

        /** @var \Wms\Domain\Entity\Ressuprimento\AndamentoRepository $andamentoRepo */
        $andamentoRepo = $this->getEntityManager()->getRepository("wms:Ressuprimento\Andamento");
        $result = $andamentoRepo->getAndamentoRessuprimento($idOndaOs);

        $this->view->andamentos = $result;
    }

}