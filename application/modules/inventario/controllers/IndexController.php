<?php
use Wms\Module\Web\Controller\Action;
use Wms\Module\Inventario\Form\FiltroImpressao as FiltroEnderecoForm;

class Inventario_IndexController  extends Action
{

    public function relatorioAction(){
        $values = $this->_getAllParams();
        /** @var \Wms\Domain\Entity\InventarioRepository $inventarioRepo */
        $inventarioRepo = $this->em->getRepository("wms:Inventario");

        $ids = implode(',',$values['mass-id']);
        $movimentacoes = $inventarioRepo->getMovimentacaoEstoqueByInventario($ids);
        $this->exportCSV($movimentacoes,'relatorio-movimentacao-estoque-ajax');
    }

    public function indexAction()
    {
        ini_set('max_execution_time', -1);
        $grid =  new \Wms\Module\Inventario\Grid\Inventario();
        $data = $this->_getAllParams();
        $this->view->grid = $grid->init($data);
        $id = $this->_getParam('id');

        $form = new \Wms\Module\Inventario\Form\Monitoramento();
        $form->init();
        $form->populate($data);
        $this->view->form = $form;
        
        
        /** @var \Wms\Domain\Entity\InventarioRepository $inventarioRepo */
        $inventarioRepo = $this->em->getRepository("wms:Inventario");

        $values = $this->_getAllParams();
        
        if (isset($values['massaction-select'])){
            if ($values['massaction-select'] == 'index/relatorio') {
                $ids = implode(',',$values['mass-id']);
                $movimentacoes = $inventarioRepo->getMovimentacaoEstoqueByInventario($ids);
                $this->exportCSV($movimentacoes,'relatorio-movimentacao-estoque-ajax');
            } else {
                if (isset($values['mass-id']) && count($values['mass-id']) > 0 ) {
                    $inventarioRepo->removeEnderecos($values['mass-id'], $id);
                    $this->_helper->messenger('success', 'Endereços removidos do inventario '.$id.' com sucesso');
                    return false;
                }
            }
        }



        if (isset($id) && !empty($id)) {
            $inventarioEn = $inventarioRepo->find($id);
            if ($inventarioEn->getCodStatus() != \Wms\Domain\Entity\Inventario::STATUS_GERADO) {
                $this->_helper->messenger('error', "Inventário já ".$inventarioEn->getStatus()->getSigla());
                return false;
            }
            $reservas = $inventarioRepo->verificaReservas($id);
            ini_set('max_execution_time', 3000);
            if (count($reservas) > 0) {
                $grdReservas = new \Wms\Module\Inventario\Grid\ReservaEstoque();
                $this->view->grid = $grdReservas->init($reservas);
            } else {
                $inventarioEn = $inventarioRepo->find($id);
                $inventarioRepo->adicionaEstoqueContagemInicial($inventarioEn); //@ToDo APAGAR SE DER PROBELMA
                $inventarioRepo->alteraStatus($inventarioEn, \Wms\Domain\Entity\Inventario::STATUS_LIBERADO);
                //$inventarioRepo->bloqueiaEnderecos($id);
                $this->_helper->messenger('success', 'Inventário liberado com sucesso');
                $this->redirect();
            }
        }
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
            } else if ($modelo == 3) {
                $inventarioRepo->exportaInventarioModelo03($idInventario);
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