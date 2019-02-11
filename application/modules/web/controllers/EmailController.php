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
        $params = $this->_getAllParams();



        $tr = new Zend_Mail_Transport_Smtp('mail.gmail.com', array(
            'ssl' => 'tls',
            'port' => 587,
            'auth'     => 'login',
            'username' => 'rodrigodantley@gmail.com',
            'password' => 'durateston',
        ));
        Zend_Mail::setDefaultTransport($tr);

        $mail = new Zend_Mail();

        $mail->setBodyText('abc');
        $mail->setFrom('rodrigodantley@gmail.com', 'Rodrigo Dantley');
        $mail->addTo('rodrigodantley@imperiumsistemas.com.br','EU');
        $mail->setSubject('email de teste');
        $mail->send();

        exit;

    }


}