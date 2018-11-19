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
        $this->setAttribs(array('id' => 'inventario-tabs', 'class' => 'saveForm'));

        $this->addSubFormTab('Produto', new TabEnderecoForm(), 'produto','produto/identificacao-form.phtml');
        $this->addSubFormTab('Embalagens', new EmbalagemForm, 'embalagem', 'produto/embalagem-form.phtml');

    }

    /**
     *
     * @param ProdutoEntity $produto
     */
    public function setDefaultsFromEntity(ProdutoEntity $produto)
    {
        $this->getSubForm('produto')->setDefaultsFromEntity($produto);
        $this->getSubForm('embalagem')->setDefaultsFromEntity($produto);
    }

}