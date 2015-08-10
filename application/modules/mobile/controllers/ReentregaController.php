<?php
use Wms\Controller\Action;
use Wms\Module\Mobile\Form\Reentrega as FormReentrega;

class Mobile_ReentregaController extends Action
{

    public function indexAction()
    {

    }

    public function recebimentoAction()
    {
        $this->view->form = new FormReentrega;
    }

    public function buscarAction()
    {
        $params = $this->_getAllParams();
        unset($params['module']);
        unset($params['controller']);
        unset($params['action']);

        if (!empty($params['carga']) && isset($params['carga'])) {
            /** @var \Wms\Domain\Entity\Expedicao\NotaFiscalSaidaRepository $notaFiscalSaidaRepo */
            $notaFiscalSaidaRepo = $this->getEntityManager()->getRepository("wms:Expedicao\NotaFiscalSaida");
            $this->view->notasFiscaisByCarga = $notaFiscalSaidaRepo->getNotaFiscalOuCarga($params);
        }
    }

}

