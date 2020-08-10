<?php


use Wms\Module\Web\Controller\Action;
use Wms\Module\Web\Page;
use Wms\Module\Inventario\Form\FiltroImpressao as FiltroEnderecoForm;

class Inventario_Novo_IndexController  extends Action
{

    public function indexAction()
    {
        $importaInventario = $this->getSystemParameterValue("IMPORTA_INVENTARIO");
        $this->view->usaGrade = ($this->getSystemParameterValue("UTILIZA_GRADE") === 'S');
        $this->view->showCodInvErp = ($importaInventario == 'S');

        $modelo = $this->getSystemParameterValue("MODELO_EXPORTACAO_INVENTARIO");
        $this->view->showExportInventario = (($modelo != "1") && ($modelo != "3") && ($modelo != "4"));

        $buttons[] = array(
            'label' => 'Novo Inventário por Endereço',
            'cssClass' => 'button',
            'urlParams' => array(
                'module' => 'inventario_novo',
                'controller' => 'index',
                'action' => 'criar-inventario',
                'criterio' => \Wms\Domain\Entity\InventarioNovo::CRITERIO_ENDERECO
            ),
            'tag' => 'a'
        );
        $buttons[] = array(
            'label' => 'Novo Inventário por Produto',
            'cssClass' => 'button',
            'urlParams' => array(
                'module' => 'inventario_novo',
                'controller' => 'index',
                'action' => 'criar-inventario',
                'criterio' => \Wms\Domain\Entity\InventarioNovo::CRITERIO_PRODUTO
            ),
            'tag' => 'a'
        );

        $this->configurePage($buttons);
    }

    public function getInventariosAjaxAction()
    {
        $data = json_decode($this->getRequest()->getRawBody(),true);
        $response = new stdClass();
        if (isset($data['getStatusArr'])) {
            $response->statusArr = \Wms\Domain\Entity\InventarioNovo::$tipoStatus;
            unset($data['getStatusArr']);
        }
        $response->inventarios = $this->_em->getRepository('wms:InventarioNovo')->listInventarios($data);
        $this->_helper->json($response);
    }

    public function criarInventarioAction()
    {
        $this->view->criterio = $this->getRequest()->getParam("criterio");
        $buttons = [];
        $source = [];
        if ($this->view->criterio === \Wms\Domain\Entity\InventarioNovo::CRITERIO_PRODUTO) {
            $utilizaGrade = $this->getSystemParameterValue("UTILIZA_GRADE");
            if ($this->getRequest()->isPost()) {
                $list = json_decode($this->getRequest()->getParam('itens'), true);
                $source = $this->_em->getRepository('wms:InventarioNovo')->getPreSelectedCriarNovoInventario($list);
            }
            $this->view->form = new \Wms\Module\InventarioNovo\Form\InventarioProdutoForm();
            $this->view->form->init($utilizaGrade);
            $buttons[] = array(
                'label' => 'Novo Inventário por Endereço',
                'cssClass' => 'button',
                'urlParams' => array(
                    'module' => 'inventario_novo',
                    'controller' => 'index',
                    'action' => 'criar-inventario',
                    'criterio' => \Wms\Domain\Entity\InventarioNovo::CRITERIO_ENDERECO
                ),
                'tag' => 'a'
            );
        } else {
            $this->view->form = new \Wms\Module\InventarioNovo\Form\InventarioEnderecoForm();
            $buttons[] = array(
                'label' => 'Novo Inventário por Produto',
                'cssClass' => 'button',
                'urlParams' => array(
                    'module' => 'inventario_novo',
                    'controller' => 'index',
                    'action' => 'criar-inventario',
                    'criterio' => \Wms\Domain\Entity\InventarioNovo::CRITERIO_PRODUTO
                ),
                'tag' => 'a'
            );
        }
        $this->view->preSelectedItens = json_encode($source);
        $this->configurePage($buttons);
    }

    public function criaInventarioAjaxAction()
    {
        if ($this->getRequest()->isPost()) {
            $data = json_decode($this->getRequest()->getRawBody(),true);
            $objResponse = new stdClass();
            try{
                /** @var \Wms\Service\InventarioService $invServc */
                $invServc = $this->getServiceLocator()->getService("Inventario");
                $novoInventario = $invServc->registrarNovoInventario($data);
                $objResponse->msg = $novoInventario->getDescricao() . " número: " . $novoInventario->getId();
                $this->_helper->json($objResponse);
            } catch (Exception $e) {
                $this->getResponse()->setHttpResponseCode((!empty($e->getCode())) ? $e->getCode() : 500);
                $objResponse->exception = $e->getMessage();
                $this->_helper->json($objResponse);
            }
        }
    }

