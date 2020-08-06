<?php

use Wms\Domain\Entity\Expedicao\ConferenciaCarregamento;

class Mobile_CarregamentoController extends \Wms\Controller\Action
{
    public function indexAction()
    {
        $this->view->confCarregs = $this->em->getRepository(ConferenciaCarregamento::class)->getConfsAndamento();
    }

    public function confByDanfeAction()
    {
        $this->view->isOldBrowserVersion = $this->getOldBrowserVersion();
        $em = $this->getEntityManager();

        /** @var \Wms\Domain\Entity\Expedicao\NotaFiscalSaidaRepository $notaFiscalSaidaRepo */
        $notaFiscalSaidaRepo = $this->getEntityManager()->getRepository("wms:Expedicao\NotaFiscalSaida");
        /** @var \Wms\Domain\Entity\Expedicao\PedidoRepository $pedidoRepository */
        $pedidoRepository = $this->getEntityManager()->getRepository('wms:Expedicao\Pedido');

        $em->beginTransaction();
        try {
            $pedidoEntities = $pedidoRepository->getPedidosFinalizadosNaoFaturados();
            foreach ($pedidoEntities as $pedidoEntity) {
                $params = array();
                $params['pedido'] = $pedidoEntity->getCodExterno();
                $result = $notaFiscalSaidaRepo->getNotaFiscalSaida($params);
                if ($result) {
                    $pedidoEntity->setFaturado('S');
                    $em->merge($pedidoEntity);
                    $em->flush();
                }
            }
            $em->commit();
        } catch (\Exception $e) {
            $em->rollback();
            $this->addFlashMessage("error", $e->getMessage() . ' - ' .$e->getTraceAsString());
        }
    }

    public function getInfoDanfeAction()
    {
        try {
            $keyPass = $this->getRequest()->getParam('keypass');
                /*$clienteDanfes = null;
                if (in_array($keyPass , ['32158456214511251454188546632548573529185248', '51454188546632548321584562145112573529185248']))
                    $clienteDanfes = [
                        'codExpedicao' => 508,
                        'clientes' => [
                            321 => [
                                'id' => 321,
                                'nome' => 'Cliente Teste',
                                'totalDanfes' => 2,
                                'checked' => 0,
                                'danfes' => [
                                    '32158456214511251454188546632548573529185248' => ['status' => false, 'nota' => 123],
                                    '51454188546632548321584562145112573529185248' => ['status' => false, 'nota' => 124]
                                ]
                            ]
                        ]
                    ];
                else if (in_array($keyPass , ['32158456214511251454188546632548573529185247', '51454188546632548321584562145112573529185246']))
                    $clienteDanfes = [
                        'codExpedicao' => 508,
                        'clientes' => [
                            322 => [
                                'id' => 322,
                                'nome' => 'Cliente Teste 2',
                                'totalDanfes' => 2,
                                'checked' => 0,
                                'danfes' => [
                                    '32158456214511251454188546632548573529185247' => ['status' => false, 'nota' => 5586],
                                    '51454188546632548321584562145112573529185246' => ['status' => false, 'nota' => 9965]
                                ]
                            ]
                        ]
                    ];
                else if (in_array($keyPass , ['32158456214511251454188546632548573529185241']))
                    $clienteDanfes = [
                        'codExpedicao' => 507,
                        'clientes' => [
                            326 => [
                                'id' => 326,
                                'nome' => 'Cliente Teste 6',
                                'totalDanfes' => 1,
                                'checked' => 0,
                                'danfes' => [
                                    '32158456214511251454188546632548573529185241' => ['status' => false, 'nota' => 3568]
                                ]
                            ]
                        ]
                    ];*/
            $clienteDanfes = $this->_em->getRepository(ConferenciaCarregamento::class)->getInfoToConfCarregByDanfe($keyPass);

            if (empty($clienteDanfes))
                throw new Exception("Nenhuma nota foi encontrada com esta chave de acesso '$keyPass'");

            $response = ['status' => 'ok', 'response' => $clienteDanfes];
        } catch (Exception $e) {
            $response = ['status' => 'error', 'exception' => $e->getMessage(), 'errorCode' => $e->getCode()];
        }

        $this->_helper->json($response);
    }

    public function confByExpAction()
    {
        $this->view->isOldBrowserVersion = $this->getOldBrowserVersion();
        $this->view->expedicoes =  $this->em->getRepository(ConferenciaCarregamento::class)->getExpedicoesToConf();
    }

    public function findExpedicaoByFilterAjaxAction()
    {
        $filter = $this->getRequest()->getParam('filter');
        $value = $this->getRequest()->getParam('value');

        $exp = $this->_em->getRepository(\Wms\Domain\Entity\Expedicao::class)->findExpedicaoByFilters($filter, $value);

        $this->_helper->json(['status'=> 'ok', 'result' => ['expedicao' => $exp]]);
    }

    public function newConfAction()
    {
        try {
            $expedicao = $this->getRequest()->getParam('expedicao');
            $expedicao['tipoConferencia'] = $this->getRequest()->getParam('criterio');
            $conf = $this->getServiceLocator()->getService('ConferenciaCarregamento')->registrarNovaConferencia($expedicao);

            $response = ['status' => 'ok', 'response' => $conf->toArray()];
        } catch (Exception $e) {
            $result = ['status' => 'error', 'exception' => $e->getMessage(), 'errorCode' => $e->getCode()];
        }

        $this->_helper->json($response);
    }

    public function confVolumeAction()
    {
        $codConf = $this->getRequest()->getParam('codConf');
        if ($this->getRequest()->isGet()) {
            $this->view->isOldBrowserVersion = $this->getOldBrowserVersion();
            $this->view->confEn = $this->em->find(ConferenciaCarregamento::class, $codConf);

        } elseif ($this->getRequest()->isPost()) {
            try {
                $codBarras = \Wms\Util\Coletor::retiraDigitoIdentificador($this->getRequest()->getParam('codBarras'));
                $this->getServiceLocator()->getService('ConferenciaCarregamento')->conferirVolume($codConf, $codBarras);
                $result = ['status' => 'ok'];
            } catch (Exception $e) {
                $result = ['status' => 'error', 'exception' => $e->getMessage(), 'errorCode' => $e->getCode()];
            }
            $this->_helper->json($result);
        }
    }

    public function finalizarOsConfAction()
    {
        $codConf = $this->getRequest()->getParam('codConf');
        try {
            $this->getServiceLocator()->getService('ConferenciaCarregamento')->finalizarOs($codConf);
            $this->addFlashMessage("success", "Conferência finalizada com sucesso, carregamento liberado!");
        } catch (Exception $e) {
            $this->addFlashMessage("error", $e->getMessage());
            if ($e->getCode() !== 403) $this->redirect('conf-volume', null, null, ['codConf' => $codConf]);
        }
        $this->redirect('index');
    }
}