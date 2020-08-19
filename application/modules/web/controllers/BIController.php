<?php

use Core\Linfo\Exceptions\FatalException;
use Core\Linfo\Linfo;
use Core\Linfo\Common;

class Web_BIController extends \Wms\Controller\Action
{

    public function indexAction(){
        $idReportIframe = $this->getSystemParameterValue('idIframeBi');
        //$idReportIframe = "cdcca926-6d49-4dcd-8ade-3de1f4adc387&autoAuth=true&ctid=029ed487-a470-44ac-96b5-7649f9be6f91&config=eyJjbHVzdGVyVXJsIjoiaHR0cHM6Ly93YWJpLWJyYXppbC1zb3V0aC1yZWRpcmVjdC5hbmFseXNpcy53aW5kb3dzLm5ldC8ifQ%3D%3D";

        if (strlen(trim($idReportIframe)) == 0) {
            $this->addFlashMessage("Visualização do B.I. não configurada no WMS");
        } else {
            $this->view->idReportIframe = $idReportIframe;
        }


    }

}