<?php

use Wms\Domain\Entity\Recebimento as RecebimentoEntity,
    Wms\Module\Web\Page,
    Wms\Module\Web\Controller\Action\Crud,
    Wms\Controller\Action,
    Wms\Module\Web\Form\Relatorio\Recebimento\FiltroListagemProdutosSintetico,
    Wms\Module\Web\Report\Recebimento\DadosLogisticosProduto;

/**
 * Description of Web_Relatorio_ProdutosSemDadosLogisticosController
 *
 * @author Adriano Uliana <adriano.uliana@rovereti.com.br>
 */
class Web_Relatorio_ListagemProdutosSinteticoController extends Action
{

    protected $repository = 'Recebimento';

    /**
     *
     * @return type 
     */
    public function indexAction()
    {
        $form = new FiltroListagemProdutosSintetico;

        $params = $form->getParams();

        if ($params) {
            $form->populate($params);
            $produtosReport = new \Wms\Module\Web\Report\Recebimento\ListagemProdutosSintetico();
            $produtosReport->init($params);
        }
        $this->view->form = $form;
    }

}
