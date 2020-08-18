<?php

use Core\Linfo\Exceptions\FatalException;
use Core\Linfo\Linfo;
use Core\Linfo\Common;

class Web_DevtoolsController extends \Wms\Controller\Action
{

    public function gerenciarServidorAction()
    {
        $settings = Common::getVarFromFile(APPLICATION_PATH . '/configs/linfo.php', 'settings');
        $linfo = new Linfo($settings);
        $linfo->scan();
        $output = new Core\Linfo\Output\Html($linfo);
        $output->output();
    }

    public function queryAction() {

    }


}