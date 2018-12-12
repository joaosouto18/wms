<?php
namespace Wms\Module\InventarioNovo\Form;

use Wms\Domain\Entity\InventarioNovo;
use Wms\Module\Web\Form,
    Core\Form\SubForm;


class ModeloInventarioForm extends Form
{

    public function init()
    {
        //form's attr
        $this->setAttribs(array('id' => 'modelo-inventario-form', 'class' => 'saveForm'));

        $form = new SubForm;
        $form->addElement('text', 'descricao', array(
            'label' => 'Descrição',
            'size' => 50,
        ));


    }

}