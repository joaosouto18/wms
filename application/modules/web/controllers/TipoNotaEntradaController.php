<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of SystemParamsController
 *
 * @author Renato Medina <medinadato@gmail.com>
 */
class Web_TipoNotaEntradaController extends \Wms\Module\Web\Controller\Action\Crud
{

    protected $entityName = 'Deposito\Nota\Entrada\Tipo';

    public function indexAction()
    {
        $source = $this->em->createQueryBuilder()
                ->select('t')
                ->from('wms:Deposito\Nota\Entrada\Tipo', 't')
                ->orderBy('t.descricao');

        $grid = new \Core\Grid(new \Core\Grid\Source\Doctrine($source));
        $grid->setId('tipo-nota-entrada-grid');
        $grid->addColumn(array(
                    'label' => 'Código',
                    'index' => 'id',
                    'filter' => array(
                        'render' => array(
                            'type' => 'text',
                            'condition' => array('match' => array('fulltext'))
                        ),
                    ),
                ))
                ->addColumn(array(
                    'label' => 'Descrição',
                    'index' => 'descricao',
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

?>