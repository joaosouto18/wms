<?php


class Mobile_CarregamentoController extends \Wms\Controller\Action
{
    public function indexAction()
    {
        $this->view->isOldBrowserVersion = $this->getOldBrowserVersion();
    }

    public function getInfoDanfeAction()
    {

        $response = [
            'codExpedicao' => 508,
            'clientes' => [
                321 => [
                    'nome' => 'Cliente Teste',
                    'danfes' => [
                        '32158456214511251454188546632548573529185248' => false,
                        '51454188546632548321584562145112573529185248' => false
                    ]
                ]
            ]
        ];

        $this->_helper->json(['status' => 'ok', 'response' => $response]);
    }
}