<?php

use \Wms\Domain\Entity\Deposito\Box,
    \Wms\Module\Web\Controller\Action\Crud;

/**
 * Description of SystemParamsController
 *
 * @author Renato Medina <medinadato@gmail.com>
 */
class Web_BoxController extends Crud
{

    protected $entityName = 'Deposito\Box';

    public function indexAction()
    {
	$source = $this->em->createQueryBuilder()
		->select('bf, d.descricao as deposito')
		->from('wms:Deposito\Box', 'bf')
		->innerJoin('bf.deposito', 'd')
		->where('bf.deposito = :idDeposito')
		->orderBy('bf.id')
		->setParameter('idDeposito', $this->view->idDepositoLogado);

	$grid = new \Core\Grid(new \Core\Grid\Source\Doctrine($source));
	$grid->setId('box-grid');
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

    public function addAction()
    {
	$depositos = $this->em->getRepository('wms:Deposito')->findAll();

	if (count($depositos) > 0) {
	    parent::addAction();
	} else {
	    $this->addFlashMessage('error', 'Para cadastrar um box, é necessário que haja ao menos um depósito cadastrado');
	    return $this->redirect('index');
	}
    }

    /**
     * Remove um registro do banco
     * @return void
     */
    public function deleteAction()
    {
	try {
	    $id = $this->getRequest()->getParam('id');

	    if ($id == null)
		throw new \Exception('Id must be provided for the delete action');

	    $this->repository->remove($id, $this->view->idDepositoLogado);
	    $this->em->flush();
	    $this->_helper->messenger('success', 'Registro deletado com sucesso');
	    return $this->redirect('index');
	} catch (\Exception $e) {
	    $this->_helper->messenger('error', $e->getMessage());
	    return $this->redirect('index');
	}
    }

}

?>