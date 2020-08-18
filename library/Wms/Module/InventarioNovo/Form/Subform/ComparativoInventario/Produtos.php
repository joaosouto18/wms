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
        $this->addElement('hidden', 'alert', array());
    }

    public function setDefaultsGrid($values, $alert = "") {

        if (count($values) == 0) $alert = "";
        $value = array();
        $grid = new ComparativoInventarioGrid();
        $grid->init($values);
        $value['grid'] =  $grid;
        $value['alert'] = $alert;
        $this->setDefaults($value);
    }
}