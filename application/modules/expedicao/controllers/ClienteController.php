<?php
use Wms\Module\Web\Controller\Action,
    Wms\Module\Web\Page;

class Expedicao_ClienteController  extends Action
{
    public function associarPracaAction() {
        $params = $this->_getAllParams();
        unset($params['module']);
        unset($params['controller']);
        unset($params['action']);
        $clienteRepo = $this->em->getRepository('wms:Pessoa\Papel\Cliente');

        if ($this->_request->isPost()) {
            /** @var \Wms\Domain\Entity\Pessoa\Papel\ClienteRepository $clienteRepo */
            $result = $clienteRepo->atualizarPracaPorCliente($params);

            if ($result) {
                $this->addFlashMessage('info', $result);
            } else {
                $this->addFlashMessage('info', 'Houve um erro! Tente novamente!');
            }

            $this->_redirect('/expedicao/cliente/associar-praca');
        }

        if ($params != null) {
            /** @var \Wms\Domain\Entity\MapaSeparacao\PracaRepository $repoPraca */
            $repoPraca = $this->getEntityManager()->getRepository('wms:MapaSeparacao\Praca');
            $this->view->pracas = $repoPraca->getIdValue();
            $this->view->clientes = $clienteRepo->getCliente($params);
        }

        $form = new \Wms\Module\Expedicao\Form\AssociarPraca();

        //$form->populate($params);
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