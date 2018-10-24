<?php

use Wms\Module\Web\Controller\Action\Crud,
    Core\Grid;

/**
 * Description of Web_SiglaController
 *
 * @author Renato Medina <medinadato@gmail.com>
 */
class Web_SiglaController extends Crud
{

    protected $entityName = 'Util\Sigla';

    public function indexAction()
    {
        $form = new \Wms\Module\Web\Form\Util\Sigla\Filtro;

        if ($values = $form->getParams()) {

            extract($values);

            $source = $this->em->createQueryBuilder()
                    ->select('s, t.descricao')
                    ->from('wms:Util\Sigla', 's')
                    ->innerJoin('s.tipo', 't')
                    ->orderBy('t.descricao');

            if (!empty($tipo))
                $source->where("t.id = {$tipo}");

            $grid = new Grid(new Grid\Source\Doctrine($source));
            $grid->setId('sigla-grid');
            $grid->addColumn(array(
                        'label' => 'Tipo',
                        'index' => 'descricao'
                    ))
                    ->addColumn(array(
                        'label' => 'Sigla',
                        'index' => 'sigla',
                    ))
                    ->addColumn(array(
                        'label' => 'Referência',
                        'index' => 'referencia',
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

            $form->setSession($values)
                    ->populate($values);
            $this->view->grid = $grid->build();
        }


        $this->view->form = $form;
    }

    public function addAction()
    {
        $tipo = $this->em->getRepository('wms:Util\Sigla\Tipo')->findAll();

        if (count($tipo) == 0) {
            $this->addFlashMessage('error', 'Para cadastrar uma sigla no sistema, é necessário que haja ao menos um tipo de sigla cadastrado');
            return $this->redirect('index');
        }

        parent::addAction();
    }

}