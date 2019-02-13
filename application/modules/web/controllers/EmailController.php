<?php
/**
 * Created by PhpStorm.
 * User: rodrigo
 * Date: 29/01/19
 * Time: 14:00
 */

use Wms\Controller\Action;


class Web_EmailController extends Action
{

    public function indexAction()
    {
        $request = $this->getRequest();

        if ($request->isPost()) {
            $params = $this->getRequest()->getParams();
            $emailCadastrado = $this->getSystemParameterValue('EMAIL_CHAMADOS');

            $to      = 'rodrigodantley@gmail.com';
            $subject = $params['assunto'];
            $message = 'Email de: '.$params['nome'].' - '.$params['mensagem'];
            $headers = 'From: '. $emailCadastrado . "\r\n" .
                'Reply-To: '. $emailCadastrado . "\r\n" .
                'X-Mailer: PHP/' . phpversion();

            mail($to, $subject, $message, $headers);

        }




    }


}