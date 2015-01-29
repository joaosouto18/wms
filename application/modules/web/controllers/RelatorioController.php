<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

use \Wms\Module\Web\Controller\Action\Crud,
    Wms\Domain\Entity\PessoaEndereco;

/**
 * Description of UserController
 *
 * @author Renato Medina <medinadato@gmail.com>
 */
class Web_RelatorioController extends Crud 
{

    public function indexAction() 
    {

        try {
            $jasper = new Adl\Integration\RequestJasper();
	    
            /*Para enviar a saída para o browser*/
            header('Content-type: application/pdf');
	    /*Caminho do relatorio*/
            echo $jasper->run('/reports/samples/AllAccounts');

            exit;

            /*Para salvar o conteúdo em um arquivo no disco
	     * O caminho onde o arquivo será salvo é registrado em config / Data.ini
             */
            //$jasper->runReport('/reports/samples/AllAccounts','PDF', null, true);
        } catch (\Exception $e) {
            echo $e->getMessage();
            die;
	}
    }

}

?>
