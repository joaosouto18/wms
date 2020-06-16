<?php

use Wms\Domain\Entity\Enderecamento as Enderecamento;
use Wms\Module\Web\Controller\Action,
    Wms\Module\Web\Page;;

class Enderecamento_Relatorio_InventarioController extends Action
{
    public function indexAction()
    {
        $this->configurePage();
        $form = new \Wms\Module\Armazenagem\Form\Inventario\Filtro();
        $form->populate($this->_getAllParams());
        $this->view->form = $form;
    }

    public function imprimirAction() {
        $form = new \Wms\Module\Armazenagem\Form\Inventario\Filtro();
        $params = $form->getParams();

        if ($params) {
            $form->populate($params);

            /** @var Enderecamento\VSaldoInterface $saldoRepo */
            $saldoRepo = null;

			if($params['tipo'] == "C") {
                $saldoRepo = $this->_em->getRepository('wms:Enderecamento\VSaldoCompleto');
                $Report = new \Wms\Module\Armazenagem\Report\Inventario("L");
			} elseif ($params['tipo'] == "L"){
				/** @var Enderecamento\VSaldoLoteCompletoRepository $SaldoRepository */
                $saldoRepo = $this->_em->getRepository(Enderecamento\VSaldoLoteCompleto::class);
                $Report = new \Wms\Module\Armazenagem\Report\InventarioLote("L");
			} else {
				/** @var Enderecamento\VSaldoRepository $SaldoRepository */
                $saldoRepo = $this->_em->getRepository('wms:Enderecamento\VSaldo');
                $Report = new \Wms\Module\Armazenagem\Report\Inventario("L");
			}

            if ($Report->init($saldoRepo->saldo($params), $params['mostraEstoque'])) {
                $this->addFlashMessage('error', 'Produto não encontrado');
            }
        }

        $this->view->form = $form;
    }

    public function configurePage()
    {
        $buttons[] = array(
            'label' => 'Exportar Saldo csv',
            'cssClass' => 'button exportar-saldo-csv',
            'urlParams' => array(
                'module' => 'enderecamento',
                'controller' => 'movimentacao',
                'action' => 'saldo'
            ),
            'tag' => 'a'
        );

        Page::configure(array('buttons' => $buttons));
    }


}