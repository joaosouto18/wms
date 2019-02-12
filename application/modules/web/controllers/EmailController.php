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
//        $params = $this->_getAllParams();

        $request = $this->getRequest();
        if ($request->isPost()) {

            $mail = $mail = new Zend_Mail();
            $mail->setFrom('rodrigodantley@gmail.com', 'Rodrigo Dantley');
            $mail->setSubject('email from web');
            $mail->addTo('rodrigodantley@imperiumsistemas.com.br','EU');
            $mail->setBodyText('abcdefg');

//            $transport = new Zend_Mail_Transport_Sendmail();
            $mail->send();
        }




    }


}