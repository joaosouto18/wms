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
class Web_MotivoDivergenciaRecebimentoController extends \Wms\Module\Web\Controller\Action\Crud
{

    protected $entityName = 'Recebimento\Divergencia\Motivo';

    public function indexAction()
    {
	$source = $this->em->createQueryBuilder()
		->select('m')
		->from('wms:Recebimento\Divergencia\Motivo', 'm')
		->orderBy('m.descricao');

	$grid = new \Core\Grid(new \Core\Grid\Source\Doctrine($source));
	$grid->setId('mmotivo-divergenciia-recebimneto-grid');
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