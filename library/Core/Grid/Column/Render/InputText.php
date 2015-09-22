<?php
namespace Core\Grid\Column\Render;

use Core\Grid\Column\Render;


/**
 * Description of Text
 *
 */
class InputText extends Render\ARender implements Render\IRender
{
    /**
     *
     * @return string
     */
    public function render()
    {
        $row = $this->getRow();

        $id = $row['ID'];
        $value = $row['VALUE'];
        $index = $this->getColumn()->getIndex();

        if ($row[$index] != null) {
            return "<input style='width:40px;' type='text' name=".$index."[".$id."] value='$value' />";
        } else {
            return "<input style='width:40px;' type='text' name=".$index."[".$id."] value='' />";
        }

	
    }

}