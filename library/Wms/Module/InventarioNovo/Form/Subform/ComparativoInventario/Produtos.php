<?php

namespace Wms\Module\InventarioNovo\Form\Subform\ComparativoInventario;

use Core\Form\SubForm;
use Wms\Module\InventarioNovo\Grid\ComparativoInventarioGrid;

class Produtos extends SubForm
{

    /**
     * @throws \Zend_Form_Exception
     */
    public function init()
    {
        $this->addElement('hidden', 'grid', array());
    }

    public function setDefaultsGrid($values) {
        $value = array();
        $grid = new ComparativoInventarioGrid();
        $grid->init($values);
        $value['grid'] =  $grid;
        $this->setDefaults($value);
    }
}