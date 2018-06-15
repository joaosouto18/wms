<?php

namespace Core\Grid\Column\Render;

use Core\Grid\Column\Render,
    Core\Util\Converter;

/**
 * Converte para pontução padrão BR, valor esperado e.g. 10456.984 gerando e.g. 10.456,984
 *
 * @author Derlandy Belchior
 */
class Milesimal extends Render\ARender implements Render\IRender
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
            return Converter::enToBr($row[$index], 4);
        }
    }

}