    public function getEnderecosCriarAjaxAction()
    {
        $data = $this->getRequest()->getParams();
        $source = $this->_em->getRepository('wms:InventarioNovo')->getEnderecosCriarNovoInventario($data);
        $this->_helper->json($source);
    }

    public function getProdutosCriarAjaxAction()
    {
        $data = $this->getRequest()->getParams();
        $source = $this->_em->getRepository('wms:InventarioNovo')->getProdutosCriarNovoInventario($data);
        $this->_helper->json($source);
    }

    public function configurePage($buttons = [])
    {
        Page::configure(array('buttons' => $buttons));
    }

    public function liberarAction ()
    {
        $id = $this->getRequest()->getParam('id');
        try {
            if (empty($id)) {
                throw new Exception("ID do Inventário não foi especificado");
            }

            /** @var \Wms\Service\InventarioService $invServc */
            $invServc = $this->getServiceLocator()->getService("Inventario");
            $result = $invServc->liberarInventario($id);

            if (is_array($result)) {
                $grid = new \Wms\Module\InventarioNovo\Grid\ImpedimentosGrid();

                if ($result[1]->isPorProduto()){
                    $direction = 'remover-produto';
                } else {
                    $direction = 'remover-endereco';
                }

                $this->view->grid = $grid->init($result[0], $direction);
                $this->addFlashMessage("warning", "Estes elementos impedem de liberar o inventário $id");
                $this->renderScript('index'. DIRECTORY_SEPARATOR . 'impedimentos.phtml');
            } else {
                $this->addFlashMessage("success", "Inventário $id liberado com sucesso");
                $this->redirect();
            }
        } catch (Exception $e) {
            $this->addFlashMessage("error", $e->getMessage());
        }
    }

    public function listEnderecosAjaxAction()
    {
        $grid = new \Wms\Module\InventarioNovo\Grid\EnderecosGrid();
        $this->view->grid = $grid->init($this->_em->getRepository('wms:InventarioNovo')->listEnderecos($this->getRequest()->getParam('id')));
        $this->renderScript('index' . DIRECTORY_SEPARATOR . 'generic-grid-view.phtml');
    }

    public function listProdutosAjaxAction()
    {
        $grid = new \Wms\Module\InventarioNovo\Grid\ProdutosGrid();
        $this->view->grid = $grid->init($this->_em->getRepository('wms:InventarioNovo')->listProdutos($this->getRequest()->getParam('id')));
        $this->renderScript('index' . DIRECTORY_SEPARATOR . 'generic-grid-view.phtml');
    }

    public function removerEnderecoAction()
    {
        $origemRequest   = $this->getRequest()->getParam('hiddenId', "remover");

        try {
            /** @var \Wms\Service\InventarioService $invServc */
            $invServc = $this->getServiceLocator()->getService("Inventario");
            foreach ($this->getRequest()->getParam('mass-id') as $id) {
                $inventarioEn = $invServc->removerEndereco($id);
            }
            $this->addFlashMessage("success", "Endereço removido com sucesso.");

            if ($origemRequest != 'remover' && $inventarioEn->isGerado()) {
                $this->_redirect('/inventario_novo/index/liberar/id/' . $inventarioEn->getId() . '');
            } else {
                $this->redirect();
            }

        } catch (Exception $e) {
            $this->addFlashMessage("error", $e->getMessage());
        }

    }

    public function removerProdutoAction()
    {
        $origemRequest   = $this->getRequest()->getParam('hiddenId', "remover");

        try {
            /** @var \Wms\Service\InventarioService $invServc */
            $invServc = $this->getServiceLocator()->getService("Inventario");

            foreach ($this->getRequest()->getParam('mass-id') as $id) {
                $inventarioEn = $invServc->removerProduto($id);
            }
            $this->addFlashMessage("success", "Produto removido com sucesso.");

            if ($origemRequest != 'remover' && $inventarioEn->isGerado()) {
                $this->_redirect('/inventario_novo/index/liberar/id/' . $inventarioEn->getId() . '');
            } else {
                $this->redirect();
            }

        } catch (Exception $e) {
            $this->addFlashMessage("error", $e->getMessage());
        }
    }

    public function atualizarAction()
    {
        try{
            $id = $this->_getParam('id');
            $this->getServiceLocator()->getService("Inventario")->finalizarInventario($id);
            $this->addFlashMessage("success", "Atualização de estoque baseada no inventário $id concluida com sucesso");
        } catch (Exception $e) {
            $this->addFlashMessage("error", $e->getMessage());
        }
        $this->redirect();
    }

    public function interromperAction()
    {
        try{
            $id = $this->_getParam('id');
            $this->getServiceLocator()->getService("Inventario")->interromperInventario($id);
            $this->addFlashMessage("success", "O inventário $id foi interrompido e está liberado para atualizar o estoque.");
        } catch (Exception $e) {
            $this->addFlashMessage("error", $e->getMessage());
        }
        $this->redirect();
    }

