<?php
use \Wms\Module\Web\Controller\Action;
/**
 * Description of Web_LayoutDesignerController
 *
 * @author Tarcisio César <tarcisiocms@outlook.com>
 */
class Web_LayoutDesignerController extends Action
{
    public function indexAction()
    {
        \Wms\Module\Web\Page::configure(array('buttons' => [['label' => 'Novo Layout',
            'cssClass' => 'button',
            'urlParams' => array(
                'module' => 'web',
                'controller' => 'layout-designer',
                'action' => 'add'
            ),
            'tag' => 'a']]));
    }

    public function addAction()
    {
        $this->view->components = [
            [
                'type' => 'text',
                'render' => '99999',
                'field' => 'COD_PRODUTO'
            ]
        ];

        $form = new Wms\Module\Web\Form;
        $form->addElement('select', 'template',[
                'label' => 'Layout para:',
                'mostrarSelecione' => true,
                'multiOptions' => [
                    1 => 'Etiqueta de Separação',
                    2 => 'Mapa de Separação',
                    3 => 'Etiqueta de Produto',
                ]
            ])->addElement('text', 'largura', [
                'label' => 'Largura do Layout'
            ])->addElement('text', 'altura', [
                'label' => 'Altura do Layout'
            ])->addDisplayGroup($form->getElements(), 'Template');

        $form->addElement('select', 'component',[
                'label' => 'Componente',
                'mostrarSelecione' => true,
                'multiOptions' => [
                    1 => 'Etiqueta de Separação',
                    2 => 'Mapa de Separação',
                    3 => 'Etiqueta de Produto',
                ]
            ])->addElement('button', 'btnAdd', [
                'label' => ''
            ])->addDisplayGroup($form->getElements(), 'Template');

        $this->view->templateForm = $form;
    }
}