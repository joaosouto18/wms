<?php

/**
 * Description of Web_LinhaSeparacaoController
 *
 * @author Renato Medina <medinadato@gmail.com>
 */
class Web_LinhaSeparacaoController extends \Wms\Module\Web\Controller\Action\Crud
{

    protected $entityName = 'Armazenagem\LinhaSeparacao';

    public function indexAction()
    {
      $source = $this->em->createQueryBuilder()
		->select('l')
		->from('wms:Armazenagem\LinhaSeparacao', 'l')
		->orderBy('l.descricao');

	$grid = new \Core\Grid(new \Core\Grid\Source\Doctrine($source));
	$grid->setId('linha-separacao-grid');
	$grid->addColumn(array(
		    'label' => 'Código',
		    'index' => 'id',
		    'filter' => array(
			'render' => array(
			    'type' => 'number',
			    'range' => true,
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