<?php
use Wms\Module\Web\Controller\Action;

class Enderecamento_Relatorio_DisponibilidadeEnderecoController extends Action
{
    public function indexAction()
    {

        $form = new \Wms\Module\Armazenagem\Form\DisponibilidadeEstoque\Filtro();
        $values = $form->getParams();

        /** @var \Wms\Domain\Entity\Enderecamento\EstoqueRepository $EstoqueRepository */
        $EstoqueRepository   = $this->getEntityManager()->getRepository('wms:Enderecamento\Estoque');
        $enderecos = $EstoqueRepository->getSituacaoEstoque($values);

        $grid = new \Wms\Module\Enderecamento\Grid\DisponibilidadeEndereco();
        $this->view->grid = $grid->init($enderecos);

        if (isset($values['imprimir']))
        {
            $relatorio = new \Wms\Module\Armazenagem\Report\DisponibilidadeEstoque();
            $relatorio->init($enderecos);
        }

        $this->view->form = $form;
    }
}