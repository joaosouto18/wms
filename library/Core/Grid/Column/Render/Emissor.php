<?php
namespace Core\Grid\Column\Render;
use Core\Grid\Column\Render;
use Wms\Domain\Entity\NotaFiscal\Tipo;

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Text
 *
 * @author Administrator
 */
class Emissor extends Render\ARender implements Render\IRender
{
    /**
     *
     * @return string
     */
    public function render()
    {
	$row = $this->getRow();
	$index = $this->getColumn()->getIndex();
	return Tipo::$arrResponsaveis[$row[$index]];
    }

}

?>
