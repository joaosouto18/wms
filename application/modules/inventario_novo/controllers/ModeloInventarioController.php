<?php
    /**
     * Created by PhpStorm.
     * User: Joaby
     * Date: 04/12/2018
     * Time: 12:15
     */

    use Wms\Module\InventarioNovo\Grid\ModeloInventario as ModeloInventarioGrid,
        Wms\Module\InventarioNovo\Form\ModeloInventarioForm as ModeloInventarioForm,
        Wms\Module\Web\Controller\Action\Crud,
        Wms\Module\Web\Page,
        Wms\Domain\Entity\InventarioNovo;


    class Inventario_Novo_ModeloInventarioController  extends  Crud
    {
        protected $entityName = 'InventarioNovo\ModeloInventario';

        public function indexAction()
        {
            $buttons[] = array(
                'label' => 'Novo Modelo de Inventário',
                'cssClass' => 'button',
                'urlParams' => array(
                    'module' => 'inventario_novo',
                    'controller' => 'modelo-inventario',
                    'action' => 'add',

                ),
                'tag' => 'a'
            );

            $this->configurePage($buttons);

            /** @var \Wms\Domain\Entity\InventarioNovo\ModeloInventarioRepository $modeloRepo */
            $modeloRepo = $this->em->getRepository('wms:InventarioNovo\ModeloInventario');

            /*
             * chama a função que busca os modelos de inventarios e converte os objetos encontrados em arrays
             * que serão passados pro GRID
             */
            $modelos = $modeloRepo->getModelos('array', ['ativo' => 'S']);

            $grid = new ModeloInventarioGrid();
            $this->view->grid = $grid->init($modelos)->render();
        }

        public function configurePage($buttons = [])
        {
            Page::configure(array('buttons' => $buttons));
        }

        public function deleteAction()
        {
            try{
                $id = $this->_getParam('id');
                /** @var \Wms\Service\ModeloInventarioService $modeloService */
                $modeloService = $this->getServiceLocator()->getService("ModeloInventario");
                $modeloService->remover($id);

                $this->addFlashMessage('success', 'Modelo de Inventário excluído com sucesso' );
            } catch (\Exception $ex) {
                $this->addFlashMessage('error', $ex->getMessage() );
            }
            $this->_redirect('/inventario_novo/modelo-inventario');
        }

        public function addAction()
        {
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
                    ),
                )
            ));

            $form = new ModeloInventarioForm();

            try {
                if ($this->getRequest()->isPost() && $form->isValid($this->getRequest()->getPost())) {
                    $params = $this->getRequest()->getParams();

                    /** @var \Wms\Service\ModeloInventarioService $modeloService */
                    $modeloService = $this->getServiceLocator()->getService("ModeloInventario");
                    $modeloService->salvar($params);

                    $this->_helper->messenger('success', 'Modelo de Inventário inserido com sucesso.');
                    $this->redirect('index');
                }
            } catch (\Exception $e) {
                $this->_helper->messenger('error', $e->getMessage());
            }

            $this->view->form = $form;
        }

        public function editAction()
        {
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
                    ),
                )
            ));

            $form = new ModeloInventarioForm();

            try {

                $id = $this->getRequest()->getParam('id');

                if ($id == null)
                    throw new \Exception('Id must be provided for the edit action');

                /** @var InventarioNovo\ModeloInventario $entity */
                $entity = $this->repository->findOneBy(array($this->pkField => $id));

                if ($this->getRequest()->isPost() && $form->isValid($this->getRequest()->getPost())) {

                    $params = $this->getRequest()->getParams();

                    /** @var \Wms\Service\ModeloInventarioService $modeloService */
                    $modeloService = $this->getServiceLocator()->getService("ModeloInventario");
                    $modeloService->salvar($params);
                    $this->addFlashMessage('success', 'Registro alterado com sucesso');
                    $this->redirect('index');

                }
                else {

                    $dados = $entity->toArray();

                    $form->populate($dados); // pass values to form
                }
            } catch (\Exception $e) {
                $this->addFlashMessage('error', $e->getMessage());
            }
            $this->view->form = $form;
        }

        public function getModelosInventariosAjaxAction()
        {
            $this->_helper->json($this->_em->getRepository('wms:InventarioNovo\ModeloInventario')->getModelos('stdClass', ['ativo' => 'S']));
        }
    }