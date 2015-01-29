<?php

use Wms\Domain\Entity\Util\Sigla\Tipo,
    Wms\Module\Web\Controller\Action\Crud;

/**
 * Description of Web_TipoSiglaController
 *
 * @author Renato Medina <medinadato@gmail.com>
 */
class Web_TipoSiglaController extends Crud
{

    protected $entityName = 'Util\Sigla\Tipo';

    public function indexAction()
    {
        $source = $this->em->createQueryBuilder()
                ->select('t')
                ->from('wms:Util\Sigla\Tipo', 't')
                ->orderBy('t.descricao');

        $grid = new \Core\Grid(new \Core\Grid\Source\Doctrine($source));
        $grid->setId('tipo-sigla-grid');
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
                    'label' => 'Identificação do Sistema',
                    'index' => 'isSistema',
                    'render' => 'SimOrNao',
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