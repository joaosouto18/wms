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
                    'action' => 'edit'
                )
            );

            $this->configurePage($buttons);

            /** @var \Wms\Domain\Entity\InventarioNovo\ModeloInventarioRepository $modeloRepo */
            $modeloRepo = $this->em->getRepository('wms:inventarioNovo\ModeloInventario');

            /*
             * chama a função que busca os modelos de inventarios e converte os objetos encontrados em arrays
             * que serão passados pro GRID
             */
            $modelos = $modeloRepo->getModelos();
            //var_dump($modelos);

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
                $modeloRepository = $this->em->getRepository('wms:InventarioNovo\ModeloInventario');
                $modeloInventario = $modeloRepository->findOneBy(array('id'=>$id));

                $this->getEntityManager()->remove($modeloInventario);
                $this->getEntityManager()->flush();
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
                    $this->repository->save(new InventarioNovo\ModeloInventario(), $params);
                    $this->_helper->messenger('success', 'Modelo de Inventário inserido com sucesso.');
                    return $this->redirect('index');
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

                    //var_dump($params);
                    try {
                        $this->repository->save($entity, $params);
                        $this->_helper->messenger('success', 'Registro alterado com sucesso');
                    }
                    catch(Exception $e) {
                        $this->_helper->messenger("error", $e->getMessage());
                    }

                    return $this->redirect('index');
                }
                else {

                    $dados = array();

                    $dados['descricao']            = $entity->getDescricao();
                    $dados['ativo']                = $entity->getAtivo();
                    $dados['dthCriacao']           = $entity->getDthCriacao();
                    $dados['itemAItem']            = $entity->getItemAItem();
                    $dados['controlaValidade']     = $entity->getControlaValidade();
                    $dados['exigeUma']             = $entity->getExigeUMA();
                    $dados['numContagens']         = $entity->getNumContagens();
                    $dados['comparaEstoque']       = $entity->getComparaEstoque();
                    $dados['usuarioNContagens']    = $entity->getUsuarioNContagens();
                    $dados['contarTudo']           = $entity->getContarTudo();
                    $dados['volumesSeparadamente'] = $entity->getVolumesSeparadamente();
                    //$dados['importaErp']           = $entity->getImportaERP();
                    //$dados['idLayoutExp']          = $entity->getIdLayoutEXP();
                    $dados['default']              = $entity->getDefault();

                    $form->populate($dados); // pass values to form
                }
            } catch (\Exception $e) {
                $this->_helper->messenger('error', $e->getMessage());
            }
            $this->view->form = $form;
        }
    }