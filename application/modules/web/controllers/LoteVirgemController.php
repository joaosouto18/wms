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
        /** @var \Wms\Domain\Entity\Produto\LoteRepository $loteRepository */
        $loteRepository = $this->_em->getRepository('wms:Produto\Lote');
        $idPessoa = \Zend_Auth::getInstance()->getIdentity()->getId();
        $qtd = $this->_getParam('qtdLote');
        $loteInicio = $loteFim = null;
        for($i = 0; $i < $qtd; $i++) {
            $lote = $loteRepository->save(null, null, null, $idPessoa, \Wms\Domain\Entity\Produto\Lote::INTERNO);
            if ($i == 0) {
                $loteInicio = $lote;
            } else {
                $loteFim = $lote;
            }
        }
        $this->_em->flush();
        $this->_helper->json(array('success' => 'success', 'loteInicio' => $loteInicio->getDescricao(), 'loteFim' => $loteFim->getDescricao()));
    }

    public function imprimirAjaxAction()
    {
        $loteRepository = $this->_em->getRepository('wms:Produto\Lote');
        $params = $this->_getAllParams();
        $lotes = $loteRepository->getLotes($params);

        $modelo = $this->getSystemParameterValue("MODELO_ETIQUETA_LOTE");
        $xy = explode(",", $this->getSystemParameterValue("TAMANHO_ETIQUETA_LOTE"));
        if (empty($xy) && count($xy) < 2) throw new Exception("As dimensões da etiqueta de lote não foram definidas");

        $gerarEtiqueta = new \Wms\Module\Web\Report\Produto\GerarEtiqueta("P", 'mm', $xy);
        $gerarEtiqueta->etiquetaLote($lotes, $modelo);
    }

}