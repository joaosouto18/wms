<?php
use Wms\Module\Web\Controller\Action,
    Wms\Module\Web\Grid\RelatorioPedidosExpedicao as RelatorioPedidosExpedicaoGrid,
    Wms\Domain\Entity\Expedicao,
    Wms\Module\Web\Form\Subform\FiltroRelatorioPedidosExpedicao;
/**
 * Description of Web_Relatorio_PedidosExpedicaoController
 *
 * @author Michel Castro <mlagaurdia@gmail.com>
 */
class Web_Relatorio_PedidosExpedicaoController  extends Action
{
    public function indexAction()
    {
        $form = new FiltroRelatorioPedidosExpedicao;
        $this->view->form = $form;

        $params = $form->getParams();
        if (!$params) {
            $dataI1 = new \DateTime;
            $params = array(
                'dataInicial1' => $dataI1->format('d/m/Y'),
                'dataInicial2' => $dataI1->format('d/m/Y')
            );
            $form->populate($params);
        }

        $Grid = new RelatorioPedidosExpedicaoGrid();
        $this->view->grid = $Grid->init($params)
            ->render();

        if (!empty($params['submit']))
         unset($params['submit']);

        $this->view->parametros = $params;
    }

}
