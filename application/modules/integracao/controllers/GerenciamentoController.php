<?php
class Integracao_GerenciamentoController extends Core\Controller\Action\WebService
{
    public function init(){
        $front = \Zend_Controller_Front::getInstance();
        $front->setParam('noErrorHandler', true);
        $front->setParam('noViewRenderer', true);
        if (null != \Zend_Layout::getMvcInstance()) {
            \Zend_Layout::getMvcInstance()->disableLayout();
        }

        $this->getHelper('viewRenderer')->setNoRender(true);
    }

    public function indexAction()
    {
        $em = $this->getEntityManager();
        try {
            $em->beginTransaction();

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
            if (isset($params['submit'])) {
                foreach ($acoesId as $id) {
                    $acaoEn = $acaoIntRepo->find($id);
                    $acaoIntRepo->processaAcao($acaoEn, null, 'E');
                }
            } else {
                foreach ($acoesId as $id) {
                    $acaoEn = $acaoIntRepo->find($id);
                    $integracoes[$id] = $acaoEn;
                    $result = $acaoIntRepo->processaAcao($acaoEn, null, "R");
                    $arrayFinal = array_merge($arrayFinal, $result);
                }
            }

            $this->view->valores = $arrayFinal;
            $em->commit();
        } catch (\Exception $e) {
            $em->rollback();
            $this->_helper->messenger('error', $e->getMessage());
        }
    }

}