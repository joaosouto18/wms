<?php
use Wms\Module\Web\Controller\Action,
    Wms\Module\Web\Page;

class Expedicao_ClienteController  extends Action
{
    public function associarPracaAction() {
        $form = new \Wms\Module\Expedicao\Form\AssociarPraca();
        $params = $this->_getAllParams();

        /*
        try {
            // lógica para associar praça ao cliente
            $this->_helper->messenger('success', 'Mensagem!');
        }
        catch (\Exception $e) {
            $this->addFlashMessage('error',$e->getMessage());
        }
        */

        $Grid = new \Wms\Module\Expedicao\Grid\DetalheEnderecoPraca();
        $this->view->grid = $Grid->init($params)->render();

        $form->populate($params);
        $this->view->form = $form;
    }

    public function consultarAction() {
        $params = $this->_getAllParams();
        // $idCliente = $params['id'];
        unset($params['module']);
        unset($params['controller']);
        unset($params['action']);
        unset($params['submit']);

        $this->view->dadosCliente = true;
    }

}