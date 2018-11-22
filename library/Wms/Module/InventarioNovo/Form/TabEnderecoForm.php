<?php
namespace Wms\Module\InventarioNovo\Form;
use Core\Form\SubForm;

/**
 * Created by PhpStorm.
 * User: Tarcísio César
 * Date: 14/11/2018
 * Time: 16:25
 */

class TabEnderecoForm extends SubForm
{
    public function init()
    {
        $this->addElement('text', 'id', array(
                'label' => 'Endereco',
                'size' => 10,
                'readonly' => 'readonly',
                'class' => 'focus',
                'required' => true
            )
        );
    }
}