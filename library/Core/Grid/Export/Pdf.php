<?php

namespace Core\Grid\Export;

use Core\Grid\Export\IExport,
    Core\Grid;

/**
 * Description of Pdf
 *
 * @author Renato Medina <medinadato@gmail.com>
 */
class Pdf implements IExport
{

    /**
     *
     * @param Grid $grid 
     * @param string $title 
     */
    public static function render(Grid $grid, $title = '')
    {
        header("Content-Type: application/pdf;");
        
        $colsLength = array();
        $pageCharWidth = 0;
        $numRows = 0;
        $fatorTitle = 2.30;
        $fatorText = 1.90;

        // calc size of the page, columns and rows
        /** @var Grid\Column $column */
        foreach ($grid->getColumns() as $column) {
            if (!$column->getIsReportColumn())
                continue;

            $widthColumn = $column->getWidth();
            if (empty($widthColumn)) {
                $size = (strlen($column->getLabel()) * $fatorTitle);

                foreach ($grid->result as $row) {

                    if ($row[$column->getIndex()]) {
                        $rowValue = $column->getRender($row)->render();

                        $size = ($size > (strlen($rowValue) * $fatorText)) ? $size : (strlen($rowValue) * $fatorText);
                    }
                }
            } else {
                $size = ($widthColumn * 4) * $fatorText;
            }

            $pageCharWidth += $colsLength[$column->getIndex()] = $size;
        }

        $pageOrientation = ($pageCharWidth > 195) ? 'L' : 'P';

        $pdf = new \Wms\Module\Web\Pdf($pageOrientation, 'mm', 'A4');

        // header
        $pdf->setTitle(utf8_decode($title))
                ->setLabelHeight(5)
                ->setColHeight(6)
                ->setNumRows(count($grid->result))
                ->SetFont('Arial');


        foreach ($grid->getColumns() as $columns) {
            if (!$columns->getIsReportColumn())
                continue;

            $pdf->addLabel($columns->getIndex(), $colsLength[$columns->getIndex()], utf8_decode($columns->getLabel()), 'B', 0, $columns->getAlign());
        }
        $pdf->addLabel(1, 1, '', 0, 1, 'R', false, '');

        foreach ($grid->getResult() as $row) {
            foreach ($grid->getColumns() as $columns) {
                if (!$columns->getIsReportColumn())
                    continue;
                
                $rowValue = $columns->getRender($row)->render();
                $wColRow = $colsLength[$columns->getIndex()];
                $pdf->addCol($columns->getIndex(), $wColRow, $pdf->SetStringByMaxWidth($rowValue,$wColRow + (($wColRow / 100) * 25), false), 0, 0, $columns->getAlign(), false, '', $columns->getHasTotal());
            }
            $pdf->addCol(1, 1, '', 0, 1, 'R', false, '');
        }

        // page
        $pdf->addPage()
                ->render()
                ->output($grid->getId().'.pdf','D');
    }

}
