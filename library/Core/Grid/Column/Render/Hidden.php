<?php
namespace Core\Grid\Column\Render;

use Core\Grid\Column\Render;

/**
 * Description of Text
 *
 * @author Administrator
 */
class Hidden extends Render\ARender implements Render\IRender
{

    /**
     *
     * @return string
     */
    public function render()
    {
	$row = $this->getRow();
	$index = $this->getColumn()->getIndex();
	return "<input style='width:40px;' type='hidden' name=".$index."[".$row[$index]."] value='$row[$index]' />";
    }

}