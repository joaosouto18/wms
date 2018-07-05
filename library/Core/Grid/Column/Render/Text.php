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
	return $row[$index];
    }

}