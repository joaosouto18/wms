<?php
use Wms\Module\Web\Controller\Action,
    Wms\Module\Web\Page;

class Expedicao_ClienteController  extends Action
{
    public function associarPracaAction() {
        $clientes = $this->_getParam('massaction-select', null);
        $params = $this->_getAllParams();
        unset($params['module']);
        unset($params['controller']);
        unset($params['action']);

        if (!is_null($clientes)) {
            /** @var \Wms\Domain\Entity\Pessoa\Papel\ClienteRepository $clienteRepo */
            $clienteRepo = $this->em->getRepository('wms:Pessoa\Papel\Cliente');
            $result = $clienteRepo->atualizarPracaPorCliente($clientes);

            if ($result) {
                $this->addFlashMessage('info', $result);
            } else {
                $this->addFlashMessage('info', 'Houve um erro! Tente novamente!');
            }

            $this->_redirect('/expedicao/cliente/associar-praca');
        }

        $form = new \Wms\Module\Expedicao\Form\AssociarPraca();


        if ($params != null) {
            $Grid = new \Wms\Module\Expedicao\Grid\DetalheEnderecoPraca();
            $this->view->grid = $Grid->init($params)->render();
        }

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