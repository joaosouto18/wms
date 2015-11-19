<?php
use Wms\Module\Web\Controller\Action;
use Wms\Module\Inventario\Form\FiltroImpressao as FiltroEnderecoForm;

class Inventario_IndexController  extends Action
{
    public function indexAction()
    {
        ini_set('max_execution_time', 3000);
        $grid =  new \Wms\Module\Inventario\Grid\Inventario();
        $this->view->grid = $grid->init();
        $id = $this->_getParam('id');

        /** @var \Wms\Domain\Entity\InventarioRepository $inventarioRepo */
        $inventarioRepo = $this->em->getRepository("wms:Inventario");

        $values = $this->_getAllParams();
        if (isset($values['mass-id']) && count($values['mass-id']) > 0 ) {
            $inventarioRepo->removeEnderecos($values['mass-id'], $id);
            $this->_helper->messenger('success', 'Endereços removidos do inventario '.$id.' com sucesso');
            return false;
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
                $inventarioRepo->alteraStatus($inventarioEn, \Wms\Domain\Entity\Inventario::STATUS_LIBERADO);
                $inventarioRepo->bloqueiaEnderecos($id);
                $this->_helper->messenger('success', 'Inventário liberado com sucesso');
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
                }catch(\Exception $e) {
                    $this->em->rollback();
                    throw new \Exception($e->getMessage());
                }

                $this->_helper->messenger('success', 'Estoque atualizado com sucesso');
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

    public function viewAndamentoAjaxAction()
    {
        $grid =  new \Wms\Module\Inventario\Grid\Andamento();
        $this->view->grid = $grid->init($this->_getAllParams());
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
                if (!empty($params['produto'][$key]) && !empty($params['grade'][$key])) {
                    $params['codProduto'] = $params['produto'][$key];
                    $params['grade'] = $params['grade'][$key];
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
        }

        /** @var \Wms\Domain\Entity\InventarioRepository $InventarioRepo */
        $InventarioRepo = $this->_em->getRepository('wms:Inventario');
        $this->view->enderecosInventario = $InventarioRepo->impressaoInventarioByEndereco($values['identificacao'], $idInventario);
        $this->view->utilizaGrade        = $this->getSystemParameterValue("UTILIZA_GRADE");

    }

}