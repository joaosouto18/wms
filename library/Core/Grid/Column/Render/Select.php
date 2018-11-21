<?php
namespace Core\Grid\Column\Render;

use Core\Grid\Column\Render;


/**
 * Description of Text
 *
 */
class Select extends Render\ARender implements Render\IRender
{
    /**
     *
     * @return string
     */
    public function render()
    {
        $row = $this->getRow();
        $index = $this->getColumn()->getIndex();
        $values = $this->getColumn()->getValues();
        $name = strtolower($index);
        $enabled = $this->getColumn()->getEnabled();

        $disabled = "";
        if ($enabled == false) {
            $disabled = 'disabled="disabled"';
        }

        $pracas = array(
            0 => "<select name='" . $name . "' " . $disabled . " id='" . $name . "'>"
        );

        foreach ($values as $key => $praca) {
            $option = null;
            if ($row[$index] == $key) {
                $option = "<option selected='selected' value='$key'>$praca</option>";
            } else {
                $option = "<option value='$key'>$praca</option>";
            }
            array_push($pracas, $option);
        }

        array_push($pracas, "</select>");
        $result = implode('', $pracas);

        return $result;
    }

}