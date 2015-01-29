<?php

namespace Core\Grid\Export;

use Core\Grid\Export\IExport,
    Core\Grid;

/**
 * Description of Xml
 *
 * @author Renato Medina <medinadato@gmail.com>
 */
class Xml implements IExport
{

    /**
     *
     * @param Grid $grid 
     * @param string $title 
     */
    public static function render(Grid $grid, $title = '')
    {   
        header("Content-Type: text/xml;");
        
        # Instancia do objeto XMLWriter
        $xml = new \XMLWriter;

        # Cria memoria para armazenar a saida
        $xml->openMemory();
        
        # Inicia o cabeçalho do documento XML
        $xml->startDocument('1.0', 'iso-8859-1');

        # Adiciona/Inicia um Elemento / Nó Pai <item>
        $xml->startElement("root");

        foreach ($grid->getColumns() as $column) {
            $xml->startElement("item");
            foreach ($grid->getResult() as $row) {

                if (!$column->getIsReportColumn())
                    continue;

                $rowValue = $column->getRender($row)->render();

                $xml->writeElement(\Core\Util\String::convertToXmlTag($column->getLabel()), $rowValue);
            }
            $xml->endElement();
        }

        #  Finaliza o Nó Pai / Elemento <Item>
        $xml->endElement();

        # Imprime os dados armazenados
        print $xml->outputMemory(true);
    }

}
