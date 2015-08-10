<?php
use Wms\Controller\Action;
use Wms\Module\Mobile\Form\Reentrega as FormReentrega;
use Wms\Domain\Entity\Expedicao\NotaFiscalSaida as NotaFiscalSaida;

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

    public function gerarRecebimentoAction()
    {
        $params = $this->_getAllParams();

        /** @var \Wms\Domain\Entity\Expedicao\RecebimentoReentregaRepository $recebimentoReentregaRepo */
        $recebimentoReentregaRepo = $this->getEntityManager()->getRepository("wms:Expedicao\RecebimentoReentrega");
        $recebimentoReentregaEn = $recebimentoReentregaRepo->save();

        /** @var \Wms\Domain\Entity\Expedicao\RecebimentoReentregaNotaRepository $recebimentoReentregaNotaRepo */
        $recebimentoReentregaNotaRepo = $this->getEntityManager()->getRepository("wms:Expedicao\RecebimentoReentregaNota");
        $recebimentoReentregaNotaRepo->save($recebimentoReentregaEn, $params);

        var_dump($recebimentoReentregaNotaRepo); exit;


    }

}

