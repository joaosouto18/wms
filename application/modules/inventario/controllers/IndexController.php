<?php
use Wms\Module\Web\Controller\Action;

class Inventario_IndexController  extends Action
{
    public function indexAction()
    {
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
            $reservas = $inventarioRepo->verificaReservas($id);
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

}