<?php


class Mobile_CarregamentoController extends \Wms\Controller\Action
{
    public function indexAction()
    {
        $this->view->isOldBrowserVersion = $this->getOldBrowserVersion();
    }

    public function getInfoDanfeAction()
    {

        $keyPass = $this->getRequest()->getParam('keypass');
        $clienteDanfes = null;
        if (in_array($keyPass , ['32158456214511251454188546632548573529185248', '51454188546632548321584562145112573529185248']))
            $clienteDanfes = [
                'codExpedicao' => 508,
                'clientes' => [
                    321 => [
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
                        'nome' => 'Cliente Teste 6',
                        'totalDanfes' => 1,
                        'checked' => 0,
                        'danfes' => [
                            '32158456214511251454188546632548573529185241' => ['status' => false, 'nota' => 3568]
                        ]
                    ]
                ]
            ];

        if (!empty($clienteDanfes))
            $response = ['status' => 'ok', 'response' => $clienteDanfes];
        else
            $response = ['status' => 'error', 'exception' => "Nenhuma nota foi encontrada com esta chave de acesso '$keyPass'"];

        $this->_helper->json($response);
    }
}