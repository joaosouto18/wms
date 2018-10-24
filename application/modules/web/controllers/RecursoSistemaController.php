<?php

/**
 * Description of SystemParamsController
 *
 * @author Renato Medina <medinadato@gmail.com>
 */
class Web_RecursoSistemaController extends \Wms\Module\Web\Controller\Action\Crud
{

    protected $entityName = 'Sistema\Recurso';

    public function indexAction()
    {
	$source = $this->em->createQueryBuilder()
		->select('r')
		->from("wms:{$this->entityName}", 'r')
		->orderBy('r.nome');

	$grid = new \Core\Grid(new \Core\Grid\Source\Doctrine($source));
	$grid->setId('recurso-sistema-grid');
	$grid->addColumn(array(
		    'label' => 'Cod do Recurso',
		    'index' => 'id',
		    'filter' => array(
			'render' => array(
			    'type' => 'number',
			    'range' => true,
			),
		    ),
		))
		->addColumn(array(
		    'label' => 'Nome do Recurso',
		    'index' => 'nome',
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
			)
		    ),
		    'hasOrdering' => false,
		))
		->addAction(array(
		    'label' => 'Editar',
		    'actionName' => 'edit',
		    'pkIndex' => 'id',
		))
		->addAction(array(
		    'label' => 'Excluir',
		    'actionName' => 'delete',
		    'pkIndex' => 'id',
		    'cssClass' => 'del',
		))
		->setHasOrdering(true);


	$this->view->grid = $grid->build();
    }

}
