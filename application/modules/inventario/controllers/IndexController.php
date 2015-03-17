<?php
use Wms\Module\Web\Controller\Action;

class Inventario_IndexController  extends Action
{
    public function indexAction()
    {
        $grid =  new \Wms\Module\Inventario\Grid\Inventario();
        $this->view->grid = $grid->init();
        $id = $this->_getParam('id');
        if (isset($id) && !empty($id)) {
            /** @var \Wms\Domain\Entity\InventarioRepository $inventarioRepo */
            $inventarioRepo = $this->em->getRepository("wms:Inventario");
            $inventarioEn = $inventarioRepo->find($id);
            $inventarioRepo->alteraStatus($inventarioEn, \Wms\Domain\Entity\Inventario::STATUS_LIBERADO);
            $this->_helper->messenger('success', 'Inventário liberado com sucesso');
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
                return $this->redirect('index');
            }
        }
    }

}