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
        var_dump($params); exit;

//        $mail = new Zend_Mail();
//        $mail->setBodyText('abc');

    }


}