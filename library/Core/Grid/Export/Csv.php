<?php

namespace Core\Grid\Export;

use Core\Grid\Export\IExport,
    Core\Grid;

/**
 * Description of Csv
 *
 * @author Renato Medina <medinadato@gmail.com>
 */
class Csv implements IExport
{
    /**
     *
     * @param Grid $grid 
     * @param string $title 
     */
    public static function render(Grid $grid, $title = '')
    {
        header("Content-type: text/csv");
        header("Content-Disposition: attachment; filename=" . strtolower($title) . ".csv; charset=UTF-8");
        header("Pragma: no-cache");
        header("Expires: 0");        
        
        $list = array();
        $key = 0;

        foreach ($grid->getColumns() as $columns) {
            if (!$columns->getIsReportColumn()) continue;
            
            $list[$key][] = $columns->getLabel();
        }
        $key++;
        
        foreach ($grid->getResult() as $row) {
            foreach ($grid->getColumns() as $columns) {
                if (!$columns->getIsReportColumn()) continue;
                
                $rowValue = $columns->getRender($row)->render();

                $list[$key][] = $rowValue;
            }
            $key++;
        }

        $fp = fopen("php://output", 'w');
        foreach ($list as $fields) {
            $fields = array_map("utf8_decode", $fields);
            fputcsv($fp, $fields,';');
        }

        fclose($fp);
    }

}
