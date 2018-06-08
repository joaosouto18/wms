<?php
namespace Core\Grid\Column\Render;

use Core\Grid\Column\Render;

/**
 * Description of Text
 *
 * @author Administrator
 */
class Text extends Render\ARender implements Render\IRender
{

    /**
     *
     * @return string
     */
    public function render()
    {
	$row = $this->getRow();
	$index = $this->getColumn()->getIndex();

        if ($row[$index] == '') {
            return '<div style="float:right; position:  relative;left: -50%">-</div>';
        }

        return $row[$index];
    }

}