<?php

use Wms\Module\Web\Controller\Action,
    Core\Grid\Export\IExport,
    Core\Grid;

/**
 * Description of Web_Planilha_MovEntradaSaidaController
 *
 * @author Renato Medina <medinadato@gmail.com>
 */
class Web_Planilha_MovEntradaSaidaController extends Action
{

    /**
     * 
     */
    public function indexAction()
    {
        $this->getHelper('layout')->disableLayout();
        $this->getHelper('viewRenderer')->setNoRender();
        header('Content-type: application/pdf');


        $pdf = new \Wms\Module\Web\Pdf('L', 'mm', 'A4');

        // header
        $pdf->setTitle('Controle de Movimentação de Entrada e Saída')
                ->setLabelHeight(5)
                ->setColHeight(9);


        $pdf->addLabel(1, 35, 'Codigo', 'B', 0, 'C');
        $pdf->addLabel(2, 65, 'Descricao', 'B', 0, 'C');
        $pdf->addLabel(3, 40, 'Grade', 'B', 0, 'C');
        $pdf->addLabel(4, 40, 'Tipo Movimentacao', 'B', 0, 'C');
        $pdf->addLabel(5, 40, 'Endereço', 'B', 0, 'C');
        $pdf->addLabel(6, 30, 'Volume', 'B', 0, 'C');
        $pdf->addLabel(7, 30, 'Qtde. Mov.', 'B', 1, 'C');

        for ($i = 0; $i < 14; $i++) {
            $pdf->addCol(1, 35, '', 1, 0, 'L');
            $pdf->addCol(2, 65, '', 1, 0, 'L');
            $pdf->addCol(3, 40, '', 1, 0, 'L');
            $pdf->addCol(4, 40, '', 1, 0, 'L');
            $pdf->addCol(5, 40, '', 1, 0, 'L');
            $pdf->addCol(6, 30, '', 1, 0, 'L');
            $pdf->addCol(7, 30, '', 1, 1, 'L');
        }
        $date = new Zend_Date();
        $pdf->addCol(1, 1, '', 0, 1);
        $pdf->addCol(1, 50, 'Data: ______ / ______ / ______ ', 0, 0, 'L');
        $pdf->addCol(2, 125, 'Ope. Empilhadeira: ________________________________________________________ ', 0, 0, 'L');
        $pdf->addCol(7, 50, ucfirst($date->get("EEEE") . ', ' . $date->get("d") . ' de ' . $date->get("MMMM") . ' de ' . $date->get("Y")), 0, 1, 'L');

        // page
        $pdf->AddPage()
                ->render()
                ->Output();
    }

}