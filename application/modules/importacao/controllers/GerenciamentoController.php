<?php

use Wms\Module\Web\Controller\Action;

class Importacao_GerenciamentoController extends Action
{

    public function indexAction()
    {
        $em = $this->getEntityManager();
        try {
//            $em->beginTransaction();

            /** @var \Wms\Domain\Entity\Integracao\AcaoIntegracaoRepository $acaoIntRepo */
            $acaoIntRepo = $this->getEntityManager()->getRepository('wms:Integracao\AcaoIntegracao');
            $request = $this->getRequest();
            $params = $request->getParams();
            $acao = $params['id'];
            $acoesId = explode(",", $acao);
            $dataUltimaExecucao = $acaoIntRepo->findOneBy(array('id' => $acoesId[0]))->getDthUltimaExecucao();
            $dataUltimaExecucao = $dataUltimaExecucao->format('d/m/Y H:i:s');
            $form = new \Wms\Module\Expedicao\Form\Pedidos();
            $form->start($dataUltimaExecucao);
            $form->populate($params);
            $this->view->form = $form;

            $integracoes = array();
            $arrayFinal = array();

            foreach ($acoesId as $id) {
                $acaoEn = $acaoIntRepo->find($id);
                $integracoes[] = $acaoEn;
            }

            if (isset($params['submit'])) {
                $acaoIntRepo->efetivaTemporaria($integracoes);
            } else {
                $arrayFinal = $acaoIntRepo->listaTemporaria($integracoes);
            }

            $this->view->valores = $arrayFinal;
//            $em->commit();
        } catch (\Exception $e) {
//            $em->rollback();
            $this->_helper->messenger('error', $e->getMessage());
        }
    }

}