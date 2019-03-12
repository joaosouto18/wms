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

        if ($request->isPost() and isset($request->submit)) {
            $params = $this->getRequest()->getParams();

            try {

                // busco as configurações no ini
                $objCfg = new Zend_Config_Ini(APPLICATION_PATH . '/configs/smtp.ini', 'smtp');
                $arrCfg = $objCfg->toArray();

// configuro o cliente SMTP
                $config = array('auth'     => $arrCfg['smtp']['auth'],
                                'username' => $arrCfg['smtp']['usuario'],
                                'password' => $arrCfg['smtp']['senha'],
                                'smtp'     => $arrCfg['smtp']['host'],
                                'ssl'      => $arrCfg['smtp']['seguranca'],
                                'port'     => $arrCfg['smtp']['porta']);

// instancio o cliente SMTP
                $smtp = new Zend_Mail_Transport_Smtp($config['smtp'], $config);

// instancio o cliente de e-mail e tento enviar a mensagem
                $mail = new Zend_Mail();
                $mail->setFrom($arrCfg['smtp']['usuario'], $arrCfg['smtp']['titulo'])
                    ->setReplyTo($arrCfg['smtp']['usuario'], $arrCfg['smtp']['titulo'])
                    ->addTo('suporte@imperiumsistemas.com.br')
                    ->setBodyHtml($params['mensagem'])
                    ->setSubject($params['nome'].' - '.$params['assunto']);

                if ($mail->send($smtp)) {
                    $this->addFlashMessage('info', 'E-mail Enviado com Sucesso. Acompanhe seu chamado no link fornecido');
                    $this->_redirect($_SERVER['HTTP_REFERER']);
                }

// desconecto do host smtp
                $smtp->getConnection()->disconnect();

            } catch (Exception $erro) {
                $this->addFlashMessage("Error", "Erro no envio de e-mail: " . $erro->getMessage() .' -- '. $erro->getTraceAsString());
                $this->_redirect($_SERVER['HTTP_REFERER']);
            }

        }

    }


}