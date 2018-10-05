<?php

/**
 * Description of Web_LinhaSeparacaoController
 *
 * @author Renato Medina <medinadato@gmail.com>
 */
class Expedicao_MotivoCorteController extends \Wms\Module\Web\Controller\Action\Crud
{

    protected $entityName = 'Expedicao\MotivoCorte';

    public function indexAction()
    {
      $source = $this->em->createQueryBuilder()
		->select('m')
		->from('wms:Expedicao\MotivoCorte', 'm')
		->orderBy('m.id');

	$grid = new \Core\Grid(new \Core\Grid\Source\Doctrine($source));
	$grid->setId('motivo-corte-grid');
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
		    'index' => 'dscMotivo',
		    'filter' => array(
			'render' => array(
			    'type' => 'text',
			    'condition' => array('match' => array('fulltext'))
			),
		    ),
		))
        ->addColumn(array(
            'label' => 'Código Externo',
            'index' => 'codExterno',
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