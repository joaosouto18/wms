<?php
namespace Wms\Module\InventarioNovo\Form;

use Wms\Module\Web\Form;

/**
 * Created by PhpStorm.
 * User: Tarcísio César
 * Date: 14/11/2018
 * Time: 16:22
 */

class AddInventarioForm extends Form
{
    public function init()
    {
        //form's attr
        $this->setAttribs(array('id' => 'inventario-tabs', 'class' => 'saveForm', 'ng-app' => 'app', 'ng-controller' => 'cadastroInventarioCtrl'));

        $this->addSubFormTab('Por Endereço', new TabEnderecoForm(), "tabEndereco");
        $this->addSubFormTab('Por Produto', new TabProdutoForm(), "tabProduto");

    }
}