<?php
use  Wms\Module\Armazenagem\Report\OcupacaoCDPeriodo,
     Wms\Module\Web\Page,
     Wms\Module\Web\Controller\Action;

class Enderecamento_Relatorio_OcupacaocdPeriodoController extends \Wms\Controller\Action
{
    public function indexAction(){

        $this->configurePage();

        $form = new \Wms\Module\Armazenagem\Form\OcupacaocdPeriodo\Filtro();
        $values = $form->getParams();

        if ($values)
        {
            $relatorio = new OcupacaoCDPeriodo();
            $relatorio->init($values);
        }

        $this->view->form = $form;

    }

    public function configurePage()
    {
        $buttons[] = array(
            'label' => 'Gravar Estoque',
            'cssClass' => 'button gravar dialogAjax',
            'urlParams' => array(
                'module' => 'enderecamento',
                'controller' => 'relatorio_ocupacaocd-periodo',
                'action' => 'gravar',
            ),
            'tag' => 'a'
        );

        Page::configure(array('buttons' => $buttons));
    }

    public function gravarAction() {

       /** @var \Wms\Domain\Entity\Enderecamento\PosicaoEstoqueResumidoRepository $posicaoRepo */
        $posicaoRepo = $this->em->getRepository("wms:Enderecamento\PosicaoEstoqueResumido");
        $pos_estoque = $posicaoRepo->verificarResumoEstoque();

        if ($pos_estoque == 0){
            $msg = "Confirma gravar o estoque de hoje?";
        }
        else {
            $msg = "Já existem informações de estoque armazenadas na data de hoje. Deseja substitui-las?";
        }

        $this->view->msg = $msg;
    }

    public function atualizarAction() {

        /** @var \Wms\Domain\Entity\Enderecamento\PosicaoEstoqueResumidoRepository $posicaoRepo */
        $posicaoRepo = $this->em->getRepository("wms:Enderecamento\PosicaoEstoqueResumido");

        $posicaoRepo->removerEstoqueAtual();
        $posicaoRepo->gravarResumoEstoque();

        $this->addFlashMessage('success', 'Estoque gravado com sucesso');
        $this->_redirect("/enderecamento/relatorio_ocupacaocd-periodo");

    }
}