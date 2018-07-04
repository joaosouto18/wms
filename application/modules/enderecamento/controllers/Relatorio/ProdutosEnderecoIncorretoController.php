<?php
use Wms\Module\Web\Controller\Action;

class Enderecamento_Relatorio_ProdutosEnderecoIncorretoController extends Action
{
    public function indexAction()
    {

        $form = new \Wms\Module\Armazenagem\Form\ProdutosPickingIncorreto\Filtro();
        $values = $form->getParams();

        if ($values)
        {
            /** @var \Wms\Domain\Entity\Enderecamento\EstoqueRepository $EstoqueRepository */
            $EstoqueRepository   = $this->getEntityManager()->getRepository('wms:Enderecamento\Estoque');
            $enderecos = $EstoqueRepository->getProdutosArmazenadosPickingErrado($values);

            $this->exportPDF($enderecos,"Produtos-Picking-Errado.pdf","Produtos com Estoque no Picking Errado","L");

        }

        $this->view->form = $form;
    }
}