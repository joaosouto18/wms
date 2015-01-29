
<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

use \Wms\Domain\Entity\Deposito\Endereco\Regra,
    \Wms\Module\Web\Controller\Action\Crud;

/**
 * Description of SystemParamsController
 *
 * @author Renato Medina <medinadato@gmail.com>
 */
class Web_RegraEnderecoController extends Crud
{

    protected $entityName = 'Deposito\Endereco\Regra';

    public function indexAction()
    {
	$source = $this->em->createQueryBuilder()
		->select('r')
		->from('wms:Deposito\Endereco\Regra', 'r')
		->orderBy('r.descricao');

	$grid = new \Core\Grid(new \Core\Grid\Source\Doctrine($source));
	$grid->setId('regra-grid');
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