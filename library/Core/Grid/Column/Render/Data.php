<?php
namespace Core\Grid\Column\Render;

use Core\Grid\Column\Render;


/**
 * Description of Text
 *
 * @author Administrator
 */
class Data extends Render\ARender implements Render\IRender
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
	    return $row[$index]->format('d/m/Y');
	}
	
    }

}