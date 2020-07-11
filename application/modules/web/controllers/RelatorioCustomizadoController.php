<?php
use Wms\Module\Web\Controller\Action;

class Web_RelatorioCustomizadoController extends Action
{
    public function consultarAction (){

    }

    public function relatorioAction (){
        $params = $this->getRequest()->getParams();

        $paramsQuery = array();
        $sort = arraY();
        $titulo = "";
        $query = "";

        /*
         * Esta parte é resultado de consulta em banco
         * montada na mão apenas para desenvolvimento da regra de negocio
         */

        //Dados do relatório
        $query = "SELECT COD_PRODUTO as Codigo, DSC_PRODUTO 
                    FROM PRODUTO P 
                   WHERE 1 = 1 :CodProduto :DscProduto";
        $titulo = "Relatório de Produtos";

        //Critérios de Filtro
        $paramsQuery[] = array(
            'name' => 'CodProduto',
            'label' => 'Código',
            'query' => " AND P.COD_PRODUTO = ':value' ",
            'required' => "N",
            'type' => 'text'
        );
        $paramsQuery[] = array(
            'name' => 'DscProduto',
            'label' => 'Descrição',
            'required' => "N",
            'query' => " AND P.DSC_PRODUTO LIKE '%:value%' ",
            'type' => 'text'
        );

        //Critérios de Ordenação
        $sort[] = array(
            'label' => 'Código ASC',
            'value' => 'P.COD_PRODUTO ASC'
        );
        $sort[] = array(
            'label' => 'Descrição ASC',
            'value' => 'P.DSC_PRODUTO ASC'
        );



        $form = new \Wms\Module\Web\Form\RelatorioCustomizado($paramsQuery);
        $form->init($paramsQuery, $sort);
        if (isset($params['btnBuscar']) || isset($params['btnPDF']) || isset($params['btnXLS'])) {

            foreach ($paramsQuery as $value) {
                $filterValue = "";
                if (isset($params[$value['name']]) && $params[$value['name']] != null)
                    $filterValue = $params[$value['name']];

                if ($filterValue != '') {
                    $filterValue = str_replace(':value' , $filterValue, $value['query']);
                }

                $query = str_replace(":" . $value['name'], $filterValue, $query );
            }

            if (isset($params['sort']) && $params['sort'] != null) {
                $query .= " ORDER BY " . $params['sort'];
            }

            $result = $this->getEntityManager()->getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);
            $form->setDefaults($params);

            if (count($result) == 0) {
                $this->addFlashMessage('info', 'Nenhum Resultado Encontrado');
            } else {
                if (isset($params['btnBuscar'])) {
                    $this->view->result = $result;
                }
                if (isset($params['btnPDF'])) {
                    $this->exportPDF($result,  $titulo, $titulo,'L');
                }
                if (isset($params['btnXLS'])) {
                    $this->exportCSV($result, $titulo,true );
                }
            }
        }
        $this->view->title = $titulo;
        $this->view->form = $form;

    }

    public function indexAction (){

    }
}

?>
