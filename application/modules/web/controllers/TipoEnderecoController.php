<?php

use \Wms\Module\Web\Controller\Action\Crud;

/**
 * Description of Web_TipoEnderecoController
 *
 * @author Renato Medina <medinadato@gmail.com>
 */
class Web_TipoEnderecoController extends Crud
{

    protected $entityName = 'Deposito\Endereco\Tipo';

    public function indexAction()
    {
	$source = $this->em->createQueryBuilder()
		    ->select('t')
		    ->from('wms:Deposito\Endereco\Tipo', 't')
		    ->orderBy('t.descricao');
        
	$grid = new \Core\Grid(new \Core\Grid\Source\Doctrine($source));
	$grid->setId('tipo-endereco-grid');
	$grid->addColumn(array(
		    'label' => 'Descrição',
		    'index' => 'descricao',
		    'filter' => array(
			'render' => array(
			    'type' => 'text',
			    'condition' => array('match' => array('fulltext'))
			),
		    ),
		))
		->addColumn(array(
		    'label' => 'Largura (m)',
		    'index' => 'largura',
		    'render' => 'centesimal',
		    'filter' => array(
			'render' => array(
			    'type' => 'centesimal',
			    'range'=> true,
			),
		    ),
		))
		->addColumn(array(
		    'label' => 'Altura (m)',
		    'index' => 'altura',
		    'render' => 'centesimal',
		    'filter' => array(
			'render' => array(
			    'type' => 'centesimal',
			    'range' => true,
			),
		    ),
		))
		->addColumn(array(
		    'label' => 'Profundidade (m)',
		    'index' => 'profundidade',
		    'render' => 'centesimal',
		    'filter' => array(
			'render' => array(
			    'type' => 'centesimal',
			    'range' => true,
			),
		    ),
		))
		->addColumn(array(
		    'label' => 'Cubagem (m³)',
		    'index' => 'cubagem',
		    'render' => 'milesimal',
		    'filter' => array(
			'render' => array(
			    'type' => 'milesimal',
			    'range' => true,
			),
		    ),
		))
		->addColumn(array(
		    'label' => 'Capacidade (kg)',
		    'index' => 'capacidade',
		    'render' => 'centesimal',
		    'filter' => array(
			'render' => array(
			    'type' => 'centesimal',
			    'range' => true,
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