<?php

class Web_Relatorio_ListaUsuarioController extends \Wms\Controller\Action
{
    public function indexAction() 
    {
	try {
            $jasper = new Adl\Integration\RequestJasper();
            
            header('Content-type: application/pdf');
            echo $jasper->run('/reports/WMS/usuarios', 'pdf', null);
            exit;
	} catch (\Exception $e) {
	    echo $e->getMessage();
	    die;
	}
    }
}