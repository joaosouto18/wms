<?php

use \Wms\Module\Web\Controller\Action\Crud,
    \Wms\Module\Web\Page;

/**
 * Description of Web_EmpresaController
 *
 * @author Renato Medina <medinadato@gmail.com>
 */
class Web_EmpresaController extends Crud
{

    protected $entityName = 'Empresa';

    public function indexAction()
    {
        $source = $this->em->createQueryBuilder()
            ->select('e')
            ->from('wms:Empresa', 'e')
            ->orderBy('e.nomEmpresa');

        $grid = new \Core\Grid(new \Core\Grid\Source\Doctrine($source));
        $grid->setId('recurso-sistema-grid');
        $grid->addColumn(array(
            'label' => 'Nome',
            'index' => 'nomEmpresa',
            'filter' => array(
                'render' => array(
                    'type' => 'text',
                    'condition' => array('match' => array('fulltext'))
                ),
            ),
        ))
            ->addColumn(array(
            'label' => 'CNPJ',
            'index' => 'identificacao',
            'filter' => array(
                'render' => array(
                    'type' => 'text',
                    'condition' => array('match' => array('fulltext'))
                ),
            ),
        ))
            ->addColumn(array(
            'label' => 'Prioridade Estoque',
            'index' => 'prioridadeEstoque',
            'filter' => array(
                'render' => array(
                    'type' => 'text',
                    'condition' => array('match' => array('fulltext'))
                ),
            ),
        ))
            ->addAction(array(
                'label' => 'Editar',
                'actionName' => 'edit',
                'pkIndex' => 'id'
            ))
            ->setHasOrdering(true);



        $this->view->grid = $grid->build();
    }
}