<?php

/**
 * Description of SystemParamsController
 *
 * @author Renato Medina <medinadato@gmail.com>
 */
class Web_TipoPedidoExpedicaoController extends \Wms\Module\Web\Controller\Action\Crud
{

    protected $entityName = 'Deposito\Expedicao\Pedido\Tipo';

    public function indexAction()
    {
        $source = $this->em->createQueryBuilder()
                ->select('t')
                ->from('wms:Deposito\Expedicao\Pedido\Tipo', 't')
                ->orderBy('t.descricao');

        $grid = new \Core\Grid(new \Core\Grid\Source\Doctrine($source));
        $grid->setId('tipo-pedido-expedicao-grid');
        $grid->addColumn(array(
                    'label' => 'Código',
                    'index' => 'id',
                ))
                ->addColumn(array(
                    'label' => 'Descrição',
                    'index' => 'descricao'
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
                ));

        $this->view->grid = $grid->build();
    }

}