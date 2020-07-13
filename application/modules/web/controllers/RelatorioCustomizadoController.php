<?php
use Wms\Module\Web\Controller\Action;

class Web_RelatorioCustomizadoController extends Action
{
    public function relatorioAction (){
        $params = $this->getRequest()->getParams();
        $idRelatorio = 1;

        $reportService = $this->getServiceLocator()->getService('RelatorioCustomizado');
        $report = $reportService->getDadosReport($idRelatorio);

        $paramsQuery = $report['filters'];
        $sort = $report['sort'];
        $title = $report['title'];
        $query = $report['query'];

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
                    $this->exportPDF($result,  $title,$title,'L');
                }
                if (isset($params['btnXLS'])) {
                    $this->exportCSV($result, $title,true );
                }
            }
        }
        $this->view->title = $title;
        $this->view->form = $form;

    }

    public function indexAction (){

    }

    public function consultarAction (){

    }

}

?>
