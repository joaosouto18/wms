<?php
namespace Wms\Module\InventarioNovo\Form;
use Core\Form\SubForm;
use Wms\Module\InventarioNovo\Form\Subform\ComparativoInventario\Produtos;
use Wms\Module\Web\Form;

class ResultadoComparativoInventarioForm extends Form
{
    public function init($values = null)
    {
        $this->setAttribs(array('id' => 'inventario-comparativo-form', 'class' => 'saveForm'));
        $this->addSubFormTab('Resultado Inventário WMS', new Produtos(), 'resultado-inventario','comparativo-inventario/produtos.phtml');

        if ($values != null) {
            if ((count($values['apenas-wms']) >0) || (count($values['apenas-erp']) >0)) {
                $this->addSubFormTab('Inventário Gerado ERP', new Produtos(), 'inventario-erp','comparativo-inventario/produtos.phtml');
                $this->addSubFormTab('ERP e WMS Simultaneamente', new Produtos(), 'inventario-erp-wms','comparativo-inventario/produtos.phtml');
            }
            if ((count($values['apenas-wms']) >0)) {
                $this->addSubFormTab('Somente WMS', new Produtos(), 'apenas-wms','comparativo-inventario/produtos.phtml');
            }
            if ((count($values['apenas-erp']) >0)) {
                $this->addSubFormTab('Somente ERP', new Produtos(), 'apenas-erp','comparativo-inventario/produtos.phtml');
            }
        }

    }

    public function setDefaultsGrid($values) {
        $this->setDefaults($values);
        $this->getSubForm('resultado-inventario')->setDefaultsGrid($values['resultado-inventario']);

        if ((count($values['apenas-wms']) >0) || (count($values['apenas-erp']) >0)) {
            $this->getSubForm('inventario-erp')->setDefaultsGrid($values['inventario-erp']);
            $this->getSubForm('inventario-erp-wms')->setDefaultsGrid($values['inventario-erp-wms']);
        }
        if ((count($values['apenas-wms']) >0)) {
            $this->getSubForm('apenas-wms')->setDefaultsGrid($values['apenas-wms'], 'Estes produtos foram contados no WMS mas não estão presentes no ERP. Desta forma serão desconsiderados na hora de subir o estoque no ERP');
        }
        if ((count($values['apenas-erp']) >0)) {
            $this->getSubForm('apenas-erp')->setDefaultsGrid($values['apenas-erp'],'Estes produtos estão apenas no inventário do ERP e não foram contados no WMS. Com isso terão seu saldo zerado no ERP');
        }
    }

}