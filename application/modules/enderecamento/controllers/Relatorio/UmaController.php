<?php

use Wms\Module\Web\Controller\Action,
    Wms\Module\Web\Form\Subform\FiltroRecebimentoMercadoria,
    Wms\Module\Armazenagem\Report\UMA;

class Enderecamento_Relatorio_UmaController extends Action {

    public function indexAction() {
        $form = new FiltroRecebimentoMercadoria;
        $values = $form->getParams();
        if ($values && isset($values['status'])) {
            $relatorio = new UMA("L");
            $relatorio->init($values);
        }

        $this->view->form = $form;
    }

}
