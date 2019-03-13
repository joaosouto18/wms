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

        $this->addElement('text', 'descricao', array(
            'label' => 'Descrição',
            'size' => 50,
        ))->addElement('checkbox', 'default', array(
            'label' => 'Modelo de inventário padrão',
            'checkedValue' => 'S'
        ))->addElement('checkbox', 'ativo', array(
            'label' => 'Modelo de inventário ativo',
            'checkedValue' => 'S'
        ))->addElement('select', 'controlaValidade', array(
            'label' => 'Controla validade',
            'mostrarSelecione' => false,
            'multiOptions' => InventarioNovo\ModeloInventario::$statusValidade
        ))->addElement('checkbox', 'comparaEstoque', array(
            'label' => 'Compara estoque',
            'checkedValue' => 'S'
        ))->addElement('checkbox', 'usuarioNContagens', array(
            'label' => 'Um usuário pode fazer N contagens',
            'checkedValue' => 'S'
        ))->addElement('checkbox', 'contarTudo', array(
            'label' => 'Contar tudo do endereço',
            'checkedValue' => 'S'
        ))->addElement('checkbox', 'volumesSeparadamente', array(
            'label' => 'Contar volumes separadamente',
            'checkedValue' => 'S'
        ))->addElement('text', 'numContagens', array(
            'label' => 'Número de contagens',
            'size' => 1,
        ))->addDisplayGroup(array(
            'default',
            'descricao',
            'ativo',
            'controlaValidade',
            'comparaEstoque',
            'usuarioNContagens',
            'contarTudo',
            'volumesSeparadamente',
            'numContagens',
           ), 'identificacao');
    }



}