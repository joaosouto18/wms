<?php
/**
 * Created by PhpStorm.
 * User: Luis Fernando
 * Date: 01/06/2018
 * Time: 09:54
 */

use Wms\Module\Web\Controller\Action,
    \Wms\Module\Web\Page;

class Web_LoteVirgemController extends Action{

    public function indexAction(){
        $form = new \Wms\Module\Web\Form\LoteVirgem();
        $form->init();
        $data = $this->_getAllParams();
        $form->populate($data);
        $this->view->form = $form;
    }

    public function listAjaxAction() {
        $loteRepository = $this->_em->getRepository('wms:Produto\Lote');
        $params = $this->_getAllParams();
        $this->view->lotes = $loteRepository->getLotes($params);
    }

    public function criarLoteAjaxAction(){
        $loteRepository = $this->_em->getRepository('wms:Produto\Lote');
        $idPessoa = \Zend_Auth::getInstance()->getIdentity()->getId();
        $qtd = $this->_getParam('qtdLote');
        for($i = 0; $i < $qtd; $i++) {
            $loteRepository->save(null, null, null, $idPessoa, 'I');
        }
        $this->_em->flush();
        $this->_helper->json(array('success' => 'success'));
    }

    public function imprimirAjaxAction()
    {
        $loteRepository = $this->_em->getRepository('wms:Produto\Lote');
        $params = $this->_getAllParams();
        $lotes = $loteRepository->getLotes($params);

        /*
         * Caso for preciso criar um parametro para modelo de etiqueta
         * $this->getSystemParameterValue("MODELO_ETIQUETA_LOTE");
         */
        $modelo = 3;
        $gerarEtiqueta = null;
        switch ($modelo) {
            case 1:
                $gerarEtiqueta = new \Wms\Module\Web\Report\Produto\GerarEtiqueta("P", 'mm', array(110, 50));
                break;
            case 2:
                $gerarEtiqueta = new \Wms\Module\Web\Report\Produto\GerarEtiqueta("P", 'mm', array(110, 60));
                break;
            case 3:
                $gerarEtiqueta = new \Wms\Module\Web\Report\Produto\GerarEtiqueta("P", 'mm', array(75, 45));
                break;
            case 4:
                $gerarEtiqueta = new \Wms\Module\Web\Report\Produto\GerarEtiqueta("P", 'mm', array(113, 70));
                break;
            case 5:
                $gerarEtiqueta = new \Wms\Module\Web\Report\Produto\GerarEtiqueta("P", 'mm', array(60, 60));
                break;
        }

        $gerarEtiqueta->etiquetaLote($lotes, $modelo);
    }

}