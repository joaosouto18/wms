<?php

/**
 * Description of Web_AtividadeController
 *
 * @author Renato Medina <medinadato@gmail.com>
 */
class Web_AtividadeController extends \Wms\Module\Web\Controller\Action\Crud
{

    protected $entityName = 'Atividade';

    public function indexAction()
    {
	$source = $this->em->createQueryBuilder()
		->select('a, s.descricao as setor')
		->from('wms:Atividade', 'a')
		->innerJoin('a.setorOperacional', 's')
		->orderBy('a.descricao');

	$grid = new \Core\Grid(new \Core\Grid\Source\Doctrine($source));
	$grid->setId('atividade-grid');
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
		->addColumn(array(
		    'label' => 'Setor',
		    'index' => 'setor',
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