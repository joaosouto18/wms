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

        $buttons[] = array(
            'label' => 'Novo Inventário por Endereço',
            'cssClass' => 'button',
            'urlParams' => array(
                'module' => 'inventario_novo',
                'controller' => 'index',
                'action' => 'criar-inventario',
                'criterio' => 'endereco'
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
                'criterio' => 'produto'
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
        if ($this->getRequest()->isGet()) {
            $this->view->criterio = $this->getRequest()->getParam("criterio");
            $buttons = [];
            if ($this->view->criterio === \Wms\Domain\Entity\InventarioNovo::CRITERIO_PRODUTO) {
                $utilizaGrade = $this->getSystemParameterValue("UTILIZA_GRADE");
                $this->view->form = new \Wms\Module\InventarioNovo\Form\InventarioProdutoForm();
                $this->view->form->init($utilizaGrade);
                $buttons[] = array(
                    'label' => 'Novo Inventário por Endereço',
                    'cssClass' => 'button',
                    'urlParams' => array(
                        'module' => 'inventario_novo',
                        'controller' => 'index',
                        'action' => 'criar-inventario',
                        'criterio' => 'endereco'
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
                        'criterio' => 'produto'
                    ),
                    'tag' => 'a'
                );
            }
            $this->configurePage($buttons);
        } elseif ($this->getRequest()->isPost()) {
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
                $this->view->grid = $grid->init($result);
                $this->addFlashMessage("warning", "Estes elementos impedem de liberar o inventário $id");
                $this->renderScript('index\impedimentos.phtml');
            } else {
                $this->addFlashMessage("success", "Inventário $id liberado com sucesso");
                $this->redirect();
            }
        } catch (Exception $e) {
            $this->addFlashMessage("error", $e->getMessage());
        }
    }

    public function removerEnderecoAction()
    {
        $idInventario = $this->getRequest()->getParam('id');
        $idEndereco   = $this->getRequest()->getParam('idEndereco');

        try {
            if (empty($idInventario))  {
                throw new Exception("ID do Inventário não foi especificado.");
            }

            if (empty($idEndereco))  {
                throw new Exception("Endereço não foi especificado.");
            }

            /** @var \Wms\Service\InventarioService $invServc */
            $invServc = $this->getServiceLocator()->getService("Inventario");
            $invServc->removerItem($idInventario, $idEndereco, 'E', null, null);

            $this->addFlashMessage("success", "Endereço removido com sucesso.");

        } catch (Exception $e) {
            $this->addFlashMessage("error", $e->getMessage());
        }
        //$this->renderScript('index\impedimentos.phtml');
        $this->_redirect('/inventario_novo/index/liberar/id/'.$idInventario.'');
    }

    public function removerProdutoAction()
    {
        $idInventario = $this->getRequest()->getParam('id');
        $idProduto    = $this->getRequest()->getParam('idProduto');
        $grade        = $this->getRequest()->getParam('grade');
        $lote         = $this->getRequest()->getParam('lote');

        try {
            if (empty($idInventario))  {
                throw new Exception("ID do Inventário não foi especificado.");
            }
            if (empty($idProduto))  {
                throw new Exception("Produto não foi especificado.");
            }
            if (empty($grade))  {
                throw new Exception("Grade não foi especificada.");
            }
            if (empty($lote))  {
                throw new Exception("Lote não foi especificado.");
            }

            /** @var \Wms\Service\InventarioService $invServc */
            $invServc = $this->getServiceLocator()->getService("Inventario");
            $invServc->removerItem($idInventario, $idProduto, 'P', $grade, $lote);

            $this->addFlashMessage("success", "Produto removido com sucesso.");

        } catch (Exception $e) {
            $this->addFlashMessage("error", $e->getMessage());
        }
        //$this->renderScript('index\impedimentos.phtml');
        $this->_redirect('/inventario_novo/index/liberar/id/'.$idInventario.'');
    }

    public function atualizarAction()
    {
        $id = $this->_getParam('id');
        if (isset($id) && !empty($id)) {
            /** @var \Wms\Domain\Entity\InventarioRepository $inventarioRepo */
            $inventarioRepo = $this->em->getRepository("wms:Inventario");
            $inventarioEn = $inventarioRepo->find($id);
            if ($inventarioEn) {

                ini_set('max_execution_time', 3000);
                try {
                    $this->em->beginTransaction();
                    $inventarioRepo->atualizarEstoque($inventarioEn);
                    $inventarioRepo->desbloqueiaEnderecos($id);
                    $this->em->commit();
                    $this->_helper->messenger('success', 'Estoque atualizado com sucesso');
                }catch(\Exception $e) {
                    $this->em->rollback();
                    $this->_helper->messenger('success', $e->getMessage());
                }

                return $this->redirect('index');
            }
        }
    }

    public function cancelarAction()
    {
        $id = $this->_getParam('id');
        if (isset($id) && !empty($id)) {
            /** @var \Wms\Domain\Entity\InventarioRepository $inventarioRepo */
            $inventarioRepo = $this->em->getRepository("wms:Inventario");
            $inventarioEn = $inventarioRepo->find($id);
            if ($inventarioEn) {
                $inventarioRepo->cancelar($inventarioEn);
                $inventarioRepo->desbloqueiaEnderecos($id);
                return $this->redirect('index');
            }
        }
    }

    public function viewMovimentacoesAjaxAction() {
        $id = $this->_getParam('id');
        if (isset($id) && !empty($id)) {
            /** @var \Wms\Domain\Entity\InventarioRepository $inventarioRepo */
            $inventarioRepo = $this->em->getRepository("wms:Inventario");
            $movimentacoes = $inventarioRepo->getMovimentacaoEstoqueByInventario($id);
            $this->exportCSV($movimentacoes,'movimentacao');
//            $this->exportPDF($movimentacoes, "movimentacoes-invenario","Movimentações de Estoque por Inventário","P");
        }
        //return $this->redirect('index');
    }
    
    public function viewAndamentoAjaxAction()
    {
        $grid =  new \Wms\Module\Inventario\Grid\Andamento();
        $this->view->grid = $grid->init($this->_getAllParams());
    }

    public function exportInventarioAjaxAction()
    {
        $idInventario = $this->_getParam('id');

        try {
            /** @var \Wms\Domain\Entity\InventarioRepository $inventarioRepo */
            $inventarioRepo = $this->_em->getRepository('wms:Inventario');

            $modelo = $this->getSystemParameterValue("MODELO_EXPORTACAO_INVENTARIO");

            if ($modelo == 1) {
                $inventarioRepo->exportaInventarioModelo01($idInventario);
            } else {
                $inventarioRepo->exportaInventarioModelo02($idInventario);
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
            $form = new \Wms\Module\Inventario\Form\FormCodInventarioERP();
            $form->setDefault('id', $id);

            if (!empty($codInventarioErp)) {
                /** @var \Wms\Domain\Entity\InventarioRepository $inventarioRepo */
                $inventarioRepo = $this->em->getRepository('wms:Inventario');
//                $check = $inventarioRepo->findOneBy(array('codInventarioERP' => $codInventarioErp));
//                if (empty($check)) {
                    $inventarioRepo->setCodInventarioERP($id,$codInventarioErp);
                    $this->addFlashMessage('success', 'Código vinculado com sucesso!');
//                } else {
//                    $idInventario = $check->getId();
//                    $this->addFlashMessage('error', "O inventário $idInventario já está vinculado com esse código $codInventarioErp");
//                }
                $this->redirect('index');
            }

            $this->view->form = $form;
        } catch (Exception $e){
            $this->addFlashMessage('error', $e->getMessage());
            $this->redirect('index');
        }
    }

    public function viewRuaAjaxAction()
    {
        $grid =  new \Wms\Module\Inventario\Grid\Rua();
        $this->view->grid = $grid->init($this->_getAllParams());
    }

    public function viewDetalheContagemAjaxAction()
    {
        $grid =  new \Wms\Module\Inventario\Grid\DetalheContagem();
        $this->view->grid = $grid->init($this->_getAllParams());
    }

    public function imprimirEnderecosAjaxAction()
    {
        $this->view->form = $form = new FiltroEnderecoForm();
        $values = $form->getParams();
        $idInventario = $this->_getParam('id');

        if ($values) {
            /** @var \Wms\Domain\Entity\InventarioRepository $InventarioRepo */
            $InventarioRepo = $this->_em->getRepository('wms:Inventario');
            $result = $InventarioRepo->impressaoInventarioByEndereco($values['identificacao'], $idInventario);
            $this->exportPDF($result, 'Relatório Inventário', 'Inventário', 'P');
        }
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