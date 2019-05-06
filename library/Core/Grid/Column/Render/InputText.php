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

        if ($row[$index] == "N") {
            return '<div style="float:right; position:  relative;left: -50%">-</div>';
        } else {

            $inputId = (isset($this->options['id']) && !is_null($this->options['id'])) ? "id='".$this->options['id']."-$id'" : "";
            $inputClass = (isset($this->options['class']) && !is_null($this->options['class'])) ? "class='".$this->options['class']."'" : "";

            if ($row[$index] != null) {
                return "<input style='width:40px;' type='text' $inputId $inputClass name=".$index."[".$id."] value='$value' />";
            } else {
                return "<input style='width:40px;' type='text' $inputId $inputClass name=".$index."[".$id."] value='' />";
            }
        }
    }

}