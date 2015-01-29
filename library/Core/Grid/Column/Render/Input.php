<?php
namespace Core\Grid\Column\Render;

use Core\Grid\Column\Render;


/**
 * Description of Text
 *
 */
class Input extends Render\ARender implements Render\IRender
{
    /**
     *
     * @return string
     */
    public function render()
    {
        $row = $this->getRow();
        $index = $this->getColumn()->getIndex();

        if (isset($row['sequencia']) and ($row['sequencia'] == null)) {
            $sequencia = '';
        } else {
            $sequencia = $row['sequencia'];
        }

        if ($row[$index] != null) {
            return "<input style='width:40px;' type='text' name=".$index."[".$row[$index]."] value='$sequencia' />";
        }
	
    }

}