<?php
use Wms\Controller\Action,
    Wms\Module\Mobile\Form\PickingLeitura as PickingLeitura,
    Wms\Domain\Entity\Expedicao;

class Mobile_ConsultaRessuprimentoController extends Action
{
    public function indexAction()
    {
        $form = new PickingLeitura();
        $form->setControllerUrl("consulta-ressuprimento");
        $form->setActionUrl("index");
        $form->setLabel("Busca de Produto/Picking");
        $form->setLabelElement("Código de Barras do Produto");

        $codigoBarras = $this->_getParam('codigoBarras');
        if ($codigoBarras != NULL) {
            $this->redirect("listar-ondas",'onda-ressuprimento','mobile',array('codProduto'=>'teste'));
        }


        $this->view->form = $form;
        $form->init();

    }

}

