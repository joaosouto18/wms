<?php
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

			if($params['tipo'] == "C") {
				/** @var \Wms\Domain\Entity\Enderecamento\VSaldoCompletoRepository $SaldoCompletoRepository */
				$SaldoCompletoRepository   = $this->_em->getRepository('wms:Enderecamento\VSaldoCompleto');
				$saldo = $SaldoCompletoRepository->saldo($params);
			} else {
				/** @var \Wms\Domain\Entity\Enderecamento\VSaldoRepository $SaldoRepository */
				$SaldoRepository   = $this->_em->getRepository('wms:Enderecamento\VSaldo');
				$saldo = $SaldoRepository->saldo($params);
			}

            $Report = new \Wms\Module\Armazenagem\Report\Inventario("L");
			
            if ($Report->init($saldo,$params['mostraEstoque'])) {
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