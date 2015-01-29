<?php

use \Wms\Domain\Entity\Deposito\AreaArmazenagem,
    \Wms\Module\Web\Controller\Action\Crud;

/**
 * Description of SystemParamsController
 *
 * @author Renato Medina <medinadato@gmail.com>
 */
class Web_AreaArmazenagemController extends Crud
{

    protected $entityName = 'Deposito\AreaArmazenagem';

    public function indexAction()
    {

	$source = $this->em->createQueryBuilder()
		->select('a, d.descricao as dscDeposito')
		->from('wms:Deposito\AreaArmazenagem', 'a')
		->innerJoin('a.deposito', 'd')
		->where('a.deposito = :idDeposito')
		->orderBy('a.descricao')
		->setParameter('idDeposito', $this->view->idDepositoLogado);

	$grid = new \Core\Grid(new \Core\Grid\Source\Doctrine($source));
	$grid->setId('area-armazenagem-grid');
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
		    'label' => 'Área de Armazenagem',
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

    public function addAction()
    {
	$depositos = $this->em->getRepository('wms:Deposito')->findAll();

	if (count($depositos) > 0) {
	    parent::addAction();
	} else {
	    $this->addFlashMessage('error', 'Para cadastrar uma área de armazenagem, é necessário que haja ao menos um depósito cadastrada');
	    return $this->redirect('index');
	}
    }

}