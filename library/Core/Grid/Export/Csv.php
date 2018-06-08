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

        $file = 'teste';

        foreach ($grid->getColumns() as $columns) {
            if (!$columns->getIsReportColumn()) continue;
            
            $list[$key][] = $columns->getLabel();
        }
        $key++;
        foreach ($grid->getResult() as $row) {
            foreach ($grid->getColumns() as $columns) {
                if (!$columns->getIsReportColumn()) continue;

                $rowValue = $columns->getRender($row)->render();
                if (substr($rowValue,0,4) == '<div') {
                    $value = self::get_tag($rowValue,'div');
                    $rowValue = $value[0];
                }
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

    public static function get_tag($txt,$tag){
        $offset = 0;
        $start_tag = "<".$tag;
        $end_tag = "</".$tag.">";
        $arr = array();
        do{
            $pos = strpos($txt,$start_tag,$offset);
            if(!($pos === false)){
                $str_pos = strpos($txt,">",$pos)+1;
                $end_pos = strpos($txt,$end_tag,$str_pos);
                $len = $end_pos - $str_pos;
                $f_text = substr($txt,$str_pos,$len);


                $arr[] = $f_text;
                $offset = $end_pos;
            }
        }while($pos);
        return $arr;
    }

}
