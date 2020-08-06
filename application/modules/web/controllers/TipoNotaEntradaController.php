<?php

use Wms\Module\Web\Page;


/**
 * Description of SystemParamsController
 *
 * @author Renato Medina <medinadato@gmail.com>
 */
class Web_TipoNotaEntradaController extends \Wms\Module\Web\Controller\Action\Crud
{

    protected $entityName = 'NotaFiscal\Tipo';

    public function indexAction()
    {
        $source = $this->em->createQueryBuilder()
                ->select("t")
                ->from('wms:NotaFiscal\Tipo', 't')
                ->orderBy('t.id');

        $grid = new \Core\Grid(new \Core\Grid\Source\Doctrine($source));
        $grid->setId('tipo-nota-entrada-grid');
        $grid->addColumn(array(
                    'label' => 'Descrição',
                    'index' => 'descricao',
                ))
                ->addColumn(array(
                    'label' => 'Emissor',
                    'index' => 'emissor',
                    'render' => 'Emissor'
                ))
                ->addColumn(array(
                    'label' => 'Cód. Externo',
                    'index' => 'codExterno',
                ))
                ->addAction(array(
                    'label' => 'Editar',
                    'actionName' => 'edit',
                    'pkIndex' => 'id',
                    'condition' => function ($row) {
                        return empty($row['systemDefault']);
                    }
                ))
                ->addAction(array(
                    'label' => 'Excluir',
                    'actionName' => 'delete',
                    'pkIndex' => 'id',
                    'cssClass' => 'del',
                    'condition' => function ($row) {
                        return empty($row['systemDefault']);
                    }
                ));

        $this->view->grid = $grid->build();
    }

    public function addAction() {
        //adding default buttons to the page
        Page::configure(array(
            'buttons' => array(
                array(
                    'label' => 'Voltar',
                    'cssClass' => 'btnBack',
                    'urlParams' => array(
                        'action' => 'index',
                        'id' => null
                    ),
                    'tag' => 'a'
                ),
                array(
                    'label' => 'Salvar',
                    'cssClass' => 'btnSave'
                )
            )
        ));

        //finds the form class from the entity name
        $form = new \Wms\Module\Web\Form\NotaFiscal\Tipo();

        $this->em->beginTransaction();
        try {
            if ($this->getRequest()->isPost() && $form->isValid($_POST)) {
                $this->repository->save($this->getRequest()->getParams());
                $this->em->flush();
                $this->em->commit();
                $this->addFlashMessage('success', 'Registro adicionado com sucesso');
                $this->redirect('index');
            }
        } catch (\Exception $e) {
            $this->em->rollback();
            $this->addFlashMessage('error', $e->getMessage());
            $form->populate($this->getRequest()->getParams());
        }

        $this->view->form = $form;
    }

    public function editAction() {

        //adding default buttons to the page
        Page::configure(array(
            'buttons' => array(
                array(
                    'label' => 'Voltar',
                    'cssClass' => 'btnBack',
                    'urlParams' => array(
                        'action' => 'index',
                        'id' => null
                    ),
                    'tag' => 'a'
                ),
                array(
                    'label' => 'Adicionar novo',
                    'cssClass' => 'btnAdd',
                    'urlParams' => array(
                        'action' => 'add',
                        'id' => null
                    ),
                    'tag' => 'a'
                ),
                array(
                    'label' => 'Excluir',
                    'cssClass' => 'btnDelete',
                    'urlParams' => array(
                        'action' => 'delete'
                    ),
                    'tag' => 'a'
                ),
                array(
                    'label' => 'Salvar',
                    'cssClass' => 'btnSave'
                )
            )
        ));

        //finds the form class from the entity name
        $form = new \Wms\Module\Web\Form\NotaFiscal\Tipo();
        $this->em->beginTransaction();
        try {
            $id = $this->getRequest()->getParam('id');

            if ($id == null)
                throw new \Exception('Id must be provided for the edit action');

            $entity = $this->repository->findOneBy(array($this->pkField => $id));

            if ($this->getRequest()->isPost() && $form->isValid($this->getRequest()->getPost())) {
                $this->repository->save($this->getRequest()->getParams());
                $this->em->flush();
                $this->em->commit();
                $this->addFlashMessage('success', 'Registro alterado com sucesso');
                $this->redirect('index');
            }
            $form->setDefaultsFromEntity($entity); // pass values to form
        } catch (\Exception $e) {
            $this->em->rollback();
            $this->addFlashMessage('error', $e->getMessage());
        }
        $this->view->form = $form;
    }
}

?>