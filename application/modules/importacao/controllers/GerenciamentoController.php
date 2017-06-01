<?php

use Wms\Module\Web\Controller\Action;
use \Wms\Module\Web\Page;

class Importacao_GerenciamentoController extends Action
{

    public function indexAction()
    {

        $request = $this->getRequest();
        $params = $request->getParams();
        $acao = $params['id'];

        Page::configure(array(
            'buttons' => array(
                array(
                    'label' => 'Buscar Registros no ERP',
                    'cssClass' => 'btnSave',
                    'urlParams' => array(
                        'module' => 'importacao',
                        'controller' => 'gerenciamento',
                        'action' => 'index',
                        'id' => $acao
                    ),
                    'tag' => 'a'
                )
            )
        ));


        $em = $this->getEntityManager();
        try {
//            $em->beginTransaction();

            /** @var \Wms\Domain\Entity\Integracao\AcaoIntegracaoRepository $acaoIntRepo */
            $acaoIntRepo = $this->getEntityManager()->getRepository('wms:Integracao\AcaoIntegracao');
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
                $result = $acaoIntRepo->efetivaTemporaria($integracoes);
                if (!($result === true)) {
                    $this->addFlashMessage('error',$result);
                }
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