<?php

use Wms\Domain\Entity\Recebimento as RecebimentoEntity,
    Wms\Module\Web\Page,
    Wms\Module\Web\Controller\Action\Crud,
    Wms\Controller\Action,
    Wms\Module\Web\Form\Relatorio\Ressuprimento\FiltroDadosOnda;

/**
 * Description of Web_Relatorio_RelatorioOndasController
 *
 * @author Michel Castro <mlagaurdia@gmail.com>
 */
class Web_Relatorio_RelatorioOndasController extends Action
{


    /**
     *
     * @return type 
     */
    public function indexAction()
    {
        $form = new FiltroDadosOnda;

        $params = $form->getParams();

        $this->view->form = $form;
    }

}
