<?php

use Wms\Controller\Action,
    Wms\Module\Web\Form\Relatorio\Recebimento\FiltroProdutoRecebido,
    Wms\Module\Web\Report\Recebimento\ProdutoRecebido;

/**
 * Descrição de Web_Relatorio_ProdutoRecebidoController
 *
 * @author Adriano Uliana <adriano.uliana@rovereti.com.br>
 */
class Web_Relatorio_ProdutoRecebidoController extends Action
{

    /**
     *
     * @return type 
     */
    public function indexAction()
    {
        $form = new FiltroProdutoRecebido;

        $params = $form->getParams();

        if ($params) {
            $form->populate($params);
            $ProdutoRecebidoReport = new ProdutoRecebido;
            $ProdutoRecebidoReport->init($params);
        }

        $this->view->form = $form;
    }

}