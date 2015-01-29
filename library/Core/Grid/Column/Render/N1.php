<?php

namespace Core\Grid\Column\Render;

use Core\Grid\Column\Render,
    Core\Util\Converter;

/**
 * Converte para pontução padrão BR, valor esperado e.g. 10456.98 gerando e.g. 10.456,98
 *
 * @author Adriano Uliana
 */
class N1 extends Render\ARender implements Render\IRender
{

    /**
     *
     * @return string
     */
    public function render()
    {
        $row = $this->getRow();
        $index = $this->getColumn()->getIndex();

        if ($row[$index] != null) {
            return Converter::enToBr($row[$index], 1);
        }
    }

}