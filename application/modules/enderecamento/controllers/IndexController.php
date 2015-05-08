<?php
use Wms\Module\Web\Controller\Action,
    Wms\Module\Web\Form\Subform\FiltroRecebimentoMercadoria,
    Wms\Module\Web\Grid\Enderecamento\Listagem as Grid;

class Enderecamento_IndexController extends Action
{

    public function indexAction()
    {
        $form = new FiltroRecebimentoMercadoria;
        $values = $form->getParams();

        //Caso nao seja preenchido nenhum filtro preenche automaticamente com a data inicial de ontem e de hoje
        if (!$values) {

            $dataI1 = new \DateTime;
            $dataI1->modify('-1 day');
            $dataI2 = new \DateTime();

            $values = array(
                'dataInicial1' => $dataI1->format('d/m/Y'),
                'dataInicial2' => $dataI2->format('d/m/Y'),
                'dataFinal1' => '',
                'dataFinal2' => '',
                'idRecebimento' => '',
                'uma' => ''
            );
        }

        // grid
        $grid = new Grid;
        $this->view->grid = $grid->init($values)
            ->render();
        $this->view->form = $form->setSession($values)
            ->populate($values);


    }


} 