<?php
namespace Core\Grid\Column\Render;

use Core\Grid\Column\Render;


/**
 * Description of Text
 *
 */
class Checkbox extends Render\ARender implements Render\IRender
{
    /**
     *
     * @return string
     */
    public function render()
    {
        $row = $this->getRow();
        $index = $this->getColumn()->getIndex();
        $name = strtolower($index);
        if (empty($row[$index]) and ($row[$index] == null)) {
            $valor = '';
        } else {
            $valor = $row[$index];
        }

        if ( !empty($valor) ) {
            return "<input class='checkBoxClass' type='checkbox' name='".$name."[]' id='".$name."' value='$valor' />";
        }
	
    }

}