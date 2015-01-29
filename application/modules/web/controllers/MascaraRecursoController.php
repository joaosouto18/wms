<?php

use \Wms\Module\Web\Controller\Action\Crud;

class Web_MascaraRecursoController extends Crud
{

    protected $entityName = 'Sistema\Recurso\Mascara';

    public function indexAction()
    {   
	$source = $this->em->createQueryBuilder()
		->select('m, r.descricao')
                ->addSelect("CASE WHEN (TRUNC(m.datFinalVigencia) >= TO_CHAR(CURRENT_DATE(), 'DD/MM/YY')) THEN 'Ativo' ELSE 'Inativo' END status")
		->from('wms:Sistema\Recurso\Mascara', 'm')
		->innerJoin('m.recurso', 'r')
		->orderBy('status', 'ASC');

	$grid = new \Core\Grid(new \Core\Grid\Source\Doctrine($source));

	$grid->addColumn(array(
		    'label' => 'Recurso',
		    'index' => 'descricao',
		    'filter' => array(
			'render' => array(
			    'type' => 'text',
			    'condition' => array('match' => array('fulltext'))
			),
		    ),
		))
		->addColumn(array(
		    'label' => 'Data Inícial da Vigência',
		    'index' => 'datInicioVigencia',
		    'render' => 'Data',
		    'filter' => array(
			'render' => array(
			    'type' => 'date',
			    'range' => true,
			),
		    ),
		))
		->addColumn(array(
		    'label' => 'Data Final da Vigência',
		    'index' => 'datFinalVigencia',
		    'render' => 'Data',
		    'filter' => array(
			'render' => array(
			    'type' => 'date',
			    'range' => true,
			),
		    ),
		))
		->addColumn(array(
		    'label' => 'Máscara',
		    'index' => 'dscMascaraAuditoria',
		    'filter' => array(
			'render' => array(
			    'type' => 'text',
			    'condition' => array('match' => array('fulltext'))
			),
		    ),
		    'hasOrdering' => false,
		))
		->addColumn(array(
		    'label' => 'Status',
		    'index' => 'status',
		    'hasOrdering' => false,
                    //'status' => 'booleanToString',
		))
		->setHasOrdering(true);
        
        $action = new \Core\Grid\Action(array(
		    'label' => 'Excluir',
		    'actionName' => 'delete',
		    'pkIndex' => 'id',
		    'cssClass' => 'del'
		));
        
        $action->setCondition('\Wms\Module\Web\Grid\MascaraRecurso::condition');
        $grid->addAction($action);

	$this->view->grid = $grid->build();
    }

}