<?php

use Wms\Module\Web\Controller\Action;

class Importacao_GerenciamentoController extends Action
{

    public function indexAction()
    {
//        $em = $this->getEntityManager();
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
//            $em->commit();
        } catch (\Exception $e) {
//            $em->rollback();
            $this->_helper->messenger('error', $e->getMessage());
        }
    }

    public function corteErpAjaxAction()
    {
        $em = $this->getEntityManager();

        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', '-1');
        $idExpedicao = $this->getRequest()->getParam('id');

        /** @var \Wms\Domain\Entity\Integracao\AcaoIntegracaoRepository $acaoIntRepo */
        $acaoIntRepo = $em->getRepository('wms:Integracao\AcaoIntegracao');
        /** @var Wms\Domain\Entity\Expedicao\CargaRepository $cargaRepository */
        $cargaRepository = $em->getRepository('wms:Expedicao\Carga');

        try {
            $cargaEntities = $cargaRepository->findBy(array('codExpedicao' => $idExpedicao));
            $cargas = array();
            foreach ($cargaEntities as $cargaEntity) {
                $cargas[] = $cargaEntity->getCodCargaExterno();
            }
            $idCargas[] = implode(',',$cargas);

            $acaoEn = $acaoIntRepo->find(5);
            $acaoIntRepo->processaAcao($acaoEn,$idCargas,'E');

            $this->addFlashMessage('success','Pedidos cortados com sucesso pelo ERP');
            $this->redirect('index','index','expedicao');

        } catch (\Exception $e) {
            $this->_helper->messenger('error', $e->getMessage());
        }
        exit;
    }

}