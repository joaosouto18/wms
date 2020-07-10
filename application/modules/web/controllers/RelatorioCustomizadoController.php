<?php
use Wms\Module\Web\Controller\Action;

class Web_RelatorioCustomizadoController extends Action
{
    public function consultarAction (){

    }

    public function relatorioAction (){
        $Query = "SELECT COD_PRODUTO as Codigo, DSC_PRODUTO 
                    FROM PRODUTO P 
                   WHERE 1 = 1 @CodProduto";

        $paramsQuery = array();
        $paramsQuery[] = array(
            'name' => 'CodProduto',
            'label' => 'Código',
            'query' => " AND P.COD_PRODUTO = '@Value' ",
            'type' => 'text'
        );
        $paramsQuery[] = array(
            'name' => 'DscProduto',
            'label' => 'Descrição',
            'query' => " AND P.DSC_PRODUTO LIKE '%@Value%' ",
            'type' => 'text'
        );

        $form = new \Wms\Module\Web\Form\RelatorioCustomizado($paramsQuery);
        $form->init($paramsQuery);
        $this->view->form = $form;

    }

    public function indexAction (){

    }
}

?>
