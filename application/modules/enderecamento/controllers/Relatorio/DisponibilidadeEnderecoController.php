<?php
use Wms\Module\Web\Controller\Action;

class Enderecamento_Relatorio_DisponibilidadeEnderecoController extends Action
{
    public function indexAction()
    {

        $form = new \Wms\Module\Armazenagem\Form\DisponibilidadeEstoque\Filtro();
        $values = $form->getParams();

        if ($values)
        {

            if (isset($values['buscar']))
            {
            /** @var \Wms\Domain\Entity\Enderecamento\EstoqueRepository $EstoqueRepository */
            $EstoqueRepository   = $this->getEntityManager()->getRepository('wms:Enderecamento\Estoque');
            $enderecos = $EstoqueRepository->getSituacaoEstoque($values);

            $this->view->endDisponivel = $enderecos;
            }
            if (isset($values['imprimir']))
            {
                $relatorio = new \Wms\Module\Armazenagem\Report\DisponibilidadeEstoque();
                $relatorio->init($values);
            }
        }

        $this->view->form = $form;
    }
}