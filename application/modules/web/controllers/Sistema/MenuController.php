<?php

use \Wms\Module\Web\Controller\Action\Crud;

/**
 * Admin_LoginController
 *
 * @author : Augusto Vespermann
 */
class Web_Sistema_MenuController extends Crud
{

    protected $entityName = 'Sistema\MenuItem';

    public function indexAction()
    {

        $source = $this->em->createQueryBuilder()
                ->select('mi, mip.dscMenuItem menuPai, CONCAT(CONCAT(r.descricao,\' > \'),p.nome) permissao')
                ->from('wms:Sistema\MenuItem', 'mi')
                ->innerJoin('mi.pai', 'mip')
                ->innerJoin('mi.permissao', 'p')
                ->innerJoin('p.recurso', 'r')
                ->orderBy('mip.peso, mi.peso');

        $grid = new \Core\Grid(new \Core\Grid\Source\Doctrine($source));
        $grid->setId('-grid');
        $grid->addColumn(array(
                    'label' => 'Código',
                    'index' => 'id',
                    'filter' => array(
                        'render' => array(
                            'type' => 'number',
                        ),
                    ),
                ))
                ->addColumn(array(
                    'label' => 'Pai',
                    'index' => 'menuPai',
                    'filter' => array(
                        'render' => array(
                            'type' => 'text',
                            'condition' => array('match' => array('fulltext'))
                        ),
                    ),
                ))
                ->addColumn(array(
                    'label' => 'Descrição',
                    'index' => 'dscMenuItem',
                    'filter' => array(
                        'render' => array(
                            'type' => 'text',
                            'condition' => array('match' => array('fulltext'))
                        ),
                    ),
                ))
                ->addColumn(array(
                    'label' => 'Permissão',
                    'index' => 'permissao',
                    'filter' => array(
                        'render' => array(
                            'type' => 'text',
                            'condition' => array('match' => array('fulltext'))
                        ),
                    ),
                ))
                ->addColumn(array(
                    'label' => 'Peso',
                    'index' => 'peso',
                    'hasOrdering' => false,
                ))
                ->addColumn(array(
                    'label' => 'URL',
                    'index' => 'url',
                    'hasOrdering' => false,
                ))
                ->addColumn(array(
                    'label' => 'Target',
                    'index' => 'target',
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