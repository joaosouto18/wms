<?php

use Core\Controller\Action,
    Core\Grid;

class Web_GridController extends Wms\Module\Web\Controller\Action
{

    public function init()
    {
	/* Initialize action controller here */
    }

    public function indexAction()
    {
	$em = $this->getDoctrineContainer()->getEntityManager();
	
	$qb = $em->createQueryBuilder();
	$qb->select('a')
		->from('wms:Ajuda', 'a');
	
	$grid = new Grid(new \Core\Grid\Source\Doctrine($qb));

	$grid->addColumn(array(
	    'label' => 'Nome',
	    'index' => 'Nome',
	    'width' => '200',
	    'render' => array(
		'type' => 'link',
		'options' => array(
		    'href' => 'dscConteudoAjuda',
		    'label' => 'dscConteudoAjuda'
		)
	    )
	));

	$grid->addColumn(array(
	    'label' => 'E-mail',
	    'index' => 'dscIdentificacaoAjuda',
	    'width' => '200',
	    'render' => array(
		'type' => 'text'
	    )
	));
	
	$grid->addAction(array(
	    'label'         => 'Editar ajuda',
	    'pkIndex'	    => 'codIdentificacaoAjuda',
	    'controllerName'=> 'ajuda',
	    'actionName'    => 'edit',
	));
	
	$grid->addAction(array(
	    'label'         => 'Visualizar ajuda',
	    'pkIndex'	    => 'codIdentificacaoAjuda',
	    'controllerName'=> 'ajuda',
	    'actionName'    => 'view',
	    'cssClass'	    => 'view'
	));
	
	$grid->addAction(array(
	    'label'         => 'Remover ajuda',
	    'pkIndex'	    => 'codIdentificacaoAjuda',
	    'controllerName'=> 'ajuda',
	    'actionName'    => 'delete',
	    'cssClass'	    => 'del'
	));

	$this->view->grid = $grid->build();
    }

}

