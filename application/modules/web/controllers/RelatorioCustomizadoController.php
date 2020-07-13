<?php
use Wms\Module\Web\Controller\Action;

class Web_RelatorioCustomizadoController extends Action
{
    public function relatorioAction (){
        $params = $this->getRequest()->getParams();
        $idRelatorio = 1;

        $reportService = $this->getServiceLocator()->getService('RelatorioCustomizado');
        $report = $reportService->getAssemblyReport($idRelatorio);

        $filters = $report['filters'];
        $sort = $report['sort'];
        $title = $report['title'];

        $form = new \Wms\Module\Web\Form\RelatorioCustomizado();
        $form->init($filters, $sort);
        $form->setDefaults($params);

        if (isset($params['btnBuscar']) || isset($params['btnPDF']) || isset($params['btnXLS'])) {

            $result = $reportService->executeReport($idRelatorio,$params);

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
