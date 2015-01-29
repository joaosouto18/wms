<?php
use Wms\Module\Web\Controller\Action,
    Wms\Module\Web\Grid\Expedicao as ExpedicaoGrid,
    Wms\Domain\Entity\Expedicao,
    Wms\Module\Web\Form\Subform\FiltroExpedicaoMercadoria;

class Expedicao_OndaRessuprimentoController  extends Action
{
    public function indexAction()
    {
        $form = new FiltroExpedicaoMercadoria;
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
        /** @var \Wms\Domain\Entity\ExpedicaoRepository $expedicaoRepo */
        $expedicaoRepo = $this->getEntityManager()->getRepository("wms:Expedicao");
        $expedicoes = $expedicaoRepo->getExpedicaoSemOndaByParams($params);
        $this->view->expedicoes = $expedicoes;
    }

    public function gerarAction()
    {
        /** @var \Wms\Domain\Entity\ExpedicaoRepository $expedicaoRepo */
        $expedicaoRepo = $this->getEntityManager()->getRepository("wms:Expedicao");
        $expedicoes = $this->_getParam("expedicao");
        try {
            ini_set('max_execution_time', 300);
            $expedicaoRepo->gerarOnda($expedicoes);
            ini_set('max_execution_time', 30);
            $this->addFlashMessage("success","Ondas Geradas com sucesso");
        } catch(\Exception $e) {
            $this->addFlashMessage("error","Falha gerando ressuprimento. " . $e->getMessage());
        }
        $this->redirect("index","onda-ressuprimento","expedicao");

    }

}