<?php


use Wms\Module\Web\Controller\Action;
use Wms\Module\Web\Page;
use Wms\Module\Inventario\Form\FiltroImpressao as FiltroEnderecoForm;

class Inventario_Novo_IndexController  extends Action
{

    public function indexAction()
    {
        $importaInventario = $this->getSystemParameterValue("IMPORTA_INVENTARIO");
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
        $source = $this->_em->getRepository('wms:Inventario')->getInventarios(null, $data);
        $this->_helper->json($source);
    }

    public function criarInventarioAction()
    {
        $criterio = $this->getRequest()->getParam("criterio");
        if ($criterio == 'produto') {
            $utilizaGrade = $this->getSystemParameterValue("UTILIZA_GRADE");
            $this->view->form = new \Wms\Module\InventarioNovo\Form\InventarioProdutoForm();
            $this->view->form->init($utilizaGrade);
        }
        else {
            $this->view->form = new \Wms\Module\InventarioNovo\Form\InventarioEnderecoForm();

        }
        $this->configurePage();
    }

    public function configurePage($buttons = [])
    {
        Page::configure(array('buttons' => $buttons));
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