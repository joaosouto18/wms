<?php

use \Wms\Domain\Entity\Deposito\Endereco\Caracteristica,
    \Wms\Module\Web\Controller\Action\Crud;

/**
 * Description of Web_CaracteristicaEnderecoController
 *
 * @author Renato Medina <medinadato@gmail.com>
 */
class Web_CaracteristicaEnderecoController extends Crud
{

    protected $entityName = 'Deposito\Endereco\Caracteristica';

    public function indexAction()
    {
	$source = $this->em->createQueryBuilder()
		->select('c')
		->from('wms:Deposito\Endereco\Caracteristica', 'c')
		->orderBy('c.descricao');

	$grid = new \Core\Grid(new \Core\Grid\Source\Doctrine($source));
	$grid->setId('caracteristica-grid');
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
			)
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