    public function cancelarAction()
    {
        try{
            $id = $this->_getParam('id');
            $this->getServiceLocator()->getService("Inventario")->cancelarInventario($id);
            $this->addFlashMessage("success", "O inventário $id foi cancelado");
        } catch (Exception $e) {
            $this->addFlashMessage("error", $e->getMessage());
        }
        $this->redirect();
    }

    public function viewMovimentacoesAjaxAction() {
        $id = $this->_getParam('id');
        if (isset($id) && !empty($id)) {
            /** @var \Wms\Domain\Entity\Enderecamento\HistoricoEstoqueRepository $historicoEstoqueRepo */
            $historicoEstoqueRepo = $this->em->getRepository("wms:Enderecamento\HistoricoEstoque");
            $movimentacoes = $historicoEstoqueRepo->getMovimentacaoInventario($id);

            //var_dump($movimentacoes);
            $this->exportCSV($movimentacoes,'movimentacao');
        }
    }
    
    public function viewAndamentoAjaxAction()
    {
        $this->view->andamentos = json_encode($this->getServiceLocator()->getService("Inventario")->getMovimentacaoByInventario($this->_getParam('id')));
    }

    public function exportInventarioAjaxAction()
    {
        $idInventario = $this->_getParam('id');

        try {

            $modelo = $this->getSystemParameterValue("MODELO_EXPORTACAO_INVENTARIO");
            if (empty($modelo))
                throw new Exception("O modelo de exportação não foi definido! Por favor, defina em <b>Sistemas->Configurações->Inventário->Formato de Exportação do Inventário</b>");

            if ($modelo == 1) {
                $this->getServiceLocator()->getService("Inventario")->exportarInventarioModelo1($idInventario);
            } elseif ($modelo == 2){
                $caminho = $this->getSystemParameterValue("DIRETORIO_IMPORTACAO");
                if (empty($caminho) || !is_dir($caminho))
                    throw new Exception("O diretório de importação/exportação não foi definido! Por favor, defina em <b>Sistemas->Configurações->Parâmetros do sistema->Diretório dos Arquivos de Importação</b>");
                $this->getServiceLocator()->getService("Inventario")->exportarInventarioModelo2($idInventario, $caminho);
            } elseif ($modelo == 3){
                $this->getServiceLocator()->getService("Inventario")->exportarInventarioModelo3($idInventario);
            } elseif ($modelo == 4){
                $this->getServiceLocator()->getService("Inventario")->exportarInventarioModelo4($idInventario);
            }
            $this->addFlashMessage('success', "Inventário $idInventario exportado com sucesso");

        } catch (Exception $e){
            $this->addFlashMessage('error', $e->getMessage());
        }

        $this->redirect('index');

    }

    public function viewVincularCodErpAjaxAction()
    {
        try {
            $id = $this->_getParam('id');
            $codInventarioErp = $this->_getParam('codInventarioErp');

            if (!empty($codInventarioErp)) {
                $this->getServiceLocator()->getService("Inventario")->setCodInventarioERP($id, $codInventarioErp);
                $this->addFlashMessage('success', 'Código vinculado com sucesso!');
                $this->redirect('index');
            }

            $this->view->id = $id;
        } catch (Exception $e){
            $this->addFlashMessage('error', $e->getMessage());
            $this->redirect('index');
        }
    }

    public function getPreviewResultAjaxAction()
    {
        $idInventario = $this->_getParam('id');
        /** @var \Wms\Domain\Entity\InventarioNovoRepository $inventarioRepo */
        $inventarioRepo = $this->getEntityManager()->getRepository('wms:InventarioNovo');
        $stdClassInventario = $inventarioRepo->getInventarios('stdClass', [ "id" => $idInventario ])[0];
        /** @var \Wms\Service\InventarioService $inventarioService */
        $inventarioService =  $this->getServiceLocator()->getService("Inventario");

        $results = $inventarioService->getResultadoInventario($idInventario);

        $mask = \Wms\Util\Endereco::mascara(null, '9');

        $this->_helper->json(["inventario" => $stdClassInventario, "results" => $results, "mask" => $mask]);
    }

    public function getDivergenciasAjaxAction()
    {
        $idInventario = $this->_getParam('id');
        /** @var \Wms\Domain\Entity\InventarioNovoRepository $inventarioRepo */
        $inventarioRepo = $this->getEntityManager()->getRepository('wms:InventarioNovo');

        $stdClassInventario = $inventarioRepo->getInventarios('stdClass', [ "id" => $idInventario ])[0];

        /** @var \Wms\Service\InventarioService $inventarioService */
        $inventarioService =  $this->getServiceLocator()->getService("Inventario");

        $results = $inventarioService->getDivergenciasInventario($idInventario);

        $this->_helper->json(["inventario" => $stdClassInventario, "results" => $results]);
    }

