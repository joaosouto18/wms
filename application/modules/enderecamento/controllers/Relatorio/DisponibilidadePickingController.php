<?php
use Wms\Module\Web\Controller\Action;

class Enderecamento_Relatorio_DisponibilidadePickingController extends Action
{
    public function indexAction()
    {
        $form = new \Wms\Module\Armazenagem\Form\OcupacaocdPeriodo\Filtro();
        $form->init(false);
        $values = $form->getParams();

        if ($values)
        {
            /** @var \Wms\Domain\Entity\Deposito\EnderecoRepository $enderecoRepo */
            $enderecoRepo = $this->em->getRepository("wms:Deposito\Endereco");
            $enderecos = $enderecoRepo->getPickingSemProdutos($values);
            $this->view->enderecos = $enderecos;
            if (count($enderecos) == 0) {
                $this->addFlashMessage('info','Não existe nenhum endereço de picking sem produto');
            }
        }
        $this->view->form = $form;
    }
}