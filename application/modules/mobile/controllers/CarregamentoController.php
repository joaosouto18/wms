<?php

use Wms\Domain\Entity\Expedicao\ConferenciaCarregamento;

class Mobile_CarregamentoController extends \Wms\Controller\Action
{
    public function indexAction()
    {
        $param = 'E';
        if ($param === 'D') {
            $this->redirect('conf-by-danfe');
        } else {
            $this->redirect('conf-by-exp');
        }
    }

    public function confEmAndamentoAction()
    {

    }

    public function confByDanfeAction()
    {
        $this->view->isOldBrowserVersion = $this->getOldBrowserVersion();
    }

    public function confByExpAction()
    {
        $this->view->expedicoes = [
            ['id' => 508, 'placa' => 'PCU-1212'],
            ['id' => 502, 'placa' => 'JGX-4521'],
            ['id' => 500, 'placa' => 'QSP-3255'],
        ];
        $this->renderScript('carregamento'.DIRECTORY_SEPARATOR.'conf-by-exp.phtml');
    }

    public function getInfoDanfeAction()
    {
        try {
            /*
                $clienteDanfes = null;
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
                    ];
                */
            $keyPass = $this->getRequest()->getParam('keypass');
            $clienteDanfes = $this->_em->getRepository(ConferenciaCarregamento::class)->getInfoToConfCarregByDanfe($keyPass);

            if (empty($clienteDanfes))
                throw new Exception("Nenhuma nota foi encontrada com esta chave de acesso '$keyPass'");

            $response = ['status' => 'ok', 'response' => $clienteDanfes];
        } catch (Exception $e) {
            $response = ['status' => 'error', 'exception' => $e->getMessage()];
        }

        $this->_helper->json($response);
    }
}