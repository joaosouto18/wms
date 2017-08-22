<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

use Wms\Module\Web\Controller\Action,
    Wms\Module\Expedicao\Grid\RessuprimentoPreventivo as OsGrid;

class Expedicao_RessuprimentoPreventivoController extends Action {

    public function indexAction() {
        $form = new \Wms\Module\Expedicao\Form\RessuprimentoPreventivo();
        $form->init();
        $data = $this->_getAllParams();
        $form->populate($data);
        $this->view->form = $form;
    }

    public function pickingAjaxAction() {
        $params = $this->_getAllParams();
        $OndaRessupRep = $this->em->getRepository("wms:Ressuprimento\OndaRessuprimento");
        $dados = $OndaRessupRep->calculaRessuprimentoPreventivoByParams($params);
        
        $Grid = new OsGrid();
        $Grid->init($dados)->render();

        $pager = $Grid->getPager();
        $pager->setMaxPerPage(30000);
        $Grid->setPager($pager);

        $this->view->grid = $Grid->render();
    }

    public function listAjaxAction() {
        $EstoqueRepo = $this->getEntityManager()->getRepository("wms:Enderecamento\Estoque");
        $params = $this->_getAllParams();
        $enderecos = $EstoqueRepo->getEstoquePreventivoByParams($params);
        $this->view->enderecos = $enderecos;
    }
}