    public function exportDivergenciasAjaxAction()
    {
        $params = json_decode($this->getRequest()->getRawBody(),true);
        if ($params['destino'] == 'pdf')
            $this->exportPDF($params['divergencias'], 'Relatório Divergências Inventário', 'Inventário', 'L');
        if ($params['destino'] == 'csv')
            $this->exportCSV($params['divergencias'], 'Relatório Divergências Inventário', true);
    }

    public function digitacaoInventarioAjaxAction()
    {
        $em = $this->getEntityManager();
        $this->view->form = $form = new FiltroEnderecoForm();
        $values = $form->getParams();
        $params = $this->_getAllParams();
        $params['codInventario'] = $idInventario = $this->_getParam('id');

        if ($this->getRequest()->isPost()) {
            //REPOSITÓRIOS
            /** @var \Wms\Domain\Entity\OrdemServicoRepository $ordemServicoRepo */
            $ordemServicoRepo = $em->getRepository('wms:OrdemServico');
            /** @var \Wms\Domain\Entity\Inventario\ContagemOsRepository $contagemOSRepo */
            $contagemOSRepo = $em->getRepository('wms:Inventario\ContagemOs');
            /** @var \Wms\Domain\Entity\Inventario\ContagemEnderecoRepository $contagemEndRepo */
            $contagemEndRepo = $em->getRepository('wms:Inventario\ContagemEndereco');
            /** @var \Wms\Domain\Entity\Inventario\EnderecoRepository $inventarioEndRepo */
            $inventarioEndRepo = $em->getRepository('wms:Inventario\Endereco');
            /** @var \Wms\Domain\Entity\Deposito\EnderecoRepository $enderecoRepo */
            $enderecoRepo = $em->getRepository('wms:Deposito\Endereco');

            try {
                $contagemOSEn = $contagemOSRepo->findOneBy(array('inventario' => $idInventario));
                if (count($contagemOSEn) > 0) {
                    $ordemServicoEn = $contagemOSEn->getOs();
                    $params['codOs'] = $ordemServicoEn->getId();
                } else {
                    $ordemServicoEn = $ordemServicoRepo->saveByInventarioManual();
                    $params['codOs'] = $ordemServicoEn->getId();
                    $contagemOSEn = $contagemOSRepo->save($params);
                }

                foreach ($params['endereco'] as $key => $value) {
                    $enderecoEn = $enderecoRepo->findOneBy(array('descricao' => $value));
                    $params['codProdutoVolume'] = null;
                    $params['codProdutoEmbalagem'] = null;
                    if (!empty($params['idProduto'][$key]) && !empty($params['grades'][$key]) && !empty($params['quantidade'][$key])) {
                        $params['codProduto'] = $params['idProduto'][$key];
                        $params['grade'] = $params['grades'][$key];
                        $params['qtd'] = $params['quantidade'][$key];
                        $params['numContagem'] = 1;
                        $params['idContagemOs'] = $contagemOSEn->getId();
                        $volumeEn = $em->getRepository('wms:Produto\Volume')->findOneBy(array('codProduto' => $params['codProduto'], 'grade' => $params['grade']));
                        if (isset($volumeEn) && !empty($volumeEn)) {
                            $params['codProdutoVolume'] = $volumeEn->getId();
                        }
                        $embalagemEn = $em->getRepository('wms:Produto\Embalagem')->findOneBy(array('codProduto' => $params['codProduto'], 'grade' => $params['grade']));
                        if (isset($embalagemEn) && !empty($embalagemEn)) {
                            $params['codProdutoEmbalagem'] = $embalagemEn->getId();
                        }
                        $params['idInventarioEnd'] = $inventarioEndRepo->findOneBy(array('inventario' => $idInventario, 'depositoEndereco' => $enderecoEn))->getId();
                        $params['qtdAvaria'] = null;

                        $contagemEndEn = $contagemEndRepo->save($params);
                    }
                }

                if ($contagemEndEn) {
                    $this->addFlashMessage('success', 'Produtos conferidos com sucesso para o inventário '.$params['codInventario']);
                    $this->redirect('index');
                }

            } catch (\Exception $e) {
                $this->addFlashMessage('error', $e->getMessage());
                $this->redirect('index');
            }
        }

        /** @var \Wms\Domain\Entity\InventarioRepository $InventarioRepo */
        $InventarioRepo = $this->_em->getRepository('wms:Inventario');
        $this->view->enderecosInventario = $InventarioRepo->impressaoInventarioByEndereco($values['identificacao'], $idInventario);
        $this->view->utilizaGrade        = $this->getSystemParameterValue("UTILIZA_GRADE");

    }


}