<?php
namespace Core\Grid\Column\Render;

use Core\Grid\Column\Render;

/**
 * Description of Text
 *
 * @author Administrator
 */
class Info extends Render\ARender implements Render\IRender
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
            return '';
        }

        return '<a class="dialogAjax" href="/index/info-ajax/info/'. $row[$index] . '" title="' . $row[$index] . '"><img src="/img/icons/bell.png"></a>';
    }

}