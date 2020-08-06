<?php
namespace Wms\Module\InventarioNovo\Form;
use Core\Form\SubForm;
use Wms\Module\InventarioNovo\Form\Subform\ComparativoInventario\Produtos;
use Wms\Module\Web\Form;

class ComparativoInventarioForm extends Form
{
    public function init($showExport = false)
    {
        $this->addElement('text', 'codInventario', array(
                'label' => 'Cód. Inventário (ERP)',
                'class' => 'focus'
            ))
            ->addElement('submit', 'btnSubmit', array(
                'class' => 'btn',
                'label' => 'Buscar',
                'decorators' => array('ViewHelper'),
                'attribs' => array('style' => 'margin-top:16px')
            ));
        if ($showExport == true) {
            $this->addElement('submit', 'btnExport', array(
                'class' => 'btn',
                'label' => 'Exportar',
                'decorators' => array('ViewHelper'),
                'attribs' => array('style' => 'margin-top:16px')
            ));

        }
            $this->addDisplayGroup($this->getElements(), 'Buscar', array('legend' => 'Inventário ERP'));
    }

}