<?php
use Wms\Module\Web\Controller\Action,
    Wms\Module\Web\Page;

class Web_RelatorioCustomizadoController extends Action
{
    public function relatorioAction (){
        $buttons[] =  array(
            'label' => 'Selecionar outro relatório',
            'cssClass' => 'btnBack',
            //  'tag' => 'a'
        );

        Page::configure(array('buttons' => $buttons));

        $params = $this->getRequest()->getParams();
        $idRelatorio = 1;

        /** @var \Wms\Service\RelatorioCustomizadoService $reportService */
        $reportService = $this->getServiceLocator()->getService('RelatorioCustomizado');
        $assemblyData = $reportService->getAssemblyDataReport($idRelatorio);

        $title = $assemblyData['title'];

        $form = new \Wms\Module\Web\Form\RelatorioCustomizado();
        $form->init($assemblyData);
        $form->setDefaults($params);

        if (isset($params['btnBuscar']) || isset($params['btnPDF']) || isset($params['btnXLS'])) {

            $result = $reportService->executeReport($idRelatorio,$params);

            if (count($result) == 0) {
                $this->addFlashMessage('info', 'Nenhum Resultado Encontrado');
            } else {
                if (isset($params['btnBuscar'])) {
                    $grid = new \Wms\Module\Web\Grid\RelatorioCustomizado();
                    $grid->init($result, $assemblyData);
                    $this->view->grid = $grid;
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

    public function consultarAction (){
        $reportService = $this->getServiceLocator()->getService('RelatorioCustomizado');
        $relatoriosDisponíveis = json_encode($reportService->getReports());

        $this->view->reports = $relatoriosDisponíveis;
    }

}

?>
