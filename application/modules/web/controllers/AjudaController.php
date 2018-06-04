<?php

use \Wms\Module\Web\Controller\Action\Crud;

/**
 * Web_AjudaController
 *
 * @author : Renato Medina [medinadato@gmail.com]
 */
class Web_AjudaController extends Crud
{
    
    protected $entityName = 'Ajuda';
    
    public function indexAction()
    {

        $source = $this->em->createQueryBuilder()
                ->select('a, a.dscAjuda')
                ->from('wms:Ajuda', 'a')
                ->innerJoin('a.recursoAcao', 'ra')
                ->orderBy('a.numPeso');

        $grid = new \Core\Grid(new \Core\Grid\Source\Doctrine($source));
        $grid->setId('-grid');
        $grid->addColumn(array(
                    'label' => 'Ajuda Pai',
                    'index' => 'dscAjuda',
                    'filter' => array(
                        'render' => array(
                            'type' => 'text',
                            'condition' => array('match' => array('fulltext'))
                        ),
                    ),
                ))
                ->addColumn(array(
                    'label' => 'Título da Ajuda',
                    'index' => 'dscAjuda',
                    'filter' => array(
                        'render' => array(
                            'type' => 'text',
                            'condition' => array('match' => array('fulltext'))
                        ),
                    ),
                ))
                ->addColumn(array(
                    'label' => 'Recurso/Acao',
                    'index' => 'dscAjuda',
                    'filter' => array(
                        'render' => array(
                            'type' => 'text',
                            'condition' => array('match' => array('fulltext'))
                        ),
                    ),
                ))
                ->addColumn(array(
                    'label' => 'Peso',
                    'index' => 'numPeso',
                    'hasOrdering' => false,
                ))
                ->addAction(array(
                    'label' => 'Editar',
                    'actionName' => 'edit',
                    'pkIndex' => 'id'
                ))
                ->addAction(array(
                    'label' => 'Excluir',
                    'actionName' => 'delete',
                    'pkIndex' => 'id',
                    'cssClass' => 'del'
                ))
                ->setHasOrdering(true);

        $this->view->grid = $grid->build();
    }

}