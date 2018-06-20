<?php

use \Wms\Domain\Entity\Sistema\Parametro,
    \Wms\Module\Web\Controller\Action\Crud;

/**
 * Description of SystemParamsController
 *
 * @author Renato Medina <medinadato@gmail.com>
 */
class Web_ParametroSistemaController extends Crud
{

    protected $entityName = 'Sistema\Parametro';

    public function indexAction()
    {
	$source = $this->em->createQueryBuilder()
		->select('p, c.descricao as dscContexto')
		->from('wms:Sistema\Parametro', 'p')
		->innerJoin('p.contexto', 'c')
		->orderBy('p.titulo');

	$grid = new \Core\Grid(new \Core\Grid\Source\Doctrine($source));
	$grid->addColumn(array(
		    'label' => 'Título',
		    'index' => 'titulo',
		    'filter' => array(
			'render' => array(
			    'type' => 'text',
			    'condition' => array('match' => array('fulltext'))
			),
		    ),
		))
		->addColumn(array(
		    'label' => 'Constante',
		    'index' => 'constante',
		    'filter' => array(
			'render' => array(
			    'type' => 'text',
			    'condition' => array('match' => array('fulltext'))
			),
		    ),
		))
		->addColumn(array(
		    'label' => 'Contexto',
		    'index' => 'dscContexto',
		    'filter' => array(
			'render' => array(
			    'type' => 'text',
			    'condition' => array('match' => array('fulltext'))
			),
		    ),
		))
		->addColumn(array(
		    'label' => 'Tipo',
		    'index' => 'idTipoAtributo',
		    'render' => array(
			'type' => 'arrayMap',
			'options' => array(
			    'array' => Parametro::$listaTipoAtributo
			)
		    ),
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
	$contexts = $this->em->getRepository('wms:Sistema\Parametro\Contexto')->findAll();

	if (count($contexts) == 0) {
	    $this->addFlashMessage('error', 'Para cadastrar um parâmetro no sistema, é necessário que haja ao menos um contexto de parâmetro cadastrado');
	    return $this->redirect('index');
	}
        
        parent::addAction();
    }

}