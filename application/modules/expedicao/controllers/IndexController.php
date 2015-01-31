<?php
use Wms\Module\Web\Controller\Action,
    Wms\Module\Web\Grid\Expedicao as ExpedicaoGrid,
    Wms\Domain\Entity\Expedicao,
    Wms\Module\Web\Form\Subform\FiltroExpedicaoMercadoria;

class Expedicao_IndexController  extends Action
{
    public function indexAction()
    {
        $form = new FiltroExpedicaoMercadoria;
        $this->view->form = $form;
        $params = $this->_getAllParams();

        $s = new Zend_Session_Namespace('sessionUrl');
        $s->setExpirationSeconds(900, 'url');
        $s->url=$params;

        unset($params['module']);
        unset($params['controller']);
        unset($params['action']);
        $dataI1 = new \DateTime;

        if ( !empty($params) ) {

            if ( !empty($params['idExpedicao']) || !empty($params['codCargaExterno']) ){
                $idExpedicao=null;
                $idCarga=null;

                if (!empty($params['idExpedicao']) )
                    $idExpedicao=$params['idExpedicao'];

                if (!empty($params['codCargaExterno']) )
                    $idCarga=$params['codCargaExterno'];

                $params=array();
                $params['idExpedicao']=$idExpedicao;
                $params['codCargaExterno']=$idCarga;
            }
            if ( !empty($params['control']) )
                $this->view->control = $params['control'];

        } else {
            $params = array(
                'dataInicial1' => $dataI1->format('d/m/Y'),
                'dataInicial2' => $dataI1->format('d/m/Y')
            );
        }

        $form->populate($params);

        $Grid = new ExpedicaoGrid();
        $this->view->grid = $Grid->init($params)
            ->render();

        $this->view->refresh = true;
    }
	
}